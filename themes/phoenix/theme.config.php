<?php
return [
    'extends' => 'bootstrap3',
    'js' => [
        'phoenix.js',
    ],
    'helpers' => [
        'factories' => [
            'UChicago\View\Helper\Phoenix\ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinksFactory',
        ],
        'aliases' => [
            'ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinks',
        ],
    ],
];
