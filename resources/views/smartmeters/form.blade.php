{{-- resources/views/smartmeters/form.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-xl font-semibold mb-4">
                        {{ isset($smartmeter) ? 'Slimme Meter Bewerken' : 'Nieuwe Slimme Meter Aanmaken' }}</h2>

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" novalidate
                        action="{{ isset($smartmeter) ? route('smartmeters.update', $smartmeter->id) : route('smartmeters.store') }}">
                        @csrf
                        @if(isset($smartmeter)) <!-- Changed from $smartMeter -->
                            @method('PUT')
                        @endif

                        <div class="mb-4">
                            <label for="meter_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meter
                                ID <span class="text-red-600">*</span></label>
                            <input type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                id="meter_id" name="meter_id"
                                value="{{ old('meter_id', isset($smartmeter) ? $smartmeter->meter_id : '') }}" required>
                            @error('meter_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="name"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">Naam <span
                                   class="text-red-600">*</span></label>
                            <input type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   id="name" name="name"
                                   value="{{ old('name', isset($smartmeter) ? $smartmeter->name : '') }}">
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="location"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Locatie</label>
                            <input type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                id="location" name="location"
                                value="{{ old('location', isset($smartmeter) ? $smartmeter->location : '') }}">
                            @error('location')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meet Typen <span
                                    class="text-red-600">*</span></span>
                            <div class="flex space-x-6">
                                <div class="flex items-center">
                                    <input
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                        type="checkbox" id="measures_electricity" name="measures_electricity" value="1" {{ old('measures_electricity', isset($smartmeter) ? $smartmeter->measures_electricity : '1') ? 'checked' : '' }}>
                                    <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                                        for="measures_electricity">
                                        Elektriciteit
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                        type="checkbox" id="measures_gas" name="measures_gas" value="1" {{ old('measures_gas', isset($smartmeter) ? $smartmeter->measures_gas : '1') ? 'checked' : '' }}>
                                    <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300" for="measures_gas">
                                        Gas
                                    </label>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selecteer tenminste één meettype</p>
                            @if ($errors->has('error'))
                                <div class="mt-1 text-sm text-red-600">{{ $errors->first('error') }}</div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="installation_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Installatiedatum</label>
                            <input type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                id="installation_date" name="installation_date"
                                value="{{ old('installation_date', isset($smartmeter) ? $smartmeter->installation_date?->format('Y-m-d') : '') }}">
                            @error('installation_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="account_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Koppel aan
                                Gebruiker</label>
                            <select
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                id="account_id" name="account_id">
                                <option value="">-- Selecteer een gebruiker --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (old('account_id', isset($smartmeter) ? $smartmeter->account_id : (isset($selectedUser) ? $selectedUser->id : ''))) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optioneel. U kunt de meter later ook
                                nog aan een gebruiker koppelen.</p>
                        </div>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <input
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    type="checkbox" id="active" name="active" value="1" {{ old('active', isset($smartmeter) ? $smartmeter->active : '1') ? 'checked' : '' }}>
                                <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300" for="active">
                                    Meter actief
                                </label>
                            </div>
                            @error('active')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-between mt-6">
                            <a href="{{ isset($smartmeter) ? route('smartmeters.show', $smartmeter->id) : route('smartmeters.index') }}"
                                class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Annuleren
                            </a>
                            <button type="submit" dusk="save-button"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ isset($smartmeter) ? 'Wijzigingen opslaan' : 'Meter aanmaken' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
