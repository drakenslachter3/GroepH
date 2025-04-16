<div x-data="{
    open: false,
    toggle() {
        if (this.open) {
            return this.close()
        }

        this.$refs.button.focus()
        this.open = true
    },
    close(focusAfter) {
        if (!this.open) return

        this.open = false

        focusAfter && focusAfter.focus()
    }
}" x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()" x-id="['dropdown-button']" class="relative">
    <!-- Notification Bell Button -->
    <button x-ref="button" x-on:click="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
        type="button"
        class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
        <span class="sr-only">Berichten</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($pendingCount > 0)
            <span
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                {{ $pendingCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div x-ref="panel" x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95" :id="$id('dropdown-button')" style="display: none;"
        class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
        <div class="p-2">
            <div class="border-b pb-2 mb-2">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Wachtwoord reset verzoeken</h3>
            </div>

            @if ($pendingCount === 0)
                <div class="py-4 text-center text-gray-500 dark:text-gray-400">
                    <p>Geen nieuwe verzoeken</p>
                </div>
            @else
                <div class="max-h-80 overflow-y-auto">
                    @foreach ($pendingRequests as $request)
                        <div class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md mb-2">
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $request->user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->email }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $request->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <form method="POST" action="{{ route('password.admin.approve', $request) }}"
                                        class="inline-block" x-on:submit="open = false">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs px-2 py-1 bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded hover:bg-green-200 dark:hover:bg-green-700 transition">
                                            Accepteren 
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('password.admin.deny', $request) }}"
                                        class="inline-block" x-on:submit="open = false">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs px-2 py-1 bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 rounded hover:bg-red-200 dark:hover:bg-red-700 transition">
                                            Afwijzen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
