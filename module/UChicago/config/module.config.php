<?php

return [
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'UChicago\\ILS\\Driver\\Folio' => 'VuFind\\ILS\\Driver\\FolioFactory',
                ],
                'aliases' => [
                    'VuFind\\ILS\\Driver\\Folio' => 'UChicago\\ILS\\Driver\\Folio',
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
            'ajaxhandler' => [
                'factories' => [
                    'UChicago\\AjaxHandler\\GetDedupedEholdings' => 'UChicago\\AjaxHandler\\AbstractAjaxHandlerFactory',
                ],
                'aliases' => [
                    'dedupedEholdings' => 'UChicago\\AjaxHandler\\GetDedupedEholdings',
                ],
            ],
            'related' => [
                'factories' => [
                    'UChicago\\Related\\Bookplate' => 'VuFind\\Related\\BookplateFactory',
                ],
                'aliases' => [
                    'VuFind\\Related\\Bookplate' => 'UChicago\\Related\\Bookplate',
                ],
            ],
            'recorddriver' => [
                'factories' => [
                    'UChicago\\RecordDriver\\SolrMarc' => 'VuFind\\RecordDriver\\SolrDefaultFactory',
                ],
                'aliases' => [
                    'VuFind\\RecordDriver\\SolrMarc' => 'UChicago\\RecordDriver\\SolrMarc',
                ],
                'delegators' => [
                    'UChicago\\RecordDriver\\SolrMarc' => [
                        0 => 'VuFind\\RecordDriver\\IlsAwareDelegatorFactory',
                    ],
                ],
            ],
        ],
    ],
];
