<?php

/**
 * Shibboleth authentication module.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2014.
 * Copyright (C) The National Library of Finland 2016.
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
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Bernd Oberknapp <bo@ub.uni-freiburg.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace UChicago\Auth;

use Laminas\Http\PhpEnvironment\Request;
use VuFind\Auth\Shibboleth\ConfigurationLoaderInterface;
use VuFind\Exception\Auth as AuthException;

/**
 * Shibboleth authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Bernd Oberknapp <bo@ub.uni-freiburg.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Shibboleth extends \VuFind\Auth\Shibboleth
{

    /**
     * Get the URL to establish a session (needed when the internal VuFind login
     * form is inadequate).  Returns false when no session initiator is needed.
     *
     * @param string $target Full URL where external authentication method should
     * send user after login (some drivers may override this).
     *
     * @return bool|string
     */
    public function getSessionInitiator($target)
    {
        $config = $this->getConfig();
        $authtype = $config->Shibboleth->authtype ?? 'Shibboleth';
        if ( $authtype == "OpenIDC" ) {
            $shibTarget = $config->Shibboleth->target ?? $target;
            $append = (strpos($shibTarget, '?') !== false) ? '&' : '?';
            // Adding the auth_method parameter makes it possible to handle logins when
            // using an auth method that proxies others.
            $sessionInitiator = $config->Shibboleth->login
                . '?target_link_uri=' . urlencode($shibTarget)
                . urlencode($append . 'auth_method=Shibboleth')
                . '&iss=' . urlencode($config->Shibboleth->provider_id);
        }
        else {
            $shibTarget = $config->Shibboleth->target ?? $target;
            $append = (strpos($shibTarget, '?') !== false) ? '&' : '?';
            // Adding the auth_method parameter makes it possible to handle logins when
            // using an auth method that proxies others.
            $sessionInitiator = $config->Shibboleth->login
                . '?target=' . urlencode($shibTarget)
                . urlencode($append . 'auth_method=Shibboleth');

            if (isset($config->Shibboleth->provider_id)) {
                $sessionInitiator = $sessionInitiator . '&entityID=' .
                    urlencode($config->Shibboleth->provider_id);
            }
        }
        return $sessionInitiator;
    }

}
