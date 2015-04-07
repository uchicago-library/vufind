<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Class adds donor boookplates to the full record view. 
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class BookPlates extends AbstractHelper
{

    /**
     * Blacklist for deduping recurring bookplates.
     */
    public $currentPlates = array();

    /**
     * Function for adding donor information to the full record view.
     *
     */ 
    public function __invoke() {
        $html= '';
        if (strcmp(get_class($this->view->driver), 'UChicago\RecordDriver\SolrMarcPhoenix') == 0){
            $bookPlateArray = $this->view->driver->getBookPlate();
            foreach ($bookPlateArray as $item) {
                while (!empty($item)) {
                    $bookPlateTitle = array_shift($item);
                    $bookPlateCode  = array_shift($item);
                    $thumbImg = 'http://www.lib.uchicago.edu/bookplates/' . strtolower($bookPlateCode) . '-thumb.jpg';
                    $link = 'http://www.lib.uchicago.edu/bookplates/' . strtolower($bookPlateCode) . '-full.jpg';
                    /*Check if the current bookplate is in the blacklist.*/
                    if (!in_array($bookPlateTitle, $this->currentPlates)) {
                        $html .= '<div class="bookplate"><a href="' . $link . '" target="_blank" style="background: none;">' . 
                            '<img alt=" " src="' . $thumbImg . '"></a>' . '<br/><div class="bookplateTitle">' . $bookPlateTitle . '</div>' . '</div>';
                        /*Add the new bookplate to the blacklist*/
                        array_push($this->currentPlates, $bookPlateTitle);
                    }
                }
            }
        }
        if (!empty($html)) { 
            $html = '<div id="bookplates">' . $html . '</div>'; 
        }
        return $html;
    }
}
