<?php
/**
 * ISO2709 MARC exchange format support class.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @package  MARC
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace UChicago\Marc\Serialization;

/**
 * ISO2709 exchange format support class.
 *
 * @category VuFind
 * @package  MARC
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class Iso2709 extends \VuFind\Marc\Serialization\Iso2709 implements \VuFind\Marc\Serialization\SerializationInterface 
{
    /**
     * Parse MARCXML string
     *
     * @param string $marc MARCXML
     *
     * @throws Exception
     * @return array
     */
    public static function fromString(string $marc): array
    {
        $leader = substr($marc, 0, 24);
        $fields = [];
        $dataStart = 0 + (int)substr($marc, 12, 5);
        $dirLen = $dataStart - self::LEADER_LEN - 1;

        $offset = 0;
        while ($offset < $dirLen) {
            $tag = substr($marc, self::LEADER_LEN + $offset, 3);
            $len = (int)substr($marc, self::LEADER_LEN + $offset + 3, 4);
            $dataOffset
                = (int)substr($marc, self::LEADER_LEN + $offset + 7, 5);

            $tagData = substr($marc, $dataStart + $dataOffset, $len);

            if (substr($tagData, -1, 1) == self::END_OF_FIELD) {
                $tagData = substr($tagData, 0, -1);
            } else {
                ### UChicago customization ###
                // Commented out to allow MARC records over 99,999 characters display
                // This can probably be eliminated after upgrade to VuFind 9.0 along with related overrides
                // in UChicago/Marc/MarcReader.php and UChicago/RecordDriver/SolrMarc.php
                //throw new \Exception(
                //    "Invalid MARC record (end of field not found): $marc"
                //);
                ### ./UChicago customization ###
            }

            if (ctype_digit($tag) && $tag < 10) {
                $fields[$tag][] = $tagData;
            } else {
                $newField = [
                    'i1' => $tagData[0] ?? ' ',
                    'i2' => $tagData[1] ?? ' '
                ];
                $subfields = explode(
                    self::SUBFIELD_INDICATOR, substr($tagData, 3)
                );
                foreach ($subfields as $subfield) {
                    if ('' === $subfield) {
                        continue;
                    }
                    $newField['s'][] = [
                        (string)$subfield[0] => substr($subfield, 1)
                    ];
                }
                $fields[$tag][] = $newField;
            }

            $offset += 12;
        }

        return [$leader, $fields];
    }
}
