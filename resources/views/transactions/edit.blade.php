<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Transaction') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('transactions.update', $transaction) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" name="description" id="description" value="{{ $transaction->description }}" readonly
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="mb-6">
                            <label for="card_id" class="block text-sm font-medium text-gray-700">Card</label>
                            <select name="card_id" id="card_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a card</option>
                                @foreach($cards as $card)
                                    <option value="{{ $card->id }}" {{ $transaction->card_id == $card->id ? 'selected' : '' }}>
                                        {{ $card->name }} ({{ $card->issuer }} *{{ $card->last_four }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category_id" id="category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $transaction->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Category Suggestions -->
                        <div id="category-suggestions" class="mb-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Suggested Categories</label>
                            <div id="suggestions-list" class="space-y-2"></div>
                        </div>

                        <div class="mb-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="ignored" value="1" {{ $transaction->ignored ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">Ignore this transaction</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('transactions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Transaction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const description = document.getElementById('description');
            const categorySelect = document.getElementById('category_id');
            const suggestionsDiv = document.getElementById('category-suggestions');
            const suggestionsList = document.getElementById('suggestions-list');
            let debounceTimer;

            // Function to fetch category suggestions
            async function fetchSuggestions() {
                const response = await fetch(`{{ route('transactions.suggestions') }}?description=${encodeURIComponent(description.value)}`);
                const suggestions = await response.json();

                if (suggestions.length > 0) {
                    suggestionsList.innerHTML = suggestions.map(suggestion => `
                        <button type="button" 
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                            onclick="selectCategory(${suggestion.category_id})">
                            ${suggestion.name} (${suggestion.match_count} matches)
                        </button>
                    `).join('');
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.classList.add('hidden');
                }
            }

            // Function to select a suggested category
            window.selectCategory = function(categoryId) {
                categorySelect.value = categoryId;
                suggestionsDiv.classList.add('hidden');
            };

            // Debounced search
            description.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchSuggestions, 300);
            });

            // Initial suggestions
            fetchSuggestions();
        });
    </script>
    @endpush
</x-app-layout> 