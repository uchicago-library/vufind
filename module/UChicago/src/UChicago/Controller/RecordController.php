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

        // Create view
        $mailer = $this->getServiceLocator()->get('VuFind\Mailer');
        $view = $this->createEmailViewModel(
            null, $mailer->getDefaultRecordSubject($driver)
        );
        $mailer->setMaxRecipients($view->maxRecipients);

        // UChicago - only difference
        $view->subject = 'Library Catalog Record: ' . $driver->getBreadcrumb();
        // End UChicago

        // Set up reCaptcha
        $view->useRecaptcha = $this->recaptcha()->active('email');
        // Process form submission:
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            // Attempt to send the email and show an appropriate flash message:
            try {
                $cc = $this->params()->fromPost('ccself') && $view->from != $view->to
                    ? $view->from : null;
                $mailer->sendRecord(
                    $view->to, $view->from, $view->message, $driver,
                    $this->getViewRenderer(), $view->subject, $cc
                );
                $this->flashMessenger()->addMessage('email_success', 'success');
                return $this->redirectToRecord();
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
        }

        // Display the template:
        $view->setTemplate('record/email');
        return $view;
    }
}
