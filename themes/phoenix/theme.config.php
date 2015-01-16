<?php
return array(
    'extends' => 'bootstrap3',
    'less' => array(
        'active' => false
    ),
    'css' => array(
        '//identity.uchicago.edu/c/fonts/proximanova.css:all',
    ),
    'helpers' => array(
        'factories' => array(
            'BookPlates' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\BookPlates();
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
            'ServiceLinks' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $config = !isset($config->ServiceLinks) ? false : $config->ServiceLinks;
                return new \UChicago\View\Helper\Phoenix\ServiceLinks($config);
            },
        )    
    ),
);
