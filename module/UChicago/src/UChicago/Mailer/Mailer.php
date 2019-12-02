<?php
namespace UChicago\Mailer;
use VuFind\Exception\Mail as MailException,
    Zend\Mail\Message,
    Zend\Mail\Header\ContentType;

class Mailer extends \VuFind\Mailer\Mailer
{

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
