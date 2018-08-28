<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Class for adding an alert message to the page footer.
 * Alert messages should be set in config.ini.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 */
class Alert extends AbstractHelper
{

    /**
     * Constructor
     *
     * @param array $alertMsg an array of strings to be displayed.
     * Each string represents a paragraph.
     */
    public function __construct($config)
    {
        $this->alertMsg = !isset($config->Alert->label)
            ? false : $config->Alert->label;
        $this->alertMsg = !isset($config->Alert->paragraph)
            ? false : $config->Alert->paragraph;
        $this->alertHtml = !isset($config->Alert->html)
            ? false : $config->Alert->html;
    }


    /**
     * Method adds an alert message to the bottom of all pages if it
     * is set in the [Alert] section of config.ini.
     */
    public function getAlert() {
        $html = null; 
        $i = 0;
        if ($this->alertHtml) {
            $html = '<div class="attention row">';
            $html .= $this->alertHtml;
            $html .= '</div>';
        }
        elseif ($this->alertMsg) {
            $html = '<div class="attention row">';
            foreach($this->alertMsg as $msg) {
                if ($i == 0) {
                    $html .= '<p>' . '<span class="label label-info">' . $this->alertLabel . '<i class="fa  fa-exclamation-circle"></i></span> ' . $msg . '</p>';
                }
                else {
                    $html .= '<p>' . $msg . '</p>';
                } 
                $i++;
            }
            $html .= '</div>';
        }
        return $html;
    }
    
}
