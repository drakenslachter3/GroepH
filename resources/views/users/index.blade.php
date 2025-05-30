@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Gebruikersbeheer</h2>
                        <a href="{{ route('users.create') }}"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-600 text-white rounded-md shadow-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Nieuwe Gebruiker
                        </a>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto relative">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">ID</th>
                                    <th scope="col" class="py-3 px-6">Naam</th>
                                    <th scope="col" class="py-3 px-6">Email</th>
                                    <th scope="col" class="py-3 px-6">Beschrijving</th>
                                    <th scope="col" class="py-3 px-6">Slimme Meters</th>
                                    <th scope="col" class="py-3 px-6">Rol</th>
                                    <th scope="col" class="py-3 px-6">Status</th>
                                    <th scope="col" class="py-3 px-6">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="py-4 px-6">{{ $user->id }}</td>
                                        <td class="py-4 px-6">{{ $user->name }}</td>
                                        <td class="py-4 px-6">{{ $user->email }}</td>
                                        <td class="py-4 px-6">
                                            @if($user->description)
                                                <span class="line-clamp-1 max-w-xs" title="{{ $user->description }}">
                                                    {{ $user->description }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 italic">Geen beschrijving</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                                    {{ $user->smart_meters_count ?? 0 }}
                                                </span>
                                                @if($user->smart_meters_count > 0)
                                                    <a href="{{ route('smartmeters.userMeters', $user->id) }}" class="ml-2 text-blue-600 hover:underline">
                                                        Beheren
                                                    </a>
                                                @else
                                                    <a href="{{ route('smartmeters.userMeters', $user->id) }}" class="ml-2 text-blue-600 hover:underline">
                                                        Toevoegen
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($user->role == 'owner')
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Eigenaar</span>
                                            @elseif($user->role == 'admin')
                                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Beheerder</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Gebruiker</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($user->active)
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Actief</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('users.show', $user->id) }}"
                                                    class="px-3 py-1 text-blue-500 hover:text-blue-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('users.edit', $user->id) }}"
                                                    class="px-3 py-1 text-yellow-500 hover:text-yellow-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('users.delete', $user->id) }}"
                                                    style="display: inline;">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Weet je zeker dat je de gebruiker {{ $user->name }} wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.');"
                                                        class="px-3 py-1 text-red-500 hover:text-red-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td colspan="8" class="py-4 px-6 text-center">Geen gebruikers gevonden</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection