<?php
/* This code is only needed until we upgrade to VuFind 11 or later. Look at closed issue #169 and the pull request for branch 169-backport-rate-limiter-and-cloudfare-turnstile to see the full list of files that can be removed after we upgrade. */
/**
 * Rate limiter manager.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @package  Cache
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace UChicago\RateLimiter;

use Closure;
use Laminas\EventManager\EventInterface;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mvc\MvcEvent;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Net\IpAddressUtils;
use UChicago\RateLimiter\Turnstile\Turnstile;

use function in_array;
use function is_bool;

/**
 * Rate limiter manager.
 *
 * @category VuFind
 * @package  Cache
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class RateLimiterManager implements LoggerAwareInterface, TranslatorAwareInterface
{
    use LoggerAwareTrait;
    use TranslatorAwareTrait;

    /**
     * Turnstile service
     *
     * @var ?Turnstile
     */
    protected $turnstile = null;

    /**
     * Current event description for logging
     *
     * @var string
     */
    protected $eventDesc = '??';

    /**
     * Client details for logging
     *
     * @var string
     */
    protected $clientLogDetails;

    /**
     * Constructor
     *
     * @param array          $config                     Rate limiter configuration
     * @param string         $clientIp                   Client's IP address
     * @param ?int           $userId                     User ID or null if not logged in
     * @param Closure        $rateLimiterFactoryCallback Rate limiter factory callback
     * @param IpAddressUtils $ipUtils                    IP address utilities
     */
    public function __construct(
        protected array $config,
        protected string $clientIp,
        protected ?int $userId,
        protected Closure $rateLimiterFactoryCallback,
        protected IpAddressUtils $ipUtils
    ) {
        $this->clientLogDetails = "ip:$clientIp";
        if (null !== $userId) {
            $this->clientLogDetails .= " u:$userId";
        }
    }

    /**
     * Set the turnstile service instance.
     *
     * @param Turnstile $turnstile Turnstile service
     *
     * @return void
     */
    public function setTurnstile(Turnstile $turnstile)
    {
        $this->turnstile = $turnstile;
    }

    /**
     * Check if rate limiter is enabled
     *
     * @return bool|string False if disabled, true if enabled and enforcing,
     * 'report_only' if enabled for logging only (not enforcing the limits)
     */
    public function isEnabled(): bool|string
    {
        $mode = $this->config['General']['enabled'] ?? false;
        return is_bool($mode) ? $mode : (string)$mode;
    }

    /**
     * Check if the given event is allowed
     *
     * @param EventInterface $event Event
     *
     * @return array Associative array with the following keys:
     *   bool    allow              Whether to allow the request
     *   ?int    requestsRemaining  Remaining requests
     *   ?int    retryAfter        Retry after seconds if limit exceeded
     *   ?int    requestLimit      Current limit
     *   ?string message           Response message if limit reached
     */
    public function check(EventInterface $event): array
    {
        $result = [
            'allow' => true,
            'requestsRemaining' => null,
            'retryAfter' => null,
            'requestLimit' => null,
            'message' => null,
        ];

        if (!$this->isEnabled() || !($event instanceof MvcEvent)) {
            return $result;
        }

        $routeMatch = $event->getRouteMatch();
        $controller = $routeMatch?->getParam('controller') ?? '??';
        $action = ($routeMatch?->getParam('action') ?? '??');
        $this->eventDesc = "$controller/$action";
        if ('AJAX' === $controller && 'JSON' === $action) {
            $req = $event->getRequest();
            $method = $req->getPost('method') ?? $req->getQuery('method');
            $this->eventDesc .= " $method";
        }

        try {
            // Check for a matching policy:
            if (!($policyId = $this->getPolicyIdForEvent($event))) {
                $this->verboseDebug('No policy matches event');
                return $result;
            }

            // Check Turnstile first if enabled for this policy
            if (
                ($this->config['Policies'][$policyId]['turnstileRateLimiterSettings'] ?? false) &&
                $this->turnstile?->isChallengeAllowed($event)
            ) {
                // Check for prior Turnstile result
                $priorResult = $this->turnstile->checkPriorResult($policyId, $this->clientIp);
                
                // If no prior result exists or challenge failed, require Turnstile challenge
                if ($priorResult === null || $priorResult === false) {
                    $this->verboseDebug('Turnstile challenge required for policy ' . $policyId);
                    return [
                        'allow' => false,
                        'requestsRemaining' => 0,
                        'retryAfter' => 0,
                        'requestLimit' => 1,
                        'message' => 'Turnstile challenge required',
                    ];
                }
            }

            // Check rate limiter after Turnstile validation
            $limiter = ($this->rateLimiterFactoryCallback)(
                $this->config,
                $policyId,
                $this->clientIp,
                $this->userId
            );
            $limit = $limiter->consume(1);

            $result = [
                'allow' => $limit->isAccepted(),
                'requestsRemaining' => $limit->getRemainingTokens(),
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
                'requestLimit' => $limit->getLimit(),
            ];

            if (!$limit->isAccepted()) {
                $result['message'] = $this->getTranslator()->translate(
                    'rate_limit_exceeded'
                );
            }

            $this->verboseDebug(
                ($limit->isAccepted() ? 'Accepted' : 'Refused')
                . " by policy '$policyId'"
                . ', remaining: ' . $result['requestsRemaining']
                . ', retry-after: ' . $result['retryAfter']
                . ', limit: ' . $result['requestLimit']
            );

            // Add headers if configured:
            if ($this->config['Policies'][$policyId]['addHeaders'] ?? false) {
                $headers = $event->getResponse()->getHeaders();
                $headers->addHeaders(
                    [
                        'X-RateLimit-Limit' => $result['requestLimit'],
                        'X-RateLimit-Remaining' => $result['requestsRemaining'],
                        'X-RateLimit-Reset' => $result['retryAfter'],
                    ]
                );
                if (!$limit->isAccepted()) {
                    $headers->addHeaders(
                        ['Retry-After' => $result['retryAfter']]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logError(
                "Rate limiter error for {$this->eventDesc} from {$this->clientLogDetails}: "
                . $e->getMessage()
            );
        }

        return $result;
    }

    /**
     * Get policy ID for the current event
     *
     * @param MvcEvent $event Event
     *
     * @return ?string
     */
    protected function getPolicyIdForEvent(MvcEvent $event): ?string
    {
        $routeMatch = $event->getRouteMatch();
        $controller = $routeMatch?->getParam('controller') ?? '??';
        $action = $routeMatch?->getParam('action') ?? '??';

        foreach ($this->config['Policies'] ?? [] as $id => $policy) {
            if (empty($policy['controllers'])) {
                continue;
            }
            foreach ($policy['controllers'] as $pattern) {
                if (preg_match("@$pattern@", "$controller/$action")) {
                    return $id;
                }
            }
        }
        return null;
    }

    /**
     * Log debug message if verbose debugging is enabled
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function verboseDebug($msg)
    {
        if ($this->config['General']['verboseDebug'] ?? false) {
            $this->debug(
                "Rate limiter for {$this->eventDesc} from {$this->clientLogDetails}: $msg"
            );
        }
    }
}
