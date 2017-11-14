<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class that adds Hathi links to the full record view on records with
 * a bib number beginning with an ocm or ocn prefix.  
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class HathiLink extends AbstractHelper
{
    /**
     * Checks to see if a shelving location of "Full Text" exists.
     * Used in outlier cases where a MARC 903a exists for a Hathi 
     * link but no other full text links exist.
     *
     * @param $holdings, the holdings to search.
     * @param $related, optional boolean. If set to true, the method
     * will return true for the existence of related links in additon 
     * to full text links.
     *
     * @return boolean
     */
    public function hasFullTextOnline($holdings, $related='false') {
        $fullTextOnline = false;
        foreach ($holdings as $location => $holding) {
            if ($location == "FullText" ) {
                $fullTextOnline = true;
                break;
            }
            elseif($related == true and $location == "Related") {
                $fullTextOnline = true;
                break;
            }
        }
        return $fullTextOnline;
    }

    /**
     * Helper method checks for the presence of a 903a equal to "Hathi".
     * Returns true if a 903 equal to "Hathi" is present, otherwise false. 
     *
     * @return boolean
     */
    private function isHathi903() {
        $rawMarcData = $this->view->driver->crosswalk('hathiLink');
        $hathi903 = false; 
        if (isset($rawMarcData['Project identifier'])) {
            foreach ($rawMarcData['Project identifier'] as $identifier) {
                if (implode($identifier['subfieldData']) == "Hathi") {
                    $hathi903 = true;
                    break;
                }
            }
        }
        return $hathi903;
    }

    /**
     * Helper method checks if a record is a Hathi record according to the following criteria:
     * a record has a bib prefix of "ocm", a record has a bib prefix of "ocn", a record has a
     * 903a equal to "Hahi".
     *
     * @return boolean
     */
    public function isHathi() {
        $hathi = false;
        $bibId = $this->view->driver->getUniqueID(); 
        if ((preg_match ('/^ocm/i', $bibId) == 1) || (preg_match ('/^ocn/i', $bibId ) == 1) || ($this->isHathi903()) ) {
            $hathi = true;
        }
        return $hathi;
    }

    /**
     * Gets OCLC numbers from the record.
     *
     */
    private function getOCLCNumbers() {
        $rawMarcData = $this->view->driver->crosswalk('hathiLink');
        $oclcNumbers = [];
        foreach($rawMarcData['oclcNumber'] as $data) {
            $num = implode($data['subfieldData']);
            if (preg_match('/\(OCoLC\)/i', $num)) {
                $oclcNumbers[] = $num;
            }
        }
        // We don't use getCleanOCLCNum() or getOCLC() because both of these only return 1 oclc number, we need all of them
        return preg_replace('/\(OCoLC\)/i', '', implode ('|oclc:', array_filter($oclcNumbers)));
    }

    /**
     * Method for adding Hathi links to bib records that have an "ocm" or "ocn" prefix.
     * Also returns Hathi links for records that have a 903a and an 035a.
     * Returns false if the bib number does not have the "ocm" or "ocn" prefix or if 
     * a 903a is not present.
     *
     * @return string, link to full text in HathiTrust or false.
     */ 
    public function getHathiLink()
    {
        $bibId = $this->view->driver->getUniqueID();
        //Handle hathi links with proxy for https
        $url = 'http://forms.lib.uchicago.edu/lib/hathi/info.php?q=oclc:';
        $hathiLink = false;
        $rawMarcData = $this->view->driver->crosswalk('hathiLink');
        $graphic = '<img src="/vufind/themes/phoenix/images/hathitrustFavicon.png" alt="HathiTrust Digital Library"/>'; 

        if ($this->isHathi()) {
            $oclcNumber = $this->getOCLCNumbers();
            $hathiLink = '<div class="hathiPreviewDiv"><a href="' . $url . $oclcNumber . '" class="hathi eLink">HathiTrust Digital Library</a></div>';
        }
        return $hathiLink;
    }
}
