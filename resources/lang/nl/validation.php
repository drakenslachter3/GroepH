<?php

return [

    'required' => 'Het veld :attribute is verplicht.',
    'unique' => 'Het veld :attribute moet uniek zijn.',

    'custom' => [
        'name' => [
            'required' => 'Naam is verplicht.',
        ],
        'meter_id' => [
            'required' => 'Meter ID is verplicht.',
            'unique' => 'Meter ID moet uniek zijn.',
        ],
    ],

    'attributes' => [
        'name' => 'Naam',
        'meter_id' => 'Meter ID',
    ],

];
