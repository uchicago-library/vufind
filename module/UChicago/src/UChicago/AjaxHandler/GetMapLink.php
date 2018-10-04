<?php
/**
 * "Get Map Link" AJAX handler
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
 * "Get Map Link" AJAX handler
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
class GetMapLink extends \VuFind\AjaxHandler\AbstractBase
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

    public function handleRequest(Params $params) {
        $config = $this->getConfig();

        $location = $_GET['location'];
        $callnum = $_GET['callnum'];
        $prefix = $_GET['prefix'];

        $url = $config['MapLookupService']['url'] . '&location=' . urlencode($location) . '&callnum=' . urlencode($callnum) . '&callnumPrefix=' . urlencode($prefix);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($url);

        $client = new Client();
        $client->setOptions(array('timeout' => 6));

        $response = $client->dispatch($request);
        $content = $response->getBody();

        return [ json_decode($content, true) ];
    }

    public function getConfig() {
        return $this->config;
    }
}
