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

    public function sendRecords($to, $from, $msg, $records, $view)
    {
        $subject = $this->translate('Library Catalog Records');

        $body = '';
        for ($r = 0; $r < count($records); $r++) {
            if ($r == count($records) - 1) {
                $m = $msg;
            } else {
                $m = '';
            }
            $body .= $view->partial(
                'Email/record.phtml',
                array(
                    'driver' => $records[$r], 'to' => $to, 'from' => $from, 'message' => $m
                )
            );
        }
        return $this->send($to, $from, $subject, $body);
    }
}
