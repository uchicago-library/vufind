<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Class for streamlining bootstrap alert messages.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class BootstrapAlert extends AbstractHelper
{

    /**
     * Build a bootstrap alert. 
     
     * @param string $type, should be success, info, waring, or danger 
     * @param string $msg, the message to  display.
     * @param string $style, control the bottom margin with css styles
     * 
     * @return an html string formatted with bootstrap styles. 
     */
    public function makeAlert($type, $msg, $style='') {
        $html = '<div class="alert alert-%s" role="alert" style="%s">%s</div>';
        return sprintf($html, $type, $style, $msg);
    }
    
}
