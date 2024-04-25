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
    public function getUniqueCallNumbers($items, $fullDetails = false)
    {
        $callNos = [];
        foreach ($items as $i => $item) {
            if (isset($item['holding_callnumber']) && strlen($item['holding_callnumber']) > 0) {
                $prefix = $item['holding_callnumber_prefix'] ?? '';
                $callnumber = $item['holding_callnumber'];
                $callNos[$i]['callnumber'] = $callnumber;
                $callNos[$i]['display'] = $prefix ? $prefix . ' ' . $callnumber : $callnumber;
                $callNos[$i]['prefix'] = $item['callnumber_prefix'] ?? '';
                break;
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
