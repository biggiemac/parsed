<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Card') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('cards.update', $card) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Card Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $card->name) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="e.g., Personal AMEX">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Card Type</label>
                            <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Type</option>
                                <option value="credit" {{ old('type', $card->type) == 'credit' ? 'selected' : '' }}>Credit</option>
                                <option value="debit" {{ old('type', $card->type) == 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="prepaid" {{ old('type', $card->type) == 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="issuer" class="block text-sm font-medium text-gray-700">Card Issuer</label>
                            <select name="issuer" id="issuer" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Issuer</option>
                                <option value="AMEX" {{ old('issuer', $card->issuer) == 'AMEX' ? 'selected' : '' }}>American Express</option>
                                <option value="VISA" {{ old('issuer', $card->issuer) == 'VISA' ? 'selected' : '' }}>Visa</option>
                                <option value="Mastercard" {{ old('issuer', $card->issuer) == 'Mastercard' ? 'selected' : '' }}>Mastercard</option>
                                <option value="Discover" {{ old('issuer', $card->issuer) == 'Discover' ? 'selected' : '' }}>Discover</option>
                            </select>
                            @error('issuer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_four" class="block text-sm font-medium text-gray-700">Last 4 Digits</label>
                            <input type="text" name="last_four" id="last_four" value="{{ old('last_four', $card->last_four) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="e.g., 1234" maxlength="4" pattern="[0-9]{4}">
                            @error('last_four')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $card->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Card
                            </button>
                            <a href="{{ route('cards.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 