<?php
$config = [
    'controllers' => [
      'factories' => [
        'VuFindAdmin\Controller\PinController' => 'VuFind\Controller\AbstractBaseFactory',
        'UChicago\Controller\CartController' => 'VuFind\Controller\CartControllerFactory',
        'UChicago\Controller\MyResearchController' => 'VuFind\Controller\AbstractBaseFactory',
        'UChicago\Controller\HoldsController' => 'VuFind\Controller\HoldsControllerFactory',
        /* BEGIN: Only needed until we upgrade to 9.1.1 */
        'UChicago\Controller\CoverController' => 'UChicago\Controller\CoverControllerFactory',
        /* END: Only needed until we upgrade to 9.1.1 */
        /* BEGIN: Only needed until we upgrade to 11 */
        'UChicago\Controller\TurnstileController' => 'UChicago\Controller\TurnstileControllerFactory',
        'UChicago\Controller\CombinedController' => 'VuFind\Controller\AbstractBaseFactory', //Test this one before you remove after upgrade
        /* END: Only needed until we upgrade to 11 */
      ],
      'aliases' => [
        'Pin' => 'VuFindAdmin\Controller\PinController',
        'MyResearch' => 'UChicago\Controller\MyResearchController',
        'myresearch' => 'UChicago\Controller\MyResearchController',
        'Holds' => 'UChicago\Controller\HoldsController',
        'holds' => 'UChicago\Controller\HoldsController',
        'Requests' => 'UChicago\Controller\HoldsController',
        'requests' => 'UChicago\Controller\HoldsController',
        'Cart' => 'UChicago\Controller\CartController',
        'cart' => 'UChicago\Controller\CartController',
        /* BEGIN: Only needed until we upgrade to 9.1.1 */
        'Cover' => 'UChicago\Controller\CoverController',
        'cover' => 'UChicago\Controller\CoverController',
        /* END: Only needed until we upgrade to 9.1.1 */
        /* BEGIN: Only needed until we upgrade to 11 */
        'Turnstile' => 'UChicago\Controller\TurnstileController',
        'turnstile' => 'UChicago\Controller\TurnstileController',
        'Combined' => 'UChicago\Controller\CombinedController', //Test this one before you remove after upgrade
        'combined' => 'UChicago\Controller\CombinedController', //Test this one before you remove after upgrade
        /* END: Only needed until we upgrade to 11 */
      ],
    ],
    'router' => [
      'routes' => [
        'admin' => [
          'child_routes' => [
            'pin' => [
              'type' => 'Laminas\\Router\\Http\\Segment',
              'options' => [
                'route' => '/Pin[/:action]',
                'defaults' => [
                  'controller' => 'Pin',
                  'action' => 'Home',
                ],
              ],
            ],
          ],
        ],
      ],
    ],
    'service_manager' =>
    [
        'allow_override' => true,
        'factories' => [
            'UChicago\Mailer\Mailer' => 'VuFind\Mailer\Factory',
            /* BEGIN: Only needed until we upgrade to 11 */
            'UChicago\RateLimiter\RateLimiterManager' => 'UChicago\RateLimiter\RateLimiterManagerFactory',
            'UChicago\RateLimiter\Turnstile\Turnstile' => 'UChicago\RateLimiter\Turnstile\TurnstileFactory',
            'UChicago\Cache\Manager' => 'UChicago\Cache\ManagerFactory',
            'UChicago\Config\PluginManager' => 'VuFind\Config\PluginManagerFactory',
            /* END: Only needed until we upgrade to 11 */
        ],           
        'aliases' => [
            'UChicago\Mailer' => 'UChicago\Mailer\Mailer',
            /* BEGIN: Only needed until we upgrade to 11 */
            'UChicago\CacheManager' => 'UChicago\Cache\Manager',
            'UChicago\Config' => 'UChicago\Config\PluginManager'
            /* END: Only needed until we upgrade to 11 */
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'auth' => [
                'factories' => [
                    'UChicago\Auth\Shibboleth' => 'VuFind\Auth\ShibbolethFactory',
                ],
                'aliases' => [
                    'shibboleth' => 'UChicago\Auth\Shibboleth',
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'UChicago\\RecordTab\\HoldingsILS' => 'VuFind\\RecordTab\\HoldingsILSFactory',
                    'UChicago\RecordTab\TOCAlt' => 'UChicago\RecordTab\TOCAltFactory',
                ],
                'aliases' => [
                    'VuFind\\RecordTab\\HoldingsILS' => 'UChicago\\RecordTab\\HoldingsILS',
                    'tocalt' => 'UChicago\RecordTab\TOCAlt',
                ],
            ],
            'ils_driver' => [
                'factories' => [
                    'UChicago\\ILS\\Driver\\Folio' => 'VuFind\\ILS\\Driver\\FolioFactory',
                ],
                'aliases' => [
                    'VuFind\\ILS\\Driver\\Folio' => 'UChicago\\ILS\\Driver\\Folio',
                ]
            ],
            'ajaxhandler' => [
                'factories' => [
                    'UChicago\\AjaxHandler\\GetDedupedEholdings' => 'UChicago\\AjaxHandler\\AbstractAjaxHandlerFactory',
                    'UChicago\\AjaxHandler\\GetItemStatuses' => 'VuFind\\AjaxHandler\\GetItemStatusesFactory',
                ],
                'aliases' => [
                    'dedupedEholdings' => 'UChicago\\AjaxHandler\\GetDedupedEholdings',
                    'getItemStatuses' => 'UChicago\\AjaxHandler\\GetItemStatuses',
                ],
            ],
            'related' => [
                'factories' => [
                    'UChicago\\Related\\Bookplate' => 'VuFind\\Related\\BookplateFactory',
                ],
                'aliases' => [
                    'VuFind\\Related\\Bookplate' => 'UChicago\\Related\\Bookplate',
                ],
            ],
            'recorddriver' => [
                'factories' => [
                    'UChicago\\RecordDriver\\SolrMarc' => 'VuFind\\RecordDriver\\SolrDefaultFactory',
                ],
                'aliases' => [
                    'VuFind\\RecordDriver\\SolrMarc' => 'UChicago\\RecordDriver\\SolrMarc',
                ],
                'delegators' => [
                    'UChicago\\RecordDriver\\SolrMarc' => [
                        0 => 'VuFind\\RecordDriver\\IlsAwareDelegatorFactory',
                    ],
                ],
            ],
            'autocomplete' => [
                'factories' => [
                    'UChicago\\Autocomplete\\Solr' => 'VuFind\\Autocomplete\\SolrFactory',
                ],
                'aliases' => [
                    'VuFind\\Autocomplete\\Solr' => 'UChicago\\Autocomplete\\Solr',
                ],
            ],
        ],
    ],
];

$staticRoutes = [
    /* BEGIN: Only needed until we upgrade to 11 */
    /* BUT: keep this [] for use with static routes in the future */
    'Turnstile/Challenge', 'Turnstile/Verify',
    /* END: Only needed until we upgrade to 11 */
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
