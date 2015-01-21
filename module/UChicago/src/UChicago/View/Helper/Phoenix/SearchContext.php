<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class that finds the search context for 
 * display purposes in the view.  
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class SearchContext extends AbstractHelper
{

    /**
     * Configurations from the ini file. These are the 
     * solr core field / display name  mappings to show.
     */
    protected $contextConfig;

    /**
     * Constructor, adds configurations.
     */
    public function __construct($config) {
        $configArray = array();
        foreach ($config as $key => $val) {
           $configArray[$key] = $val; 
        }
        $this->contextConfig = $configArray;
    }

    /**
     * Method displayes a friendly display name for the 
     * user's given search context based on the current
     * solr core.  
     *
     * @return a string version of the current search context
     * or an empty string if nothing is found.
     */
    public function getDisplay() {

        /*Dictionary of friendly display names and 
        solr core mappings from the .ini file.*/
        $lookup = $this->contextConfig;

        # The solr used to render the search.
        $solrCore = $this->view->params->getsearchClassId(); 

        return (array_key_exists($solrCore, $lookup) ? $lookup[$solrCore] : '');
    }

    /**
     * Method for seeing if the current search
     * is a website search.
     * 
     * returns boolean
     */
    public function isWebsiteSearch() {
        return $this->view->params->getsearchClassId() == "SolrWeb";
    }
}
