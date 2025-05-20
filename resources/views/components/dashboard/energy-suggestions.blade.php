@props(['title', 'usagePattern', 'housingType', 'season'])

<section aria-labelledby="suggestion-widget-title">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <x-widget-navigation :showPrevious="true" />
        <x-widget-heading :title="$title" />
        <x-widget-navigation :showNext="true" />
        <div class="space-y-4">
            <!-- Elektriciteit tips -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="font-medium text-blue-700">Elektriciteit Besparing</h4>
                <p class="mt-1 text-blue-600">
                    @if($usagePattern === 'avond')
                        Uw elektriciteitsverbruik is het hoogst tussen 18:00 en 21:00 uur. Overweeg het gebruik van grote apparaten te verplaatsen naar daluren (21:00-07:00) om ongeveer 14% te besparen op uw elektriciteitskosten.
                    @elseif($usagePattern === 'ochtend')
                        Uw elektriciteitsverbruik piekt in de ochtenduren. Spreid het gebruik van grote apparaten zoals wasmachine en vaatwasser over de dag om pieken te vermijden.
                    @else
                        Door LED-verlichting te gebruiken in plaats van gloeilampen kunt u tot 80% energie besparen op uw verlichting. Ook het uitschakelen van apparaten in plaats van stand-by bespaart jaarlijks tot €70.
                    @endif
                </p>
            </div>
            
            <!-- Gas tips op basis van woningtype -->
            <div class="bg-yellow-50 p-4 rounded-lg">
                <h4 class="font-medium text-yellow-700">Gas Besparing voor uw {{ $housingType }}</h4>
                <p class="mt-1 text-yellow-600">
                    @switch($housingType)
                        @case('appartement')
                            In een appartement kunt u tot 15% op gasverbruik besparen door radiatorfolie te plaatsen achter radiatoren aan de buitenmuur, en tochtstrips rond ramen en deuren aan te brengen.
                            @break
                        @case('tussenwoning')
                            Voor een tussenwoning is dakisolatie zeer effectief. Hiermee bespaart u tot 20% op uw gasverbruik. Ook het isoleren van de vloer levert een besparing van 10% op.
                            @break
                        @case('hoekwoning')
                            Bij een hoekwoning kan spouwmuurisolatie tot 25% besparing op uw gasrekening opleveren. Ook het isoleren van de vloer bespaart ongeveer 10%.
                            @break
                        @case('twee_onder_een_kap')
                            Voor een twee-onder-een-kapwoning is een combinatie van spouwmuurisolatie en dakisolatie het meest effectief, met een potentiële besparing tot 30%.
                            @break
                        @case('vrijstaand')
                            Bij een vrijstaande woning kunt u het meest besparen met complete schilisolatie (muren, dak en vloer), wat tot 35% besparing op uw gasverbruik kan opleveren.
                            @break
                        @default
                            Isolatie is de effectiefste manier om gasverbruik te verlagen. Begin met de grootste oppervlakken zoals het dak en de muren voor het beste resultaat.
                    @endswitch
                </p>
            </div>
            
            <!-- Seizoensgebonden tips -->
            <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="font-medium text-green-700">Tips voor de {{ $season }}</h4>
                <p class="mt-1 text-green-600">
                    @switch($season)
                        @case('winter')
                            In de winter bespaart u gas door de thermostaat 's nachts en bij afwezigheid op 15°C te zetten. Elke graad lager kan tot 6% besparing opleveren. Houd radiatoren vrij en sluit gordijnen 's avonds.
                            @break
                        @case('lente')
                            In de lente kunt u de verwarming vaak al uit zetten en natuurlijke ventilatie gebruiken. Zet ramen tegenover elkaar open voor effectieve doorluchting zonder energieverlies.
                            @break
                        @case('zomer')
                            In de zomer kunt u uw energierekening laag houden door zonwering te gebruiken overdag en 's nachts te ventileren in plaats van airconditioning. Een ventilator gebruikt tot 90% minder energie dan airco.
                            @break
                        @case('herfst')
                            In de herfst is het verstandig om uw cv-installatie te laten onderhouden voor optimale efficiëntie. Ontlucht radiatoren en test uw thermostaat voor het stookseizoen begint.
                            @break
                    @endswitch
                </p>
            </div>
        </div>
    </div>
</section>