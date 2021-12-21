<?php
return [
    'extends' => 'bootstrap3',
    'js' => [
        'phoenix.js',
    ],
    'helpers' => [
        'factories' => [
            'UChicago\View\Helper\Phoenix\ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinksFactory',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'UChicago\View\Helper\Phoenix\RecordDataFormatterFactory',
        ],
        'aliases' => [
            'ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinks',
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
        ],
    ],
];
