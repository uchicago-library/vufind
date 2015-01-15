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
            'feedback' => 'UChicago\Controller\FeedbackController',
            'my-research' => 'UChicago\Controller\MyResearchController',
        ),
    ),
    'service_manager' => array(
        'allow_override' => true,
        'factories' => array(
            'VuFind\Mailer' => 'UChicago\Mailer\Factory',
        ),
    ),//service_manager
    'vufind' => array(
        'plugin_managers' => array(
            'recorddriver' => array(
                'factories' => array(
                    'solrmarc' => function ($sm) {
                        $driver = new \UChicago\RecordDriver\SolrMarcPhoenix(
                            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                            null,
                            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                        );
                        $driver->attachILS(
                            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                        );
                        return $driver;
                    },
                    'SolrSfx' => function ($sm) {
                        $driver = new \UChicago\RecordDriver\SolrMarcPhoenix(
                            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                            null,
                            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                        );
                        return $driver;
                    },
                    'SolrHathi' => function ($sm) {
                        $driver = new \UChicago\RecordDriver\SolrMarcPhoenix(
                            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                            null,
                            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                        );
                        return $driver;
                    },
                ),
            ),//recorddriver
            'recordtab' => array(
                'factories' => array(
                    'holdingsils' => function ($sm) {
                        // If VuFind is configured to suppress the
                        // holdings tab when the
                        // ILS driver specifies no holdings, we need to
                        // pass in a connection
                        // object:
                        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
                        if (isset($config->Site->hideHoldingsTabWhenEmpty)
                            && $config->Site->hideHoldingsTabWhenEmpty
                        ) { 
                            $catalog = $sm->getServiceLocator()->get('VuFind\ILSConnection');
                        } else {
                            $catalog = false;
                        }   
                        return new \UChicago\RecordTab\HoldingsILS($catalog);
                    },  
                ),  
            ),//recordtab
            'resolver_driver' => array(
                'factories' => array(
                    'sfx' => function ($sm) {
                        return new \UChicago\Resolver\Driver\Sfx(
                            $sm->getServiceLocator()->get('VuFind\Config')->get('config')->OpenURL->url,
                            $sm->getServiceLocator()->get('VuFind\Http')
                                ->createClient()
                        );
                    },
                ),
            ),//resolver_driver
        ),
    ),
);


// Define static routes -- Controller/Action strings
$staticRoutes = array(
    'Feedback/KnowledgeTracker',
    'Feedback/KnowledgeTrackerForm',
    'MyResearch/StorageRequest',
);
    
// Build static routes
foreach ($staticRoutes as $route) {
    list($controller, $action) = explode('/', $route);
    $routeName = str_replace('/', '-', strtolower($route));
    $config['router']['routes'][$routeName] = array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/' . $route,
            'defaults' => array(
                'controller' => $controller,
                'action'     => $action,
            )
        )
    );
}

return $config;
