<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Energienotificaties') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Alle Energienotificaties</h2>
                        <a href="{{ route('notifications.settings') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm">
                            Instellingen
                        </a>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($notifications->count() > 0)
                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <div class="p-4 bg-white dark:bg-gray-700 shadow-sm rounded-lg border-l-4 border-{{ $notification->getSeverityColorClass() }}-500 {{ $notification->status === 'read' ? 'opacity-70' : '' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-{{ $notification->getSeverityColorClass() }}-100 text-{{ $notification->getSeverityColorClass() }}-800 dark:bg-{{ $notification->getSeverityColorClass() }}-900/30 dark:text-{{ $notification->getSeverityColorClass() }}-300 mr-2">
                                                    {{ $notification->getTypeLabel() }}
                                                </span>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $notification->created_at->format('d-m-Y H:i') }}
                                                </p>
                                                @if($notification->status === 'read')
                                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300">
                                                        Gelezen
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="font-medium text-gray-900 dark:text-white mt-2">{{ $notification->message }}</p>
                                            
                                            @if(count($notification->suggestions) > 0)
                                                <div class="mt-3">
                                                    <p class="font-medium text-gray-700 dark:text-gray-300">Suggesties:</p>
                                                    <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-1 space-y-1">
                                                        @foreach($notification->suggestions as $suggestion)
                                                            <li>
                                                                <span class="font-medium">{{ $suggestion['title'] }}</span> - {{ $suggestion['description'] }}
                                                                @if(isset($suggestion['saving']))
                                                                    <span class="text-green-600 dark:text-green-400">(Besparing: {{ $suggestion['saving'] }})</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                            @if($notification->status === 'unread')
                                                <button 
                                                    data-notification-id="{{ $notification->id }}"
                                                    class="mark-as-read text-xs px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                                    Markeer als gelezen
                                                </button>
                                            @endif
                                            @if($notification->status !== 'dismissed')
                                                <button 
                                                    data-notification-id="{{ $notification->id }}"
                                                    class="dismiss-notification text-xs px-3 py-1 bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-700/30 transition">
                                                    Verwijderen
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <p>Geen notificaties gevonden</p>
                        </div>
                    @endif
                </div>
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
                            window.location.reload();
                        }
                    });
                });
            });
        });
    </script>
    @endpush
</x-app-layout>