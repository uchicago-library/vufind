<?php
/* This code is only needed until we upgrade to VuFind 11 or later. Look at closed issue #169 and the pull request for branch 169-backport-rate-limiter-and-cloudfare-turnstile to see the full list of files that can be removed after we upgrade. */
/**
 * VuFind controller base class (defines some methods that can be shared by other
 * controllers).
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
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace UChicago\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\ILS as ILSException;
use VuFind\Http\PhpEnvironment\Request as HttpRequest;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * VuFind controller base class (defines some methods that can be shared by other
 * controllers).
 *
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 *
 * @method Plugin\Captcha captcha() Captcha plugin
 * @method Plugin\DbUpgrade dbUpgrade() DbUpgrade plugin
 * @method Plugin\Favorites favorites() Favorites plugin
 * @method FlashMessenger flashMessenger() FlashMessenger plugin
 * @method Plugin\Followup followup() Followup plugin
 * @method Plugin\Holds holds() Holds plugin
 * @method Plugin\ILLRequests ILLRequests() ILLRequests plugin
 * @method Plugin\IlsRecords ilsRecords() IlsRecords plugin
 * @method Plugin\NewItems newItems() NewItems plugin
 * @method Plugin\Permission permission() Permission plugin
 * @method Plugin\Renewals renewals() Renewals plugin
 * @method Plugin\Reserves reserves() Reserves plugin
 * @method Plugin\ResultScroller resultScroller() ResultScroller plugin
 * @method Plugin\StorageRetrievalRequests storageRetrievalRequests()
 * StorageRetrievalRequests plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class AbstractBase extends \VuFind\Controller\AbstractBase 
{
    // Nothing needed
}
