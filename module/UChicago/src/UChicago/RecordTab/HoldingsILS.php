<?php
/**
 * Holdings (ILS) tab
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
 * @package  RecordTabs
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
namespace UChicago\RecordTab;

/**
 * Holdings (ILS) tab
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
class HoldingsILS extends \VuFind\RecordTab\HoldingsILS
{
    /**
     * ILS connection (or null if not applicable)
     *
     * @param Connection
     */
    protected $catalog;

    /**
     * Configuration, the number of items and holdings_text_fields to display 
     * before collapsing them. 
     *
     * @param, integer if set, blank string otherwise. 
     */
    protected $displayNum;

    /**
     * Constructor
     *
     * @param \VuFind\ILS\Connection|bool $catalog  ILS connection to use to check
     * for holdings before displaying the tab; set to null if no check is needed
     * @param string                      $template Holdings template to use
     *
     * @param $displayNum, integer or blank string, the number of items to display
     */
    public function __construct(Connection $catalog = null, $template = null, $displayNum)
    {
        $this->catalog = $catalog;
        $this->template = $template ?? 'standard';
        $this->displayNum = $displayNum;
    }


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
        $callNos = [];
        foreach ($items as $item) {
            if (isset($item['callnumberDisplay']) && strlen($item['callnumberDisplay']) > 0) {
                $callNos['display'][] = $item['callnumberDisplay'];
                $callNos['sort'][] = $item['callnumber'];
                $callNos['callnumbertypeid'][] = isset($item['callnumbertypeid']) ? $item['callnumbertypeid'] : null;
                if (array_key_exists('holdingCallNumPrefix', $item)) {
                  $callNos['holdingCallNumPrefix'] = $item['holdingCallNumPrefix'];
                } else {
                  $callNos['holdingCallNumPrefix'] = '';
                }
            }
        }

        if(count($callNos) > 0) {
            sort($callNos['sort']);
            return ['display' => array_unique($callNos['display']), 'sort' => $callNos['sort'], 'typeid' => $callNos['callnumbertypeid'], 'prefix' => $callNos['holdingCallNumPrefix']];
        }
        return [];
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

    /**
     * Gets the duedate or time for a checkedout item.
     *
     * @param array $row, information about the holding.
     *
     * @return string, the duedate or time for a loaned item. 
     */
    public function getDueDate($row)
    {
        $location = ((array_key_exists('itemLocation', $row) && $row['itemLocation']) ? $row['itemLocation'] : $row['location']);
        if ($row['duedate'] == 'Indefinite') {
            $due = 'no due date unless recalled';
        } else {
            if (strpos($location, 'Reserve') !== false || strpos($location, 'TECHB@R') !== false  || stripos($location, 'Techbar') !== false) {
               $due = date("F j, g:ia", strtotime($row['duedate']));
            } else {
               $due = date("F j, Y", strtotime($row['duedate']));
            }
        }
        return $due;
    }

    /**
     * Gets the number of items and/or holdings_text_fields 
     * to display by default before collapsing them. 
     *
     * @returns string
     */
    public function getDisplayNumber()
    {
        return (string) $this->displayNum;
    }
}
