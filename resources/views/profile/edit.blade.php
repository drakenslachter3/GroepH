@extends('layouts.app')

@section('title', 'Profiel Bewerken')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Mijn Profiel</h1>
        <p class="text-gray-600">Bewerk je persoonlijke informatie</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Profile Form --}}
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="bg-white shadow-md rounded-lg p-6">
            
            {{-- Basic Info --}}
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Basis Informatie</h2>
            
            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Naam <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $user->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        E-mailadres <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" required
                           value="{{ old('email', $user->email) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Additional Info Section --}}
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Extra Informatie</h2>
            <p class="text-gray-600 mb-4">
                Deel optioneel extra informatie over je energiegebruik, woning of situatie. 
                Dit helpt ons je betere, gepersonaliseerde bespaartips te geven.
            </p>
            
            <div>
                <label for="additional_info" class="block text-sm font-medium text-gray-700 mb-2">
                    Aanvullende informatie <span class="text-gray-500">(optioneel)</span>
                </label>
                <textarea name="additional_info" id="additional_info" rows="5"
                          placeholder="Bijvoorbeeld: 'Ik heb zonnepanelen sinds 2020', 'Woon in een appartement uit 1985', 'Werk veel thuis', 'Heb een warmtepomp', etc."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('additional_info') border-red-500 @enderror">{{ old('additional_info', $user->additional_info) }}</textarea>
                
                <div class="mt-2 flex justify-between text-sm text-gray-500">
                    <span>Deze informatie wordt alleen gebruikt voor betere energietips</span>
                    <span id="char-count">{{ strlen($user->additional_info ?? '') }}/1000</span>
                </div>
                
                @error('additional_info')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Active Suggestions Display --}}
        @if($user->activeSuggestions->count() > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    Jouw Persoonlijke Bespaartips
                </h2>
                
                <div class="space-y-3">
                    @foreach($user->activeSuggestions as $suggestion)
                        <div class="bg-white rounded-lg p-4 border border-blue-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-2">{{ $suggestion->title }}</h3>
                                    <p class="text-gray-700 mb-2">{{ $suggestion->suggestion }}</p>
                                    <p class="text-xs text-gray-500">
                                        Toegevoegd {{ $suggestion->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="completeSuggestion({{ $suggestion->id }})"
                                            class="text-green-600 hover:text-green-800 text-sm">
                                        ✓ Gedaan
                                    </button>
                                    <button onclick="dismissSuggestion({{ $suggestion->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm">
                                        ✗ Verbergen
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Save Button --}}
        <div class="flex justify-end">
            <button type="submit" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 font-medium">
                Profiel Opslaan
            </button>
        </div>
    </form>
</div>

<script>
// Character counter for additional_info
document.getElementById('additional_info').addEventListener('input', function() {
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

// Suggestion actions
function completeSuggestion(id) {
    if (confirm('Heb je deze tip uitgevoerd?')) {
        fetch(`/profile/suggestions/${id}/complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function dismissSuggestion(id) {
    if (confirm('Wil je deze tip verbergen?')) {
        fetch(`/profile/suggestions/${id}/dismiss`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>
@endsection