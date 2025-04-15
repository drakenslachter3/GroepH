@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Gebruiker details</h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('users.edit', $user->id) }}"
                                class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow-sm flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Bewerken
                            </a>
                            <form method="POST" action="{{ route('users.delete', $user->id) }}"
                                style="display: inline;">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('Weet je zeker dat je de gebruiker {{ $user->name }} wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.');"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Verwijderen
                                </button>
                            </form>
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
                                    @if($user->active)
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actief</span>
                                    @else
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactief</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Rol:</span>
                                <div class="mt-1">
                                    @if($user->role == 'owner')
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Eigenaar</span>
                                    @elseif($user->role == 'admin')
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Beheerder</span>
                                    @else
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Gebruiker</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Naam:</span>
                                <span class="mt-1">{{ $user->name }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email:</span>
                                <span class="mt-1">{{ $user->email }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefoonnummer:</span>
                                <span class="mt-1">{{ $user->phone ?: 'Niet opgegeven' }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Adres:</span>
                                <span class="mt-1">{{ $user->address ?: 'Niet opgegeven' }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Postcode:</span>
                                <span class="mt-1">{{ $user->postal_code ?: 'Niet opgegeven' }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Stad:</span>
                                <span class="mt-1">{{ $user->city ?: 'Niet opgegeven' }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Aangemaakt op:</span>
                                <span class="mt-1">{{ $user->created_at->format('d-m-Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Laatst bijgewerkt:</span>
                                <span class="mt-1">{{ $user->updated_at->format('d-m-Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Slimme Meters Sectie -->
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Gekoppelde Slimme Meters</h3>
                            <a href="{{ route('smartmeters.userMeters', $user->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Meters beheren
                            </a>
                        </div>

                        @if($user->smartMeters->count() > 0)
                            <div class="overflow-x-auto relative">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="py-3 px-6">Meter ID</th>
                                            <th scope="col" class="py-3 px-6">Type</th>
                                            <th scope="col" class="py-3 px-6">Locatie</th>
                                            <th scope="col" class="py-3 px-6">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->smartMeters as $meter)
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <td class="py-4 px-6">{{ $meter->meter_id }}</td>
                                                <td class="py-4 px-6">{{ ucfirst($meter->type) }}</td>
                                                <td class="py-4 px-6">{{ $meter->location ?: 'Niet gespecificeerd' }}</td>
                                                <td class="py-4 px-6">
                                                    @if($meter->active)
                                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Actief</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactief</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded text-center">
                                <p>Deze gebruiker heeft nog geen slimme meters gekoppeld.</p>
                                <a href="{{ route('smartmeters.userMeters', $user->id) }}" class="inline-block mt-2 text-blue-600 hover:underline">Klik hier om meters te koppelen</a>
                            </div>
                        @endif
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('users.index') }}"
                            class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Terug naar overzicht
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection