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
    <!-- Energy Notification Bell Button -->
    <button x-ref="button" x-on:click="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
        type="button"
        class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
        <span class="sr-only">Energienotificaties</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>

        @if ($unreadCount > 0)
            <span
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                {{ $unreadCount }}
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
            <div class="border-b pb-2 mb-2 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Energienotificaties</h3>
                <a href="{{ route('notifications.settings') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Instellingen
                </a>
            </div>

            @if ($unreadCount === 0)
                <div class="py-4 text-center text-gray-500 dark:text-gray-400">
                    <p>Geen nieuwe notificaties</p>
                </div>
            @else
                <div class="max-h-80 overflow-y-auto">
                    @foreach ($unreadNotifications as $notification)
                        <div class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md mb-2 border-l-4 border-{{ $notification->getSeverityColorClass() }}-500">
                            <div class="flex justify-between">
                                <div class="w-full">
                                    <div class="flex items-center">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-{{ $notification->getSeverityColorClass() }}-100 text-{{ $notification->getSeverityColorClass() }}-800 dark:bg-{{ $notification->getSeverityColorClass() }}-900/30 dark:text-{{ $notification->getSeverityColorClass() }}-300 mr-2">
                                            {{ $notification->getTypeLabel() }}
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $notification->message }}</p>
                                    
                                    @if(count($notification->suggestions) > 0)
                                        <div class="mt-2 text-sm">
                                            <p class="font-medium text-gray-700 dark:text-gray-300">Suggesties:</p>
                                            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-1">
                                                @foreach(array_slice($notification->suggestions, 0, 2) as $suggestion)
                                                    <li>{{ $suggestion['title'] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-end mt-2 space-x-2">
                                <button 
                                    data-notification-id="{{ $notification->id }}"
                                    class="mark-as-read text-xs px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Gelezen
                                </button>
                                <button 
                                    data-notification-id="{{ $notification->id }}"
                                    class="dismiss-notification text-xs px-2 py-1 bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 rounded hover:bg-red-200 dark:hover:bg-red-700 transition">
                                    Verwijderen
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-2 pt-2 border-t">
                    <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        Alle notificaties bekijken
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mark as read functionality
        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationId = this.dataset.notificationId;
                
                fetch(`/notifications/${notificationId}/mark-as-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page or update the UI
                        window.location.reload();
                    }
                });
            });
        });

        // Dismiss notification functionality
        document.querySelectorAll('.dismiss-notification').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationId = this.dataset.notificationId;
                
                fetch(`/notifications/${notificationId}/dismiss`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page or update the UI
                        window.location.reload();
                    }
                });
            });
        });
    });
</script>
@endpush