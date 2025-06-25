@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Energie Notificaties</h2>
                    <a href="{{ route('notifications.settings') }}"
                       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm">
                        Instellingen
                    </a>
                </div>

                @if($notifications->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12h5v12z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Geen notificaties</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Je hebt momenteel geen energie notificaties.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($notifications as $notification)
                            <div class="border rounded-lg p-4 
                                @if($notification->severity === 'critical') border-red-500 bg-red-50 dark:bg-red-900/20 dark:border-red-700
                                @elseif($notification->severity === 'warning') border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700
                                @else border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700
                                @endif
                                {{ $notification->status === 'unread' ? 'ring-2 ring-opacity-50' : '' }}
                                @if($notification->severity === 'critical' && $notification->status === 'unread') ring-red-500
                                @elseif($notification->severity === 'warning' && $notification->status === 'unread') ring-yellow-500
                                @elseif($notification->status === 'unread') ring-blue-500
                                @endif">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($notification->severity === 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @elseif($notification->severity === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @endif">
                                                {{ ucfirst($notification->severity) }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst($notification->type) }}
                                            </span>
                                            @if($notification->status === 'unread')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Nieuw
                                                </span>
                                            @endif
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $notification->message }}</p>

                                        @if(!empty($notification->suggestions))
                                            <div class="mt-4">
                                                <p class="font-medium text-gray-700 dark:text-gray-300 mb-3">Bespaartips:</p>
                                                <div class="space-y-3">
                                                    @php
                                                        $customSuggestions = collect($notification->suggestions)->where('type', 'custom');
                                                        $standardSuggestions = collect($notification->suggestions)->where('type', 'standard');
                                                    @endphp
                                                    
                                                    {{-- Custom suggestions eerst --}}
                                                    @if($customSuggestions->count() > 0)
                                                        @foreach($customSuggestions as $suggestion)
                                                            <div class="bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
                                                                <div class="flex items-start space-x-2">
                                                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                    </svg>
                                                                    <div class="flex-1">
                                                                        <div class="flex items-center space-x-2 mb-1">
                                                                            <h4 class="font-semibold text-blue-900 dark:text-blue-100">{{ $suggestion['title'] }}</h4>
                                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                                                                Persoonlijk
                                                                            </span>
                                                                        </div>
                                                                        <p class="text-blue-800 dark:text-blue-200 text-sm">{{ $suggestion['description'] }}</p>
                                                                        @if(isset($suggestion['saving']))
                                                                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">{{ $suggestion['saving'] }}</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    
                                                    {{-- Standaard suggestions --}}
                                                    @foreach($standardSuggestions as $suggestion)
                                                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                                            <div class="flex items-start space-x-2">
                                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                                                </svg>
                                                                <div class="flex-1">
                                                                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $suggestion['title'] }}</h4>
                                                                    <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $suggestion['description'] }}</p>
                                                                    @if(isset($suggestion['saving']))
                                                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">Besparing: {{ $suggestion['saving'] }}</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex space-x-2 ml-4">
                                        @if($notification->status === 'unread')
                                            <button
                                                data-notification-id="{{ $notification->id }}"
                                                class="mark-as-read text-xs px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                                Markeer als gelezen
                                            </button>
                                        @endif
                                        <button
                                            data-notification-id="{{ $notification->id }}"
                                            class="dismiss-notification text-xs px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800 transition">
                                            Verbergen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- JavaScript voor notification acties --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark as read functionality
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            
            fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the "Nieuw" badge and unread styling
                    const notificationElement = this.closest('.border');
                    notificationElement.classList.remove('ring-2', 'ring-opacity-50', 'ring-red-500', 'ring-yellow-500', 'ring-blue-500');
                    
                    // Remove "Nieuw" badge
                    const newBadge = notificationElement.querySelector('.bg-green-100');
                    if (newBadge) {
                        newBadge.remove();
                    }
                    
                    // Remove the button
                    this.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het markeren van de notificatie.');
            });
        });
    });

    // Dismiss functionality
    document.querySelectorAll('.dismiss-notification').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            
            if (confirm('Weet je zeker dat je deze notificatie wilt verbergen?')) {
                fetch(`/notifications/${notificationId}/dismiss`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the entire notification
                        this.closest('.border').remove();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het verbergen van de notificatie.');
                });
            }
        });
    });
});
</script>
@endsection