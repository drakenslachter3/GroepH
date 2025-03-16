@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-xl font-semibold mb-4">{{ isset($account) ? 'Account bewerken' : 'Nieuw account aanmaken' }}</h2>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ isset($account) ? route('accounts.update', $account->id) : route('accounts.store') }}">
                    @csrf
                    @if(isset($account))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Naam <span class="text-red-600">*</span></label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="name" name="name" value="{{ old('name', isset($account) ? $account->name : '') }}" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-600">*</span></label>
                        <input type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="email" name="email" value="{{ old('email', isset($account) ? $account->email : '') }}" required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wachtwoord {{ isset($account) ? '' : '<span class="text-red-600">*</span>' }}</label>
                        <input type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="password" name="password" {{ isset($account) ? '' : 'required' }}>
                        @if(isset($account))
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Laat leeg om het huidige wachtwoord te behouden</p>
                        @endif
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bevestig wachtwoord {{ isset($account) ? '' : '<span class="text-red-600">*</span>' }}</label>
                        <input type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="password_confirmation" name="password_confirmation" {{ isset($account) ? '' : 'required' }}>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefoonnummer</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="phone" name="phone" value="{{ old('phone', isset($account) ? $account->phone : '') }}">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adres</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="address" name="address" value="{{ old('address', isset($account) ? $account->address : '') }}">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postcode</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="postal_code" name="postal_code" value="{{ old('postal_code', isset($account) ? $account->postal_code : '') }}">
                        @error('postal_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stad</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="city" name="city" value="{{ old('city', isset($account) ? $account->city : '') }}">
                        @error('city')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="smart_meter_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slimme Meter ID</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="smart_meter_id" name="smart_meter_id">
                            <option value="">-- Selecteer een slimme meter --</option>
                            @foreach($smartMeters as $meter)
                                <option value="{{ $meter->id }}" {{ (old('smart_meter_id', isset($account) && $account->smartMeter ? $account->smartMeter->id : '') == $meter->id) ? 'selected' : '' }}>
                                    {{ $meter->meter_id }} - {{ $meter->location }}
                                </option>
                            @endforeach
                        </select>
                        @error('smart_meter_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol <span class="text-red-600">*</span></label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="role" name="role" required>
                            <option value="user" {{ (old('role', isset($account) ? $account->role : '') == 'user') ? 'selected' : '' }}>Gebruiker</option>
                            <option value="admin" {{ (old('role', isset($account) ? $account->role : '') == 'admin') ? 'selected' : '' }}>Beheerder</option>
                            <option value="owner" {{ (old('role', isset($account) ? $account->role : '') == 'owner') ? 'selected' : '' }}>Eigenaar</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center">
                            <input class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" type="checkbox" id="active" name="active" value="1" {{ old('active', isset($account) ? $account->active : '1') ? 'checked' : '' }}>
                            <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300" for="active">
                                Account actief
                            </label>
                        </div>
                        @error('active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('accounts.index') }}" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Annuleren
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ isset($account) ? 'Wijzigingen opslaan' : 'Account aanmaken' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection