<?php
return [
    'extends' => 'bootstrap3',
    'js' => [
        'phoenix.js',
    ],
    'favicon' => 'favicon.ico',
    'helpers' => [
        'factories' => [
            'UChicago\View\Helper\Phoenix\Citation' => 'VuFind\View\Helper\Root\CitationFactory',
            'UChicago\View\Helper\Phoenix\ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinksFactory',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'UChicago\View\Helper\Phoenix\RecordDataFormatterFactory',
            'UChicago\View\Helper\Phoenix\RecordLinker' => 'VuFind\View\Helper\Root\RecordLinkerFactory',
            'UChicago\View\Helper\Phoenix\ZoteroHarvesting' => 'UChicago\View\Helper\Phoenix\ZoteroHarvestingFactory',
        ],
        'aliases' => [
            'citation' => 'UChicago\View\Helper\Phoenix\Citation',
            'ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinks',
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'recordLinker' => 'UChicago\View\Helper\Phoenix\RecordLinker',
            'ZoteroHarvesting' => 'UChicago\View\Helper\Phoenix\ZoteroHarvesting',
        ],
    ],
];
