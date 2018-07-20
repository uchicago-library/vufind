<?php
namespace UChicago\Mailer;
use VuFind\Exception\Mail as MailException,
    Zend\Mail\Message,
    Zend\Mail\Header\ContentType;

class Mailer extends \VuFind\Mailer\Mailer
{
    /**
     * Send an email message representing a record.
     *
     * @param string                            $to      Recipient email address
     * @param string|\Zend\Mail\Address         $from    Sender name and email
     * address
     * @param string                            $msg     User notes to include in
     * message
     * @param \VuFind\RecordDriver\AbstractBase $record  Record being emailed
     * @param \Zend\View\Renderer\PhpRenderer   $view    View object (used to render
     * email templates)
     * @param string                            $subject Subject for email (optional)
     * @param string                            $cc      CC recipient (null for none)
     *
     * @throws MailException
     * @return void
     */
    public function sendRecord($to, $from, $msg, $record, $view, $subject = null,
        $cc = null
    ) {
        if (null === $subject) {
            $subject = $this->getDefaultRecordSubject($record);
        }
        $body = $view->partial(
            'Email/record.phtml',
            [
                'driver' => $record, 'to' => $to, 'from' => $from, 'message' => $msg
            ]
        );
        return $this->send($to, $from, $subject, $body, $cc);
    }

    public function sendRecords($to, $from, $msg, $records, $view, $subject = null,
        $cc = null)
    {

        if (null === $subject) {
            $subject = $this->translate('Library Catalog Records');
        }

        $body = '';
        for ($r = 0; $r < count($records); $r++) {
            if ($r == count($records) - 1) {
                $m = $msg;
            } else {
                $m = '';
            }
            $body .= $view->partial(
                'Email/record.phtml',
                ['driver' => $records[$r], 'to' => $to, 'from' => $from, 'message' => $m]
            );
        }
        return $this->send($to, $from, $subject, $body, $cc);
    }
}
