<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" value="{{ $category->name }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Category
                            </button>
                        </div>
                    </form>

                    <!-- Category Rules Section -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Category Rules</h3>
                        
                        <!-- Add New Rule Form -->
                        <form action="{{ route('categories.rules.store', $category) }}" method="POST" class="mb-6">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="pattern" class="block text-sm font-medium text-gray-700">Pattern</label>
                                    <input type="text" name="pattern" id="pattern" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">Text to match in transaction descriptions</p>
                                </div>
                                <div>
                                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                                    <input type="number" name="priority" id="priority" value="0" min="0"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">Higher priority rules are checked first</p>
                                </div>
                                <div class="flex items-end">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_regex" value="1"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Use regex pattern</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Add Rule
                                </button>
                            </div>
                        </form>

                        <!-- Rules List -->
                        @if($category->rules->isEmpty())
                            <p class="text-gray-500">No rules defined for this category.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($category->rules as $rule)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                Pattern: <code class="bg-gray-100 px-2 py-1 rounded">{{ $rule->pattern }}</code>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Priority: {{ $rule->priority }} | Type: {{ $rule->is_regex ? 'Regex' : 'Text' }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <form action="{{ route('categories.rules.destroy', $rule) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to delete this rule?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 