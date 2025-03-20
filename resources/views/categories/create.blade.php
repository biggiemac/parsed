<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Category') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700">Category Name</label>
                    <div class="mt-1">
                        <input type="text" name="name" id="name" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            value="{{ old('name') }}">
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 