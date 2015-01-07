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
        )    
    ),
);
