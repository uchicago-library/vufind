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
            'FeedbackLink' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\FeedbackLink();
            },
            'knowledgeTracker' => '\UChicago\View\Helper\Phoenix\Factory::getKnowledgeTracker',
            'HathiLink' => function ($sm) {
                return new \UChicago\View\Helper\Phoenix\HathiLink();
            },
            'ServiceLinks' => function ($sm) {
                $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                $config = !isset($config->ServiceLinks) ? false : $config->ServiceLinks;
                return new \UChicago\View\Helper\Phoenix\ServiceLinks($config);
            },
        )    
    ),
);
