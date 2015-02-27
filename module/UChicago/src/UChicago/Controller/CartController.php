<?php

namespace UChicago\Controller;
use VuFind\Exception\Mail as MailException,
    Zend\Session\Container as SessionContainer;

/**
 * Book Bag / Bulk Action Controller
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

class CartController extends \VuFind\Controller\CartController
{
    /**
     * Email a batch of records.
     *
     * @return mixed
     */
    public function emailAction()
    {
        // Retrieve ID list:
        $ids = is_null($this->params()->fromPost('selectAll'))
            ? $this->params()->fromPost('ids')
            : $this->params()->fromPost('idsAll');

        // Retrieve follow-up information if necessary:
        if (!is_array($ids) || empty($ids)) {
            $ids = $this->followup()->retrieveAndClear('cartIds');
        }
        if (!is_array($ids) || empty($ids)) {
            return $this->redirectToSource('error', 'bulk_noitems_advice');
        }

        // Force login if necessary:
        $config = $this->getConfig();
        if ((!isset($config->Mail->require_login) || $config->Mail->require_login)
            && !$this->getUser()
        ) {
            return $this->forceLogin(
                null, array('cartIds' => $ids, 'cartAction' => 'Email')
            );
        }

        $view = $this->createEmailViewModel();
        $view->records = $this->getRecordLoader()->loadBatch($ids);

        // Process form submission:
        if ($this->formWasSubmitted('submit')) {
            // Attempt to send the email and show an appropriate flash message:
            try {
                // If we got this far, we're ready to send the email:
                $this->getServiceLocator()->get('VuFind\Mailer')->sendRecords(
                    $view->to, $view->from, $view->message,
                    $view->records, $this->getViewRenderer()
                );
                return $this->redirectToSource('info', 'email_success');
            } catch (MailException $e) {
                $this->flashMessenger()->setNamespace('error')
                    ->addMessage($e->getMessage());
            }
        }

        return $view;
    }
}
