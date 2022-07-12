<?php
/**
 * Record link view helper
 *
 * PHP version 7
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace UChicago\View\Helper\Phoenix;

/**
 * Record link view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordLink extends \VuFind\View\Helper\Root\RecordLink
{
    /**
     * UChicago customization: modified only to add support for ICU numbers.
     * Given an array representing a related record (which may be a bib ID or OCLC
     * number), this helper renders a URL linking to that record.
     *
     * @param array  $link   Link information from record model
     * @param bool   $escape Should we escape the rendered URL?
     * @param string $source Source ID for backend being used to retrieve records
     *
     * @return string       URL derived from link information
     */
    public function related($link, $escape = true, $source = DEFAULT_SEARCH_BACKEND)
    {
        $urlHelper = $this->getView()->plugin('url');
        $baseUrl = $urlHelper($this->getSearchActionForSource($source));
        switch ($link['type']) {
        ### UChicago customization ###
        case 'icu':
            $url = $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
            break;
        ### ./UChicago customization ###
        case 'bib':
            $url = $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
            break;
        case 'dlc':
            $url = $baseUrl
                . '?lookfor=' . urlencode('"' . $link['value'] . '"')
                . '&type=lccn&jumpto=1';
            break;
        case 'isn':
            $url = $baseUrl
                . '?join=AND&bool0[]=AND&lookfor0[]=%22'
                . urlencode($link['value'])
                . '%22&type0[]=isn&bool1[]=NOT&lookfor1[]=%22'
                . urlencode($link['exclude'])
                . '%22&type1[]=id&sort=title&view=list';
            break;
        case 'oclc':
            $url = $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=oclc_num&jumpto=1';
            break;
        case 'title':
            $url = $baseUrl
                . '?lookfor=' . urlencode($link['value'])
                . '&type=title';
            break;
        default:
            throw new \Exception('Unexpected link type: ' . $link['type']);
        }

        $escapeHelper = $this->getView()->plugin('escapeHtml');
        return $escape ? $escapeHelper($url) : $url;
    }

}
