<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Date Range Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form action="{{ route('transactions.report') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Category Report -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Spending by Category</h3>
                        <a href="{{ route('transactions.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Export CSV
                        </a>
                    </div>
                    @if($categoryReport->isEmpty())
                        <p class="text-gray-500">No transactions found for the selected date range.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($categoryReport as $category)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $category->category?->name ?? 'Uncategorized' }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        ${{ number_format($category->total, 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card Member Report -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Spending by Card Member</h3>
                    @if($cardMemberReport->isEmpty())
                        <p class="text-gray-500">No transactions found for the selected date range.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($cardMemberReport as $member)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $member->card_member }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        ${{ number_format($member->total, 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card Report -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Spending by Card</h3>
                    @if($cardReport->isEmpty())
                        <p class="text-gray-500">No transactions found for the selected date range.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($cardReport as $card)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">
                                            @if($card->card)
                                                {{ $card->card->name }}
                                                <span class="text-gray-500">(*{{ $card->card->last_four }})</span>
                                            @else
                                                <span class="text-gray-400">Unassigned Card</span>
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500 ml-2">({{ $card->transaction_count }} transactions)</span>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        ${{ number_format($card->total, 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 