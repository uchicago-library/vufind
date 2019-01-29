<?php
/**
 * Functions to add advanced MARC-driven functionality to a record driver already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2017.
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
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace UChicago\RecordDriver;

use UChicago\View\Helper\Phoenix\RecordLink;

/**
 * Functions to add advanced MARC-driven functionality to a record driver already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait MarcAdvancedTrait
{
    /**
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return array|bool                 Array on success, boolean false if no
     * valid link could be found in the data.
     */
    protected function getFieldData($field)
    {

        // Configuration
        $config = [
            '760' => 'abcdghimnostxy',
            '762' => 'abcdghimnostxy',
            '765' => 'abcdghikmnorstuxyz',
            '767' => 'abcdghikmnorstuxyz',
            '770' => 'abcdghikmnorstuxyz',
            '772' => 'abcdghikmnorstuxyz',
            '773' => 'abstx',
            '774' => 'abstx ',
            '775' => 'abcdefghikmnorstuxyz',
            '776' => 'abcdghikmnorstuxyz',
            '777' => 'abcdghikmnostxy',
            '786' => 'abcdhijkmnoprstuxy',
            '787' => 'abcdghikmnorstuxyz',
        ];


        // Get subfields from config if possible, otherwise use fallback
        $tag = $field->getTag();
        $subfields = str_split('ast');
        if (array_key_exists($tag, $config)) {
            $subfields = str_split($config[$tag]);
        }

        // Build the title out of subfields
        $title = '';
        foreach($subfields as $subfield) {
            if ($t = $field->getSubfield($subfield)) {
                $title .= ' ' . $t->getData();
            }
        }

        // Bail if there isn't a title
        if (empty($title)) {
            return false;
        }

        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types
            : 'id,oclc,dlc,isbn,issn,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $field->getSubfields('w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)) {
            case 'icu':
                foreach ($linkFields as $current) {
                    if ($icu = $this->getIdFromLinkingField($current, 'ICU', true)) {
                        // Bail if we have bad data, e.g. (ICU)BID=4430978.
                        if (!is_numeric($icu)) {
                            $linkType = '';
                            continue;
                        }
                        $link = ['type' => 'icu', 'value' => $icu];
                    }
                }
                break;
            case 'oclc':
                foreach ($linkFields as $current) {
                    if ($oclc = $this->getIdFromLinkingField($current, 'OCoLC')) {
                        $link = ['type' => 'oclc', 'value' => $oclc];
                    }
                }
                break;
            case 'dlc':
                foreach ($linkFields as $current) {
                    if ($dlc = $this->getIdFromLinkingField($current, 'DLC', true)) {
                        $link = ['type' => 'dlc', 'value' => $dlc];
                    }
                }
                break;
            case 'id':
                foreach ($linkFields as $current) {
                    if ($bibLink = $this->getIdFromLinkingField($current)) {
                        $link = ['type' => 'bib', 'value' => $bibLink];
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $field->getSubfield('z')) {
                    $link = [
                        'type' => 'isn', 'value' => trim($isbn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'issn':
                if ($issn = $field->getSubfield('x')) {
                    $link = [
                        'type' => 'isn', 'value' => trim($issn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'title':
                $link = ['type' => 'title', 'value' => $title];
                break;
            case 'none':
                $link = ['type' => 'none', 'value' => $title];
                break;
            }
            // Exit loop if we have a link
            if (isset($link)) {
                break;
            }
        }
        // Make sure we have something to display:
        return !isset($link) ? false : [
            'title' => $this->getRecordLinkNote($field),
            'value' => $title,
            'link'  => $link
        ];
    }
}
