@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-xl font-semibold mb-4">{{ isset($user) ? 'Gebruiker bewerken' : 'Nieuwe gebruiker aanmaken' }}</h2>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}">
                    @csrf
                    @if(isset($user))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Naam <span class="text-red-600">*</span></label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="name" name="name" value="{{ old('name', isset($user) ? $user->name : '') }}" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-600">*</span></label>
                        <input type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="email" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}" required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Description Field -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Beschrijving</label>
                        <textarea class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="description" name="description" rows="3">{{ old('description', isset($user) ? $user->description : '') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wachtwoord {!! isset($user) ? '' : '<span class="text-red-600">*</span>' !!}</label>
                        <input type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="password" name="password" {{ isset($user) ? '' : 'required' }}>
                        @if(isset($user))
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Laat leeg om het huidige wachtwoord te behouden</p>
                        @endif
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bevestig wachtwoord {!! isset($user) ? '' : '<span class="text-red-600">*</span>' !!}</label>
                        <input type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="password_confirmation" name="password_confirmation" {{ isset($user) ? '' : 'required' }}>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefoonnummer</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="phone" name="phone" value="{{ old('phone', isset($user) ? $user->phone : '') }}">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Smart Meter Selection (Improved Version) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slimme Meter</label>
                        
                        <div class="relative" x-data="{ open: false, selectedMeter: null, search: '' }">
                            <!-- Hidden input to store the selected smart meter ID -->
                            <input type="hidden" name="smart_meter_id" :value="selectedMeter ? selectedMeter.id : ''" />
                            
                            <!-- Search input field -->
                            <div class="flex">
                                <input 
                                    type="text" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Zoek een slimme meter... (ID of locatie)"
                                    @focus="open = true"
                                    @click.outside="open = false"
                                    x-model="search"
                                    :value="selectedMeter ? selectedMeter.meter_id + ' - ' + selectedMeter.location : ''"
                                />
                                <button type="button" class="ml-2 mt-1 bg-indigo-600 p-2 rounded-md text-white" @click="open = !open">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Results dropdown -->
                            <div 
                                x-show="open" 
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md py-1 text-base overflow-auto focus:outline-none max-h-60"
                                style="display: none;"
                            >
                                <div x-show="!filteredMeters().length" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    Geen slimme meters gevonden
                                </div>
                                
                                <template x-for="meter in filteredMeters()" :key="meter.id">
                                    <div 
                                        @click="selectedMeter = meter; open = false; search = meter.meter_id + ' - ' + meter.location;"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-100 dark:hover:bg-indigo-900"
                                        :class="selectedMeter && selectedMeter.id === meter.id ? 'bg-indigo-50 dark:bg-indigo-800' : ''"
                                    >
                                        <div class="flex items-center">
                                            <span class="block truncate text-gray-900 dark:text-white" x-text="meter.meter_id + ' - ' + meter.location"></span>
                                        </div>
                                        <span 
                                            x-show="selectedMeter && selectedMeter.id === meter.id" 
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 dark:text-indigo-400"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Selected smart meter display -->
                            <div x-show="selectedMeter" class="mt-2 p-2 bg-indigo-50 dark:bg-indigo-900 rounded-md flex justify-between items-center">
                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="selectedMeter ? 'Geselecteerd: ' + selectedMeter.meter_id + ' - ' + selectedMeter.location : ''"></span>
                                <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800" @click="selectedMeter = null; search = '';">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        @error('smart_meter_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol <span class="text-red-600">*</span></label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="role" name="role" required>
                            <option value="user" {{ (old('role', isset($user) ? $user->role : '') == 'user') ? 'selected' : '' }}>Gebruiker</option>
                            <option value="admin" {{ (old('role', isset($user) ? $user->role : '') == 'admin') ? 'selected' : '' }}>Beheerder</option>
                            <option value="owner" {{ (old('role', isset($user) ? $user->role : '') == 'owner') ? 'selected' : '' }}>Eigenaar</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center">
                            <input class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" type="checkbox" id="active" name="active" value="1" {{ old('active', isset($user) ? $user->active : '1') ? 'checked' : '' }}>
                            <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300" for="active">
                                Gebruiker actief
                            </label>
                        </div>
                        @error('active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Annuleren
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ isset($user) ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('smartMeterSelector', () => ({
            init() {
                // Initialize with current user's smart meter if editing
                @if(isset($user) && $user->smartMeter)
                this.selectedMeter = {
                    id: {{ $user->smartMeter->id }},
                    meter_id: "{{ $user->smartMeter->meter_id }}",
                    location: "{{ $user->smartMeter->location }}"
                };
                this.search = this.selectedMeter.meter_id + ' - ' + this.selectedMeter.location;
                @endif
            },
            open: false,
            selectedMeter: null,
            search: '',
            smartMeters: @json($smartMeters->map(function($meter) {
                return [
                    'id' => $meter->id,
                    'meter_id' => $meter->meter_id,
                    'location' => $meter->location
                ];
            })),
            filteredMeters() {
                if (!this.search) return this.smartMeters;
                
                const searchLower = this.search.toLowerCase();
                return this.smartMeters.filter(meter => 
                    meter.meter_id.toLowerCase().includes(searchLower) || 
                    meter.location.toLowerCase().includes(searchLower)
                );
            }
        }));
    });
</script>
@endsection