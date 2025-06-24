@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                
                {{-- Header --}}
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold">Energietip Toevoegen</h2>
                        <p class="text-gray-600 dark:text-gray-400">Voor: <strong>{{ $user->name }}</strong> ({{ $user->email }})</p>
                    </div>
                    <a href="{{ route('users.show', $user) }}" 
                       class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md">
                        Terug naar gebruiker
                    </a>
                </div>

                {{-- User Info Box --}}
                @if($user->additional_info)
                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Gebruiker informatie:</h3>
                        <p class="text-blue-800 dark:text-blue-200 text-sm whitespace-pre-wrap">{{ $user->additional_info }}</p>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('users.store-suggestion', $user) }}" class="space-y-6">
                    @csrf

                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Titel van de tip <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" required
                               value="{{ old('title') }}"
                               placeholder="bijv. 'Optimaliseer je zonnepanelen'"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 dark:text-gray-100 @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Suggestion --}}
                    <div>
                        <label for="suggestion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Energietip <span class="text-red-500">*</span>
                        </label>
                        <textarea name="suggestion" id="suggestion" rows="5" required
                                  placeholder="Geef hier de concrete tip met uitleg. Probeer specifiek en actionable te zijn..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 dark:text-gray-100 @error('suggestion') border-red-500 @enderror">{{ old('suggestion') }}</textarea>
                        
                        <div class="mt-2 flex justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>Probeer specifiek en actionable te zijn</span>
                            <span id="char-count">0/1000</span>
                        </div>
                        
                        @error('suggestion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tip Voorbeelden --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">💡 Tip Voorbeelden:</h3>
                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <li>• "Plan wasmachine en vaatwasser overdag (10-16u) om je zonnepanelen optimaal te benutten"</li>
                            <li>• "Zet je thermostaat 1 graad lager en bespaar direct 6% op je gasverbruik"</li>
                            <li>• "Vervang je oude koelkast door een A+++ model - dit kan €100+ per jaar besparen"</li>
                            <li>• "Gebruik een waterbesparende douchekop om 30% minder warm water te gebruiken"</li>
                        </ul>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-between items-center pt-4">
                        <a href="{{ route('users.show', $user) }}" 
                           class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            Annuleren
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium">
                            Tip Toevoegen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter
document.getElementById('suggestion').addEventListener('input', function() {
    const maxLength = 1000;
    const currentLength = this.value.length;
    const counter = document.getElementById('char-count');
    
    counter.textContent = currentLength + '/' + maxLength;
    
    if (currentLength > maxLength * 0.9) {
        counter.classList.add('text-orange-600');
    } else {
        counter.classList.remove('text-orange-600');
    }
});
</script>
@endsection