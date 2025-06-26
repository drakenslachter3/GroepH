<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
            Bewerk je persoonlijke informatie
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 bg-green-900 border border-green-700 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Profile Form --}}
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-8">
            @csrf
            @method('PATCH')

            {{-- Basic Info --}}
            <div class="bg-white p-6 rounded-lg dark:bg-gray-700">
                <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-100">Basis Informatie</h2>

                <div class="space-y-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Naam <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-100 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            E-mailadres <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input type="email" name="email" id="email" required value="{{ old('email', $user->email) }}"
                            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-100 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Extra Info --}}
            <div class="bg-white p-6 rounded-lg dark:bg-gray-700">
                <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Extra Informatie</h2>

                <p class="text-gray-600 mb-6 dark:text-gray-400">
                    Deel optioneel extra informatie over je energiegebruik, woning of situatie. Dit helpt
                    ons je betere, gepersonaliseerde bespaartips te geven.
                </p>

                <div>
                    <label for="additional_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Aanvullende informatie (optioneel)
                    </label>
                    <textarea name="additional_info" id="additional_info" rows="6" maxlength="1000"
                        placeholder="Bijvoorbeeld: 'Ik heb zonnepanelen sinds 2020', 'Woon in een appartement uit 1985', 'Werk veel thuis', etc."
                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-100 @error('additional_info') border-red-500 @enderror">{{ old('additional_info', $user->additional_info) }}</textarea>

                    <div class="mt-2 flex justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>Deze informatie wordt alleen gebruikt voor betere energietips</span>
                        <span id="char-count">{{ strlen($user->additional_info ?? '') }}/1000</span>
                    </div>

                    @error('additional_info')
                        <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Custom Suggestions Display --}}
            @if($user->activeSuggestions->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                </path>
                            </svg>
                            Jouw Persoonlijke Bespaartips
                        </h2>

                        <div class="space-y-4">
                            @foreach($user->activeSuggestions as $suggestion)
                                <div
                                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-semibold text-blue-900 dark:text-blue-100">{{ $suggestion->title }}
                                            </h3>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                                Persoonlijk
                                            </span>
                                        </div>
                                        <p class="text-blue-800 dark:text-blue-200 mb-2">{{ $suggestion->suggestion }}</p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">
                                            Toegevoegd {{ $suggestion->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Save Button --}}
            <div class="flex justify-end">
                <button type="submit"
                    class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition duration-200">
                    Profiel Opslaan
                </button>
            </div>
        </form>
    </div>

    {{-- JavaScript voor suggestion acties en character count --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Character count voor textarea
            const textarea = document.getElementById('additional_info');
            const charCount = document.getElementById('char-count');

            if (textarea && charCount) {
                textarea.addEventListener('input', function () {
                    const count = this.value.length;
                    charCount.textContent = count + '/1000';

                    if (count > 950) {
                        charCount.classList.add('text-red-400');
                        charCount.classList.remove('text-gray-500');
                    } else {
                        charCount.classList.add('text-gray-500');
                        charCount.classList.remove('text-red-400');
                    }
                });
            }
        });

        function completeSuggestion(suggestionId) {
            fetch(`/profile/suggestions/${suggestionId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Reload de pagina om de suggestie te verbergen
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het markeren van de suggestie.');
                });
        }

        function dismissSuggestion(suggestionId) {
            fetch(`/profile/suggestions/${suggestionId}/dismiss`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Reload de pagina om de suggestie te verbergen
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het afwijzen van de suggestie.');
                });
        }
    </script>
</x-app-layout>