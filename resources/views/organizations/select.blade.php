<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Select Organization') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Your Organizations</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($organizations as $organization)
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-medium mb-2">{{ $organization->name }}</h4>
                                <p class="text-sm text-gray-600 mb-4">Role: {{ ucfirst($organization->pivot->role) }}</p>
                                <form action="{{ route('organizations.switch', $organization) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Select
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if($organizations->isEmpty())
                        <p class="text-gray-600 text-center py-4">You don't have any organizations yet.</p>
                    @endif

                    <div class="mt-6 pt-6 border-t">
                        <a href="{{ route('organizations.create') }}" class="inline-block bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Create New Organization
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 