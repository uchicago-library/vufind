<?php
/**
 * MyResearch Controller
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace UChicago\Controller;

use VuFind\Exception\Auth as AuthException,
    VuFind\Exception\ListPermission as ListPermissionException,
    VuFind\Exception\RecordMissing as RecordMissingException,
    UChicago\StorageRequest\StorageRequest,
    Zend\Stdlib\Parameters;

/**
 * Controller for the user account area.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
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
        $this->flashMessenger()->setNamespace('error')->addMessage($msg);
    }

    /*
     * The add action takes a GET request. That is potentially strange,
     * because it changes data on the server. The correct way to do it
     * is to at least do POST requests, because we're changing or
     * altering something. 
     */

    public function storagerequestAction() 
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

        $storagerequest = new StorageRequest($config, $user->cat_username);

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
	                if ($entry['barcode'] == $barcode) {
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
                $this->flashMessenger()->setNamespace('info')->addMessage('Your request is being processed. Your materials will be available for pickup at (' . $pickup_info . ') and held for you for 7 days.');
                }
                break;
        }

	    return $this->createViewModel(
	        array(
	            'storagerequests' => $storagerequest->getRequests()
	        )
	    );
    }

    /**
     * Send list of checked out books to view. 
     * Overriding VuFind core so we can return a flag for detecting
     * if any of the renewal requests failed. This allows us to display
     * the appropriate message at the top of the page and saves us an 
     * extra loop in the template.
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

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(), $catalog, $patron
            )
            : array();

        // Set a flag for passing to the template.
        // This will tell us which alert message to display.
        $failure = false;
        foreach ($renewResult as $entry) {  
            if ($entry['success'] == false) {
                $failure = true;
            }
        }
        
        // By default, assume we will not need to display a renewal form:
        $renewForm = false;

        // Get checked out item details:
        $result = $catalog->getMyTransactions($patron);
        $transactions = array();
        foreach ($result as $current) {
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

            // Build record driver:
            $transactions[] = $this->getDriverForILSRecord($current);
        }

        return $this->createViewModel(
            array(
                'transactions' => $transactions, 'renewForm' => $renewForm,
                'renewResult' => $renewResult,
                'failure' => $failure,
            )
        );
    }
}

