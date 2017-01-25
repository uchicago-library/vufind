<?php
/**
 * Helper class for making settings in the config.ini and config_base.ini available to templates.
 * Helps to avoid making view helpers and modules unnecessarily when only a config value is needed.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace UChicago\View\Helper\Phoenix;

/**
 * Helper class for making settings in the config.ini and config_base.ini available to templates.
 * Helps to avoid making view helpers and modules unnecessarily when only a config value is needed.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class GetConfig extends \Zend\View\Helper\AbstractHelper
{

    /**
     * Site email.
     */
    public $siteEmail;

    /**
     * Browse types.
     */
    public $alphabrowseSearchTypes;
    

    /**
     * Constructor
     *
     * @param string $siteEmail site email from the config file
     */
    public function __construct($siteConfig)
    {
        $this->siteEmail = isset($siteConfig->Site->email) ? $siteConfig->Site->email : false;
        $this->alphabrowseSearchTypes = isset($siteConfig->AlphaBrowse_Types) ? $siteConfig->AlphaBrowse_Types : false;
        $this->dueSoonInterval = isset($siteConfig->MyAccount->due_soon) ? $siteConfig->MyAccount->due_soon : false;
    }

    /**
     * Make the site email address available to the templates.     
     *
     * @return string $siteEmail from the config file
     */
    public function getSiteEmail()
    {
        return $this->siteEmail;
    }

    /**
     * Make alphabrowse search types available to the templates. 
     *
     * @return string $siteEmail from the config file
     */
    public function getAlphabrowseSearchTypes() 
    {
        return $this->alphabrowseSearchTypes;
    }

    /**
     * Make alphabrowse search types available to the templates. 
     *
     * @return string $siteEmail from the config file
     */
    public function getWhenDueAlertInterval() 
    {
        return $this->dueSoonInterval;
    }

}
