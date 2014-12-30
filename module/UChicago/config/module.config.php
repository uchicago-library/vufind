<?php
namespace UChicago\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            'record' => function ($sm) {
                return new \UChicago\Controller\RecordController(
                    $sm->getServiceLocator()->get('VuFind\Config')->get('config')
                );
            },
        ),
        'invokables' => array(
            'my-research' => 'UChicago\Controller\MyResearchController',
        ),
    ),
);

return $config;


