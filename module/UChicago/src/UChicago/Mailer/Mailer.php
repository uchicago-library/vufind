<?php
namespace UChicago\Mailer;
use VuFind\Exception\Mail as MailException,
    Zend\Mail\Message,
    Zend\Mail\Header\ContentType;

class Mailer extends \VuFind\Mailer\Mailer
{
    public function sendRecord($to, $from, $msg, $record, $view)
    {
        $subject = $record->getBreadcrumb();
        $body = $view->partial(
            'Email/record.phtml',
            array(
                'driver' => $record, 'to' => $to, 'from' => $from, 'message' => $msg
            )
        );
        return $this->send($to, $from, $subject, $body);
    }
}
