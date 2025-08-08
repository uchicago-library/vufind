<?php
/**
 * Turnstile Controller
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2024.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Maccabee Levine <msl321@lehigh.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace UChicago\Controller;

use Laminas\Log\LoggerAwareInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFind\Crypt\HMAC;
use VuFind\Log\LoggerAwareTrait;
use UChicago\RateLimiter\RateLimiterManager;
use UChicago\RateLimiter\Turnstile\Turnstile;

/**
 * Controller Cloudflare Turnstile access checks.
 *
 * @category VuFind
 * @package  Controller
 * @author   Maccabee Levine <msl321@lehigh.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class TurnstileController extends AbstractBase implements
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Request properties to be securely hashed, to avoid manipulation
     *
     * @var array
     */
    ### UChicago customization ###
    protected $hashKeys = ['siteKey', 'policyId', 'destination', 'redirectKey'];
    ### ./UChicago customization ###

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm                 Service locator
     * @param Turnstile               $turnstile          Turnstile service
     * @param RateLimiterManager      $rateLimiterManager Rate Limiter Manager instance
     * @param array                   $config             Rate Limiter configuration
     * @param HMAC                    $hmac               HMAC service
     */
    public function __construct(
        ServiceLocatorInterface $sm,
        protected Turnstile $turnstile,
        protected RateLimiterManager $rateLimiterManager,
        protected array $config,
        protected HMAC $hmac
    ) {
        parent::__construct($sm);
    }

    /**
     * Present the Turnstile challenge to the user
     *
     * @return mixed
     */
    public function challengeAction()
    {
        $context = json_decode(base64_decode($this->params()->fromQuery('context')), true);
        $context['siteKey'] = $this->config['Turnstile']['siteKey'];
        $context['jsLibraryUrl'] = $this->config['Turnstile']['jsLibraryUrl']
            ?? 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        $context['hash'] = $this->hmac->generate($this->hashKeys, $context);

        $this->layout()->searchbox = false;
        return $this->createViewModel($context);
    }

    /**
     * Verify the Turnstile widget result against the Turnstile backend
     *
     * @return mixed
     */
    public function verifyAction()
    {
        ### UChicago customization ###
        // Extract all POST parameters first to ensure they're available for HMAC verification
        // Note: $redirectKey must be extracted before HMAC generation since compact($this->hashKeys)
        // requires all variables listed in $hashKeys to be defined. To not do so creats an HMAC
        // verification bypass security vulnerability
        $token = $this->params()->fromPost('token');
        $policyId = $this->params()->fromPost('policyId');
        $destination = $this->params()->fromPost('destination');
        $redirectKey = $this->params()->fromPost('redirectKey');
        $priorHash = $this->params()->fromPost('hash');
        ### ./UChicago customization ###

        $siteKey = $this->config['Turnstile']['siteKey'];
        $newHash = $this->hmac->generate($this->hashKeys, compact($this->hashKeys));
        if ($newHash != $priorHash) {
            throw new \Exception('Wrong hash value used in Turnstile verification.');
        }

        $ipAddress = $this->event->getRequest()->getServer('REMOTE_ADDR');
        $this->turnstile->validateAndCacheResult($token, $policyId, $ipAddress);

        ### UChicago customization ###
        ### Forward GET parameters for search ###

        // Try to retrieve the full URL from session (with query parameters)
        $redirectUrl = $destination; // fallback to path-only destination
        if ($redirectKey && isset($_SESSION[$redirectKey])) {
            $sessionData = $_SESSION[$redirectKey];

            // Check if session data is valid and not expired
            if (isset($sessionData['url'], $sessionData['expires']) && $sessionData['expires'] > time()) {
                $redirectUrl = $sessionData['url'];
            }

            // Clean up the session data
            unset($_SESSION[$redirectKey]);
        }

        // Either way, return an http redirect to the referrer page.
        return $this->redirect()->toUrl($redirectUrl);
        ### ./UChicago customization ###
    }
}
