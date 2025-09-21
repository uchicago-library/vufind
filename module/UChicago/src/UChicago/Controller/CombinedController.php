<?php
/* This code might only be needed until we upgrade to VuFind 11 or later, however, it's patching something that could be broken in stock VuFind. Test after upgrade and see if this is still needed. Look at closed issue #169 and the pull request for branch 169-backport-rate-limiter-and-cloudfare-turnstile to see the full list of files that can be removed after we upgrade. */
/**
 * Combined Search Controller
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace UChicago\Controller;

use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFind\Search\SearchRunner;

/**
 * Redirects the user to the appropriate default VuFind action.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class CombinedController extends \VuFind\Controller\CombinedController
{

    /**
     * Action to process the combined search box.
     *
     * @return mixed
     */
    public function searchboxAction()
    {
        ### UChicago customization ###
        ### Patch for empty searches being routed to the combined search box by default with turnstile ###
        $type = null;
        $target = null;
        $queryType = $this->params()->fromQuery('type');
        if ($queryType !== null && $queryType !== '') {
            [$type, $target] = explode(':', $this->params()->fromQuery('type'), 2);
        }
        ### ./UChicago customization ###
        switch ($type) {
            case 'VuFind':
                [$searchClassId, $type] = explode('|', $target);
                $params = $this->getRequest()->getQuery()->toArray();
                $params['type'] = $type;

                // Disable retained filters if we are switching classes!
                $activeClass = $this->params()->fromQuery('activeSearchClassId');
                if ($activeClass != $searchClassId) {
                    unset($params['filter']);
                }
                // We don't need to pass activeSearchClassId forward:
                unset($params['activeSearchClassId']);

                $route = $this->serviceLocator
                    ->get(\VuFind\Search\Options\PluginManager::class)
                    ->get($searchClassId)->getSearchAction();
                $base = $this->url()->fromRoute($route);
                return $this->redirect()
                    ->toUrl($base . '?' . http_build_query($params));
            case 'External':
                $lookfor = $this->params()->fromQuery('lookfor');
                $finalTarget = (false === strpos($target, '%%lookfor%%'))
                    ? $target . urlencode($lookfor)
                    : str_replace('%%lookfor%%', urlencode($lookfor), $target);
                return $this->redirect()->toUrl($finalTarget);
            default:
                // If parameters are completely missing, redirect to home instead
                // of throwing an error; this is possibly a misbehaving crawler that
                // followed the SearchBox URL without passing any parameters.
                if (empty($type) && empty($target)) {
                    return $this->redirect()->toRoute('home');
                }
                // If we have a weird value here, report it as an Exception:
                throw new \VuFind\Exception\BadRequest(
                    'Unexpected search type: "' . $type . '".'
                );
        }
    }
}
