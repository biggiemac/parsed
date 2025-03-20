<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invite Users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Send Invitation by Email</h3>
                        <form method="POST" action="{{ route('invitations.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" name="email" id="email" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Send Invitation
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Generate Invitation Link</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select id="role" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button id="generateLink" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Generate New Link
                            </button>
                            <div id="linkContainer" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Shareable Link</label>
                                <div class="flex space-x-2">
                                    <input type="text" id="inviteLink" readonly
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-gray-50">
                                    <button id="copyLink" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        Copy
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">This link will expire in 7 days</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Pending Invitations</h3>
                        @if($invitations->isEmpty())
                            <p class="text-gray-500">No pending invitations</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($invitations as $invitation)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $invitation->email ?? 'Shareable Link' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $invitation->created_at->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if($invitation->accepted_at)
                                                        <span class="text-green-600">Accepted</span>
                                                    @else
                                                        <span class="text-yellow-600">Pending</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    @if(!$invitation->accepted_at)
                                                        <form action="{{ route('invitations.destroy', $invitation) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to cancel this invitation?')">
                                                                Cancel
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generateBtn = document.getElementById('generateLink');
            const linkContainer = document.getElementById('linkContainer');
            const linkInput = document.getElementById('inviteLink');
            const copyBtn = document.getElementById('copyLink');
            const roleSelect = document.getElementById('role');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }

            generateBtn.addEventListener('click', async function() {
                try {
                    // Disable button and show loading state
                    generateBtn.disabled = true;
                    generateBtn.textContent = 'Generating...';
                    
                    const response = await fetch('{{ route("invitations.generate-link") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            role: roleSelect.value
                        }),
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    
                    // Show the link
                    linkInput.value = data.link;
                    linkContainer.classList.remove('hidden');
                    
                    // Select the link for easy copying
                    linkInput.select();
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || 'Failed to generate invitation link. Please try again.');
                } finally {
                    // Reset button state
                    generateBtn.disabled = false;
                    generateBtn.textContent = 'Generate New Link';
                }
            });

            copyBtn.addEventListener('click', function() {
                try {
                    linkInput.select();
                    document.execCommand('copy');
                    
                    // Show feedback
                    copyBtn.textContent = 'Copied!';
                    setTimeout(() => {
                        copyBtn.textContent = 'Copy';
                    }, 2000);
                } catch (error) {
                    console.error('Copy failed:', error);
                    alert('Failed to copy link. Please try selecting and copying manually.');
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 