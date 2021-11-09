<?php

namespace UChicago\RecordDriver;

class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     * Override the default method so that 1. It always returns an array and
     * 2. It separates strings with multiple ISSNs.
     *
     * @return array
     */
    public function getISSNs()
    {
        // If ISSN is in the index, it should automatically be an array... but if
        // it's not set at all, we should normalize the value to an empty array.
        $rawISSNs = isset($this->fields['issn']) && is_array($this->fields['issn']) ?
            $this->fields['issn'] : [];

        $rawISSNs = array_map(function($val) {
            return explode(' ', $val);
        }, $rawISSNs);

        $issns = !empty($rawISSNs) ? call_user_func_array('array_merge', $rawISSNs) : [];

        return $issns;
    }
}
