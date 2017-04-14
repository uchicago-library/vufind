<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class that adds e-resource links to the full record and results views 
 * for records with e-holdings.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class Eholdings extends AbstractHelper
{

    /**
     * Sort subfield data from 908 fields for eholdings.
     * This method assumes that order is respected for
     * repeatable fields that matter.
     * 
     * @param $array, subfield data
     * 
     * @returns associative array of data sorted by subfield
     */
    public function sortEholdingSubfields($array) {
        $data = ['u' => [], 'z' => [], 'p' => [], 'c' => [], 'a' => []];
        foreach ($array as $key => $value) {
            if (strpos($key, 'u') === 0) {
                array_push($data['u'], $value);
            }
            else if (strpos($key, 'z') === 0) {
                array_push($data['z'], $value);
            }
            else if (strpos($key, 'p') === 0) {
                array_push($data['p'], $value);
            }
            else if (strpos($key, 'c') === 0) {
                array_push($data['c'], $value);
            }
            else if (strpos($key, 'a') === 0) {
                array_push($data['a'], $value);
            }
        }
        return $data;
    }

    /**
     * Gets e-holding links and associated data 
     *
     * @rerutns array
     */
    public function getEholdingLinks() {
        $links = []; 
        $data = $this->view->driver->getEholdingsFromMarc();
        if ($data === null) {
            return $links;
        }


        $link = [];
        $i = 0;
        foreach ($data['urls'] as $urlData) {

            $filter = 'www.';
            $subfields = $urlData['subfieldData'];

            /* Sort repeatable subfields into clusters */
            $sortedSubfields = $this->sortEholdingSubfields($subfields);

            /* Loop over subfield u data and build links for display */
            foreach($sortedSubfields['u'] as $url) {
                
                $link[$i]['url'] = $url;
                
                /* Build human readable text for the link as a fallback */
                $descriptionArray = parse_url($url);
                $host = $descriptionArray['host'];
                $hasFilter = (substr($host, 0, 4) == $filter);
                $description = $hasFilter ? substr($host, strlen($filter)) : $host;

                /* Choose link text */
                if (empty($sortedSubfields['z'])) {
                    $link[$i]['desc'] = !empty($sortedSubfields['p']) ? array_shift($sortedSubfields['p']) : $description;
                }
                else {
                    $link[$i]['desc'] = !empty($sortedSubfields['p']) ?  array_shift($sortedSubfields['p']) : '';
                }

                /* Get the link type, callnumber and display text*/
                $link[$i]['type'] = !empty($sortedSubfields['c']) ? $sortedSubfields['c'][0] : null; // <--- Don't use array_shift because this is holdings level?
                $link[$i]['callnumber'] = !empty($sortedSubfields['a'])? array_shift($sortedSubfields['a']) : null;
                $link[$i]['text'] = !empty($sortedSubfields['z']) ? array_shift($sortedSubfields['z']) : null;

                $links = $link;
                $i++;
            }

        }
        return $links;
    }


    /**
     * Returns a friendly display label for an e-holdings link.
     *
     * @param string $type, 'FullText' or 'Related', the type of resource for which to display text.
     *
     * @returns string
     */
    public function getEholdingsLinkText($type) {
        $lookup = ['FullText' => 'Full text online', 
                   'Related' => 'Related text online'];
        /* After PHP 5.4.8 use something like this:
        assert(in_array($type, array_keys($lookup)), 'The e-holding type is not valid.'); */
        assert(in_array($type, array_keys($lookup))); 
        return $lookup[$type];
    }
}
