<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class that adds the markup for the brief/detailed view toggle  
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class ViewToggle extends AbstractHelper
{

   /**
     * Export support function to convert Summon format to EndNote format.
     *
     */ 
    public function __invoke()
    {

	    print '<div class="view-toggle"><a href="" class="bv">Brief View</a> | <span class="dv">Detailed View</span></div>';

    }
}
