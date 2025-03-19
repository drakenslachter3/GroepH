@props(['tips'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 bg-white border-b border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Gepersonaliseerde Besparingstips</h3>
        
        <div class="space-y-4">
            @foreach($tips as $tip)
                <div class="bg-{{ $tip['type'] === 'electricity' ? 'blue' : ($tip['type'] === 'gas' ? 'yellow' : 'green') }}-50 p-4 rounded-lg">
                    <div class="flex justify-between">
                        <h4 class="font-medium text-{{ $tip['type'] === 'electricity' ? 'blue' : ($tip['type'] === 'gas' ? 'yellow' : 'green') }}-700">{{ $tip['title'] }}</h4>
                        <span class="text-{{ $tip['type'] === 'electricity' ? 'blue' : ($tip['type'] === 'gas' ? 'yellow' : 'green') }}-700 font-bold">
                            Potentiële besparing: {{ $tip['saving_potential'] }}
                        </span>
                    </div>
                    <p class="mt-1 text-{{ $tip['type'] === 'electricity' ? 'blue' : ($tip['type'] === 'gas' ? 'yellow' : 'green') }}-600">{{ $tip['description'] }}</p>
                </div>
            @endforeach
            
            @if(count($tips) === 0)
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-medium text-green-700">U doet het goed!</h4>
                    <p class="mt-1 text-green-600">Uw energieverbruik is al optimaal. Blijf zo doorgaan!</p>
                </div>
            @endif
        </div>
    </div>
</div>