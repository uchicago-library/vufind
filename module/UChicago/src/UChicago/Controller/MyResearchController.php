<?php
/**
 * MyResearch Controller
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

use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\AuthEmailNotVerified as AuthEmailNotVerifiedException;
use VuFind\Exception\AuthInProgress as AuthInProgressException;
use VuFind\Exception\Forbidden as ForbiddenException;
use VuFind\Exception\ILS as ILSException;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\Mail as MailException;
use VuFind\ILS\PaginationHelper;
use VuFind\Search\RecommendListener;

/**
 * Controller for the user account area.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class MyResearchController extends \VuFind\Controller\MyResearchController
{

    /**
     * Send list of checked out books to view
     *
     * @return mixed
     */
    public function checkedoutAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Display account blocks, if any:
        $this->addAccountBlocksToFlashMessenger($catalog, $patron);

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(),
                $catalog,
                $patron,
                $this->serviceLocator->get(\VuFind\Validator\Csrf::class)
            )
            : [];

        // By default, assume we will not need to display a renewal form:
        $renewForm = false;

        // Get paging setup:
        $config = $this->getConfig();
        $pageOptions = $this->getPaginationHelper()->getOptions(
            (int)$this->params()->fromQuery('page', 1),
            $this->params()->fromQuery('sort'),
            $config->Catalog->checked_out_page_size ?? 50,
            $catalog->checkFunction('getMyTransactions', $patron)
        );

        // Get checked out item details:
        $result = $catalog->getMyTransactions($patron, $pageOptions['ilsParams']);

        // Build paginator if needed:
        $paginator = $this->getPaginationHelper()->getPaginator(
            $pageOptions, $result['count'], $result['records']
        );
        if ($paginator) {
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($pageOptions['limit']) - 1;
        } else {
            $pageStart = 0;
            $pageEnd = $result['count'];
        }

        // If the results are not paged in the ILS, collect up to date stats for ajax
        // account notifications:
        if ((!$pageOptions['ilsPaging'] || !$paginator)
            && !empty($this->getConfig()->Authentication->enableAjax)
        ) {
            $accountStatus = [
                'ok' => 0,
                'warn' => 0,
                'overdue' => 0
            ];
        } else {
            $accountStatus = null;
        }

        $driversNeeded = $hiddenTransactions = [];
        foreach ($result['records'] as $i => $current) {
            // Add renewal details if appropriate:
            $current = $this->renewals()->addRenewDetails(
                $catalog, $current, $renewStatus
            );
            if ($renewStatus && !isset($current['renew_link'])
                && $current['renewable']
            ) {
                // Enable renewal form if necessary:
                $renewForm = true;
            }

            if (null !== $accountStatus) {
                switch ($current['dueStatus'] ?? '') {
                case 'due':
                    $accountStatus['warn']++;
                    break;
                case 'overdue':
                    $accountStatus['overdue']++;
                    break;
                default:
                    $accountStatus['ok']++;
                    break;
                }
            }

            // Build record drivers (only for the current visible page):
            if ($pageOptions['ilsPaging'] || ($i >= $pageStart && $i <= $pageEnd)) {
                $driversNeeded[] = $current;
            } else {
                $hiddenTransactions[] = $current;
            }
        }

        $transactions = $this->ilsRecords()->getDrivers($driversNeeded);
        $hasRecalls = $this->hasRecallsUC($transactions);

        $displayItemBarcode
            = !empty($config->Catalog->display_checked_out_item_barcode);

        $ilsPaging = $pageOptions['ilsPaging'];
        $sortList = $pageOptions['sortList'];
        $params = $pageOptions['ilsParams'];
        return $this->createViewModel(
            compact(
                'transactions', 'renewForm', 'renewResult', 'paginator', 'ilsPaging',
                'hiddenTransactions', 'displayItemBarcode', 'sortList', 'params',
                'accountStatus', 'hasRecalls'
            )
        );
    }

    /**
     * Determines if any transactions have a recall associated with them.
     *
     * @param $transactions array
     *
     * @return bool
     */
    protected function hasRecallsUC($transactions)
    {
        foreach ($transactions as $resource) {
            $ilsDetails = $resource->getExtraDetail('ils_details');
            if ($ilsDetails['recalled'] == true) {
                return true;
            }
        }
        return false;
    }
}
