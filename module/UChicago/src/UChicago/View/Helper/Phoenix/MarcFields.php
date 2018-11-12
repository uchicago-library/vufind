<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * A class that displays marc fields in various parts of the templates using getFormattedMarcDetails  
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class MarcFields extends \Zend\View\Helper\AbstractHelper
{

    /**
     * Constructor
     *
     * @param string $siteUrl virtual location of the record
     *
     * @return array
     */
    public function __construct($siteUrl)
    {
        $this->siteUrl = $siteUrl;
        $this->id = null;
        $this->recordLink = null;
    }

    /**
     * Set the bib number if you need to.
     * Used on results page. 
     *
     * @param string $id, the bib number
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Set the record link if you need to.
     * Used on results page. 
     *
     * @param string $recordLink, a link to the record
     */
    public function setRecordLink($recordLink) {
        $this->recordLink = $recordLink;
    }

    /**
     * Get the record link if you need to.
     * Used on results page. 
     */
    public function getRecordLink() {
        return $this->recordLink;
    }

    /**
     * Get the bib number if you need it.
     * Used on the results page.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Support method brings the siteUrl from the config files (through template.config.php)
     *
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->siteUrl;
    }

    /**
     * Method looks for array keys matching a specific pattern
     *
     * @param string regex pattern to match
     * @param array to search
     *
     * @return the matching array key
     */
    protected function findKey($pattern, $array) {
        $keys = array_keys($array);
        $foundKey = preg_grep($pattern,$keys);
        return implode($foundKey);
    }

    /**
     * Method generates an alphabrowse link with the subfield e
     * data stripped out. This is used in the author template 
     * on the full record page.
     *
     * @param array $subfieldData
     * 
     * @return a string of subfield data with the subfield e stripped out
     */
    public function getAuthorBrowseLink($subfieldData) {
        $string = '';
        foreach($subfieldData as $key => $val) {
            if (substr((string)$key, 0, 1) != 'e') {
                $string .= $val . ' ';
            }
        }
        return $string;
    }

    /**
     * Method adds proper dashes to 6XX fields (Topic / Genre)
     * Outputs spaces for pretty display, no spaces if the link boolean is true
     * Used in topics.phtml
     *
     * @param $data array: the data to massage
     * @param $link book: is it a link true or false
     */
    public function topicFormatter($data, $link) {
        foreach($data['subfieldData'] as $sdKey => $sd) {
            /*Formatted for links (no spaces)*/
            if ($link == 1) {
                if (($sdKey[0] == 'v') || ($sdKey[0] == 'x') || ($sdKey[0] == 'y') || ($sdKey[0] == 'z')) {
                    /*TEMPORARY FIX: spaces added to each side of hyphen. 
                    REMOVE spaces later - brad 09/05/2013*/
                    echo urlencode(' -- ' . $sd); 
                }
                else{
                    echo $sd;
                }
            }
            /*Formatted for pretty display (with spaces)*/
            else {
                if (($sdKey[0] == 'v') || ($sdKey[0] == 'x') || ($sdKey[0] == 'y') || ($sdKey[0] == 'z')) {
                    echo ' -- ' . $sd; 
                }
                else {
                    echo $sd . ' ';
                } 
            }
        } 
    }


    /**
     * Analyzes MARC data for each selected grouping and decides weather 
     * the information is displayable. This is only being used for 561 fields
     * however it could be expanded to include other fields.
     *
     * @param array $data MARC info
     *
     * @return boolean 
     */
    private function displayable($data) {
        $flag = true;
        foreach($data as $d) {
            if (isset($d['currentField']) && $d['currentField'] == 561) {
                if ($d['indicator1'] == "0") {
                    $flag = false;
                }
                else {
                    return true;
                }
            }
        }
        return $flag;
    }

    /**
     * Main public function for returning marc data based on the part of the templates where executed
     * Uses all of the above support methods  
     *
     * @param string $pos informs the method of location in templates
     *
     * renders a default or unique inner template and displays marc data 
     * renders an outer template with additional data not included in the marcFieldsArray
     * logs a message to the php error log if the $pos parameter isn't set
     */
    public function returnMarcData($pos)
    {
        $hasSeries = false;
        $rawMarcData = $this->view->driver->crosswalk($pos);
       
        /*Display a message if nothing is found*/ 
        if (empty($rawMarcData)) {
            echo '<tr><td>' . $this->view->transEsc('no_description') . '</td></tr>';
        }

        foreach($rawMarcData as $mkey => $marcData) {

            if ($pos == 'top' || $pos == 'top-hidden') {
 
                /*Title: get special template*/
                $isTitle = false;
                if (isset($marcData[0]['currentField'])) {
                    $isTitle = $marcData[0]['currentField'] == 245;
                }
                if (array_key_exists(0, $marcData) && $isTitle) {
                    include('themes/phoenix/templates/Helpers/MarcFields/245.phtml');
                }
                /*Get everything else*/
                else {
                    if ($this->displayable($marcData) == true) {
                        echo '<tr>';
                        echo '<th>';
                            /*Print the html label*/
                            foreach($marcData as $l){
                                $label = '';
                                if (isset($l['label'])) {
                                    $label = $l['label'];
                                }
                                if (!empty($label)) {
                                    echo $label;
                                    break;
                                }
                            }
                        echo '</th>';
                        echo '<td>';
                    }
                        foreach($marcData as $data){
                            $currentField = null;
                            if (isset($data['currentField'])) {
                                $currentField = $data['currentField'];
                            }
                            /*246: get special template*/
                            if (($currentField == 246)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/246.phtml');
                            }
                            /*780: get special template*/
                            elseif (($currentField == 780)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/780.phtml');
                            }
                            /*785: get special template*/
                            elseif (($currentField == 785)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/785.phtml');
                            }
                            /*521: get special template*/
                            elseif (($currentField == 521)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/521.phtml');
                            }
                            /*526: get special template*/
                            elseif (($currentField == 526)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/526.phtml');
                            }
                            /*541, 561, 583: get special template*/
                            elseif (($currentField == 541) || ($currentField == 561) || ($currentField == 583)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/541.phtml');
                            }
                            /*555: get special template*/
                            elseif (($currentField == 555)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/555.phtml');
                            }
                            /*565: get special template*/
                            elseif (($currentField == 565)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/565.phtml');
                            }
                            /*130, 730, 793 (Uniform Title): get special template*/
                            elseif (($currentField == 130) || ($currentField == 730) || ($currentField == 793)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/uniformTitle.phtml');
                            }
                            /*440, 490, 800, 810, 811, 830 (Series): get special template*/
                            elseif (($currentField == 440) || ($currentField == 490) || ($currentField == 800) || 
                                    ($currentField == 810) || ($currentField == 811) || ($currentField == 830)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/series.phtml');
                            }
                            /*100, 110, 111 (Author), 700, 710, 711, 790, 791, 
                            792 (Other authors/contributors - Other uniform titles together): get special template*/
                            elseif (($currentField == 100) || ($currentField == 110) || ($currentField == 111) || ($currentField == 700) || 
                                    ($currentField == 710) || ($currentField == 711) || ($currentField == 790) || ($currentField == 791)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/author.phtml');
                            }
                            /*240, 243 (Uniform Title)*/
                            elseif (($currentField == 240) || ($currentField == 243)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/uniformTitle.phtml');
                            }
                            /*600, 610, 611, 630, 650, 651, 654, 655 (Subject headings, Topics): get special template*/
                            elseif (($currentField == 600) || ($currentField == 610) || ($currentField == 611) || ($currentField == 630) || 
                                    ($currentField == 650) || ($currentField == 651) || ($currentField == 654) || ($currentField == 655)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/topics.phtml');
                            }
                            /*382 (Instrumentation): get special template*/
                            elseif (($currentField == 382)) {
                                include('themes/phoenix/templates/Helpers/MarcFields/382.phtml');
                            }
                            /*Default: get the default template*/
                            else {
                                include('themes/phoenix/templates/Helpers/MarcFields/default.phtml');
                            }
                        }
                    echo '</td>';
                    echo '</tr>';
                } 
            }
            elseif ($pos == 'atlas') {
                include('themes/phoenix/templates/Helpers/MarcFields/atlasDefault.phtml');
            }
            elseif ($pos == 'results') {
                if (isset($marcData[0]['currentField']) && $marcData[0]['currentField'] == 245) {
                     include('themes/phoenix/templates/Helpers/MarcFields/resultsTitle.phtml');               
                }
                else { 
                    foreach($marcData as $data) {
                        if (!isset($data['currentField'])) {
                            break;
                        }
                        /*100, 110, 111 (Author), 700, 710, 711, 790, 791, 
                        792 (Other authors/contributors - Other uniform titles together): get special template*/
                        if (($data['currentField'] == 100) || ($data['currentField'] == 110) || ($data['currentField'] == 111) || ($data['currentField'] == 700) || 
                                ($data['currentField'] == 710) || ($data['currentField'] == 711) || ($data['currentField'] == 790) || ($data['currentField'] == 791)) {
                            include('themes/phoenix/templates/Helpers/MarcFields/resultsAuthor.phtml');
                        }
                        elseif (($data['currentField'] == 440) || ($data['currentField'] == 490) || ($data['currentField'] == 830) 
                            || ($data['currentField'] == 800) || ($data['currentField'] == 810)) {
                            if ($hasSeries == false) {
                                include('themes/phoenix/templates/Helpers/MarcFields/resultsDefault.phtml');
                            }
                            $hasSeries = true;
                        }
                        else {
                            include('themes/phoenix/templates/Helpers/MarcFields/resultsDefault.phtml');
                        }   
                    }
                }
            }
        }
    }
}
