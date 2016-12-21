<?php
return array(
    'extends' => 'bootstrap3',
    'less' => array(
        'active' => false
    ),
    'css' => array(
        '//identity.uchicago.edu/c/fonts/proximanova.css:all',
    ),
    'js' => array(
        'jquery.cookie.js',
        'phoenix.js',
        'ga.js'
    ),
    'favicon' => 'favicon.ico',
    'helpers' => array(
        'factories' => array(
            'Alert' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $alertLabel = !isset($config->Alert->label)
                    ? false : $config->Alert->label;
                $alertMsg = !isset($config->Alert->paragraph)
                    ? false : $config->Alert->paragraph;
                $alertHtml = !isset($config->Alert->html)
                    ? false : $config->Alert->html;
                return new \UChicago\View\Helper\Phoenix\Alert($alertLabel, $alertMsg, $alertHtml);
            },
            'BookPlates' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\BookPlates();
            },
            'BootstrapAlert' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\BootstrapAlert();
            },
            'citation' => function ($sm) {
                $converter = new \VuFind\Date\Converter();
                return new \UChicago\View\Helper\Phoenix\Citation($converter);
            },
            'Eholdings' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\Eholdings();
            },
            'GetConfig' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                return new \UChicago\View\Helper\Phoenix\GetConfig($config);
            },
            'HathiLink' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\HathiLink();
            },
            'KnowledgeTracker' => '\UChicago\View\Helper\Phoenix\Factory::getKnowledgeTracker',
            'MarcFields' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $siteUrl = !isset($config->Site->url)
                    ? false : $config->Site->url;
                return new \UChicago\View\Helper\Phoenix\MarcFields($siteUrl);
            },
            'SearchContext' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $config = !isset($config->SearchContext) ? false : $config->SearchContext;
                return new \UChicago\View\Helper\Phoenix\SearchContext($config);
            },
            'ServiceLinks' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $config = !isset($config->ServiceLinks) ? false : $config->ServiceLinks;
                return new \UChicago\View\Helper\Phoenix\ServiceLinks($config);
            },
        ),
        'invokables' => array(
            'ViewToggle' => 'UChicago\View\Helper\Phoenix\ViewToggle',
        )    
    ),
);
