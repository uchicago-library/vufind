<?php
/* This code is only needed until we upgrade to VuFind 11 or later. Look at closed issue #169 and the pull request for branch 169-backport-rate-limiter-and-cloudfare-turnstile to see the full list of files that can be removed after we upgrade. */
/**
 * VuFind Cache Manager
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2007
 * Copyright (C) Leipzig University Library <info@ub.uni-leipzig.de> 2018
 * Copyright (C) The National Library of Finland 2024
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace UChicago\Cache;

use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Log\LoggerAwareInterface;
use stdClass;
use UChicago\Config\Config;
use VuFind\Log\LoggerAwareTrait;

use function dirname;
use function is_array;
use function strlen;

/**
 * VuFind Cache Manager
 *
 * Creates caches based on configuration
 *
 * @category VuFind
 * @package  Cache
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Manager extends \VuFind\Cache\Manager implements LoggerAwareInterface 
{
    use LoggerAwareTrait;

    /**
     * Create an in-memory cache
     *
     * @param array $storageConfig See Storage in RateLimiter.yaml
     *
     * @return StorageInterface
     */
    public function createInMemoryCache(array $storageConfig): StorageInterface
    {
        $adapter = $storageConfig['adapter'] ?? 'memcached';

        // The 'vufind' adapter uses a standard file-based cache to simulate an in-memory cache.
        // This is intended for TESTING PURPOSES ONLY, since it allows us to test related functionality
        // without setting up a real in-memory data store. It should not be used for any other purpose.
        if ('vufind' === strtolower($adapter)) {
            $this->logWarning('Using standard cache instead of in-memory cache -- for testing only!');
            $laminasCache = $this->getCache('object', $storageConfig['options']['namespace']);
            // Fake the capabilities to include static TTL support:
            $eventManager = $laminasCache->getEventManager();
            $eventManager->attach(
                'getCapabilities.post',
                function ($event) use ($laminasCache) {
                    $oldCapacities = $event->getResult();
                    $newCapacities = new Capabilities(
                        $laminasCache,
                        new stdClass(),
                        ['staticTtl' => true],
                        $oldCapacities
                    );
                    $event->setResult($newCapacities);
                }
            );
            if ($ttl = ($storageConfig['options']['ttl'] ?? null)) {
                $laminasCache->getOptions()->setTtl($ttl);
            }
            return $laminasCache;
        }

        $options = $storageConfig['options'];
        if ('memcached' === strtolower($adapter)) {
            $options['servers'] ??= 'localhost:11211';
        }
        $settings = compact('adapter', 'options');
        $laminasCache = $this->factory->createFromArrayConfiguration($settings);
        return $laminasCache;
    }
}
