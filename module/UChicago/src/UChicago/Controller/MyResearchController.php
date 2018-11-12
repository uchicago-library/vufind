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

use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\Forbidden as ForbiddenException;
use VuFind\Exception\ILS as ILSException;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\Mail as MailException;
use VuFind\Search\RecommendListener;
use Zend\Stdlib\Parameters;
use Zend\View\Model\ViewModel;

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
     * Process an authentication error.
     *
     * @param AuthException $e Exception to process.
     *
     * @return void
     */
    protected function processAuthenticationException(AuthException $e)
    {
        $msg = $e->getMessage();
        // If a Shibboleth-style login has failed and the user just logged
        // out, we need to override the error message with a more relevant
        // one:
        if ($msg == 'authentication_error_admin'
            && $this->getAuthManager()->userHasLoggedOut()
            && $this->getSessionInitiator()
        ) {
            $msg = 'authentication_error_loggedout';
        }
        $this->flashMessenger()->addMessage($msg, 'error');
    }

    /*
     * The add action takes a GET request. That is potentially strange,
     * because it changes data on the server. The correct way to do it
     * is to at least do POST requests, because we're changing or
     * altering something. 
     */

    public function storageRequestAction() 
    {
        $config = $this->getConfig();

        $user = $this->getUser();
        if ($user == false) {
            return $this->forceLogin();
        }
 
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            // do something.
        }

        $catalog = $this->getILS();

        $storagerequest = new \UChicago\StorageRequest\StorageRequest($config, $user->cat_username);

        // Get bib number and barcode number from URL params.
        $bib = $this->params()->fromQuery('bib');
        $barcode = $this->params()->fromQuery('barcode');

        $action = $this->params()->fromQuery('action');
        if (!$action) {
            $action = $this->params()->fromPost('action');
        }

        switch($action) {
            case 'add':
                $status = '';                
	            foreach ($catalog->getHolding($bib) as $entry) {
	                // was using this, but it wasn't able to work with
	                // issues in a serial where only some of them were
	                // available at Mansueto. if the last ones didn't
	                // have that status it would fail. 
		            //if (strpos(str_replace(" ", "", $entry['number']), $copy) === 0) {
	                if (isset($entry['barcode']) && $entry['barcode'] == $barcode) {
		                $status = $entry['status'];
		            }
	            }

	            if ($status == 'AVAILABLE-AT-MANSUETO') {
	                $storagerequest->addRequest($bib, $barcode, $catalog);
	            } else if ($status == 'LOANED') {
	                $this->flashMessenger()->setNamespace('error')->addMessage('This item is not available for request because it is currently on loan.');
	            } else {
	                $this->flashMessenger()->setNamespace('error')->addMessage('This item is not available for request.');
	            }
	            break;
	        case 'remove':
	            foreach ($this->params()->fromPost('remove') as $bib_barcode_pair) {
	                list($this_bib, $this_barcode) = explode("|", $bib_barcode_pair);
	                $storagerequest->removeRequest($this_bib, $this_barcode);
	            }
	            break;
	        case 'request':
                /*
                JEJ HACK 
                code to get this stuff from the config file will look something like this:
                foreach ($config->PickupLocations->locations as $location) {
                    list($code, $desc) = explode(":", $location);
                    printf("%s %s\n", $code, $desc);
                }
                printf("%s\n", $config->PickupLocations->defaultLocationDescription);
                */

	            if ($this->params()->fromPost('location') == '') {
	                $this->flashMessenger()->setNamespace('error')->addMessage('Please select a pickup location from the pulldown below.');
	            } else {
	                $storagerequest->placeRequest($this->params()->fromPost('barcode'), $this->params()->fromPost('bib'), $this->params()->fromPost('location')); 
	                $pickup_info = '';

                    // Must be defined after placeRequest is called
                    $failures = $storagerequest->getFailures();
                    $victories = $storagerequest->getVictories();

	                switch ($this->params()->fromPost('location')) {
	                    case 'CRERAR':
	                        $pickup_info = 'Crerar within 1 business day';
	                        break;
	                    case 'ECKHART':
	                        $pickup_info = 'Eckhart within 1 business day';
	                        break;
	                    case 'LAW':
	                        $pickup_info = 'D\'Angelo within 1 business day';
	                        break;
	                    case 'SSAd':
	                        $pickup_info = 'SSA within 1 business day';
	                        break;
	                    default:
	                        $pickup_info = 'Mansueto within 15 min during open hours';
	                        break;
	                }

                    if ($failures) {
                        if ($victories) {
                            $this->flashMessenger()->setNamespace('fail')->addMessage('Some of your requests could not be processed. Please see above.', 'info');
                        }
                        foreach ($failures as $failure) {
                            $this->flashMessenger()->setNamespace('fail')->addMessage($failure[1], 'error');
                        }
                    }
                    if ($victories && !$failures) {
                        foreach ($victories as $victory) {
                            $this->flashMessenger()->setNamespace('info')->addMessage('Your request is being processed and will be available for pickup at (' . $pickup_info . ') and held for you for 7 days.', 'success');
                        }
                    }
                }
                break;
        }

	    return $this->createViewModel(
	        ['storagerequests' => $storagerequest->getRequests()]
	    );
    }


    /**
     * Send list of checked out books to view
     * Overriding VuFind core so we can return a flag for detecting
     * if any of the renewal requests failed. This allows us to display
     * the appropriate message at the top of the page and saves us an 
     * extra loop in the template. We also have custom sorting options,
     * alert messages, and labels.
     *
     * @return mixed
     */
    public function checkedoutAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $sort = $this->params()->fromPost('sort');
        if (!$sort) {
            $sort = 'duedate';
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Display account blocks, if any:
        $this->addAccountBlocksToFlashMessenger($catalog, $patron);

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(), $catalog, $patron
            )
            : [];

        // UChicago customization:
        // We return this as part of the return value of the
        // method. Set a flag for passing to the template.
        // This will tell us which alert message to display.
        $failure = false;
        foreach ($renewResult as $entry) {  
            if ($entry['success'] == false) {
                $failure = true;
                break;
            }
        }

        // By default, assume we will not need to display a renewal form:
        $renewForm = false;

        // Get checked out item details:
        $result = $catalog->getMyTransactions($patron);

        // Sort results. 
        $sort_by = [];
        $search_types = [0 => 'loanedDate', 1 => 'duedate'];
        // convert dates like 1/1/2017 into something sortable.
        if ($sort == $search_types[0] || $sort == $search_types[1]) {
            foreach ($result as $r) {
                $d = explode('/', trim($r[$sort]));
                $sort_by[] = sprintf("%04d%02d%02d %s", $d[2], $d[0], $d[1], trim($r['title']));
            }
        } else {
            foreach ($result as $r) {
                $sort_by[] = strtolower(trim($r[$sort]));
            }
        }
        array_multisort($sort_by, SORT_ASC, $result);

        // Get page size:
        $config = $this->getConfig();
        $limit = isset($config->Catalog->checked_out_page_size)
            ? $config->Catalog->checked_out_page_size : 50;

        // Build paginator if needed:
        if ($limit > 0 && $limit < count($result)) {
            $adapter = new \Zend\Paginator\Adapter\ArrayAdapter($result);
            $paginator = new \Zend\Paginator\Paginator($adapter);
            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($limit) - 1;
        } else {
            $paginator = false;
            $pageStart = 0;
            $pageEnd = count($result);
        }

        $transactions = $hiddenTransactions = [];
        $itemsOverdue = false;
        $itemsDueSoon = false;
        $claimsReturned = false;
        $isLost = false;
        $recalled = false;
        foreach ($result as $i => $current) {
            // UChicago customization
            // Test if items are due soon or overdue
            // Set the approptiate variables and flags
            if ($current['overdue']) {
                $itemsOverdue =  true;
            }
            if ($current['duesoon']) {
                $itemsDueSoon = true; 
            }
            if ($current['claimsReturned']) {
                $claimsReturned = true; 
            }
            if ($current['isLost']) {
                $isLost = true; 
            }
            if ($current['recalled']) {
                $recalled = true; 
            }


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

            // Build record driver (only for the current visible page):
            if ($i >= $pageStart && $i <= $pageEnd) {
                $transactions[] = $this->getDriverForILSRecord($current);
            } else {
                $hiddenTransactions[] = $current;
            }
        }
        return $this->createViewModel(
            compact(
                'sort', 'transactions', 'renewForm', 'renewResult', 'paginator',
                'hiddenTransactions', 'failure', 'itemsOverdue', 'itemsDueSoon',
                'claimsReturned', 'isLost', 'recalled'
            )
        );
    }

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function holdsAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $sort = $this->params()->fromQuery('sort');
        if (!$sort) {
            $sort = 'title';
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Process cancel requests if necessary:
        $cancelStatus = $catalog->checkFunction('cancelHolds', compact('patron'));
        $view = $this->createViewModel();
        $view->cancelResults = $cancelStatus
            ? $this->holds()->cancelHolds($catalog, $patron) : [];
        // If we need to confirm
        if (!is_array($view->cancelResults)) {
            return $view->cancelResults;
        }

        // By default, assume we will not need to display a cancel form:
        $view->cancelForm = false;

        // Get held item details:
        $result = $catalog->getMyHolds($patron);
        $recordList = [];
        $this->holds()->resetValidation();
        foreach ($result as $current) {
            // Add cancel details if appropriate:
            $current = $this->holds()->addCancelDetails(
                $catalog, $current, $cancelStatus
            );
            if ($cancelStatus && $cancelStatus['function'] != "getCancelHoldLink"
                && isset($current['cancel_details'])
            ) {
                // Enable cancel form if necessary:
                $view->cancelForm = true;
            }

            // Build record driver:
            $recordList[] = $this->getDriverForILSRecord($current);
        }

        // Sort results. 
        $sort_by = [];
        foreach ($recordList as $r) {
            $ilsDetails = $r->getExtraDetail('ils_details');
            $title = strtolower(trim($r->getTitle()));
            switch ($sort) {
                case 'author':
                    $primaryAuthors = $r->getPrimaryAuthors();
                    if ($primaryAuthors) {
                        $sort_by[] = strtolower(trim($primaryAuthors[0]));
                    } else {
                        $sort_by[] = 'ZZZ';
                    }
                    break;
                case 'callNumber':
                    $callNumbers = $r->getCallNumbers();
                    if ($callNumbers) {
                        $sort_by[] = strtolower(trim($callNumbers[0]));
                    } else {
                        $sort_by[] = 'ZZZ';
                    }
                    break;
                case 'holdExpirationDate':
                    if ($ilsDetails['hold_until_date']) {
                        $s = $ilsDetails['hold_until_date'] . $title;
                    }
                    else {
                        $s = (string)INF . $title;
                    }
                    $sort_by[] = $s;
                    break;
                case 'itemStatus':
                    if ($ilsDetails['available']) {
                        $sort_by[] = 'available';
                    } else if ($ilsDetails['in_transit']) {
                        $sort_by[] = 'in_transit';
                    } else {
                        $sort_by[] = 'ZZZ';
                    }
                    break;
                case 'title':
                    $sort_by[] = $title;
                    break;
                default:
                    $sort_by[] = 'a';
            }
        }
        array_multisort($sort_by, SORT_ASC, $recordList);

        // Get List of PickUp Libraries based on patron's home library
        try {
            $view->pickup = $catalog->getPickUpLocations($patron);
        } catch (\Exception $e) {
            // Do nothing; if we're unable to load information about pickup
            // locations, they are not supported and we should ignore them.
        }
        $view->recordList = $recordList;
        $view->sort = $sort;
        return $view;
    }

    /**
     * Send list of fines to view
     *
     * @return mixed
     */
    public function finesAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $sort = $this->params()->fromQuery('sort');
        if (!$sort) {
            $sort = 'title';
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Get fine details:
        $result = $catalog->getMyFines($patron);
        $fines = [];
        foreach ($result as $row) {
            // Attempt to look up and inject title:
            try {
                if (!isset($row['id']) || empty($row['id'])) {
                    throw new \Exception();
                }
                $source = isset($row['source'])
                    ? $row['source'] : DEFAULT_SEARCH_BACKEND;
                $row['driver'] = $this->serviceLocator
                    ->get('VuFind\RecordLoader')->load($row['id'], $source);
                $row['title'] = $row['driver']->getShortTitle();
            } catch (\Exception $e) {
                if (!isset($row['title'])) {
                    $row['title'] = null;
                }
            }
            $fines[] = $row;
        }

        // Sort results. 
        $sort_by = [];
        foreach ($fines as $f) {
            if ($sort == 'amount') {
                $sort_by[] = (int)trim($f['amount']);
            } else {
                $sort_by[] = sprintf("%s %s", strtolower(trim($f[$sort])), strtolower(trim($f['title'])));
            }
        }
        array_multisort($sort_by, SORT_ASC, $fines);

        return $this->createViewModel(['fines' => $fines, 'sort' => $sort]);
    }

}

