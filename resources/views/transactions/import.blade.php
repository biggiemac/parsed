<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Statement') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-6">
                    <label for="csv_file" class="block text-sm font-medium text-gray-700">AMEX Statement CSV File</label>
                    <div class="mt-1">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required
                            class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Please upload an AMEX statement in CSV format. The file should contain the following columns:
                        Date, Description, Card Member, and Amount.
                    </p>
                    @error('csv_file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a href="{{ route('transactions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Import Transactions
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 