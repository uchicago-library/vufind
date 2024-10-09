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
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\Plugin\Url;
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
    public function __construct(\Laminas\Config\Config $config) {
        $this->config = $config;
    }

    /**
     * Helper function for decoding jsonp
     *
     */
    private function jsonp_decode($jsonp, $assoc = false) {
        if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
            $jsonp = substr(trim($jsonp), strpos($jsonp, '('));
        }
        return json_decode(trim($jsonp,'();'), $assoc);
    }

    /**
     * Get deduped e-holdings from Keith's e-holding deduping service.
     * More info: http://www2.lib.uchicago.edu/keith/tmp/dldc/kw/holdings.html
     *
     * @param string, $issns, comma separated issn numbers.
     * @param string, $sfxNum
     * @param string, $bib
     *
     * @return array
     */
    public function getDedupedEholdings($issns, $sfxNum, $bib) {
        $config = $this->getConfig();
        $code = $config['DedupedEholdings']['code'];
        $function = $config['DedupedEholdings']['function'];
        $url = $config['DedupedEholdings']['url'] . '?code=' . $code . '&function=' . $function . '&callback=vufind';

        $flag = false;
        $toss = ['sf', 'oc'];
        $bibLead = mb_substr($bib, 0, 2);
        if (strlen($bib) > 0 && in_array($bibLead, $toss) === false) {
            $url .= '&bib=' . $bib;
            $flag = true;
        }
        if (strlen($issns) > 0) {
            $url .= '&issns=' . $issns;
            $flag = true;
        }
        if (strlen($sfxNum) > 0) {
            $url .= '&sfx=' . $sfxNum;
            $flag = true;
        }

        if ($flag === false) {
            return [];
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
     * @param string, $sfxNum
     * @param string, $bib
     *
     * @return string, html
     */
    public function getDedupedEholdingsHtml($issns, $sfxNum, $bib, $header) {
        $dedupedEholdings = $this->getDedupedEholdings($issns, $sfxNum, $bib) ?? [];
        $hasHeader = ($header === 'true');
        if (count(array_filter($dedupedEholdings), COUNT_RECURSIVE) === 0) {
            return '';
        }
        if (empty($dedupedEholdings['deduped']) && empty($dedupedEholdings['complete'])) {
            return '';
        }
        $retval = '';
        if ($hasHeader == true && !empty($dedupedEholdings)) {
          $retval .= '<div class="locpanel-heading online"><h2>Online</h2></div>';
        }
        $retval .= '<div class="holdings-unit">';
        $deduped = $dedupedEholdings['deduped'];
        $complete = $dedupedEholdings['complete'];
        $retval .= $this->buildLinks($deduped, $hasHeader);
        if ($complete) {
            if (count($deduped) < count($complete)) {
                $retval .= '<div class="toggle">View all available e-resources for this title</div>';
                $retval .= '<div class="e-list hide">';
                $retval .= $this->buildLinks($complete, $hasHeader);
                $retval .= '</div>';
            }
        }
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Build link strings.
     *
     * @param array, $deduped.
     * @param bool, $isResults
     *
     * @return string
     */
    protected function buildLinks($deduped, $isResults) {
        $config = $this->getConfig();
        $format = '<div class="deduped-group"><a href="%s" class="eLink external">%s <i class="fa fa-external-link" aria-hidden="true"></i></a> %s %s %s %s</div>';
        $links = '';
        $callNoUrl = $this->config['Site']['url'] . '/alphabrowse/Home?source=lcc&from=';
        $coverageLabel = $config['DedupedEholdings']['coverage_label'] . ' ' ?? '';
        $callNoLinkTxt = $config['DedupedEholdings']['callnumber_link_text'];
        $callNoLink = ' <a href="%s">%s</a>';
        foreach($deduped as $deh) {
            $coverage = '';
            $note = !empty($deh['note']) ? '<br/>' . $deh['note'] : '';
            $materials = !empty($deh['materials']) ? '<br/>' . $deh['materials'] : '';
            $callNo = !empty($deh['callno']) && $isResults == true
                ? '<br/>' . $deh['callno'] . sprintf($callNoLink, $callNoUrl . urlencode($deh['callno']), $callNoLinkTxt)
                : '';
            if ($deh['coverageString'] != 'coverage unknown') {
                $coverage = !empty($deh['coverageString']) ? '<br/>' . $coverageLabel . $deh['coverageString'] : '';
            }
            $links .= sprintf($format, $deh['url'], $deh['name'], $coverage, $note, $materials, $callNo);
        }
        return $links;
    }

    public function handleRequest(Params $params) {
        $issns = $_GET['issns'];
        $sfxNum = $_GET['sfx'];
        $bib = $_GET['bib'];
        $header = $_GET['header'];
        $html = $this->getDedupedEholdingsHtml($issns, $sfxNum, $bib, $header);
        return [ $html ];
    }

    public function getConfig() {
        return $this->config;
    }
}
