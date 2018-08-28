<?php
/**
 * Ajax Controller Module
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace UChicago\Controller;
use VuFind\Exception\Auth as AuthException, Zend\Http\Client, Zend\Http\Request;
use VuFind\AjaxHandler\PluginManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * READ - This controller overrides the VuFind AjaxController only to hide status for analyitc 
 * records on result pages. 
 *
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
    implements TranslatorAwareInterface
{
    use AjaxResponseTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructor
     *
     * @param PluginManager $am AJAX Handler Plugin Manager
     */
    public function __construct(PluginManager $am)
    {
        // Add notices to a key in the output
        set_error_handler([static::class, 'storeError']);
        $this->ajaxManager = $am;
    }

    /**
     * Get the url to a map and link text to display for a given holding or item.
     * Uses the maplookup service in formms2.
     *
     * @param string, $location
     *
     * @param string, $callnum
     * 
     * @param string, $prefix (callnumber)
     *
     * @return array
     */
    public function getMapLink($location, $callnum, $prefix) {
        $config = $this->getConfig();
        $url = $config['MapLookupService']['url'] . '&location=' . urlencode($location) . '&callnum=' . urlencode($callnum) . '&callnumPrefix=' . urlencode($prefix);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($url);

        $client = new Client();
        $client->setOptions(array('timeout' => 6));

        $response = $client->dispatch($request);
        $content = $response->getBody();

        return json_decode($content, true);
    }

    protected function mapLinkAjax() {
        $json = $this->getMapLink($_GET['location'], $_GET['callnum'], $_GET['callnumPrefix']);
        return $this->output($json, self::STATUS_OK);
    }


    /**
     * Helper function for decoding jsonp
     *
     */
    private function jsonp_decode($jsonp, $assoc = false) {
        if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
            $jsonp = substr($jsonp, strpos($jsonp, '('));
        }
        return json_decode(trim($jsonp,'();'), $assoc);
    }

    /**
     * Get deduped e-holdings from Keith's e-holding deduping service.
     * More info: http://www2.lib.uchicago.edu/keith/tmp/dldc/kw/holdings.html
     *
     * @param string, $issns, comma separated issn numbers.
     *
     * @return array
     */
    public function getDedupedEholdings($issns) {
        $config = $this->getConfig();
        $code = $config['DedupedEholdings']['code'];
        $function = $config['DedupedEholdings']['function'];
        $url = $config['DedupedEholdings']['url'] . '?code=' . $code . '&function=' . $function . '&callback=vufind' . '&issns=' . $issns;

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($url);

        $client = new Client();
        $client->setOptions(array('timeout' => 6));

        $response = $client->dispatch($request);
        $content = $response->getBody();

        return $this->jsonp_decode($content, true);
    }

    /**
     * Convert deduped e-holdings from Keith's e-holding deduping service to HTML.
     *
     * @param array, $issns, issn numbers.
     *
     * @return string, html
     */
    public function getDedupedEholdingsHtml($issns) {
        $config = $this->getConfig();
        $dedupedEholdings = $this->getDedupedEholdings($issns);
        $coverageLabel = $config['DedupedEholdings']['coverage_label'];
        $format = '<a href="%s" class="eLink external">%s</a> %s %s<br/>';
        $retval = '';
        if ($dedupedEholdings) {
            $deduped = $dedupedEholdings['deduped'];
            $complete = $dedupedEholdings['complete'];
            foreach($deduped as $deh) {
                $retval .= sprintf($format, $deh['url'], $deh['name'], $coverageLabel, $deh['coverageString']);
            }
            if ($complete) {
                if (count($deduped) < count($complete)) {
                    $retval .= '<div class="toggle">View all available e-resources for this title</div>';
                    $retval .= '<div class="e-list hide">';
                    foreach($complete as $ch) {
                        $retval .= sprintf($format, $ch['url'], $ch['name'], $coverageLabel, $ch['coverageString']);
                    }
                    $retval .= '</div>';
                }
            }
        }
        return $retval;
    }

    protected function dedupedEholdingsAjax() {
        $issns = $_GET['issns'];
        $html = $this->getDedupedEholdingsHtml($issns);
        return $this->output($html, self::STATUS_OK);
    }

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
