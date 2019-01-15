<?php
return [
    'extends' => 'bootstrap3',
    'less' => [
        'active' => false
    ],
    'css' => [
        '//identity.uchicago.edu/c/fonts/proximanova.css:all',
    ],
    'js' => [
        'jquery.cookie.js',
        'phoenix.js',
        'ga.js'
    ],
    'favicon' => 'favicon.ico',
    'helpers' => [
        'factories' => [
            'UChicago\View\Helper\Phoenix\Alert' => 'UChicago\View\Helper\Phoenix\AlertFactory',
            'UChicago\View\Helper\Phoenix\BookPlates' => 'UChicago\View\Helper\Phoenix\BookPlatesFactory',
            'UChicago\View\Helper\Phoenix\BootstrapAlert' => 'UChicago\View\Helper\Phoenix\BootstrapAlertFactory',
            'UChicago\View\Helper\Phoenix\Citation' => 'UChicago\View\Helper\Phoenix\CitationFactory',
            'UChicago\View\Helper\Phoenix\Eholdings' => 'UChicago\View\Helper\Phoenix\EholdingsFactory',
            'UChicago\View\Helper\Phoenix\GetConfig' => 'UChicago\View\Helper\Phoenix\GetConfigFactory',
            'UChicago\View\Helper\Phoenix\HathiLink' => 'UChicago\View\Helper\Phoenix\HathiLinkFactory',
            'UChicago\View\Helper\Phoenix\KnowledgeTracker' => '\UChicago\View\Helper\Phoenix\KnowledgeTrackerFactory',
            'UChicago\View\Helper\Phoenix\MarcFields' => 'UChicago\View\Helper\Phoenix\MarcFieldsFactory',
            'UChicago\View\Helper\Phoenix\SearchContext' => 'UChicago\View\Helper\Phoenix\SearchContextFactory',
            'UChicago\View\Helper\Phoenix\ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinksFactory',
            'UChicago\View\Helper\Phoenix\RecordLink' => 'VuFind\View\Helper\Root\RecordLinkFactory',
        ],
        'aliases' => [
            'Alert' => 'UChicago\View\Helper\Phoenix\Alert',
            'BookPlates' => 'UChicago\View\Helper\Phoenix\BookPlates',
            'BootstrapAlert' => 'UChicago\View\Helper\Phoenix\BootstrapAlert',
            'Citation' => 'UChicago\View\Helper\Phoenix\Citation', 
            'Eholdings' => 'UChicago\View\Helper\Phoenix\Eholdings',
            'GetConfig' => 'UChicago\View\Helper\Phoenix\GetConfig',
            'HathiLink' => 'UChicago\View\Helper\Phoenix\HathiLink',
            'KnowledgeTracker' => 'UChicago\View\Helper\Phoenix\KnowledgeTracker',
            'MarcFields' => 'UChicago\View\Helper\Phoenix\MarcFields',
            'recordLink' => 'UChicago\View\Helper\Phoenix\RecordLink',
            'SearchContext' => 'UChicago\View\Helper\Phoenix\SearchContext',
            'ServiceLinks' => 'UChicago\View\Helper\Phoenix\ServiceLinks',
        ],
        'invokables' => [
            'jqueryValidation' => 'VuFind\View\Helper\Root\JqueryValidation',
            'ViewToggle' => 'UChicago\View\Helper\Phoenix\ViewToggle',
        ]    
    ],
];
