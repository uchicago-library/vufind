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
        $configArray = [];
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

    /**
     * Gets the current search context alphabrowse, basic-keyword, or advanced-keyword based on 
     * what's found (or not found) in the keyword_or_begins_with and basic_or_advanced cookies.
     *
     * @returns string, "alphabrowse", "basic-keyword", or "advanced-keyword".
     */
    public function getCurrentContext() {
        /*Get the cookies if they exist*/
        $searchType = isset($_COOKIE['keyword_or_begins_with']) ? $_COOKIE['keyword_or_begins_with'] : null;
        $keywordType = isset($_COOKIE['basic_or_advanced']) ? $_COOKIE['basic_or_advanced'] : null;
        
        /*Build a data structure*/
        $contextTypes = ['searchType' => $searchType, 
                              'keywordType' => $keywordType];

        /*Vocabulary mappings*/
        $lookup = ['begins with' => 'alphabrowse',
                   'basic' => 'basic-keyword',
                   'advanced' => 'advanced-keyword'];

        /*Build the proper return string based on search context*/
        switch ($contextTypes) {
            case ($contextTypes['searchType'] == 'begins with'):
                /*Alphabrowse*/
                $retval = $lookup[$contextTypes['searchType']];
                break;
            case ($contextTypes['searchType'] == 'keyword' && $contextTypes['keywordType'] == 'basic') :
                /*Basic Keyword*/
                $retval = $lookup[$contextTypes['keywordType']];
                break;
            case ($contextTypes['searchType'] == 'keyword' && $contextTypes['keywordType'] == 'advanced') :
                /*Advanced Keyword*/
                $retval = $lookup[$contextTypes['keywordType']];
                break;
            default:
                /*Default*/
                $retval = $lookup[$contextTypes['searchType']];
        }

        return $retval;
    }

    /**
     * Gets the initial searchbox display preference to help choose
     * which searchbox to display by default.
     *
     * @param string $boxType, "browse" or "keyword".
     * @param string $templateDir, the template directory of the current constructed view.
     * @param string $templateName, the name of the current constuctec view.
     *
     * @returns string, "off" or "on".
     */
    public function getInitialSearchboxPref($boxType, $templateDir=false, $templateName=false) {
        /*Get the cookies if they exist*/
        $searchType = isset($_COOKIE['keyword_or_begins_with']) ? $_COOKIE['keyword_or_begins_with'] : null;
        $keywordType = isset($_COOKIE['basic_or_advanced']) ? $_COOKIE['basic_or_advanced'] : null;
        
        /*Build the view descriptions*/
        $templateDesc = !empty($templateDir) && !empty($templateName) ? $templateDir . '-' . $templateName : null;

        /*Build a data structure*/
        $contextTypes = ['searchType' => $searchType, 
                         'keywordType' => $keywordType];

        /*Build the proper return string based on search context*/
        switch ($contextTypes) {
            /*The searchbox is an alphabrowse box and the preference is browse*/
            case ($boxType == 'browse' && $contextTypes['searchType'] == 'begins with'):
                $retval = 'on';
                break;
            /*The searchbox is a keyword box and the preference is keyword*/
            case ($boxType == 'keyword' && $contextTypes['searchType'] == 'keyword') :
                $retval = 'on';
                break;
            /*The searchbox is a keyword box and no preference is set*/
            case ($boxType == 'keyword' && $contextTypes['searchType'] == null) :
                $retval = 'on';
                break;
            default:
                $retval = 'off'; 
        }

        return $retval;


    }
}
