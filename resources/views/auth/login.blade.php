<script>
    document.addEventListener("DOMContentLoaded", function () {
        const passwordField = document.getElementById("password");
        const eyeIcon = document.getElementById("eyeIcon");

        eyeIcon.addEventListener("click", function () {
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>`;


                eyePath.setAttribute("d", "M3 12s3-6 9-6 9 6-9 6-9-6z M12 12m-3 0a3 3 0 1 1 6 0 3 3 0 1 1-6 0");
            } else {
                passwordField.type = "password";
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`;

            }
        });
    });
</script>

<x-guest-layout>
    <!-- Session Status -->
    <x-etc.auth-session-status class="mb-4" :status="session('status')" />

    <h1 tabindex="0" class="text-2xl font-bold mb-6 dark:text-white">
        {{ __('Log in op je account') }}
    </h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="flex flex-col gap-6">

            <!-- E-mailadres -->
            <div class="row-1">
                <x-etc.input-label for="email" :value="__('E-mailadres')" />
                <x-etc.text-input
                    id="email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    aria-describedby="email-error"
                />
                @if ($errors->has('email'))
                    <div id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif
            </div>

            <!-- Wachtwoord -->
            <div class="row-2">
                <x-etc.input-label for="password" :value="__('Wachtwoord')" />
                <div class="relative flex items-center">
                    <x-etc.text-input
                        id="password"
                        class="block mt-1 w-full pr-10"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        aria-describedby="password-error"
                    />

                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor"
                        class="w-6 absolute mt-1 right-0 mr-2 text-gray-500 cursor-pointer bg-white"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </div>
                @if ($errors->has('password'))
                    <div id="password-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('password') }}
                    </div>
                @endif
            </div>

            <!-- Gegevens onthouden -->
            <div class="row-3">
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            name="remember">
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Gegevens onthouden') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Knoppen en links -->
            <div class="row-4">
                <div class="flex flex-row justify-between items-center flex-wrap gap-4 mt-4">

                    <!-- Login Button -->
                    <x-primary-button dusk="login-button" type="submit">
                        {{ __('Log in') }}
                    </x-primary-button>

                    <!-- Wachtwoord vergeten -->
                    @if (Route::has('password.request'))
                        <div class="text-sm">
                            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                href="{{ route('password.request') }}">
                                {{ __('Wachtwoord vergeten?') }}
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </form>
</x-guest-layout>
