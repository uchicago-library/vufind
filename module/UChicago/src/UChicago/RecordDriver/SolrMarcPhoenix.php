<?php
/**
 * Model for MARC records in Solr.
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
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace UChicago\RecordDriver;
use VuFind\Exception\ILS as ILSException, VuFind\XSLT\Processor as XSLTProcessor;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcPhoenix extends \VuFind\RecordDriver\SolrMarc
{

    /**
     * For testing if a record can be parsed by this class in the results view.
     * If a more robust solution is needed in the future we can write a view helper
     * or controller action that uses get_class($this->driver) to return the driver
     * name in the templates (not really necessary at the moment).   
     */
    public $isMarc = true;

    /**
     * Get default OpenURL parameters.
     *
     * @return array
     */
    protected function getDefaultOpenURLParams()
    {
        // Get a representative publication date:
        $pubDate = $this->getPublicationDates();
        $pubDate = empty($pubDate) ? '' : $pubDate[0];

        // Title. Remove spaces before colons and trailing punctuation:
        $title = $this->getTitle();
        $title = str_replace(" : ", ": ", $title);
        $title = preg_replace("/[ .,:;\/]*$/", "", $title);

        // Start an array of OpenURL parameters:
        return array(
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator',
            'rft.title' => $title,
            'rft.date' => $pubDate
        );
    }

    /**
     * Get OpenURL parameters for a book.
     *
     * @return array
     */
    protected function getBookOpenURLParams()
    {
        $params = $this->getDefaultOpenURLParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['rft.genre'] = 'book';
        $params['rft.btitle'] = $params['rft.title'];
        $series = $this->getSeries();
        if (count($series) > 0) {
            // Handle both possible return formats of getSeries:
            $params['rft.series'] = is_array($series[0]) ?
                $series[0]['name'] : $series[0];
        }
        $params['rft.au'] = $this->getPrimaryAuthor();
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            // Remove trailing punctuation.
            $params['rft.pub'] = preg_replace("/[ .,:;\/]*$/", "", $publishers[0]);
        }
        $places = $this->getPlacesOfPublication();
        if (count($places) > 0) {
            // Remove trailing punctuation.
            $params['rft.place'] = preg_replace("/[ .,:;\/]*$/", "", $places[0]);
        }
        $params['rft.edition'] = $this->getEdition();
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        return $params;
    }

    /**
     * Get the item's place of publication.
     *
     * @return array
     */
    public function getPlacesOfPublication()
    {
        return isset($this->fields['publication_place']) ?
            $this->fields['publication_place'] : array();
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array('856');

        foreach ($fieldsToCheck as $field) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    $linktext = array();
                    $subfield3 = $url->getSubfield('3');
                    if ($subfield3 !== false) {
                        $linktext[] = ': ' . $subfield3->getData();
                    }

                    /*
                     * collect every subfield z and combine them.
                     */
                    $tmp = array();
                    foreach ($url->getSubfields('z') as $subfield => $value) {
                        $tmp[] = $value->getData();
                    }
                    if (count($tmp) > 0) {
                        $linktext[] = ' (' . implode(' ', $tmp) .  ')';
                    }

                    // Is there an address in the current field?
                    $address = $url->getSubfield('u');
                    if ($address) {
                        if (
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '0') ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '1')
                        ) {
                            $retVal[] = array(
                                'url'  => $address->getData(),
                                'desc' => implode('', array_merge(array('Full text online'), $linktext))
                            );
                        } else if ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '2' /*&& $subfield3->getData() == 'Finding aid'
                                                                                                --was causing a fatal error for searches returning certain records.
                                                                                                  This was preventing relevancy testing on dldc3*/) {
                            $retVal[] = array(
                                'url'  => $address->getData(),
                                'desc' => 'Finding aid'
                            );
                        } else if (
                            ($url->getIndicator(1) == ''  && $url->getIndicator(2) == '' ) ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '' ) ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '2') ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '8') ||
                            ($url->getIndicator(1) == '7' && $url->getIndicator(2) == '' )
                        ) {
                            $retVal[] = array(
                                'url'  => $address->getData(),
                                'desc' => implode('', array_merge(array('Related resource'), $linktext))
                            );
                        }
                    }
                }
            }
        }
        return $retVal;
    }

    /**
     * A junk method for combining subfield data between multiple eholdings incorrectly
     * returned from OLE. This is necessary because of a bug in the way OLE is 
     * returning 098 fields. This method should be deleted and the getAllRecordLinks
     * method updated when this is finally fixed. The order of subfields matters!
     *
     * @param array of subfield data to clean up.
     *
     * @return array of combined subfield data.
     */
    protected function combineLinkData($marcData) {
        $retval = array();
        $i = 0;

        if (isset($marcData['urls'])) {
            foreach ($marcData['urls'] as $urls) {
                if (!empty($urls['subfieldData'])) {
                    $retval['urls'][$i]['label'] = $urls['label'];
                    $retval['urls'][$i]['currentField'] = $urls['currentField'];
                    $retval['urls'][$i]['subfieldData'] = $urls['subfieldData'];
                    foreach($marcData['displayText'] as $displayText) {
                        if(!empty($displayText['subfieldData'])) {
                            foreach($displayText['subfieldData'] as $key => $value) {
                                $retval['urls'][$i]['subfieldData'][$key[0]] = $value; 
                            }
                            array_shift($marcData['displayText']);
                            break;
                        }
                    }
                    foreach($marcData['callnumber'] as $callnumber) {
                        if(!empty($callnumber['subfieldData'])) {
                            foreach($callnumber['subfieldData'] as $key => $value) {
                                $retval['urls'][$i]['subfieldData'][$key[0]] = $value; 
                            }
                            array_shift($marcData['callnumber']);
                            break;
                        }
                    }
                }
                $i++;
            }
        }
        return $retval;
    }

    /**
     * Get e-holdings from the marc record. Each link is returned as
     * array.
     * Format:
     * <code>
     * array 
     *  (
     *  [urls] => array
     *      (
     *          [1] => array
     *              (
     *                  [label] => urls
     *                  [currentField] => 098
     *                  [subfieldData] => Array
     *                      (
     *                          [u-1] => http://heinonline.org/HOL/Page?handle=hein.beal/cllwh0001&id=1&collection=beal&index=beal
     *                          [c-2] => FullText
     *                          [l-4] => Online
     *                      )
     *              )
     *      )
     *  )
     *
     * </code>
     *
     * @return null|array
     */
    public function getEholdingsFromMarc()
    {
        $rawMarcData = $this->crosswalk('eHoldings');
        $rawMarcData = $this->combineLinkData($rawMarcData);
        return !empty($rawMarcData) ? $rawMarcData : null;
    }

    /**
     * Gets bookplate 097 abnd 098 fields from the MARC record.
     *
     * @returns string
     */
    public function getBookPlate()
    {
        $output = array();
        foreach (array('097', '098') as $field) {
            $donor_codes = $this->getFieldArray($field, array('e'), false);
            $donor = $this->getFieldArray($field, array('d'), false);

            $donor_pairs = array();
            if (count($donor_codes) == count($donor)) {
                for ($d = 0; $d < count($donor_codes); $d++) {
                    $donor_pairs[] = $donor_codes[$d];
                    $donor_pairs[] = $donor[$d];
                }
            }
            $output[] = $donor_pairs;
        }
        return $output;
    }
    

   /******************** BEGIN: Full Record Display *********************/
   /** All of the methods below are support methods for the crosswalk  **/
   /** function that drives the full record display. Unrelated methods **/
   /** should be placed above this comment.                            **/

    /**
     * Configuration array for the display of MARC fields and their corresponding functions
     */
    protected $displayConfig = array(
        /*Array of marc fields and sub fields displayed at the top of the full record view.
        Included in the array is a field for labels that will be used in the html
        Note, labels in the array are overriden in the templates in certain special cases*/
        'top' => array(
            array('label'  => 'Title',
                  'values' => array('245|abcfgknps')),
                  //array('label' => 'OCLC', 'values' => array('035|a', 'atlas_oclc')),
            array('label'  => 'Author / Creator',
                  'values' => array('100|abcdequ')),
            array('label'  => 'Corporate author / creator',
                  'values' => array('110|abcdegnu')),
            array('label'  => 'Meeting name',
                  'values' => array('111|acndegjqu')),
            array('label'  => 'Uniform title',
                  'values' => array('130|adfgklmnoprs','240|adfghklmnoprs','243|adfgklmnoprs')),
            array('label'  => 'Edition',
                  'values' => array('250|ab','254|a')),
            array('label'  => 'Imprint',
                  'values' => array('260|abcdefg','264|abc')),
            array('label'  => 'Description',
                  'values' => array('300|abcefg3')),
            array('label'  => 'Language',
                  'values' => array('041|abde', '008')), //the 008 field will blow up the crosswalk function. Instead we return languages outside of the loop with the getLanguages function
            array('label'  => 'Series',
                  'values' => array('440|anpvx','490|avx','800|abcdefghklmnopqrstuv','810|abcdefghklmnoprstuv','811|acdefghklnpqstuv','830|adfghklmnoprstv')),
            array('label'  => 'Other uniform titles', //if this label changes, we'll need to change it in author.phtml and in the otherAuthorsTitles function below, I can add an ID parameter to avoid this but I'm not going to worry about it now
                  'values' => array('700|abcdfgiklmnopqrstux','710|abcdfgiklmnoprstux','711|acdefgiklnpqstux','730|adfiklmnoprs', '793|adfgiklmnoprs')), //700, 710, and 711 fields must come first 
            array('label'  => 'Other authors / contributors', //If this label changes, we'll need to change it in author.phtml too. 
                  'values' => array('700|abcdequt','710|abcdegnut','711|acdegjnqut', '790|abcdequ', '791|abcdegnu', '792|acdegnqu')), //700, 710, and 711 fields must come first. Subfield t must be included for 700, 710, and 711. This is necessary for distinguishing from Other uniform titles. We will unset it in the templates.
            array('label'  => 'Title varies',
                  'values' => array('247|abdefghnpx')),
            array('label'  => 'Preceding Entry',
                  'values' => array('780|abcdghikmnorstuxyz')),
            array('label'  => 'Succeeding Entry',
                  'values' => array('785|abcdghikmnorstuxyz')),
            array('label'  => 'Subject',
                  'values' => array('600|abcdefgklmnopqrstuvxyz','610|abcdefgklmnoprstuvxyz','611|acdefgklnpqstuvxyz','630|adfgklmnoprstvxyz','650|abcdevxyz','651|avxyz','654|abcvyz','655|abcvxyz')),
            array('label'  => 'ISBN',
                  'values' => array('020|acz')),
            array('label'  => 'ISSN',
                  'values' => array('022|ayz')),
            array('label'  => 'Cartographic data',
                  'values' => array('255|abcdefg')),
            array('label'  => 'Scale',
                  'values' => array('507|ab')),
            array('label'  => 'Format',
                  'values' => array('LDR|07')),
            array('label'  => 'Local Note',
                  'values' => array('590|a',)),
            array('label'  => 'URL for this record',
                  'values' => array('URL|http://pi.lib.uchicago.edu/1001/cat/bib/'))
        ),
        /*Array of marc fields and sub fields displayed on the top portion of the 
        full record view but hidden by default.*/
        'top-hidden' => array(
            array('label'  => 'Varying Form of Title',
                  'values' => array('246|abfginp')),
            array('label'  => 'Other title',
                  'values' => array('740|a')),
            array('label'  => 'Frequency',
                  'values' => array('310|ab', '315|ab', '321|ab')),
            array('label'  => 'Date/volume',
                  'values' => array('362|az')),
            array('label'  => 'Computer file characteristics',
                  'values' => array('256|a')),
            array('label'  => 'Physical medium',
                  'values' => array('340|abcdefhijkmno3')),
            array('label'  => 'Geospatial data',
                  'values' => array('342|abcdefghijklmnopqrstuvw')),
            array('label'  => 'Planar coordinate data',
                  'values' => array('343|abcdefghi')),
            array('label'  => 'Sound characteristics',
                  'values' => array('344|abcdefgh3')),
            array('label'  => 'Projection characteristics',
                  'values' => array('345|ab3')),
            array('label'  => 'Video characteristics',
                  'values' => array('346|ab3')),
            array('label'  => 'Digital file characteristics',
                  'values' => array('347|abcdef3')),
            array('label'  => 'Notes',
                  'values' => array('500|a', '501|a', '502|a', '504|a', '506|abcdefu', '508|a', '510|abcux', '511|a', '513|ab', '514|abcdefghijkmuz', '515|a', '516|a', '518|adop3', '525|a', '530|abcdu', '533|abcdefmn', '534|abcefklmnptxz', '535|abcdg', '536|abcdefgh', '538|aiu', '540|abcdu', '541|abcdefhno', '544|abcden', '545|abu', '546|ab', '547|a', '550|a', '552|abcdefghijklmnopu', '561|a', '562|abcde', '580|a', '583|abcdefhijklnouxz', '588|a', '598|a')),
            array('label'  => 'Summary',
                  'values' => array('520|abu')),
            array('label'  => 'Target Audience',
                  'values' => array('521|ab')),
            array('label'  => 'Geographic coverage',
                  'values' => array('522|a')),
            array('label'  => 'Cite as',
                  'values' => array('524|a')),
            array('label'  => 'Study Program Information',
                  'values' => array('526|abcdixz')),
            array('label'  => 'Cumulative Index / Finding Aids Note',
                  'values' => array('555|abcdu')),
            array('label'  => 'Documentation',
                  'values' => array('556|az')),
            array('label'  => 'Case File Characteristics',
                  'values' => array('565|abcde')),
            array('label'  => 'Methodology',
                  'values' => array('567|a')),
            array('label'  => 'Publications',
                  'values' => array('581|az')),
            array('label'  => 'Awards',
                  'values' => array('586|a')),
            array('label'  => 'Subseries of',
                  'values' => array('760|abcdghimnostxy')),
            array('label'  => 'Has subseries',
                  'values' => array('762|abcdghimnostxy')),
            array('label'  => 'Translation of',
                  'values' => array('765|abcdghikmnorstuxyz')),
            array('label'  => 'Translated as',
                  'values' => array('767|abcdghikmnorstuxyz')),
            array('label'  => 'Has supplement',
                  'values' => array('770|abcdghikmnorstuxyz')),
            array('label'  => 'Supplement to',
                  'values' => array('772|abcdghikmnorstuxyz')),
            array('label'  => 'Part of',
                  'values' => array('773|abstx')),
            array('label'  => 'Contains these parts',
                  'values' => array('774|abstx')),
            array('label'  => 'Other edition available',
                  'values' => array('775|abcdefghikmnorstuxyz')),
            array('label'  => 'Other form',
                  'values' => array('776|abcdghikmnorstuxyz')),
            array('label'  => 'Issued with',
                  'values' => array('777|abcdghikmnostxy')),
            array('label'  => 'Data source',
                  'values' => array('786|abcdhijkmnoprstuxy')),
            array('label'  => 'Related title',
                  'values' => array('787|abcdghikmnorstuxyz')),
            array('label'  => 'Standard no.',
                  'values' => array('024|acdz')),
            array('label'  => 'Publisher\'s no.',
                  'values' => array('028|ab')),
            array('label'  => 'CODEN',
                  'values' => array('030|az')),
            array('label'  => 'GPO item no.',
                  'values' => array('074|az')),
            array('label'  => 'Govt.docs classification',
                  'values' => array('086|az')),
        ),

        /*Array of marc fields and sub fields displayed in the description tab of the
        full record view. Included in the array is a field for labels that will be used in the html*/
        'details' => array(
            array('label'  => 'Varying Form of Title',
                  'values' => array('246|abfginp')),
            array('label'  => 'Other title',
                  'values' => array('740|a')),
            array('label'  => 'Frequency',
                  'values' => array('310|ab', '315|ab', '321|ab')),
            array('label'  => 'Date/volume',
                  'values' => array('362|az')),
            array('label'  => 'Computer file characteristics',
                  'values' => array('256|a')),
            array('label'  => 'Physical medium',
                  'values' => array('340|abcdefhijkmno3')),
            array('label'  => 'Geospatial data',
                  'values' => array('342|abcdefghijklmnopqrstuvw')),
            array('label'  => 'Planar coordinate data',
                  'values' => array('343|abcdefghi')),
            array('label'  => 'Sound characteristics',
                  'values' => array('344|abcdefgh3')),
            array('label'  => 'Projection characteristics',
                  'values' => array('345|ab3')),
            array('label'  => 'Video characteristics',
                  'values' => array('346|ab3')),
            array('label'  => 'Digital file characteristics',
                  'values' => array('347|abcdef3')),
            array('label'  => 'Notes',
                  'values' => array('500|a', '501|a', '502|a', '504|a', '506|abcdefu', '508|a', '510|abcux', '511|a', '513|ab', '514|abcdefghijkmuz', '515|a', '516|a', '518|adop3', '525|a', '530|abcdu', '533|abcdefmn', '534|abcefklmnptxz', '535|abcdg', '536|abcdefgh', '538|aiu', '540|abcdu', '541|abcdefhno', '544|abcden', '545|abu', '546|ab', '547|a', '550|a', '552|abcdefghijklmnopu', '561|a', '562|abcde', '580|a', '583|abcdefhijklnouxz', '588|a', '598|a')),
            array('label'  => 'Summary',
                  'values' => array('520|abu')),
            array('label'  => 'Target Audience',
                  'values' => array('521|ab')),
            array('label'  => 'Geographic coverage',
                  'values' => array('522|a')),
            array('label'  => 'Cite as',
                  'values' => array('524|a')),
            array('label'  => 'Study Program Information',
                  'values' => array('526|abcdixz')),
            array('label'  => 'Cumulative Index / Finding Aids Note',
                  'values' => array('555|abcdu')),
            array('label'  => 'Documentation',
                  'values' => array('556|az')),
            array('label'  => 'Case File Characteristics',
                  'values' => array('565|abcde')),
            array('label'  => 'Methodology',
                  'values' => array('567|a')),
            array('label'  => 'Publications',
                  'values' => array('581|az')),
            array('label'  => 'Awards',
                  'values' => array('586|a')),
            array('label'  => 'Subseries of',
                  'values' => array('760|abcdghimnostxy')),
            array('label'  => 'Has subseries',
                  'values' => array('762|abcdghimnostxy')),
            array('label'  => 'Translation of',
                  'values' => array('765|abcdghikmnorstuxyz')),
            array('label'  => 'Translated as',
                  'values' => array('767|abcdghikmnorstuxyz')),
            array('label'  => 'Has supplement',
                  'values' => array('770|abcdghikmnorstuxyz')),
            array('label'  => 'Supplement to',
                  'values' => array('772|abcdghikmnorstuxyz')),
            array('label'  => 'Part of',
                  'values' => array('773|abstx')),
            array('label'  => 'Contains these parts',
                  'values' => array('774|abstx')),
            array('label'  => 'Other edition available',
                  'values' => array('775|abcdefghikmnorstuxyz')),
            array('label'  => 'Other form',
                  'values' => array('776|abcdghikmnorstuxyz')),
            array('label'  => 'Issued with',
                  'values' => array('777|abcdghikmnostxy')),
            array('label'  => 'Data source',
                  'values' => array('786|abcdhijkmnoprstuxy')),
            array('label'  => 'Related title',
                  'values' => array('787|abcdghikmnorstuxyz')),
            array('label'  => 'Standard no.',
                  'values' => array('024|acdz')),
            array('label'  => 'Publisher\'s no.',
                  'values' => array('028|ab')),
            array('label'  => 'CODEN',
                  'values' => array('030|az')),
            array('label'  => 'GPO item no.',
                  'values' => array('074|az')),
            array('label'  => 'Govt.docs classification',
                  'values' => array('086|az')),
        ),
        /**
         * Array of marc fields to display for the Atlas plugins.
         */
        'atlas' => array(
            array('label'  => 'atlas_author',
                  'values' => array('100|a', '110|a', '111|a')),
            array('label'  => 'atlas_title',
                  'values' => array('245|a')),
            array('label'  => 'atlas_full_title',
                  'values' => array('245|abcfghknps68')),
            array('label'  => 'atlas_publication_place',
                  'values' => array('260|a')),
            array('label'  => 'atlas_publisher',
                  'values' => array('260|b')),
            array('label'  => 'atlas_publication_date',
                  'values' => array('260|c')),
            array('label'  => 'atlas_format',
                  'values' => array('LDR|07')),
            array('label'  => 'atlas_issn',
                  'values' => array('022|a')),
            array('label'  => 'atlas_edition',
                  'values' => array('250|ab368')),
            array('label'  => 'atlas_pages',
                  'values' => array('300|a')),
            array('label'  => 'atlas_oclc',                   
                  'values' => array('035|a')), 
        ),
        /**
         * Array of marc fields to display for Hathi links on records 
         * with bib numbers having an ocm or ocn prefix.
         */
        'hathiLink' => array(
            array('label'  => 'oclcNumber',
                  'values' => array('035|a')),
            array('label'  => 'Project identifier',
                  'values' => array('903|a')),
        ),
        /**
         * Array of marc fields to display for 
         * eHoldings on results pages.
         */
        'eHoldings' => array(
            array('label'  => 'urls',
                  'values' => array('098|u')),
            array('label'  => 'displayText',
                  'values' => array('098|cl')),
            array('label'  => 'callnumber',
                  'values' => array('098|a')),
        ),
        /**
         * Array of marc fields to display for 
         * MARC holdings  on results pages.
         */
        'results' => array(
            array('label'  => 'title',
                  'values' => array('245|abfgknps')),
            array('label'  => 'author',
                  'values' => array('100|abcdequ')),
            array('label'  => 'corporate-author',
                  'values' => array('110|abcdegnu')),
            array('label'  => 'edition',
                  'values' => array('250|ab','254|a')),
            array('label'  => 'imprint',
                  'values' => array('260|abcdefg','264|abc')),
            array('label'  => 'Description',
                  'values' => array('300|abcefg3')),
        ),
    ); // end $displayConfig

    /**
     * Method for returning the proper configuration array depending where we are in the templates
     *
     * @param string $pos informs the method of location in templates
     *
     * @return the proper configuration array of marc fields/subfields or log an error message
     */
    protected function getConfigArray($pos)
    {
        return $this->displayConfig[$pos];
    }

    /**
     * Support method for splitting out multiple fields and subfields from the arrays above
     *
     * @param array $marcFieldsArray
     *
     * @return array $output - a lit of entries divided into fields, subfields, and labels
     */
    protected function fieldsSplit($marcFieldsArray)
    {
        $output = array();

        foreach ($marcFieldsArray as $entry) {

        $html_id = $entry['label'];
        $the_marc_fields = $entry['values'];

            foreach ($the_marc_fields as $the_marc_fields) {

                $field = array_shift(explode("|", $the_marc_fields));

                /*Get an array of subfields for this marc field */
                $subfields = array_pop(explode("|", $the_marc_fields));
                $output[] = array($field, $subfields, $html_id);
            }
        }
        return $output;
    } 

    /**
     * Tiny helper method that adds additional information to a master array
     *
     * @param $dataCollection array in need of more details
     * @param $i integer, current location (iteration) in the loop
     * @param $configLabel string, the current html label associated with this data
     * @param $configField string, the corrent MARC field associated with this data
     * @param $currentOther, array for processing 
     *
     * @return a more informed array with additional details
     */
    protected function intelligize($dataCollection, $i, $configLabel, $configField, $currentOther)
    {
        /*Add a label to the data collection*/
        $dataCollection[$i][$configLabel]['label'] = $configLabel;
        /*Add the current field (MARC tag name) to the data collection*/
        $dataCollection[$i][$configLabel]['currentField'] = $configField;
        /*Add subfield data values to the data collection*/
        $dataCollection[$i][$configLabel]['subfieldData'] = $currentOther;
 
        return $dataCollection;

    }

    /**
     * Method for getting vernacular data from the 880 field.
     *
     * @return array containing vernacular data for every 880 field 
     */
    protected function getVernacularData()
    {
        /*Get all of the vernacular data for the record: returns the File_MARC_Data_Field Object*/
        $vernacularData = $this->marcRecord->getFields('880');

        /*Loop over the vernacular data and stash it into an array*/
        $v = array();
        foreach($vernacularData as $vernacularFields) {
            /*Only use subfield data from the File_MARC_Data_Field object: returns an array of File_MARC_Subfield objects*/
            $vernacularSubfields = $vernacularFields->getSubfields();

            $subfield6 = array_shift(explode('-', $vernacularFields->getSubfield('6')->getData()));

            /*Array for vernacular subfield data*/
            $vs = array();
            /*Put subfield data into an array with the correct hierarchy*/
            foreach($vernacularSubfields as $vernacular) {
                /*Get the data we wish to return*/
                $vernacularString = $vernacular->getData();
                
                /*Match the returned strings with the value of subfield 6.
                If a title of a book ever starts with 3 numbers that happen to be identical to the value of subfield 6 during the current iteration in the loop, there could be a problem*/ 
                if(preg_match('/^'.$subfield6.'/', $vernacularString) == 1){
                    $vernacularString = $subfield6;
                }
                $vernacularArray = explode('#^@$*!', $vernacularString);

                /*Array of subfield data for the current 880*/ 
                $vs[] = $vernacularArray;
            }
            /*Master array of subfield data for every 880*/  
            $v[] = $vs;
        }
        return $v;
    }

    /**
     * Method loops over array and groups children with the same keys
     * used by the crosswalk method
     *
     * @param array
     *
     * @return a new array with field data grouped by topic (label) 
     */
    protected function group($array)
    {
        $output = array();

        foreach ($array as $d) {
           
            foreach ($d as $k => $value) {
               
                if (!isset($output[$k])) {
                    $output[$k] = array();
                }
                /*Group all marc fields with the same lable together*/
                $output[$k][] = $value;
            }
        }
        return $output;
    }

    /**
     * Method looks for an array key matching a specific pattern and returns it
     *
     * @param string regex pattern to match
     * @param array to search
     *
     * @return the matching array key
     */
    protected function findKey($pattern, $array) {
        $keys = array_keys($array);
        $foundKey = preg_grep($pattern,$keys);
        return implode($foundKey);
    }

    /**
     * Method strips 700, 710, and 711 fields out of "Other uniform titles" when they don't have a subfield t. 
     * Because of the way this works with $i, it is likely that the 700, 710, and 711 fields will need
     * to be listed first in the config array for both, "Other authors / contributors" and "Other unifrom titles".
     *
     * @param array of grouped marc field and subfield data
     * 
     * @return array reformatted with 700, 710, and 711 data refactored for display purposes
     **/
    protected function otherAuthorsTitles($array) {

        $output = $array;

        $i = -1;
        if (isset($output['Other authors / contributors'])) {     
            foreach ($output['Other authors / contributors'] as $oac){
                $i++;
                if ($oac['currentField'] == 700 || $oac['currentField'] == 710 || $oac['currentField'] == 711){
                    $t = $this->findKey('/^t/', $oac['subfieldData']);
                    if(!$t) {
                        unset($output['Other uniform titles'][$i]);
                        /*If the array is empty get rid of it*/
                        if (empty($output['Other uniform titles'])){
                            unset($output['Other uniform titles']);
                        }
                    }
                    else {
                        unset($output['Other authors / contributors'][$i]);
                        /*If the array is empty get rid of it*/
                        if (empty($output['Other authors / contributors'])){
                            unset($output['Other authors / contributors']);
                        }
                    }

                }
            }
        }
        return $output;
    }

    /**
     * Main method for returning marc data grouped and formatted the way we want it. 
     * Takes a configuration array of MARC fields, subfields, and labels which are passed in through the getConfigArray method.
     * Field/subfield combinations dictate what data will be displayed in the templates. 
     * Labels are displayed in the html for each grouping of data. 
     *
     * @param string $pos informs the method of location in templates
     *
     * @return array of all the marc data grouped and formatted the way we want it for the front end
     */
    public function crosswalk($pos)
    {
        /*Display the correct configuration array based on where we are in the templates, determined
        by the $pos parameter passed in from the call to the view helper in the templates*/
        $marcFieldsArray = $this->getConfigArray($pos);

        /*Split our configuration array into usable chunks*/
        $marcData = $this->fieldsSplit($marcFieldsArray);

        /*Get the the vernacular data for this record*/
        $vernacularData = $this->getVernacularData(); 

        /*An array for all of the results*/
        $dataCollection = array();
 
        /*Loop over the configuration array and query the File_MARC_Record object. 
        Put results into our master $dataCollection array */
        $i = 0;
        foreach($marcData as $key => $data) {

            /*Set the important variables*/
            $configField = $data[0];
            $configSubfields = $data[1];
            $configLabel = $data[2];
 
            /*Get the File_MARC_Data_Field Object*/ 
            $results = $this->marcRecord->getFields($configField);

            /*Add subfield data values to our master array*/
            foreach ($results as $result) {

                $i++;
                $current = array();
                $n = 0;

                /*Languages, Format and Persistent URL are set differently, we won't use the default File_MARC class but rather VuFind functions
                Everything else is set in the loops below*/
                if (($configField != '041') && ($configField != '008') && ($configField != 'LDR') && ($configField != 'URL')) {
                    $subfields = $result->getSubfields();

                    /*Loop through subfield data*/
                    foreach ($subfields as $subfield) {
                        $n++;
                        $currentSubfield = $subfield->getCode();
                    
                        /*See if the current subfield is in our configuration array. 
                        If it's in the configuration array get the data from the marc object*/
                        if(preg_match('/'.$currentSubfield.'/i', $configSubfields) == 1){
                            $current[''.$currentSubfield.'-'.$n] = $subfield->getData();
                        }
                    }

                    /*Add indicator values to the data collection*/ 
                    $dataCollection[$i][$configLabel]['indicator1'] = $result->getIndicator('1');
                    $dataCollection[$i][$configLabel]['indicator2'] = $result->getIndicator('2');

                    /*Add a label to the data collection*/ 
                    $dataCollection[$i][$configLabel]['label'] = $configLabel;
                    /*Add the current field (MARC tag name) to the data collection*/
                    $dataCollection[$i][$configLabel]['currentField'] = $configField;
                    /*Add subfield data values to the data collection*/
                    $dataCollection[$i][$configLabel]['subfieldData'] = $current;
                }
 
                /*Used to keep track of our location in the vernacularData array*/
                $vc = 0;
                $currentVernacular = array();
                foreach($vernacularData as $vernacular){
 
                    if(!empty($vernacular[0][0])) {
                        $vfield = $vernacular[0][0];

                        if($vfield == $configField) {

                            /*Push the tag names off the stack since they aren't what we want to return*/
                            array_shift($vernacular);

                            foreach($vernacular as $vd){ 
                               //$dataCollection[$i][$configLabel]['vernacularData'] = $vd;
                               $v = implode(' ', $vd);
                               $currentVernacular[] = $v;
                            }

                            /*Add the vernacular data to our master array*/
                            $dataCollection[$i][$configLabel]['vernacularData'] = $currentVernacular;

                            /*Handle normal (repeating) fields*/
                            if(($configField != 700) && ($configField != 710) & ($configField != 711)) {
                                /*Get stuff out of the vernacularData array after we use it (prevents duplication on repeating fields)*/
                                unset($vernacularData[$vc]);
                                $vernacularData = array_values($vernacularData);
                                break;
                            }
                            /*Special handling for 700, 710, and 711 fields*/
                            else {
                                $vernacularData[] = $vernacularData[$vc];
                                unset($vernacularData[$vc]);
                                $vernacularData = array_values($vernacularData);
                                break;
                            }
                        }
                    }
                    /*Keep track of where we are in the vernacularData array*/
                    $vc++;
                }

                if (!empty($currentVernacular)){
                    //$dataCollection[$i][$configLabel]['vernacularData'] = $currentVernacular;
                }
            }

            /*In some special cases add things to the master array by bypassing the main piece above*/
            $currentOther = array();
            /*Format*/
            if ($configField == 'LDR') {
                $currentOther[] = implode(', ', $this->getFormats());
                $dataCollection = $this->intelligize($dataCollection, $i, $configLabel, $configField, $currentOther); 
            }
            /*Languages*/
            elseif ((($configField == '041') || ($configField == '008')) && (!isset($language)) && (implode(' ', $this->getLanguages()) != '')) {
                $language = true;
                $currentOther[] = implode(', ', $this->getLanguages());
                $dataCollection = $this->intelligize($dataCollection, $i, $configLabel, $configField, $currentOther); 
            }
            /*Persistent URL*/
            elseif (($configField == 'URL')) {
                $currentOther[] = $configSubfields.$this->getUniqueID(); 
                $dataCollection = $this->intelligize($dataCollection, $i, $configLabel, $configField, $currentOther);
            }             

            /*Add vernacular data to the master array*/
            foreach($vernacularData as $vernacular){
                 
                $currentVernacular = array();
                if(!empty($vernacular[0][0])) {
                    $vfield = $vernacular[0][0];
                }
                if($vfield == $configField) {
                    /*Push the tag names off the stack since they aren't what we want to return*/
                    array_shift($vernacular);

                    foreach($vernacular as $vd){
                       $v = implode(' ', $vd);
                       $currentVernacular[] = $v;
                    }
                }
                if (!empty($currentVernacular)){
                    $dataCollection[$i][$configLabel]['vernacularData'] = $currentVernacular; 
                }
            }
        }

        /*Reformat the array with proper grouping*/
        $groupedData = $this->group($dataCollection);

        /*Refactor data in the 700, 710, and 711 fields for display*/
        $output = $this->otherAuthorsTitles($groupedData);
        
        return $output;
    }
    
    public function isFullText()
    {
    	return true;
    	 
    	/*     	$sfx = "http://sfx.lib.uchicago.edu/sfx_local?sid=lib.uchicago.edu:generator&amp;rft.issn=0038-0644&amp;sfx.response_type=simplexml";
    	 $sfx_xml = simplexml_load_string($sfx);
    	sfx.has_full_text
    	*/
    }
}
