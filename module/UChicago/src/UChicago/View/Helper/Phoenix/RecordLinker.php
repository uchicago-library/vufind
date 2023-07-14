<?php

/**
 * Record linker view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace UChicago\View\Helper\Phoenix;

use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Record linker view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordLinker extends \VuFind\View\Helper\Root\RecordLinker
{
    /**
     * UChicago customization: modified only to add support for ICU numbers.
     * Given an array representing a related record (which may be a bib ID or OCLC
     * number), this helper renders a URL linking to that record.
     *
     * @param array  $link   Link information from record model
     * @param string $source Source ID for backend being used to retrieve records
     *
     * @return string       URL derived from link information
     */
    public function related($link, $source = DEFAULT_SEARCH_BACKEND)
    {
        $urlHelper = $this->getView()->plugin('url');
        $baseUrl = $urlHelper($this->getSearchActionForSource($source));
        switch ($link['type']) {
        ### UChicago customization ###
        case 'icu':
            return $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
        ### ./UChicago customization ###
        case 'bib':
            return $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
        case 'dlc':
            return $baseUrl
                . '?lookfor=' . urlencode('"' . $link['value'] . '"')
                . '&type=lccn&jumpto=1';
        case 'isn':
            return $baseUrl
                . '?join=AND&bool0[]=AND&lookfor0[]=%22'
                . urlencode($link['value'])
                . '%22&type0[]=isn&bool1[]=NOT&lookfor1[]=%22'
                . urlencode($link['exclude'])
                . '%22&type1[]=id&sort=title&view=list';
        case 'oclc':
            return $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=oclc_num&jumpto=1';
        case 'title':
            return $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=title';
        }
        throw new \Exception('Unexpected link type: ' . $link['type']);
    }
}
