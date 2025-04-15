@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Password Reset Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Session Status -->
                    @if(session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Error Message -->
                    @if(session('error'))
                        <div class="mb-4 font-medium text-sm text-red-600 dark:text-red-400">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($resetRequests->isEmpty())
                        <p>No password reset requests found.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Requested</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expires</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    @foreach($resetRequests as $request)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $request->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $request->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($request->isPending())
                                                    <span class="px-2 py-1 text-xs font-semibold leading-tight text-yellow-700 bg-yellow-100 rounded-full dark:bg-yellow-700 dark:text-yellow-100">Pending</span>
                                                @elseif($request->isApproved())
                                                    <span class="px-2 py-1 text-xs font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">Approved</span>
                                                @elseif($request->isDenied())
                                                    <span class="px-2 py-1 text-xs font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:bg-red-700 dark:text-red-100">Denied</span>
                                                @endif
                                                
                                                @if($request->isExpired())
                                                    <span class="ml-1 px-2 py-1 text-xs font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-100">Expired</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $request->created_at->diffForHumans() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $request->expires_at->diffForHumans() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if($request->isPending() && !$request->isExpired())
                                                    <div class="flex space-x-2">
                                                        <form method="POST" action="{{ route('password.admin.approve', $request) }}">
                                                            @csrf
                                                            <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" action="{{ route('password.admin.deny', $request) }}">
                                                            @csrf
                                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                                Deny
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">No actions available</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $resetRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection