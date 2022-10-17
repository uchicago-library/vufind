<?php
/**
 * Citation view helper
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace UChicago\View\Helper\Phoenix;

use VuFind\Date\DateException;
use VuFind\I18n\Translator\TranslatorAwareInterface;

/**
 * Citation view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Citation extends \VuFind\View\Helper\Root\Citation
    implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Strip unwanted punctuation from the right side of a string.
     *
     * @param string $text Text to clean up.
     *
     * @return string      Cleaned up text.
     */
    protected function stripPunctuation($text)
    {
        ### UChicago customization ###
        ### For some reason this method erroneously receives an array in some cases ###
        if (is_array($text)) {
           $text = implode(' ', $text);
        }
        ### ./UChicago customization ###
        $punctuation = ['.', ',', ':', ';', '/'];
        $text = trim($text);
        if (in_array(substr($text, -1), $punctuation)) {
            $text = substr($text, 0, -1);
        }
        return trim($text);
    }

}
