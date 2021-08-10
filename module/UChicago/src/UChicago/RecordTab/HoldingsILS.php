<?php

namespace UChicago\RecordTab;

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
        $callNos = [];
        foreach ($items as $i => $item) {
            if (isset($item['callnumber']) && strlen($item['callnumber']) > 0) {
                $prefix = $item['callnumber_prefix'] ?? '';
                $callnumber = $item['callnumber'];
                $callNos[$i]['callnumber'] = $callnumber;
                $callNos[$i]['display'] = $prefix ? $prefix . ' ' . $callnumber : $callnumber;
                $callNos[$i]['prefix'] = $item['callnumber_prefix'] ?? '';
            }
        }

        uasort($callNos, function($a, $b) {
            return $a['display'] <=> $b['display'];
        });

        $unique = [];
        foreach ($callNos as $no) {
            $unique[$no['display']] = $no;
        }
        $callNosUnique = array_values($unique);

        return $callNosUnique;
    }
}
