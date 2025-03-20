<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transactions') }}
            </h2>
            <div class="flex space-x-4">
                <a href="{{ route('transactions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Upload Statement
                </a>
                <form action="{{ route('transactions.reset') }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete all transactions? This action cannot be undone.');">
                    @csrf
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Reset All Transactions
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('duplicates'))
                <div class="mb-4 p-4 bg-yellow-100 text-yellow-700 rounded-md">
                    <h3 class="font-semibold mb-2">Skipped Duplicate Transactions</h3>
                    <p class="mb-2">The following transactions were skipped because they already exist:</p>
                    <ul class="list-disc list-inside">
                        @foreach(session('duplicates') as $duplicate)
                            <li>
                                {{ $duplicate['Date'] }} - {{ $duplicate['Description'] }} 
                                ({{ $duplicate['Card Member'] }} - ${{ number_format(str_replace(['$', ','], '', $duplicate['Amount']), 2) }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('fuzzyMatches'))
                <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-md">
                    <h3 class="font-semibold mb-2">Potential Duplicate Transactions</h3>
                    <p class="mb-2">The following transactions were skipped because they closely match existing transactions:</p>
                    <ul class="list-disc list-inside">
                        @foreach(session('fuzzyMatches') as $match)
                            <li class="mb-2">
                                <div class="font-medium">New Transaction:</div>
                                <div class="ml-4">
                                    {{ $match['new']['Date'] }} - {{ $match['new']['Description'] }} 
                                    ({{ $match['new']['Card Member'] }} - ${{ number_format(str_replace(['$', ','], '', $match['new']['Amount']), 2) }})
                                </div>
                                <div class="font-medium mt-1">Matches Existing:</div>
                                <div class="ml-4">
                                    {{ $match['existing']->date->format('m/d/Y') }} - {{ $match['existing']->description }} 
                                    ({{ $match['existing']->card_member }} - ${{ number_format($match['existing']->amount, 2) }})
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($transactions->isEmpty())
                <p class="text-gray-500 text-center">No transactions found. Upload a statement to get started.</p>
            @else
                <form id="bulk-edit-form" action="{{ route('transactions.bulk-update') }}" method="POST">
                    @csrf
                    <div class="flex items-center space-x-4 mb-4">
                        <select name="action" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Action</option>
                            <option value="category">Update Category</option>
                            <option value="card">Update Card</option>
                            <option value="ignore">Mark as Ignored</option>
                            <option value="unignore">Mark as Not Ignored</option>
                        </select>
                        <select name="category_id" class="hidden rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="card_id" class="hidden rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Card</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}">{{ $card->name }} (*{{ $card->last_four }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50" disabled id="bulk-submit">
                            Apply to Selected
                        </button>
                        <button type="button" id="select-all" class="text-blue-600 hover:text-blue-900">
                            Select All
                        </button>
                        <button type="button" id="deselect-all" class="text-blue-600 hover:text-blue-900">
                            Deselect All
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-4">
                                        <span class="sr-only">Select</span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Card</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Card Member</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="selected_transactions[]" value="{{ $transaction->id }}" 
                                                class="transaction-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->date->format('m/d/Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $transaction->description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($transaction->card)
                                                {{ $transaction->card->name }}
                                                <span class="text-gray-500">(*{{ $transaction->card->last_four }})</span>
                                            @else
                                                <span class="text-gray-400">Not assigned</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->card_member }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($transaction->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->category?->name ?? 'Uncategorized' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('transactions.edit', $transaction) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <button type="button" onclick="deleteTransaction({{ $transaction->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>

                @foreach($transactions as $transaction)
                    <form id="delete-form-{{ $transaction->id }}" action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const bulkForm = document.getElementById('bulk-edit-form');
            const actionSelect = bulkForm.querySelector('select[name="action"]');
            const categorySelect = bulkForm.querySelector('select[name="category_id"]');
            const cardSelect = bulkForm.querySelector('select[name="card_id"]');
            const submitButton = document.getElementById('bulk-submit');
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            const selectAllBtn = document.getElementById('select-all');
            const deselectAllBtn = document.getElementById('deselect-all');

            // Handle form submission
            bulkForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('Please select at least one transaction.');
                    return;
                }

                if (actionSelect.value === '') {
                    alert('Please select an action.');
                    return;
                }

                if (actionSelect.value === 'category' && !categorySelect.value) {
                    alert('Please select a category.');
                    return;
                }

                if (actionSelect.value === 'card' && !cardSelect.value) {
                    alert('Please select a card.');
                    return;
                }

                // Remove unused fields
                if (actionSelect.value !== 'category') {
                    categorySelect.disabled = true;
                }
                if (actionSelect.value !== 'card') {
                    cardSelect.disabled = true;
                }

                // Log form details before submission
                console.log('Form action:', this.action);
                console.log('Form method:', this.method);
                console.log('Selected transactions:', Array.from(checkedBoxes).map(cb => cb.value));
                console.log('Action:', actionSelect.value);
                console.log('Category:', categorySelect.value);
                console.log('Card:', cardSelect.value);

                // Ensure we're using POST method
                this.method = 'POST';
                this.submit();

                // Re-enable fields after submission
                categorySelect.disabled = false;
                cardSelect.disabled = false;
            });

            // Handle action select change
            actionSelect.addEventListener('change', function() {
                categorySelect.classList.add('hidden');
                cardSelect.classList.add('hidden');
                
                if (this.value === 'category') {
                    categorySelect.classList.remove('hidden');
                } else if (this.value === 'card') {
                    cardSelect.classList.remove('hidden');
                }
                
                updateSubmitButton();
            });

            // Handle checkbox changes
            function updateSubmitButton() {
                const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
                const hasAction = actionSelect.value !== '';
                submitButton.disabled = checkedBoxes.length === 0 || !hasAction;
            }

            document.querySelectorAll('.transaction-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSubmitButton);
            });

            // Select/Deselect All functionality
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => checkbox.checked = true);
                updateSubmitButton();
            });

            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => checkbox.checked = false);
                updateSubmitButton();
            });
        });
    </script>
    @endpush
</x-app-layout> 