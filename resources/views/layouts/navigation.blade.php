<nav x-data="{ open: false }"
     class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700"
     aria-label="Hoofdnavigatie"
     role="navigation">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <x-etc.application-logo />

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <a class="sr-only focus:not-sr-only"
                       href="#main-content">
                        {{ __('Ga naar de hoofdinhoud') }}
                    </a>

                    <x-etc.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-etc.nav-link>

                    <x-etc.nav-link :href="route('budget.form')" :active="request()->routeIs('budget.form')">
                        {{ __('Budget Instellen') }}
                    </x-etc.nav-link>

                    @if(Auth::user()->hasRole(['admin', 'owner']))
                        <x-etc.nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Gebruikers') }}
                        </x-etc.nav-link>

                        <x-etc.nav-link :href="route('smartmeters.index')" :active="request()->routeIs('smartmeters.*')">
                            {{ __('Slimme Meters') }}
                        </x-etc.nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="flex items-center" aria-label="Gebruikersinstellingen">
                @if (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isOwner()))
                    <div class="mr-3" aria-label="Beheerdersmeldingen">
                        <x-admin-notification-inbox />
                    </div>
                @endif
                @if (Auth::check())
                    <div class="mr-3" aria-label="Energieverbruik meldingen">
                        <x-energy-notification-inbox />
                    </div>
                @endif
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-etc.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                aria-haspopup="true" aria-expanded="false" aria-label="Gebruikersmenu">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="fill-current h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20" role="presentation" focusable="false">
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-etc.dropdown-link :href="route('profile.edit')">
                                {{ __('Profiel') }}
                            </x-etc.dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-etc.dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log uit') }}
                                </x-etc.dropdown-link>
                            </form>
                        </x-slot>
                    </x-etc.dropdown>
                </div>
            </div>

            <!-- Hamburger Menu (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        aria-label="Menu openen of sluiten"
                        :aria-expanded="open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" role="img">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden" aria-label="Mobiele navigatie">
        <div class="pt-2 pb-3 space-y-1">
            <x-etc.responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-etc.responsive-nav-link>

            <x-etc.responsive-nav-link :href="route('budget.form')" :active="request()->routeIs('budget.form')">
                {{ __('Energiebudget') }}
            </x-etc.responsive-nav-link>

            @if(Auth::user()->hasRole(['admin', 'owner']))
                <x-etc.responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Gebruikers') }}
                </x-etc.responsive-nav-link>

                <x-etc.responsive-nav-link :href="route('smartmeters.index')" :active="request()->routeIs('smartmeters.*')">
                    {{ __('Slimme Meters') }}
                </x-etc.responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-etc.responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profiel') }}
                </x-etc.responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-etc.responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Uitloggen') }}
                    </x-etc.responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
