<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('InfluxDB Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between">
                        <h3 class="text-lg font-medium">Saved Data</h3>
                        <a href="{{ route('influx.create') }}" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">
                            New Query
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 mb-4 text-green-700 bg-green-100 border border-green-200 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 mb-4 text-red-700 bg-red-100 border border-red-200 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                        Measurement
                                    </th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                        Tags
                                    </th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                        Fields
                                    </th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                        Time
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($data as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $item->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $item->measurement }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <pre class="text-xs">{{ json_encode($item->tags, JSON_PRETTY_PRINT) }}</pre>
                                        </td>
                                        <td class="px-6 py-4">
                                            <pre class="text-xs">{{ json_encode($item->fields, JSON_PRETTY_PRINT) }}</pre>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $item->time }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center">
                                            No data found. Try running a query first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>