<?php
/**
 * Default Controller
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
use Zend\Stdlib\Parameters;

/**
 * Override some default behavior in the SearchController.
 *
 * @category VuFind2
 * @package  Controller
 * @author   John Jung <jej@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://catalog.lib.uchicago.edu
 */
class SearchController extends \VuFind\Controller\SearchController
{
    /**
     * Advanced search is the default. 
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function homeAction()
    {
        return $this->forwardTo('Search', 'Advanced');
    }

    /**
     * Send search results to results view
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function resultsAction()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');

        $view = $this->createViewModel();

        // Handle saved search requests:
        $savedId = $this->params()->fromQuery('saved', false);
        if ($savedId !== false) {
            return $this->redirectToSavedSearch($savedId);
        }

        $results = $this->getResultsManager()->get($this->searchClassId);
        $params = $results->getParams();
        $params->recommendationsEnabled($this->getActiveRecommendationSettings());

        // Send both GET and POST variables to search class:
        $params->initFromRequest(
            new Parameters(
                $this->getRequest()->getQuery()->toArray()
                + $this->getRequest()->getPost()->toArray()
            )
        );

        // Make parameters available to the view:
        $view->params = $params;

        // Attempt to perform the search; if there is a problem, inspect any Solr
        // exceptions to see if we should communicate to the user about them.
        try {
            // Explicitly execute search within controller -- this allows us to
            // catch exceptions more reliably:
            $results->performAndProcessSearch();

            if (isset($config->Record->jump_to_single_search_result)  
                && $config->Record->jump_to_single_search_result  
                && $results->getResultTotal() == 1
            ) {
                $jumpto = 1;
            } else {
                $jumpto = null;
            }
            
            // If a "jumpto" parameter is set, deal with that now:
            if ($jump = $this->processJumpTo($results, $jumpto)) {
                return $jump;
            }

            // Send results to the view and remember the current URL as the last
            // search.
            $view->results = $results;
            $this->rememberSearch($results);

            // Add to search history:
            if ($this->saveToHistory) {
                $user = $this->getUser();
                $sessId = $this->getServiceLocator()->get('VuFind\SessionManager')
                    ->getId();
                $history = $this->getTable('Search');
                $history->saveSearch(
                    $this->getResultsManager(), $results, $sessId, isset($user->id) ? $user->id : null
                );
            }

            // Set up results scroller:
            if ($this->resultScrollerActive()) {
                $this->resultScroller()->init($results);
            }
        } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
            if ($e->hasTag('VuFind\Search\ParserError')) {
                // If it's a parse error or the user specified an invalid field, we
                // should display an appropriate message:
                $view->parseError = true;

                // We need to create and process an "empty results" object to
                // ensure that recommendation modules and templates behave
                // properly when displaying the error message.
                $view->results = $this->getResultsManager()->get('EmptySet');
                $view->results->setParams($params);
                $view->results->performAndProcessSearch();
            } else {
                throw $e;
            }
        }
        // Save statistics:
        if ($this->logStatistics) {
            $this->getServiceLocator()->get('VuFind\SearchStats')
                ->log($results, $this->getRequest());
        }

        // Special case: If we're in RSS view, we need to render differently:
        if (isset($view->results)
            && $view->results->getParams()->getView() == 'rss'
        ) {
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-type', 'text/xml');
            $feed = $this->getViewRenderer()->plugin('resultfeed');
            $response->setContent($feed($view->results)->export('rss'));
            return $response;
        }

        // Search toolbar
        $view->showBulkOptions = isset($config->Site->showBulkOptions)
          && $config->Site->showBulkOptions;

        return $view;
    }

    /**
     * Process the jumpto parameter -- either redirect to a specific record and
     * return view model, or ignore the parameter and return false.
     *
     * @param \VuFind\Search\Base\Results $results Search results object.
     * @param null|int                    $jumpto  Jump to this search result number. 
     *
     * @return bool|\Zend\View\Model\ViewModel
     */
    protected function processJumpTo($results, $jumpto = null)
    {
        if ($jumpto === null) {
            // Missing/invalid parameter?  Ignore it:
            $jumpto = $this->params()->fromQuery('jumpto');
            if (empty($jumpto) || !is_numeric($jumpto)) {
                return false;
            }
        }

        // Parameter out of range?  Ignore it:
        $recordList = $results->getResults();
        if (!isset($recordList[$jumpto - 1])) {
            return false;
        }

        // If we got this far, we have a valid parameter so we should redirect
        // and report success:
        $details = $this->getRecordRouter()
            ->getTabRouteDetails($recordList[$jumpto - 1]);
        return $this->redirect()->toRoute($details['route'], $details['params']);
    }
}
