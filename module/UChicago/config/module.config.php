<?php
return [
    'controllers' => [
      'factories' => [
        'VuFindAdmin\Controller\PinController' => 'VuFind\Controller\AbstractBaseFactory',
        'UChicago\Controller\CartController' => 'VuFind\Controller\CartControllerFactory',
        'UChicago\Controller\MyResearchController' => 'VuFind\Controller\AbstractBaseFactory',
        'UChicago\Controller\HoldsController' => 'VuFind\Controller\HoldsControllerFactory',
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
      ],
    ],
    'router' => [
      'routes' => [
        'admin' => [
          'child_routes' => [
            'pin' => [
              'type' => 'Zend\\Router\\Http\\Segment',
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
        ],           
        'aliases' => [
            'UChicago\Mailer' => 'UChicago\Mailer\Mailer',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'UChicago\\ILS\\Driver\\Folio' => 'VuFind\\ILS\\Driver\\FolioFactory',
                ],
                'aliases' => [
                    'VuFind\\ILS\\Driver\\Folio' => 'UChicago\\ILS\\Driver\\Folio',
                ]
            ],
            'recordtab' => [
                'factories' => [
                    'UChicago\\RecordTab\\HoldingsILS' => 'VuFind\\RecordTab\\HoldingsILSFactory',
                ],
                'aliases' => [
                    'VuFind\\RecordTab\\HoldingsILS' => 'UChicago\\RecordTab\\HoldingsILS',
                ],
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
