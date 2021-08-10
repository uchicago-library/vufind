<?php

return array (
  'vufind' => 
  array (
    'plugin_managers' => 
    array (
      'recordtab' => 
      array (
        'factories' => 
        array (
          'UChicago\\RecordTab\\HoldingsILS' => 'VuFind\\RecordTab\\HoldingsILSFactory',
        ),
        'aliases' => 
        array (
          'VuFind\\RecordTab\\HoldingsILS' => 'UChicago\\RecordTab\\HoldingsILS',
        ),
      ),
    ),
  ),
);