<?php
/**
 * Reviews tab
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   John Jung <jej@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
namespace UChicago\RecordTab;
use VuFind\Content\Loader;

/**
 * Reviews tab
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   John Jung <jej@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
abstract class AbstractContent extends \VuFind\RecordTab\AbstractContent
{
    /**
     * Get content for ISBN.
     *
     * @return array
     */
    public function getContent()
    {
        if (null === $this->results) {
            if (null === $this->loader) {
                $this->results = [];
            } else {
                foreach ($this->getRecordDriver()->getISBNs() as $isbn) {
                    $this->results = $this->loader->loadByIsbn($isbn);
                    if (!empty($this->results)) {
                        break;
                    }
                }
            }
        }
        return $this->results;
    }
}
