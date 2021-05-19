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

    public function __construct($siteConfig)
    {
        $this->hathiETAS = isset($siteConfig->Catalog->hathi_etas) ? $siteConfig->Catalog->hathi_etas : false;
        $this->hathiETASHelpLink = isset($siteConfig->Catalog->hathi_etas_help_link) ? $siteConfig->Catalog->hathi_etas_help_link : false;
    }

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
     * Helper method checks for the presence of a 903r equal to "ETAS".
     * Returns true if a 903r equal to "ETAS" is present, otherwise false.
     *
     * @return boolean
     */
    private function isETAS() {
        $rawMarcData = $this->view->driver->crosswalk('hathiLink');
        $emergency_closure = $this->hathiETAS;
        $etas = false;
        if (isset($rawMarcData['ETAS'])) {
            foreach ($rawMarcData['ETAS'] as $identifier) {
                if (implode($identifier['subfieldData']) == "ETAS") {
                    $etas = true;
                    break;
                }
            }
        }
        return $etas;
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
        $emergency_closure = $this->hathiETAS;
        // A 903|a of "Hathi" and a 903|r of "ETAS" are both present, however,
        // we are not under an emergency closure and shoul not display a link.
        if ($this->isHathi903() && $this->isETAS() && !$emergency_closure) {
            return false;
        }
        // Normal Hathi link display logic
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
        if ($rawMarcData) {
            foreach($rawMarcData['oclcNumber'] as $data) {
                $num = implode($data['subfieldData']);
                if (preg_match('/\(OCoLC\)/i', $num)) {
                    $oclcNumbers[] = $num;
                }
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
     * @param $results, boolean, is it a results view.
     * @return string, link to full text in HathiTrust or false.
     */ 
    public function getHathiLink($results=false)
    {
        $bibId = $this->view->driver->getUniqueID();
        //Handle hathi links with proxy for https
        $baseUrl = 'http://forms.lib.uchicago.edu/lib/hathi/info.php?q=oclc:';
        $hathiLink = false;
        $rawMarcData = $this->view->driver->crosswalk('hathiLink');
        $graphic = '<img src="/vufind/themes/phoenix/images/hathitrustFavicon.png" alt="HathiTrust Digital Library"/>';
        $helpLinkUrl = $this->hathiETASHelpLink;
        $helpLink = '';

        if ($this->isHathi()) {
            $oclcNumber = $this->getOCLCNumbers();
            $url = $baseUrl . $oclcNumber;
            if($this->isETAS()) {
                $linkText = 'HathiTrust Emergency Access [Login Required]';
                $url = $baseUrl . $oclcNumber . '&sso=true';
                if($helpLinkUrl) {
                    $helpLink = '<a href="'. $helpLinkUrl . '" class="login-directions">Login Directions</a>';
                }
            } else {
                $linkText = 'HathiTrust Digital Library';
            }
            if ($results === true) {
                $helpText = '';
            } else {
                $helpText = '<br/><br/><div class="etas-help-text"><i class="fa fa-lg fa-exclamation-triangle" aria-hidden="true"></i> If the volume you need is not online, find the volume below and click "Need help?" to start a request.</div>';
            }
            $hathiLink = '<div class="hathiPreviewDiv"><a href="' . $url . '" class="hathi eLink">' . $linkText . '</a>' . $helpLink . $helpText . '</div>';
        }
        return $hathiLink;
    }
}
