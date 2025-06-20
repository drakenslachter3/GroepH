@extends('layouts.admin')

@section('title', 'InfluxDB Uitval Bewerken')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">InfluxDB Uitval Bewerken</h1>
        <a href="{{ route('admin.influxdb-outages.index') }}" 
           class="text-blue-600 hover:text-blue-800 text-sm">← Terug naar overzicht</a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('admin.influxdb-outages.update', $influxdbOutage) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Tijd *
                    </label>
                    <input type="datetime-local" 
                           id="start_time" 
                           name="start_time" 
                           value="{{ old('start_time', $influxdbOutage->start_time->format('Y-m-d\TH:i')) }}" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('start_time') border-red-500 @enderror"
                           required>
                    @error('start_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Eind Tijd
                    </label>
                    <input type="datetime-local" 
                           id="end_time" 
                           name="end_time" 
                           value="{{ old('end_time', $influxdbOutage->end_time?->format('Y-m-d\TH:i')) }}" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('end_time') border-red-500 @enderror">
                    @error('end_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Status *
                </label>
                <select id="status" 
                        name="status" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror"
                        required>
                    <option value="">Selecteer status</option>
                    <option value="actief" {{ old('status', $influxdbOutage->status) === 'actief' ? 'selected' : '' }}>Actief</option>
                    <option value="opgelost" {{ old('status', $influxdbOutage->status) === 'opgelost' ? 'selected' : '' }}>Opgelost</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Beschrijving
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="4" 
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Optionele beschrijving van de uitval...">{{ old('description', $influxdbOutage->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.influxdb-outages.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-200">
                    Annuleren
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                    Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection