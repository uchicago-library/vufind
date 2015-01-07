<?php
/**
 * Hold Logic Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  ILS_Logic
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace UChicago\ILS\Logic;
use VuFind\ILS\Connection as ILSConnection;

/**
 * Hold Logic Class
 *
 * @category VuFind2
 * @package  ILS_Logic
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Holds extends \VuFind\ILS\Logic\Holds
{
    /**
     * Helper method for formatHoldings.
     * 
     * @param array $items, an associative array of item information.
     *
     * @return array of refactored item info for convenient display 
     * to be returned by the formatHoldings method.
     */ 
    protected function getFormatHoldingsTemplate($items, $keyName) {
        $array = array();
        foreach ($items as $item) {
            if (isset($item[$keyName])) {
                if (!is_array($item[$keyName])) {
                    $item[$keyName] = empty($item[$keyName])
                        ? array() : array($item[$keyName]);
                }
                foreach ($item[$keyName] as $note) {
                    if ((!in_array($note, $array)) and !empty($note)) {
                        $array[] = $note;
                    }
                }
            }
        }
        return $array;
    }

    /**
     * Support method to rearrange the holdings array for displaying convenience.
     *
     * @param array $holdings An associative array of location => item array
     *
     * @return array          An associative array keyed by location with each
     * entry being an array with 'notes', 'summary' and 'items' keys.  The 'notes'
     * and 'summary' arrays are note/summary information collected from within the
     * items.
     */
    protected function formatHoldings($holdings)
    {
        $retVal = array();

        /* Ensure that "online" (full text) always appears on top */ 
        $holdings = array_reverse($holdings, true); 

        foreach ($holdings as $location => $items) {
            $notes = $this->getFormatHoldingsTemplate($items, 'notes');
            $holdingsNotes = $this->getFormatHoldingsTemplate($items, 'holdingsNotes');
            $summaries = $this->getFormatHoldingsTemplate($items, 'summary');
            $libraryHas = $this->getFormatHoldingsTemplate($items, 'libraryHas');
            $indexes = $this->getFormatHoldingsTemplate($items, 'indexes');
            $supplements = $this->getFormatHoldingsTemplate($items, 'supplements');
            $unbound = $this->getFormatHoldingsTemplate($items, 'unbound');
            //$eHoldingsLinks = $this->getFormatHoldingsTemplate($items, 'eHoldingsLinks');
            
            $retVal[$location] = array(
                'holdingsNotes' => (!empty($holdingsNotes) ? $holdingsNotes : null),  
                'libraryHas' => (!empty($libraryHas) ? $libraryHas : null), 
                'unbound' => (!empty($unbound) ? $unbound : null), 
                'notes' => (!empty($notes) ? $notes : null), 
                'indexes' => (!empty($indexes) ? $indexes : null), 
                'supplements' => (!empty($supplements) ? $supplements : null),  
                'summary' => (!empty($summaries) ? $summaries : null), 
                'items' => (!empty($items) ? $items : null),
                //'eHoldingsLinks' => $eHoldingsLinks
            );
        }
        return $retVal;
    }

}
