@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Account details</h2>
                    <div class="flex space-x-2">
                        <a href="{{ route('accounts.edit', $account->id) }}" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Bewerken
                        </a>
                        <button onclick="deleteAccount('{{ $account->id }}')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Verwijderen
                        </button>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status:</span>
                            <div class="mt-1">
                                @if($account->active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actief</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactief</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Rol:</span>
                            <div class="mt-1">
                                @if($account->role == 'owner')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Eigenaar</span>
                                @elseif($account->role == 'admin')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Beheerder</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Gebruiker</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Naam:</span>
                            <span class="mt-1">{{ $account->name }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email:</span>
                            <span class="mt-1">{{ $account->email }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefoonnummer:</span>
                            <span class="mt-1">{{ $account->phone ?: 'Niet opgegeven' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Adres:</span>
                            <span class="mt-1">{{ $account->address ?: 'Niet opgegeven' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Postcode:</span>
                            <span class="mt-1">{{ $account->postal_code ?: 'Niet opgegeven' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Stad:</span>
                            <span class="mt-1">{{ $account->city ?: 'Niet opgegeven' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Slimme Meter:</span>
                            <div class="mt-1">
                                @if($account->smartMeter)
                                    <div class="flex items-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 mr-2">Gekoppeld</span>
                                        <span>{{ $account->smartMeter->meter_id }} - {{ $account->smartMeter->location }}</span>
                                    </div>
                                @else
                                    <span class="px-2 py-1 text-xs font
                                    @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Niet gekoppeld</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Aangemaakt op:</span>
                            <span class="mt-1">{{ $account->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Laatst bijgewerkt:</span>
                            <span class="mt-1">{{ $account->updated_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="{{ route('accounts.index') }}" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Terug naar overzicht
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Account verwijderen</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Weet je zeker dat je het account van <strong>{{ $account->name }}</strong> wilt verwijderen? Deze actie kan niet ongedaan gemaakt worden.
                </p>
            </div>
            <div class="flex justify-center gap-4 mt-4">
                <button id="cancelDelete" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md shadow-sm hover:bg-gray-400">Annuleren</button>
                <form id="deleteForm" method="POST" action="{{ route('accounts.destroy', $account->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md shadow-sm hover:bg-red-700">Verwijderen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteAccount() {
        const modal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        
        modal.classList.remove('hidden');
        
        cancelDelete.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
    }
</script>
@endsection