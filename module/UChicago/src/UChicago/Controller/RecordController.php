<?php
namespace UChicago\Controller;

class RecordController extends \VuFind\Controller\RecordController
{
    /* 
     * Overridden so we can set a subject line in the email.
     */
    public function emailAction()
    {
        // Force login if necessary:
        $config = $this->getConfig();
        if ((!isset($config->Mail->require_login) || $config->Mail->require_login)
            && !$this->getUser()
        ) {
            return $this->forceLogin();
        }

        // Retrieve the record driver:
        $driver = $this->loadRecord();

        // Process form submission:
        $view = $this->createEmailViewModel();
        if ($this->params()->fromPost('submit')) {
            // Attempt to send the email and show an appropriate flash message:
            try {
                $this->getServiceLocator()->get('VuFind\Mailer')->sendRecord(
                    $view->to, $view->from, $view->message, $driver,
                    $this->getViewRenderer()
                );
                $this->flashMessenger()->setNamespace('info')
                    ->addMessage('email_success');
                return $this->redirectToRecord();
            } catch (MailException $e) {
                $this->flashMessenger()->setNamespace('error')
                    ->addMessage($e->getMessage());
            }
        }

        // Display the template:
        $view->setTemplate('record/email');
        
        $view->subject = 'Library Catalog Record: ' . $driver->getBreadcrumb();

        return $view;
    }
}
