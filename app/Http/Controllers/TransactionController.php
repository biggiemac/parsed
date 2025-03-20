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

    /**
     * Export all transactions with full details for migration
     */
    public function exportAll()
    {
        $transactions = Transaction::with(['category', 'card'])
            ->orderBy('date', 'desc')
            ->get();

        $csv = \League\Csv\Writer::createFromString('');
        $csv->insertOne([
            'Date',
            'Description',
            'Card Member',
            'Amount',
            'Category',
            'Card Name',
            'Card Type',
            'Card Issuer',
            'Card Last Four',
            'Is Ignored'
        ]);

        foreach ($transactions as $transaction) {
            $csv->insertOne([
                $transaction->date->format('Y-m-d'), // Using Y-m-d for better import compatibility
                $transaction->description,
                $transaction->card_member,
                $transaction->amount,
                $transaction->category?->name ?? '',
                $transaction->card?->name ?? '',
                $transaction->card?->type ?? '',
                $transaction->card?->issuer ?? '',
                $transaction->card?->last_four ?? '',
                $transaction->ignored ? '1' : '0'
            ]);
        }

        $timestamp = now()->format('Y-m-d_His');
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transactions_export_' . $timestamp . '.csv"');
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

        $rules = [
            'action' => 'required|in:category,card,ignore,unignore',
            'selected_transactions' => 'required|array',
            'selected_transactions.*' => 'exists:transactions,id',
        ];

        // Add conditional validation rules
        if ($request->action === 'category') {
            $rules['category_id'] = 'required|exists:categories,id';
        } elseif ($request->action === 'card') {
            $rules['card_id'] = 'required|exists:cards,id';
        }

        $request->validate($rules);

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

    public function import(Request $request)
    {
        $request->validate([
            'transactions_file' => 'required|file|mimes:csv,json|max:10240', // max 10MB
        ]);

        $file = $request->file('transactions_file');
        $extension = $file->getClientOriginalExtension();
        $transactions = [];

        if ($extension === 'json') {
            $transactions = json_decode(file_get_contents($file->path()), true);
        } else {
            // CSV handling
            $handle = fopen($file->path(), 'r');
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $transactions[] = array_combine($headers, $row);
            }
            
            fclose($handle);
        }

        // Convert array to collection
        $transactions = collect($transactions);

        $imported = 0;
        $skipped = 0;
        $errors = collect(); // Initialize errors as a collection

        foreach ($transactions as $data) {
            try {
                // Map the field names from the file to our expected format
                $mappedData = [
                    'date' => isset($data['Date']) ? Carbon::parse($data['Date']) : null,
                    'description' => $data['Description'] ?? null,
                    'amount' => isset($data['Amount']) ? str_replace(['$', ','], '', $data['Amount']) : null,
                    'card_member' => $data['Card Member'] ?? null,
                    'category' => $data['Category'] ?? null,
                    'card_name' => $data['Card Name'] ?? null,
                    'last_four' => $data['Card Last Four'] ?? null,
                    'is_ignored' => isset($data['Is Ignored']) ? (bool)$data['Is Ignored'] : false,
                ];
                
                // Check for required fields
                if (!isset($mappedData['description']) || !isset($mappedData['amount'])) {
                    $errors->push("Missing required fields for transaction: " . json_encode($data));
                    continue;
                }
                
                // Check if transaction already exists
                $exists = Transaction::where('date', $mappedData['date'])
                    ->where('description', $mappedData['description'])
                    ->where('amount', $mappedData['amount'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Find category if provided
                $category_id = null;
                if (isset($mappedData['category'])) {
                    $category = Category::firstOrCreate(
                        ['name' => $mappedData['category']],
                        ['organization_id' => auth()->user()->currentOrganization->id]
                    );
                    $category_id = $category->id;
                }

                // Find or create card if provided
                $card_id = null;
                if (isset($mappedData['card_name']) && isset($mappedData['last_four'])) {
                    $card = Card::firstOrCreate(
                        [
                            'last_four' => $mappedData['last_four'],
                            'organization_id' => auth()->user()->currentOrganization->id,
                            'user_id' => auth()->id()
                        ],
                        [
                            'name' => $mappedData['card_name'],
                            'type' => $data['Card Type'] ?? null,
                            'issuer' => $data['Card Issuer'] ?? null
                        ]
                    );
                    $card_id = $card->id;
                }

                // Create the transaction
                Transaction::create([
                    'date' => $mappedData['date'],
                    'description' => $mappedData['description'],
                    'amount' => $mappedData['amount'],
                    'category_id' => $category_id,
                    'card_id' => $card_id,
                    'card_member' => $mappedData['card_member'],
                    'organization_id' => auth()->user()->currentOrganization->id,
                    'is_ignored' => $mappedData['is_ignored'],
                    'original_csv_row' => json_encode($data) // Store the original data
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors->push("Error importing transaction: " . json_encode($data) . " - " . $e->getMessage());
            }
        }

        $message = "Successfully imported {$imported} transactions.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicate transactions.";
        }
        
        return redirect()->route('transactions.index')
            ->with('success', $message)
            ->with('errors', $errors);
    }
}
