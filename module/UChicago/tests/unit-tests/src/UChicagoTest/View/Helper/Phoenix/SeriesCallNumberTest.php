<?php

/**
 * SeriesCallNumber view helper test
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
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace UChicagoTest\View\Helper\Phoenix;

use UChicago\View\Helper\Phoenix\SeriesCallNumber;

/**
 * SeriesCallNumber view helper test
 *
 * @category VuFind
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SeriesCallNumberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for Harvard-Yenching call numbers.
     *
     * @return array
     */
    public static function harvardYenchingProvider(): array
    {
        return [
            'book number, two-digit volume' => ['J4150.3 7274 v.58', 'J4150.3 7274'],
            'book number, one-digit volume' => ['J4150.3 7274 v.1', 'J4150.3 7274'],
            'book number, volume ten' => ['J4150.3 7274 v.10', 'J4150.3 7274'],
            'integer class, zero-padded book number' => ['J1433 0009 v.1', 'J1433 0009'],
            'volume range' => ['J1433 1265 v.20-21', 'J1433 1265'],
            'decimal book number' => ['J3381 9163.1 v.1', 'J3381 9163.1'],
        ];
    }

    /**
     * Data provider for call numbers that use the general rule.
     *
     * @return array
     */
    public static function generalProvider(): array
    {
        return [
            'year and number' => ['AS182.M241 1955 no.3', 'AS182.M241'],
            'year and two-digit number' => ['HG2401.W67 2001 no.26', 'HG2401.W67'],
            'year and volume' => ['M3.G95 1954 v.1', 'M3.G95'],
            'year, series, and volume' => ['M3.H41 1958 ser.15 v.1', 'M3.H41'],
            'year and volume, longer class' => ['BX880.C741 1997 v.1', 'BX880.C741'],
            'Harvard-Yenching shape' => ['J4150.3 7274 v.58', 'J4150.3'],
        ];
    }

    /**
     * Data provider for Harvard-Yenching location items whose call number
     * does not have the Harvard-Yenching shape.
     *
     * @return array
     */
    public static function harvardYenchingFallbackProvider(): array
    {
        return [
            'cutter, year, and volume' => ['PL2658.E1 C45 1990 v.2', 'PL2658.E1 C45'],
            'year and number' => ['AS182.M241 1955 no.3', 'AS182.M241'],
        ];
    }

    /**
     * Test that a Harvard-Yenching call number keeps its book number.
     *
     * @param string $callNumber Item call number
     * @param string $expected   Expected series call number
     *
     * @return void
     *
     * @dataProvider harvardYenchingProvider
     */
    public function testHarvardYenchingKeepsBookNumber(
        string $callNumber,
        string $expected
    ): void {
        $helper = new SeriesCallNumber();
        $this->assertSame($expected, $helper($callNumber, 'JRL-GenHY'));
    }

    /**
     * Test that other locations use the general rule.
     *
     * @param string $callNumber Item call number
     * @param string $expected   Expected series call number
     *
     * @return void
     *
     * @dataProvider generalProvider
     */
    public function testOtherLocationUsesGeneralRule(
        string $callNumber,
        string $expected
    ): void {
        $helper = new SeriesCallNumber();
        $this->assertSame($expected, $helper($callNumber, 'JRL-Gen'));
    }

    /**
     * Test that a missing location code uses the general rule.
     *
     * @param string $callNumber Item call number
     * @param string $expected   Expected series call number
     *
     * @return void
     *
     * @dataProvider generalProvider
     */
    public function testNoLocationUsesGeneralRule(
        string $callNumber,
        string $expected
    ): void {
        $helper = new SeriesCallNumber();
        $this->assertSame($expected, $helper($callNumber));
        $this->assertSame($expected, $helper($callNumber, null));
    }

    /**
     * Test that a Harvard-Yenching location item without the
     * Harvard-Yenching shape uses the general rule.
     *
     * @param string $callNumber Item call number
     * @param string $expected   Expected series call number
     *
     * @return void
     *
     * @dataProvider harvardYenchingFallbackProvider
     */
    public function testHarvardYenchingFallsBackToGeneralRule(
        string $callNumber,
        string $expected
    ): void {
        $helper = new SeriesCallNumber();
        $this->assertSame($expected, $helper($callNumber, 'JRL-GenHY'));
    }
}
