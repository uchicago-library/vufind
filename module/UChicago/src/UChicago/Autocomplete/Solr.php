<?php

namespace UChicago\Autocomplete;

class Solr extends \VuFind\Autocomplete\Solr
{
    /**
     * Given the values from a Solr field and the user's search query, pick the best
     * match to display as a recommendation.
     *
     * @param array|string $value Field value (or array of field values)
     * @param string       $query User search query
     * @param bool         $exact Ignore non-exact matches?
     *
     * @return bool|string        String to use as recommendation, or false if
     * no appropriate value was found.
     */
    protected function pickBestMatch($value, $query, $exact)
    {
        // By default, assume no match:
        $bestMatch = false;

        // Different processing for arrays vs. non-arrays:
        if (is_array($value) && !empty($value)) {
            // Do any of the values within this multi-valued array match the
            // query?  Try to find the closest available match.
            foreach ($value as $next) {
                if ($this->matchQueryTerms($next, $query)) {
                    $bestMatch = $next;
                    break;
                }
            }

            // If we didn't find an exact match, use the first value unless
            // we have the "precise matches only" property set, in which case
            // we don't want to use any of these values.
            if (!$bestMatch && !$exact) {
                $bestMatch = $value[0];
            }
        } else {
            // If we have a single value, we will use it if we're in non-strict
            // mode OR if we're in strict mode and it actually matches.
            if (!$exact || $this->matchQueryTerms($value, $query)) {
                ### UChicago customization ###
                $bestMatch = htmlspecialchars($value);
                ### ./UChicago customization ###
            }
        }
        return $bestMatch;
    }
}

