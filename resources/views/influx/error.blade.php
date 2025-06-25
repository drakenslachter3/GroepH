{{-- resources/views/smartmeters/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18.364 5.636L5.636 18.364M6.343 6.343l11.314 11.314" />
                        </svg>
                        InfluxDB-foutmelding
                    </h2>
                </div>

                <div class="mb-4 p-4 border border-red-200 dark:border-red-600 rounded bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-100 shadow">
                    <p class="text-md">
                        Probeer het later opnieuw of neem contact op met de beheerder.
                    </p>
                </div>

                <div>
                    <a href="{{ url()->previous() }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded shadow transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 19l-7-7 7-7" />
                        </svg>
                        Terug
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
