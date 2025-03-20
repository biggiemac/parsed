<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(auth()->user()->currentOrganization)
                        <h3 class="text-lg font-medium mb-4">{{ auth()->user()->currentOrganization->name }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <a href="{{ route('transactions.index') }}" class="block p-6 bg-white border rounded-lg hover:shadow-md transition-shadow">
                                <h4 class="text-lg font-medium mb-2">Transactions</h4>
                                <p class="text-gray-600">View and manage your transactions</p>
                            </a>
                            <a href="{{ route('categories.index') }}" class="block p-6 bg-white border rounded-lg hover:shadow-md transition-shadow">
                                <h4 class="text-lg font-medium mb-2">Categories</h4>
                                <p class="text-gray-600">Manage transaction categories</p>
                            </a>
                            <a href="{{ route('cards.index') }}" class="block p-6 bg-white border rounded-lg hover:shadow-md transition-shadow">
                                <h4 class="text-lg font-medium mb-2">Cards</h4>
                                <p class="text-gray-600">Manage your credit cards</p>
                            </a>
                            <a href="{{ route('organization-members.index') }}" class="block p-6 bg-white border rounded-lg hover:shadow-md transition-shadow">
                                <h4 class="text-lg font-medium mb-2">Members</h4>
                                <p class="text-gray-600">Manage organization members</p>
                            </a>
                            <a href="{{ route('category-rules.index') }}" class="block p-6 bg-white border rounded-lg hover:shadow-md transition-shadow">
                                <h4 class="text-lg font-medium mb-2">Category Rules</h4>
                                <p class="text-gray-600">Set up automatic categorization rules</p>
                            </a>
                        </div>
                    @else
                        <div class="text-center">
                            <h3 class="text-lg font-medium mb-4">Welcome to {{ config('app.name') }}</h3>
                            <p class="text-gray-600 mb-4">Please select or create an organization to get started.</p>
                            <div class="flex justify-center space-x-4">
                                <a href="{{ route('organizations.select') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Select Organization
                                </a>
                                <a href="{{ route('organizations.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Create Organization
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 