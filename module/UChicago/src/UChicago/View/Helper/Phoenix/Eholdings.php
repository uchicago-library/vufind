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
     * Gets e-holding links and associated data 
     *
     * @rerutns array
     */
    public function getEholdingLinks() {
        $links = array(); 
        $data = $this->view->driver->getEholdingsFromMarc();
        if ($data === null) {
            return $links;
        }
        foreach ($data as $urlData) {
            $link = array();
            $i = 0;
            foreach ($urlData as $url) {
                $filter = 'www.';
                $descriptionArray = parse_url($url['subfieldData']['u-1']);
                $host = $descriptionArray['host'];
                $hasFilter = (substr($host, 0, 4) == $filter);
                $description = $hasFilter ? substr($host, strlen($filter)) : $host;
                 
                $link[$i]['url'] = $url['subfieldData']['u-1'];
                $link[$i]['desc'] = $description; //placeholder 
                $link[$i]['type'] = $url['subfieldData']['c'];
                $link[$i]['callnumber'] = $url['subfieldData']['a']; 
                $i++; 
            }
            $links = $link;
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
        $lookup = array('FullText' => 'Full text online', 
                        'Related' => 'Related text online');
        assert(in_array($type, $lookup), 'The e-holding type is not valid.'); 
        return $lookup[$type];
    }
}
