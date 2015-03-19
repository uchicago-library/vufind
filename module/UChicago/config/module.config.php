<?php
namespace UChicago\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            'record' => 'UChicago\Controller\Factory::getRecordController',
        ),
        'invokables' => array(
            'adminpin' => 'VuFindAdmin\Controller\PinController',
            'ajax' => 'UChicago\Controller\AjaxController',
            'cart' => 'UChicago\Controller\CartController',
            'feedback' => 'UChicago\Controller\FeedbackController',
            'my-research' => 'UChicago\Controller\MyResearchController',
            'search' => 'UChicago\Controller\SearchController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'admin' => array(
                'child_routes' => array(
                    'pin' => array(
                        'type' => 'Zend\Mvc\Router\Http\Segment',
                        'options' => array(
                            'route'    => '/Pin[/:action]',
                            'defaults' => array(
                                'controller' => 'AdminPin',
                                'action'     => 'Home',
                            )
                        )
                    ),
                ),
            ),
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
                    'solrmarc' => 'UChicago\RecordDriver\Factory::getSolrMarc',
                    'solrsfx' => 'UChicago\RecordDriver\Factory::getSolrSfx',
                    'solrhathi' => 'UChicago\RecordDriver\Factory::getSolrHathi',
                ),
            ),//recorddriver
            'recordtab' => array(
                'factories' => array(
                    'holdingsils' => 'UChicago\RecordTab\Factory::getHoldingsILS', 
                ),  
            ),//recordtab
        ),//plugin_managers
        // This section controls which tabs are used for which record driver classes.
        // Each sub-array is a map from a tab name (as used in a record URL) to a tab
        // service (found in recordtab_plugin_manager, below).  If a particular record
        // driver is not defined here, it will inherit configuration from a configured
        // parent class.  The defaultTab setting may be used to specify the default
        // active tab; if null, the value from the relevant .ini file will be used.
        'recorddriver_tabs' => array(
            'VuFind\RecordDriver\SolrMarc' => array(
                'tabs' => array(
                    'Holdings' => 'HoldingsILS', 
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Similar' => null,
                    'Details' => 'StaffViewMARC',
                ),
                'defaultTab' => null,
            ),

        ),//recorddriver_tabs
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
