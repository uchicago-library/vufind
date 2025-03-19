<?php
/* This code is only needed until we upgrade to VuFind 11 or later. Look at closed issue #169 and the pull request for branch 169-backport-rate-limiter-and-cloudfare-turnstile to see the full list of files that can be removed after we upgrade. */
/**
 * VuFind Bootstrapper
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
 * @package  Bootstrap
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace UChicago;

use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Psr\Container\ContainerInterface;
use VuFind\I18n\Locale\LocaleSettings;
use UChicago\RateLimiter\RateLimiterManager;

/**
 * VuFind Bootstrapper
 *
 * @category VuFind
 * @package  Bootstrap
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Bootstrapper extends \VuFind\Bootstrapper
{
    /**
     * Set up rate limiter
     *
     * @return void
     */
    protected function initRateLimiter(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $callback = function ($event) {
            // Create rate limiter manager here so that we don't e.g. initialize the session too early:
            $rateLimiterManager = $this->container->get(RateLimiterManager::class);
            if (!$rateLimiterManager->isEnabled()) {
                return;
            }
            $result = $rateLimiterManager->check($event);
            if (!$result['allow']) {
                $response = $event->getResponse();
                if ($result['presentTurnstileChallenge'] ?? false) {
                    $this->presentTurnstileChallenge($rateLimiterManager, $event, $response);
                } else {
                    $response->setStatusCode(429);
                    $response->setContent($result['message']);
                }
                $event->stopPropagation(true);
                return $response;
            }
        };
        $this->events->attach('dispatch', $callback, 11000);
    }

    /**
     * Present a Cloudflare Turnstile challenge to the user
     *
     * @param RateLimiterManager               $rateLimiterManager The RateLimiterManager
     * @param MvcEvent                         $event              The current Laminas event
     * @param Laminas\Stdlib\ResponseInterface $response           Response object to modify to present the challenge
     *
     * @return void
     */
    protected function presentTurnstileChallenge(
        RateLimiterManager $rateLimiterManager,
        MvcEvent $event,
        \Laminas\Stdlib\ResponseInterface $response
    ): void {
        // Although the challenge could be displayed at the current URL, redirecting
        // to a simple URL (combined with a policy that blocks the referrer URL) may
        // hide search or result data from being accessible to Turnstile.
        $response->setStatusCode(307);
        $policyId = $rateLimiterManager->getPolicyIdForEvent($event);
        // base64_encoding the destination URL is just further obfuscation
        $context = base64_encode(json_encode([
            'policyId' => $policyId,
            'destination' => $event->getRequest()->getUri()->getPath(),
        ]));
        $response->getHeaders()->addHeaderLine(
            'Location',
            $event->getRequest()->getBaseUrl() . '/Turnstile/Challenge?context=' . $context
        );
    }
}
