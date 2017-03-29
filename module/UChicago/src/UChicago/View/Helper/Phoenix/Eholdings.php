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
     * Return the first key name if it begins 
     * with the input.
     *
     * @param $array, an array to search.
     * @param $q, string to search for.
     *
     * @returns string, name of the key beginning
     * with what is being looked for.
     */
    public function geKeyBeginsWith($array, $q) {
        $filtered = array();
        foreach ($array as $key => $value) {
            if (strpos($key, $q) === 0) {
                return $key;
            }
        }
        return null;
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

        $i = 0;
        $link = [];
        foreach ($data['urls'] as $urlData) {

            $filter = 'www.';
            $subfields = $data['urls'][$i]['subfieldData'];
            $url = $subfields[$this->geKeyBeginsWith($subfields, 'u')];
            $descriptionArray = parse_url($url);
            $host = $descriptionArray['host'];
            $hasFilter = (substr($host, 0, 4) == $filter);
            $description = $hasFilter ? substr($host, strlen($filter)) : $host;

            /* Get the subfield keys */
            $z = $this->geKeyBeginsWith($subfields, 'z');
            $p = $this->geKeyBeginsWith($subfields, 'p');
            $c = $this->geKeyBeginsWith($subfields, 'c');
            $a = $this->geKeyBeginsWith($subfields, 'a');

            /* Get the url */ 
            $link[$i]['url'] = $url;
            if (!array_key_exists($z, $subfields)) {
                $link[$i]['desc'] = array_key_exists($p, $subfields) ? $subfields[$p] : $description;
            }
            else {
                $link[$i]['desc'] = array_key_exists($p, $subfields) ? $subfields[$p] : '';
            }

            /* Get the link type, callnumber and display text*/ 
            $link[$i]['type'] = isset($subfields[$c]) ? $subfields[$c] : null;
            $link[$i]['callnumber'] = isset($subfields[$a]) ? $subfields[$a] : null;
            $link[$i]['text'] = array_key_exists($z, $subfields) ? $subfields[$z] : null;

            $links = $link;
            $i++; 
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
