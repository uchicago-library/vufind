<?php
/**
 * Model for MARC records in Solr.
 *
 * PHP version 5 
 *
 * Copyright (C) Villanova University 2010. 
 * Copyright (C) The National Library of Finland 2015.
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
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace UChicago\RecordDriver;
use VuFind\Exception\ILS as ILSException, VuFind\XSLT\Processor as XSLTProcessor;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
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
     * Get an array of lines from the table of contents.
     *
     * @return array
     */
    public function getTOC()
    {
        // Return empty array if we have no table of contents:
        $fields = $this->getMarcRecord()->getFields('505');
        if (!$fields) {
            return [];
        }

        // If we got this far, we have a table -- collect it as a string:
        $toc = [];
        foreach ($fields as $field) {
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                if ($subfield->getCode() == '6') {
                    continue;
                }
                // Break the string into appropriate chunks, filtering empty strings,
                // and merge them into return array:
                $toc = array_merge(
                    $toc,
                    array_filter(explode('--', $subfield->getData()), 'trim')
                );
            }
        }
        return $toc;
    }

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
        return [
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator',
            'rft.title' => $title,
            'rft.date' => $pubDate
        ];
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
            $this->fields['publication_place'] : [];
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
        $retVal = [];

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = ['856'];

        foreach ($fieldsToCheck as $field) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    $linktext = [];
                    $subfield3 = $url->getSubfield('3');
                    if ($subfield3 !== false) {
                        $linktext[] = ': ' . $subfield3->getData();
                    }

                    /*
                     * collect every subfield z and combine them.
                     */
                    $tmp = [];
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
                            $retVal[] = [
                                'url'  => $address->getData(),
                                'desc' => implode('', array_merge(array('Full text online'), $linktext))
                            ];
                        } else if ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '2' /*&& $subfield3->getData() == 'Finding aid'
                                                                                                --was causing a fatal error for searches returning certain records.
                                                                                                  This was preventing relevancy testing on dldc3*/) {
                            $retVal[] = [
                                'url'  => $address->getData(),
                                'desc' => 'Finding aid'
                            ];
                        } else if (
                            ($url->getIndicator(1) == ''  && $url->getIndicator(2) == '' ) ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '' ) ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '2') ||
                            ($url->getIndicator(1) == '4' && $url->getIndicator(2) == '8') ||
                            ($url->getIndicator(1) == '7' && $url->getIndicator(2) == '' )
                        ) {
                            $retVal[] = [
                                'url'  => $address->getData(),
                                'desc' => implode('', array_merge(array('Related resource'), $linktext))
                            ];
                        }
                    }
                }
            }
        }
        return $retVal;
    }

    /**
     * A heinous junk method for combining subfield data between multiple eholdings incorrectly
     * returned from OLE. This is necessary because of a bug in the way OLE is 
     * returning 098 fields. This method should be deleted and the getAllRecordLinks
     * method updated when this is finally fixed. The order of subfields matters!
     *
     * @param array of subfield data to clean up.
     *
     * @return array of combined subfield data.
     */
    protected function combineLinkData($marcData) {
        $retval = [];
        $i = 0;

        if (isset($marcData['urls'])) {
            foreach ($marcData['urls'] as $urls) {
                if (!empty($urls['subfieldData'])) {
                    $retval['urls'][$i]['label'] = $urls['label'];
                    $retval['urls'][$i]['currentField'] = $urls['currentField'];
                    $retval['urls'][$i]['subfieldData'] = $urls['subfieldData'];
                    foreach($marcData['displayText'] as $num => $displayText) {
                        if(!empty($displayText['subfieldData'])) {
                            foreach($displayText['subfieldData'] as $key => $value) {
                                $retval['urls'][$i]['subfieldData'][$key[0]] = $value; 
                            }
                            unset($marcData['displayText'][$num]);
                            break;
                        }
                    }
                    foreach($marcData['callnumber'] as $num => $callnumber) {
                        if(!empty($callnumber['subfieldData'])) {
                            foreach($callnumber['subfieldData'] as $key => $value) {
                                $retval['urls'][$i]['subfieldData'][$key[0]] = $value; 
                            }
                            unset($marcData['callnumber'][$num]);
                            break;
                        }
                    }
                    foreach($marcData['linkText'] as $num => $linkText) {
                        if(!empty($linkText['subfieldData'])) {
                            foreach($linkText['subfieldData'] as $key => $value) {
                                $retval['urls'][$i]['subfieldData'][$key[0]] = $value; 
                            }
                            unset($marcData['linkText'][$num]);
                            break;
                        }
                    }
                    foreach($marcData['note'] as $num => $note) {
                        if(!empty($note['subfieldData'])) {
                            $retval['urls'][$i]['subfieldData']['p'] = implode(' ', $note['subfieldData']);
                            unset($marcData['note'][$num]);
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
        $output = [];
        foreach (array('097', '098') as $field) {
            $donor_codes = $this->getFieldArray($field, ['e'], false);
            $donor = $this->getFieldArray($field, ['d'], false);

            $donor_pairs = [];
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
    protected $displayConfig = [
        /*Array of marc fields and sub fields displayed at the top of the full record view.
        Included in the array is a field for labels that will be used in the html
        Note, labels in the array are overriden in the templates in certain special cases*/
        'top' => [
            ['label'  => 'Title',
             'values' => ['245|abcfgknps']],
             //['label' => 'OCLC', 'values' => ['035|a', 'atlas_oclc']],
            ['label'  => 'Author / Creator',
             'values' => ['100|abcdequ']],
            ['label'  => 'Corporate author / creator',
             'values' => ['110|abcdegnu']],
            ['label'  => 'Meeting name',
             'values' => ['111|acndegjqu']],
            ['label'  => 'Uniform title',
             'values' => ['130|adfgklmnoprs','240|adfghklmnoprs','243|adfgklmnoprs']],
            ['label'  => 'Edition',
             'values' => ['250|ab','254|a']],
            ['label'  => 'Imprint',
             'values' => ['260|abcdefg','264|abc']],
            ['label'  => 'Description',
             'values' => ['300|abcefg3']],
            ['label'  => 'Language',
             'values' => ['041|abde', '008']], //the 008 field will blow up the crosswalk function. Instead we return languages outside of the loop with the getLanguages function
            ['label'  => 'Series',
             'values' => ['440|anpvx','490|avx','800|abcdefghklmnopqrstuv','810|abcdefghklmnoprstuv','811|acdefghklnpqstuv','830|adfghklmnoprstv']],
            ['label'  => 'Preceding Entry',
             'values' => ['780|abcdghikmnorstuxyz']],
            ['label'  => 'Succeeding Entry',
             'values' => ['785|abcdghikmnorstuxyz']],
            ['label'  => 'Subject',
             'values' => ['600|abcdefgklmnopqrstuvxyz','610|abcdefgklmnoprstuvxyz','611|acdefgklnpqstuvxyz','630|adfgklmnoprstvxyz','650|abcdevxyz','651|avxyz','654|abcvyz','655|abcvxyz']],
            ['label'  => 'Cartographic data',
             'values' => ['255|abcdefg']],
            ['label'  => 'Scale',
             'values' => ['507|ab']],
            ['label'  => 'Format',
             'values' => ['LDR|07']],
            ['label'  => 'Local Note',
             'values' => ['590|3a',]],
            ['label'  => 'URL for this record',
             'values' => ['URL|http://pi.lib.uchicago.edu/1001/cat/bib/']]
        ],
        /*Array of marc fields and sub fields displayed on the top portion of the 
        full record view but hidden by default.*/
        'top-hidden' => [
            ['label'  => 'Varying Form of Title',
             'values' => ['246|abfginp']],
            ['label'  => 'Title varies',
             'values' => ['247|abdefghnpx']],
            ['label'  => 'Other title',
             'values' => ['740|apn']],
            ['label'  => 'Other uniform titles', //if this label changes, we'll need to change it in author.phtml and in the otherAuthorsTitles function below, I can add an ID parameter to avoid this but I'm not going to worry about it now
             'values' => ['700|abcdfgiklmnopqrstux','710|abcdfgiklmnoprstux','711|acdefgiklnpqstux','730|adfiklmnoprs', '793|adfgiklmnoprs']], //700, 710, and 711 fields must come first 
            ['label'  => 'Other authors / contributors', //If this label changes, we'll need to change it in author.phtml too. 
             'values' => ['700|abcdequt','710|abcdegnut','711|acdegjnqut', '790|abcdequ', '791|abcdegnu', '792|acdegnqu']], //700, 710, and 711 fields must come first. Subfield t must be included for 700, 710, and 711. This is necessary for distinguishing from Other uniform titles. We will unset it in the templates.
            ['label'  => 'Frequency',
             'values' => ['310|ab', '315|ab', '321|ab']],
            ['label'  => 'ISBN',
             'values' => ['020|acz']],
            ['label'  => 'ISSN',
             'values' => ['022|ayz']],
            ['label'  => 'Instrumentation',
             'values' => ['382|abdenpv']],           
            ['label'  => 'Date/volume',
             'values' => ['362|az']],
            ['label'  => 'Computer file characteristics',
             'values' => ['256|a']],
            ['label'  => 'Physical medium',
             'values' => ['340|abcdefhijkmno3']],
            ['label'  => 'Geospatial data',
             'values' => ['342|abcdefghijklmnopqrstuvw']],
            ['label'  => 'Planar coordinate data',
             'values' => ['343|abcdefghi']],
            ['label'  => 'Sound characteristics',
             'values' => ['344|abcdefgh3']],
            ['label'  => 'Projection characteristics',
             'values' => ['345|ab3']],
            ['label'  => 'Video characteristics',
             'values' => ['346|ab3']],
            ['label'  => 'Digital file characteristics',
             'values' => ['347|abcdef3']],
            ['label'  => 'Language/Script',
             'values' => ['540|abcdu']],
            ['label'  => 'Provenance',
             'values' => ['561|3a', '562|3abcde', '563|3a']],
            ['label'  => 'Notes',
             'values' => ['500|a', '501|a', '502|a', '504|a', '506|abcdefu', '508|a', '510|abcux', '511|a', '513|ab', '514|abcdefghijkmuz', '515|a', '516|a', '518|adop3', '525|a', '530|abcdu', '533|abcdefmn', '534|abcefklmnptxz', '535|abcdg', '536|abcdefgh', '538|aiu', '544|abcden', '545|abu', '546|ab', '547|a', '550|a', '552|abcdefghijklmnopu', '580|a', '583|abcdefhijklnouxz', '588|a', '598|a']],
            ['label'  => 'Summary',
             'values' => ['520|abu']],
            ['label'  => 'Target Audience',
             'values' => ['521|ab']],
            ['label'  => 'Geographic coverage',
             'values' => ['522|a']],
            ['label'  => 'Cite as',
             'values' => ['524|a']],
            ['label'  => 'Study Program Information',
             'values' => ['526|abcdixz']],
            ['label'  => 'Cumulative Index / Finding Aids Note',
             'values' => ['555|abcdu']],
            ['label'  => 'Documentation',
             'values' => ['556|az']],
            ['label'  => 'Case File Characteristics',
             'values' => ['565|abcde']],
            ['label'  => 'Methodology',
             'values' => ['567|a']],
            ['label'  => 'Publications',
             'values' => ['581|az']],
            ['label'  => 'Awards',
             'values' => ['586|a']],
            ['label'  => 'Subseries of',
             'values' => ['760|abcdghimnostxy']],
            ['label'  => 'Has subseries',
             'values' => ['762|abcdghimnostxy']],
            ['label'  => 'Translation of',
             'values' => ['765|abcdghikmnorstuxyz']],
            ['label'  => 'Translated as',
             'values' => ['767|abcdghikmnorstuxyz']],
            ['label'  => 'Has supplement',
             'values' => ['770|abcdghikmnorstuxyz']],
            ['label'  => 'Supplement to',
             'values' => ['772|abcdghikmnorstuxyz']],
            ['label'  => 'Part of',
             'values' => ['773|abstx']],
            ['label'  => 'Contains these parts',
             'values' => ['774|abstx']],
            ['label'  => 'Other edition available',
             'values' => ['775|abcdefghikmnorstuxyz']],
            ['label'  => 'Other form',
             'values' => ['776|abcdghikmnorstuxyz']],
            ['label'  => 'Issued with',
             'values' => ['777|abcdghikmnostxy']],
            ['label'  => 'Data source',
             'values' => ['786|abcdhijkmnoprstuxy']],
            ['label'  => 'Related title',
             'values' => ['787|abcdghikmnorstuxyz']],
            ['label'  => 'Standard no.',
             'values' => ['024|acdz']],
            ['label'  => 'Publisher\'s no.',
             'values' => ['028|ab']],
            ['label'  => 'CODEN',
             'values' => ['030|az']],
            ['label'  => 'GPO item no.',
             'values' => ['074|az']],
            ['label'  => 'Govt.docs classification',
             'values' => ['086|az']],
        ],
        /**
         * Array of marc fields to display for the Atlas plugins.
         */
        'atlas' => [
            ['label'  => 'atlas_author',
             'values' => ['100|a', '110|a', '111|a']],
            ['label'  => 'atlas_title',
             'values' => ['245|a']],
            ['label'  => 'atlas_full_title',
             'values' => ['245|abcfghknps68']],
            ['label'  => 'atlas_publication_place',
             'values' => ['260|a']],
            ['label'  => 'atlas_publisher',
             'values' => ['260|b']],
            ['label'  => 'atlas_publication_date',
             'values' => ['260|c']],
            ['label'  => 'atlas_format',
             'values' => ['LDR|07']],
            ['label'  => 'atlas_issn',
             'values' => ['022|a']],
            ['label'  => 'atlas_edition',
             'values' => ['250|ab368']],
            ['label'  => 'atlas_pages',
             'values' => ['300|a']],
            ['label'  => 'atlas_oclc',              
             'values' => ['035|a']], 
        ],
        /**
         * Array of marc fields to display for Hathi links on records 
         * with bib numbers having an ocm or ocn prefix.
         */
        'hathiLink' => [
            ['label'  => 'oclcNumber',
             'values' => ['035|a']],
            ['label'  => 'Project identifier',
             'values' => ['903|a']],
        ],
        /**
         * Array of marc fields to display for 
         * eHoldings on full record and results pages.
         */
        'eHoldings' => [
            ['label'  => 'urls',
             'values' => ['098|u']],
            ['label'  => 'displayText',
             'values' => ['098|cl']],
            ['label'  => 'callnumber',
             'values' => ['098|a']],
            ['label'  => 'note',
             'values' => ['098|p']],
            ['label'  => 'linkText',
             'values' => ['098|z']],
        ],
        /**
         * Array of marc fields to display for 
         * MARC holdings  on results pages.
         */
        'results' => [
            ['label'  => 'title',
             'values' => ['245|abfgknps']],
            ['label'  => 'author',
             'values' => ['100|abcdequ']],
            ['label'  => 'corporate-author',
             'values' => ['110|abcdegnu']],
            ['label'  => 'edition',
             'values' => ['250|ab','254|a']],
            ['label'  => 'imprint',
             'values' => ['260|abcdefg','264|abc']], 
            ['label'  => 'Description',
             'values' => ['300|abcefg3']],
            ['label'  => 'Series',
             'values' => ['440|anpvx', '490|avx', '830|anpvsx', '800|acdtklfv', '810|abtv']],
        ],
    ]; // end $displayConfig

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
        $output = [];

        foreach ($marcFieldsArray as $entry) {

        $html_id = $entry['label'];
        $the_marc_fields = $entry['values'];

            foreach ($the_marc_fields as $the_marc_fields) {

                $field = array_shift(explode("|", $the_marc_fields));

                /*Get an array of subfields for this marc field */
                $subfields = array_pop(explode("|", $the_marc_fields));
                $output[] = [$field, $subfields, $html_id];
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
        $v = [];
        foreach($vernacularData as $vernacularFields) {
            /*Only use subfield data from the File_MARC_Data_Field object: returns an array of File_MARC_Subfield objects*/
            $vernacularSubfields = $vernacularFields->getSubfields();

            $s6 = $vernacularFields->getSubfield('6');
            if ($s6) {
                $subfield6 = array_shift(explode('-', $s6->getData()));
            } else {
                $subfield6 = '';
            }

            /*Array for vernacular subfield data*/
            $vs = [];
            /*Put subfield data into an array with the correct hierarchy*/
            foreach($vernacularSubfields as $vernacular) {
                /*Get the data we wish to return*/
                $vernacularString = $vernacular->getData();
                
                /*Match the returned strings with the value of subfield 6.
                If a title of a book ever starts with 3 numbers that happen to be identical to the value of subfield 6 during the current iteration in the loop, there could be a problem*/ 
                if($subfield6 && preg_match('/^'.$subfield6.'/', $vernacularString) == 1){
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
        $output = [];

        foreach ($array as $d) {
           
            foreach ($d as $k => $value) {
               
                if (!isset($output[$k])) {
                    $output[$k] = [];
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
        $dataCollection = [];
 
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
                $current = [];
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
                $currentVernacular = [];
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
            $currentOther = [];
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
                 
                $currentVernacular = [];
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
