<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['category', 'card'])
            ->where('ignored', false)
            ->orderByRaw('CASE WHEN category_id IS NULL THEN 0 ELSE 1 END') // Show uncategorized first
            ->orderBy('date', 'desc')
            ->paginate(20);

        $categories = Category::orderBy('name')->get();
        $cards = auth()->user()->cards()->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories', 'cards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('transactions.upload');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $csv = Reader::createFromPath($file->getPathname());
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $duplicates = [];
        $processed = 0;
        $fuzzyMatches = [];

        foreach ($records as $record) {
            // Skip the Account field as per requirements
            $date = Carbon::createFromFormat('m/d/Y', $record['Date'])->startOfDay();
            $amount = (float) str_replace(['$', ','], '', $record['Amount']);

            // First check for exact duplicates
            $exactDuplicate = Transaction::where('date', $date)
                ->where('description', $record['Description'])
                ->where('card_member', $record['Card Member'])
                ->whereRaw('ABS(amount - ?) < 0.01', [$amount]) // Compare with small epsilon for floating point
                ->first();

            if ($exactDuplicate) {
                $duplicates[] = $record;
                continue;
            }

            // If no exact match, check for fuzzy matches
            $fuzzyMatch = $this->findFuzzyMatch($date, $record['Description'], $record['Card Member'], $amount);
            if ($fuzzyMatch) {
                $fuzzyMatches[] = [
                    'new' => $record,
                    'existing' => $fuzzyMatch
                ];
                continue;
            }

            Transaction::create([
                'date' => $date,
                'description' => $record['Description'],
                'card_member' => $record['Card Member'],
                'amount' => $amount,
                'original_csv_row' => json_encode($record)
            ]);

            $processed++;
        }

        return redirect()->route('transactions.index')
            ->with('success', "Processed $processed transactions")
            ->with('duplicates', $duplicates)
            ->with('fuzzyMatches', $fuzzyMatches);
    }

    /**
     * Find a fuzzy match for a transaction
     */
    private function findFuzzyMatch($date, $description, $cardMember, $amount)
    {
        // Get transactions from the same day with the same card member and amount
        $candidates = Transaction::where('date', $date)
            ->where('card_member', $cardMember)
            ->whereRaw('ABS(amount - ?) < 0.01', [$amount]) // Compare with small epsilon for floating point
            ->get();

        $bestMatch = null;
        $highestSimilarity = 0;

        foreach ($candidates as $candidate) {
            $similarity = $this->calculateSimilarity($description, $candidate->description);
            
            // If similarity is above 90%, consider it a match
            if ($similarity > 90 && $similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatch = $candidate;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate similarity between two strings
     */
    private function calculateSimilarity($str1, $str2)
    {
        // Clean up strings for comparison
        $str1 = $this->cleanDescription($str1);
        $str2 = $this->cleanDescription($str2);

        // Calculate similarity percentage
        similar_text($str1, $str2, $percent);
        return $percent;
    }

    /**
     * Clean up description for comparison
     */
    private function cleanDescription($description)
    {
        // Remove common prefixes/suffixes that might vary
        $description = preg_replace('/^AMEX\s*/i', '', $description);
        $description = preg_replace('/^AMERICAN EXPRESS\s*/i', '', $description);
        
        // Remove any trailing spaces and convert to lowercase
        return strtolower(trim($description));
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        $categories = Category::all();
        $cards = auth()->user()->cards()->orderBy('name')->get();
        return view('transactions.edit', compact('transaction', 'categories', 'cards'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'card_id' => 'nullable|exists:cards,id',
            'ignored' => 'boolean'
        ]);

        $transaction->update($request->only(['category_id', 'card_id', 'ignored']));

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully');
    }

    public function report(Request $request)
    {
        // Get date range from request or default to current month
        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->has('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // Get category report
        $categoryReport = Transaction::where('ignored', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->with('category')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        // Get card member report
        $cardMemberReport = Transaction::where('ignored', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('card_member', DB::raw('SUM(amount) as total'))
            ->groupBy('card_member')
            ->orderByDesc('total')
            ->get();

        // Get card report
        $cardReport = Transaction::where('ignored', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('card_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as transaction_count'))
            ->with('card')
            ->groupBy('card_id')
            ->orderByDesc('total')
            ->get();

        return view('transactions.report', compact('startDate', 'endDate', 'categoryReport', 'cardMemberReport', 'cardReport'));
    }

    public function exportReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $transactions = Transaction::where('ignored', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->get();

        $csv = \League\Csv\Writer::createFromString('');
        $csv->insertOne(['Date', 'Description', 'Card Member', 'Amount', 'Category']);

        foreach ($transactions as $transaction) {
            $csv->insertOne([
                $transaction->date->format('m/d/Y'),
                $transaction->description,
                $transaction->card_member,
                $transaction->amount,
                $transaction->category?->name ?? 'Uncategorized'
            ]);
        }

        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transactions.csv"');
    }

    public function getDashboardStats()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentMonthTotal = Transaction::where('ignored', false)
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->sum('amount');

        $lastMonthTotal = Transaction::where('ignored', false)
            ->whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('amount');

        $totalTransactions = Transaction::where('ignored', false)->count();
        $uncategorizedTransactions = Transaction::where('ignored', false)
            ->whereNull('category_id')
            ->count();

        $topCategories = Transaction::where('ignored', false)
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->with('category')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'currentMonthTotal' => $currentMonthTotal,
            'lastMonthTotal' => $lastMonthTotal,
            'totalTransactions' => $totalTransactions,
            'uncategorizedTransactions' => $uncategorizedTransactions,
            'topCategories' => $topCategories,
        ];
    }

    public function getCategorySuggestions(Request $request)
    {
        $description = $request->input('description');
        
        if (empty($description)) {
            return response()->json([]);
        }

        // Get all rules that match this description
        $matchingRules = CategoryRule::with('category')
            ->orderByDesc('priority')
            ->get()
            ->filter(function ($rule) use ($description) {
                return $rule->matches($description);
            })
            ->map(function ($rule) {
                return [
                    'category_id' => $rule->category_id,
                    'name' => $rule->category->name,
                    'match_count' => 1,
                    'is_rule' => true
                ];
            });

        // Get all transactions with the same category
        $transactions = Transaction::where('ignored', false)
            ->whereNotNull('category_id')
            ->where('description', 'like', '%' . $description . '%')
            ->select('category_id', DB::raw('COUNT(*) as match_count'))
            ->groupBy('category_id')
            ->orderByDesc('match_count')
            ->limit(3)
            ->with('category')
            ->get()
            ->map(function ($transaction) {
                return [
                    'category_id' => $transaction->category_id,
                    'name' => $transaction->category->name,
                    'match_count' => $transaction->match_count,
                    'is_rule' => false
                ];
            });

        // Combine and deduplicate results, preferring rules over transaction matches
        $results = $matchingRules->concat($transactions)
            ->unique('category_id')
            ->values()
            ->take(3);

        return response()->json($results);
    }

    /**
     * Reset all transactions
     */
    public function reset(Request $request)
    {
        // Delete all transactions
        Transaction::truncate();

        return redirect()->route('transactions.index')
            ->with('success', 'All transactions have been reset successfully.');
    }

    /**
     * Bulk update transactions
     */
    public function bulkUpdate(Request $request)
    {
        \Log::info('Bulk Update Request:', [
            'method' => $request->method(),
            'url' => $request->url(),
            'path' => $request->path(),
            'data' => $request->all()
        ]);

        $request->validate([
            'action' => 'required|in:category,card,ignore,unignore',
            'selected_transactions' => 'required|array',
            'selected_transactions.*' => 'exists:transactions,id',
            'category_id' => 'required_if:action,category|exists:categories,id',
            'card_id' => 'required_if:action,card|exists:cards,id',
        ]);

        $transactions = Transaction::whereIn('id', $request->selected_transactions);

        switch ($request->action) {
            case 'category':
                $transactions->update(['category_id' => $request->category_id]);
                $message = 'Categories updated successfully';
                break;
            case 'card':
                $transactions->update(['card_id' => $request->card_id]);
                $message = 'Cards updated successfully';
                break;
            case 'ignore':
                $transactions->update(['ignored' => true]);
                $message = 'Transactions marked as ignored';
                break;
            case 'unignore':
                $transactions->update(['ignored' => false]);
                $message = 'Transactions marked as not ignored';
                break;
        }

        return redirect()->route('transactions.index')
            ->with('success', $message);
    }
}
