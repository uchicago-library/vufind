<?php
namespace UChicago\Module\Configuration;

$config = array(
    'controllers' => array(
        'invokables' => array(
            'feedback' => 'UChicago\Controller\FeedbackController',
        ),
    ),
);


$staticRoutes = array(
    'Feedback/KnowledgeTracker',
    'Feedback/KnowledgeTrackerForm',
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
