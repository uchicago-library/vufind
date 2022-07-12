<?php
return [
    'extends' => 'bootstrap3',
    'js' => [
        'phoenix.js',
    ],
    'favicon' => 'favicon.ico',
    'helpers' => [
        'factories' => [
            'UChicago\View\Helper\Phoenix\ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinksFactory',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'UChicago\View\Helper\Phoenix\RecordDataFormatterFactory',
            'UChicago\View\Helper\Phoenix\RecordLink' => 'VuFind\View\Helper\Root\RecordLinkFactory',
            'UChicago\View\Helper\Phoenix\ZoteroHarvesting' => 'UChicago\View\Helper\Phoenix\ZoteroHarvestingFactory',
        ],
        'aliases' => [
            'ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinks',
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'recordLink' => 'UChicago\View\Helper\Phoenix\RecordLink',
            'ZoteroHarvesting' => 'UChicago\View\Helper\Phoenix\ZoteroHarvesting',
        ],
    ],
];
