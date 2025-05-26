<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('New InfluxDB Query') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between">
                        <h3 class="text-lg font-medium">Query InfluxDB</h3>
                        <button id="testConnection" class="px-4 py-2 text-white bg-green-500 rounded hover:bg-green-600">
                            Test Connection
                        </button>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="p-4 mb-4 text-red-700 bg-red-100 border border-red-200 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div id="connectionStatus" class="hidden p-4 mb-4 text-center rounded"></div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('influx.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="query" class="block mb-2 text-sm font-medium text-gray-700">
                                Flux Query
                            </label>
                            <textarea
                                id="query"
                                name="query"
                                rows="8"
                                class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="from(bucket: &quot;your_bucket&quot;)&#10;  |> range(start: -1h)&#10;  |> filter(fn: (r) => r._measurement == &quot;measurement_name&quot;)&#10;  |> limit(n: 10)"
                            >{{ old('query') ?? 'from(bucket: "' . config('influxdb.bucket') . '")
  |> range(start: -1h)
  |> limit(n: 10)' }}</textarea>
                            @error('query')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('influx.index') }}" class="px-4 py-2 mr-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">
                                Execute Query and Save Results
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('testConnection').addEventListener('click', function() {
            const statusEl = document.getElementById('connectionStatus');
            statusEl.classList.remove('hidden', 'bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700');
            statusEl.innerHTML = 'Testing connection...';
            statusEl.classList.add('bg-blue-100', 'text-blue-700');

            fetch('{{ route('influx.test-connection') }}')
                .then(response => response.json())
                .then(data => {
                    statusEl.classList.remove('bg-blue-100', 'text-blue-700');
                    if (data.success) {
                        statusEl.classList.add('bg-green-100', 'text-green-700');
                    } else {
                        statusEl.classList.add('bg-red-100', 'text-red-700');
                    }
                    statusEl.innerHTML = data.message;
                })
                .catch(error => {
                    statusEl.classList.remove('bg-blue-100', 'text-blue-700');
                    statusEl.classList.add('bg-red-100', 'text-red-700');
                    statusEl.innerHTML = 'Error: ' + error.message;
                });
        });
    </script>
</x-app-layout>