<?php
/**
 * VuFind Mailer Class
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2009.
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
 * @package  Mailer
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace UChicago\Mailer;

use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use VuFind\Exception\Mail as MailException;

/**
 * VuFind Mailer Class
 *
 * @category VuFind
 * @package  Mailer
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
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
