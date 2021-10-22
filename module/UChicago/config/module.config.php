<?php

return [
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'UChicago\ILS\Driver\Folio' => 'VuFind\ILS\Driver\FolioFactory',
                ],
                'aliases' => [
                    'VuFind\ILS\Driver\Folio' => 'UChicago\ILS\Driver\Folio',
                ]
            ],
            'recordtab' => [
                'factories' => [
                    'UChicago\\RecordTab\\HoldingsILS' => 'VuFind\\RecordTab\\HoldingsILSFactory',
                ],
                'aliases' => [
                    'VuFind\\RecordTab\\HoldingsILS' => 'UChicago\\RecordTab\\HoldingsILS',
                ],
            ],
        ],
    ],
];
