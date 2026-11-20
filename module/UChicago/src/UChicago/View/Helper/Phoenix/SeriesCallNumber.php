<?php

/**
 * Series call number view helper
 *
 * PHP version 7
 *
 * Copyright (C) The University of Chicago Library 2026.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace UChicago\View\Helper\Phoenix;

/**
 * Reduces an analytic item's call number to the call number of its series.
 *
 * @category VuFind
 * @package  View_Helpers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SeriesCallNumber extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Holdings location code for Harvard-Yenching call numbers.
     *
     * @var string
     */
    public const HARVARD_YENCHING_LOCATION = 'JRL-GenHY';

    /**
     * Return the series part of a call number.
     *
     * A Harvard-Yenching call number keeps its class number and its book
     * number, for example "J4150.3 7274 v.58" becomes "J4150.3 7274". Any
     * other call number is cut at the first space followed by a 4-digit
     * year or by a token that ends in a period, such as "v." or "no.".
     *
     * @param string  $callNumber   Item call number
     * @param ?string $locationCode Holdings location code
     *
     * @return string
     */
    public function __invoke(string $callNumber, ?string $locationCode = null): string
    {
        if (
            $locationCode === self::HARVARD_YENCHING_LOCATION
            && preg_match(
                '/^([A-Z]+\d+(?:\.\d+)?\s+\d+(?:\.\d+)?)(?=\s|$)/',
                $callNumber,
                $matches
            )
        ) {
            return $matches[1];
        }
        return preg_split("/\s+(?:\d{4}|[a-zA-Z0-9]+\.)/", $callNumber)[0];
    }
}
