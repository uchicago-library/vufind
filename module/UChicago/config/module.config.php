<?php

return array (
  'controllers' => 
  array (
    'factories' => 
    array (
      'VuFindAdmin\Controller\PinController' => 'VuFind\Controller\AbstractBaseFactory',
      'UChicago\Controller\CartController' => 'VuFind\Controller\CartControllerFactory',
      'UChicago\Controller\MyResearchController' => 'VuFind\Controller\AbstractBaseFactory',
      'UChicago\Controller\RecordController' => 'VuFind\Controller\AbstractBaseWithConfigFactory',
      'UChicago\Controller\AjaxController' => 'UChicago\Controller\AjaxControllerFactory',
    ),
    'aliases' => array(
      'AJAX' => 'UChicago\Controller\AjaxController',
      'ajax' => 'UChicago\Controller\AjaxController',
      'Pin' => 'VuFindAdmin\Controller\PinController',
      'MyResearch' => 'UChicago\Controller\MyResearchController',
      'myresearch' => 'UChicago\Controller\MyResearchController',
    ), 
    array (
      'VuFind\\Controller\\AjaxController' => 'UChicago\\Controller\\AjaxController',
    ),
  ),
  'router' => 
  array (
    'routes' => 
    array (
      'admin' => 
      array (
        'child_routes' => 
        array (
          'pin' => 
          array (
            'type' => 'Zend\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/Pin[/:action]',
              'defaults' => 
              array (
                'controller' => 'Pin',
                'action' => 'Home',
              ),
            ),
          ),
        ),
      ),
      'myresearch-storagerequest' => 
      array (
        'type' => 'Zend\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/MyResearch/StorageRequest',
          'defaults' => 
          array (
            'controller' => 'MyResearch',
            'action' => 'StorageRequest',
          ),
        ),
      ),
    ),
  ),
  'service_manager' => 
  array (
    'allow_override' => true,
    'factories' => 
    array (
      'VuFind\\ContentTOCPluginManager' => 'UChicago\\Service\\Factory::getContentTOCPluginManager',
      'VuFind\\Mailer' => 'UChicago\\Mailer\\Factory',
      'UChicago\\AjaxHandler\\PluginManager' => 'VuFind\\ServiceManager\\AbstractPluginManagerFactory',
    ),
  ),
  'vufind' => 
  array (
    'plugin_managers' =>
     array (
      'ajaxhandler' =>
      array (
        'aliases' =>
        array (
          'dedupedEholdings' => 'UChicago\AjaxHandler\GetDedupedEholdings',
          'mapLink' => 'UChicago\AjaxHandler\GetMapLink',
          'getItemStatuses' => 'UChicago\AjaxHandler\GetItemStatuses',
        ),
        'factories' =>
        array (
          'UChicago\AjaxHandler\GetDedupedEholdings' => 'UChicago\AjaxHandler\AbstractAjaxHandlerFactory',
          'UChicago\AjaxHandler\GetMapLink' => 'UChicago\AjaxHandler\AbstractAjaxHandlerFactory',
          'UChicago\AjaxHandler\GetItemStatuses' => 'VuFind\AjaxHandler\GetItemStatusesFactory',
        ),
      ),
      'content' => 
      array (
        'factories' => 
        array (
          'toc' => 'UChicago\\Content\\Factory::getTOC',
        ),
      ),
      'content_toc' => 
      array (
        'factories' => 
        array (
          'syndetics' => 'UChicago\\Content\\TOC\\Factory::getSyndetics',
        ),
      ),
      'recorddriver' => 
      array (
        'factories' => 
        array (
          'UChicago\RecordDriver\SolrMarcPhoenix' => 'UChicago\RecordDriver\Factory::getSolrMarc',
          'UChicago\RecordDriver\SolrMarcPhoenix' => 'UChicago\RecordDriver\Factory::getSolrSfx',
          'UChicago\RecordDriver\SolrMarcPhoenix' => 'UChicago\RecordDriver\Factory::getSolrHathi',
        ),
        'delegators' => 
        array (
          'UChicago\\RecordDriver\\SolrMarcPhoenix' => 
          array (
            0 => 'VuFind\\RecordDriver\\IlsAwareDelegatorFactory',
          ),
        ),
        'aliases' => 
        array (
          'solrmarc' => 'UChicago\\RecordDriver\\SolrMarcPhoenix',
          'solrsfx' => 'UChicago\\RecordDriver\\SolrMarcPhoenix',
          'solrhathi' => 'UChicago\\RecordDriver\\SolrMarcPhoenix',
        ),
      ),
      'recordtab' => 
      array (
        'factories' => 
        array (
          'UChicago\\RecordTab\\HoldingsILS' => 'UChicago\\RecordTab\\Factory::getHoldingsILS',
          'chicagotoc' => 'UChicago\\RecordTab\\Factory::getTOC',
        ),
      ),
      'search_results' => 
      array (
        'abstract_factories' => 
        array (
          0 => 'VuFind\\Search\\Results\\PluginFactory',
        ),
        'factories' => 
        array (
          'solr' => 
          array (
            0 => 'VuFind\\Search\\Results\\Factory',
            1 => 'getSolr',
          ),
        ),
      ),
    ),
    'recorddriver_tabs' => 
    array (
      'UChicago\\RecordDriver\\SolrMarcPhoenix' => 
      array (
        'tabs' => 
        array (
          'Holdings' => 'UChicago\\RecordTab\\HoldingsILS',
          'Description' => NULL,
          'TOC' => 'ChicagoTOC',
          'UserComments' => 'UserComments',
          'Reviews' => 'Reviews',
          'Excerpt' => 'Excerpt',
          'Preview' => 'preview',
          'HierarchyTree' => 'HierarchyTree',
          'Map' => 'Map',
          'Similar' => NULL,
          'Details' => 'StaffViewMARC',
        ),
        'defaultTab' => NULL,
      ),
    ),
  ),
);
