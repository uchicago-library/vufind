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
class GetDedupedEholdings extends \VuFind\AjaxHandler\AbstractBase
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Top-level VuFind configuration (config.ini)
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss   Session settings
     * @param User|bool         $user Logged in user (or false)
     * @param Url               $url  URL helper
     */
    public function __construct(\Zend\Config\Config $config) {
        $this->config = $config;
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
    public function getDedupedEholdings($issns, $sfxNum) {
        $config = $this->getConfig();
        $code = $config['DedupedEholdings']['code'];
        $function = $config['DedupedEholdings']['function'];
        $url = $config['DedupedEholdings']['url'] . '?code=' . $code . '&function=' . $function . '&callback=vufind';

        if (strlen($issns) > 0) {
            $url .= '&issns=' . $issns;
        }
        if (strlen($sfxNum) > 0) {
            $url .= '&sfx=' . $sfxNum;
        }

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
    public function getDedupedEholdingsHtml($issns, $sfxNum, $header) {
        $config = $this->getConfig();
        $dedupedEholdings = $this->getDedupedEholdings($issns, $sfxNum);
        $coverageLabel = $config['DedupedEholdings']['coverage_label'];
        $format = '<a href="%s" class="eLink external">%s</a> %s %s<br/>%s';
        $retval = '';
        if ($header === 'true') {
          $retval .= '<h3>Deduped Eholdings</h3>';
        }
        if ($dedupedEholdings) {
            $deduped = $dedupedEholdings['deduped'];
            $complete = $dedupedEholdings['complete'];
            foreach($deduped as $deh) {
                $retval .= sprintf($format, $deh['url'], $deh['name'], $coverageLabel, $deh['coverageString'], $deh['note']);
            }
            if ($complete) {
                if (count($deduped) < count($complete)) {
                    $retval .= '<div class="toggle">View all available e-resources for this title</div>';
                    $retval .= '<div class="e-list hide">';
                    foreach($complete as $ch) {
                        $retval .= sprintf($format, $ch['url'], $ch['name'], $coverageLabel, $ch['coverageString'], $ch['note']);
                    }
                    $retval .= '</div>';
                }
            }
        }
        return $retval;
    }

    public function handleRequest(Params $params) {
        $issns = $_GET['issns'];
        $sfxNum = $_GET['sfx'];
        $header = $_GET['header'];

        $html = $this->getDedupedEholdingsHtml($issns, $sfxNum, $header);
        return [ $html ];
    }

    public function getConfig() {
        return $this->config;
    }
}
