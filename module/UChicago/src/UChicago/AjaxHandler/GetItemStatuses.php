<?php
/**
 * "Get Deduped Eholdings" AJAX handler
 *
 * PHP version 5
 *
 * Copyright (C) The University of Chicago Library 2018
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
 * @package  AJAX
 * @author   John Jung <jej@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace UChicago\AjaxHandler;
use VuFind\Db\Row\User;
use VuFind\ILS\Connection;
use VuFind\Session\Settings as SessionSettings;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Url;
/**
 * "Get Deduped Eholdings" AJAX handler
 *
 * This will check the ILS for being online and will return the ils-offline
 * template upon failure.
 *
 * @category VuFind
 * @package  AJAX
 * @author   John Jung <jej@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses
{

    /**
     * Support method for getItemStatuses() -- process a single bibliographic record
     * for location settings other than "group".
     *
     * @param array  $record            Information on items linked to a single bib
     *                                  record
     * @param array  $messages          Custom status HTML
     *                                  (keys = available/unavailable)
     * @param string $locationSetting   The location mode setting used for
     *                                  pickValue()
     * @param string $callnumberSetting The callnumber mode setting used for
     *                                  pickValue()
     *
     * @return array                    Summarized availability information
     */
    protected function getItemStatus($record, $messages, $locationSetting,
        $callnumberSetting
    ) {
        // Summarize call number, location and availability info across all items:
        $callNumbers = $locations = [];
        $use_unknown_status = $available = false;
        $services = [];

        foreach ($record as $info) {
            // Find an available copy
            if ($info['availability']) {
                $available = true;
            }
            // Check for a use_unknown_message flag
            if (isset($info['use_unknown_message'])
                && $info['use_unknown_message'] == true
            ) {
                $use_unknown_status = true;
            }
            // Store call number/location info:
            $callNumbers[] = $info['callnumber'];
            $locations[] = $info['location'];
            // Store all available services
            if (isset($info['services'])) {
                $services = array_merge($services, $info['services']);
            }
        }

        $callnumberHandler = $this->getCallnumberHandler(
            $callNumbers, $callnumberSetting
        );

        // Determine call number string based on findings:
        $callNumber = $this->pickValue(
            $callNumbers, $callnumberSetting, 'Multiple Call Numbers'
        );

        // Determine location string based on findings:
        $location = $this->pickValue(
            $locations, $locationSetting, 'Multiple Locations', 'location_'
        );

        if (!empty($services)) {
            $availability_message = $this->reduceServices($services);
        } else {
            $availability_message = $use_unknown_status
                ? $messages['unknown']
                : $messages[$available ? 'available' : 'unavailable'];
        }

        // Has the effect of hiding the status for analyitc records
        // on results pages - brad
        if ($info['status'] == 'ANAL') {
            $availability_message = '';
        }

        /* BEGIN: Terrible Hack for Law Status Override - REVERT THIS */
        if ($info['statusOverride'] == 'LAW-STATUS-OVERRIDE') {
            $available = false;
            $availability_message = $messages['unavailable'];
        }
        /* END: Terrible Hack for Law Status Override - REVERT THIS */

        // Send back the collected details:
        return [
            'id' => $record[0]['id'],
            'availability' => ($available ? 'true' : 'false'),
            'availability_message' => $availability_message,
            'location' => htmlentities($location, ENT_COMPAT, 'UTF-8'),
            'locationList' => false,
            'reserve' =>
                ($record[0]['reserve'] == 'Y' ? 'true' : 'false'),
            'reserve_message' => $record[0]['reserve'] == 'Y'
                ? $this->translate('on_reserve')
                : $this->translate('Not On Reserve'),
            'callnumber' => htmlentities($callNumber, ENT_COMPAT, 'UTF-8'),
            'callnumber_handler' => $callnumberHandler
        ];
    }

}
