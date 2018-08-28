<?php
/**
 * Record Tab Factory Class
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2014.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace UChicago\RecordTab;
use Zend\ServiceManager\ServiceManager;

/**
 * Record Tab Factory Class
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory extends \VuFind\RecordTab\Factory
{

    /**
     * Factory for HoldingsILS tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return UChicago HoldingsILS
     */
    public static function getHoldingsILS(ServiceManager $sm)
    {
        // If VuFind is configured to suppress the holdings tab when the
        // ILS driver specifies no holdings, we need to pass in a connection
        // object:
        $config = $sm->get('VuFind\Config\PluginManager')->get('config');
        $catalog = ($config->Site->hideHoldingsTabWhenEmpty ?? false)
            ? $sm->get('VuFind\ILS\Connection') : null;

        // The number of items and holdings_text_fields to display before collapsing them.
        $displayNum = isset($config['Catalog']['items_display_number']) ? $config['Catalog']['items_display_number'] : '';

        return new \UChicago\RecordTab\HoldingsILS(
            $catalog,
            (string)($config->Site->holdingsTemplate ?? 'standard'),
            $displayNum
        );
    }

    /**
     * Factory for TOC tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return TablesOfContents
     */
    public static function getTOC(ServiceManager $sm)
    {
        $config = $sm->get('VuFind\Config\PluginManager')->get('config');
        // Only instantiate the loader if the feature is enabled:
        if (isset($config->Content->toc)) {
            $loader = $sm->get('VuFind\Content\PluginManager')
                ->get('toc');
        } else {
            $loader = null;
        }
        return new \UChicago\RecordTab\TOC($loader, static::getHideSetting($config, 'toc'));
    }
}
