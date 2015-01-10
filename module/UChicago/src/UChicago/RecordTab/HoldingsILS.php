<?php
/**
 * Holdings (ILS) tab
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  RecordTabs
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_tabs Wiki
 */
namespace UChicago\RecordTab;

/**
 * Holdings (ILS) tab
 *
 * @category VuFind2
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_tabs Wiki
 */
class HoldingsILS extends \VuFind\RecordTab\HoldingsILS
{

    /**
     * Support method used by template -- extract all unique call numbers from
     * an array of items.
     *
     * @param array $items Items to search through.
     *
     * @return array
     */
    public function getUniqueCallNumbersPhoenix($items, $display=false)
    {
        $callNos = array();
        foreach ($items as $item) {
            if (isset($item['callnumberDisplay']) && strlen($item['callnumberDisplay']) > 0) {
                $callNos['display'][] = $item['callnumberDisplay'];
                $callNos['sort'][] = $item['callnumber'];
            }
        }

        sort($callNos['sort']);
        return array('display' => array_unique($callNos['display']), 'sort' => $callNos['sort']);
    }

    /**
     * Gets the first barcode of a holding
     * 
     * @param array $holding
     *
     * @return string, the first barcode in the holding.
     */
    public function getFirstBarcode($holding)
    {
        $firstBarcode = '';
        foreach($holding['items'] as $item) {
            if (!empty($item['barcode'])){
                $firstBarcode = $item['barcode'];
                break;
            }
        }
        return $firstBarcode;
    }
}
