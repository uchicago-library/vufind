<?php

namespace UChicago\RecordDriver;

class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    ### UChicago customization ###
    // Must exist to override \VuFind\Marc\Serialization\Iso2709
    // Added to allow MARC records over 99,999 characters to display
    // This can probably be eliminated after upgrade to VuFind 9.0 along with overrides
    // in UChicago/Marc/MarcReader.php and UChicago/Marc/Serialization/Iso2709.php
    protected $marcReaderClass = \UChicago\Marc\MarcReader::class;
    ### ./UChicago customization ###

    /**
     * UChicago customization: this disables the summaries that are displayed
     * in the description tab and under the title on the full record page.
     * There is no way to do this in the config.
     *
     * @return array
     */
    public function getSummary()
    {
        return [];
    }

    /**
     * UChicago customization: modified only to add support for ICU numbers.
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param array $field Field to examine
     *
     * @return array|bool  Array on success, boolean false if no valid link could be
     * found in the data.
     */
    protected function getFieldData($field)
    {
        // Make sure that there is a t field to be displayed:
        if (!($title = $this->getSubfield($field, 't'))) {
            return false;
        }

        $linkTypeSetting = $this->mainConfig->Record->marc_links_link_types
            ?? 'id,oclc,dlc,isbn,issn,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $this->getSubfields($field, 'w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)) {
            ### UChicago customization ###
            case 'icu':
                foreach ($linkFields as $current) {
                    if ($icu = $this->getIdFromLinkingField($current, 'ICU', true)) {
                        // Bail if we have bad data, e.g. (ICU)BID=4430978.
                        if (!is_numeric($icu)) {
                            $linkType = '';
                            continue;
                        }
                        $link = ['type' => 'icu', 'value' => $icu];
                    }
                }
                // We are looping over link types in the config (marc_links_link_types).
                // The loop goes in the same order as the config. The first item we have
                // configured is ID which is a bib. ICU is another way we express bib
                // numbers and it's the second item in the config. If we're on a 776
                // field, we don't want anything other than a bib link so we break out
                // of the switch and the outer loop.
                if ($field['tag'] === '776') {
                    break 2;
                }
                break;
            ### ./UChicago customization ###
            case 'oclc':
                foreach ($linkFields as $current) {
                    if ($oclc = $this->getIdFromLinkingField($current, 'OCoLC')) {
                        $link = ['type' => 'oclc', 'value' => $oclc];
                    }
                }
                break;
            case 'dlc':
                foreach ($linkFields as $current) {
                    if ($dlc = $this->getIdFromLinkingField($current, 'DLC', true)) {
                        $link = ['type' => 'dlc', 'value' => $dlc];
                    }
                }
                break;
            case 'id':
                foreach ($linkFields as $current) {
                    if ($bibLink = $this->getIdFromLinkingField($current)) {
                        $link = ['type' => 'bib', 'value' => $bibLink];
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $this->getSubfield($field, 'z')) {
                    $link = [
                        'type' => 'isn', 'value' => $isbn,
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'issn':
                if ($issn = $this->getSubfield($field, 'x')) {
                    $link = [
                        'type' => 'isn', 'value' => $issn,
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'title':
                $link = ['type' => 'title', 'value' => $title];
                break;
            }
            // Exit loop if we have a link
            if (isset($link)) {
                break;
            }
        }
        // Make sure we have something to display:
        return !isset($link) ? false : [
            'title' => $this->getRecordLinkNote($field),
            'value' => $title,
            'link'  => $link
        ];
    }

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

    public function simpleParse($field, $subfields)
    {
        $data = $this->getFieldArray($field, $subfields);
        $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields);
        $retval = '';
        foreach ($data as $d) {
            $retval .= $d . '<br/>';
        }
        foreach ($altData as $a) {
            $retval .= $a . '<br/>';
        }
        return $retval;
    }

    public function multiSimpleParse($fields)
    {
        $retval = '';
        foreach ($fields as $data) {
            $retval .= $this->simpleParse($data[0], $data[1]); 
        }
        return $retval;
    }

    /**
     * Subfield e data stripped out for link.
     */ 
    public function authorParse($field, $subfields)
    {
        $data = $this->getFieldArray($field, $subfields);
        $linkData = $this->getFieldArray($field, array_diff($subfields, ['e'])); // Strip out subfield e for links.
        $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields);
        return ['displayData' => $data, 'linkData' => $linkData, 'altData' => $altData];
    }

    /**
     * Subfield e data stripped out for link.
     */
    public function seriesParse($fields)
    {
        $retval = [];
        foreach ($fields as $multi) {
            $field = $multi[0];
            $subfields = $multi[1];
            $data = $this->getFieldArray($field, $subfields);
            if ($field != '490') {
                $linkData = $this->getFieldArray($field, array_diff($subfields, ['v'])); // Strip out subfield v for links.
            } else {
                $linkData = [];
            }
            $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields);
            array_push($retval, ['displayData' => $data, 'linkData' => $linkData, 'altData' => $altData]);
        }
        return $retval; 
    }

    public function subjectParse($fields)
    {
        $retval = [];
        foreach ($fields as $multi) {
            $field = $multi[0];
            $subfields = $multi[1];
            $data = $this->getFieldArray($field, $subfields, true, ' -- ');
            $i2s = [];
            $ffs = $this->getMarcRecord()->getFields($field);
            if (!empty($ffs)) {
                foreach ($ffs as $i2) {
                    $s2 = $i2->getSubfield('2');
                    if ($s2 != false) {
                        $heading = $s2->getData();
                        if (strtolower($heading) === 'fast' && $i2->getIndicator(2) === '7') {
                            array_push($i2s, true);
                        } else {
                            array_push($i2s, false);
                        }
                    } else {
                        array_push($i2s, false);
                    }
                }
            }
            $linkData = $this->getFieldArray($field, $subfields, true, '--');
            $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields);
            array_push($retval, ['displayData' => $data, 'linkData' => $linkData, 'altData' => $altData, 'areFasts' => $i2s]);
        }
        return $retval;
    }

    public function uniformTitleParse($fields)
    {
        $retval = [];
        foreach ($fields as $multi) {
            $field = $multi[0];
            $subfields = $multi[1];
            $data = $this->getFieldArray($field, $subfields);
            $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields);
            array_push($retval, ['displayData' => $data, 'linkData' => $data, 'altData' => $altData]);
        }
        return $retval;
    }

    public function otherAuthorsAndContributorsParse($fields)
    {
        $retval = [];
        foreach ($fields as $data) {
            $t = $this->getFieldArray($data[0], ['t']);
            $subfields = array_diff($data[1], ['t']);
            if (empty($t)) {
                array_push($retval, $this->authorParse($data[0], $subfields));
            }
        }
        return $retval;
    }

    public function otherUniformTitlesParse($fields)
    {
        $retval = [];
        foreach ($fields as $data) {
            $field = $data[0];
            $subfields = $data[1];
            $data = $this->getFieldArray($field, $subfields);
            $altData = $this->getMarcReader()->getLinkedFieldsSubFields('880', $field, $subfields); 
            if ($field == '700' || $field == '710' || $field == '711') {
                $t = $this->getFieldArray($field, ['t']);
                if (!empty($t)) {
                    array_push($retval, ['displayData' => $data, 'linkData' => $data, 'altData' => $altData]);
                }
            } else {
                array_push($retval, ['displayData' => $data, 'linkData' => $data, 'altData' => $altData]);
            }
        }
        return $retval;
    }

    public function getUCAuthorCreator()
    {
        $field = '100';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'q', 'u'];
        return $this->authorParse($field, $subfields);
    }

    public function getUCCorporateAuthorCreator()
    {
        $field = '110';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'g', 'n', 'u'];
        return $this->authorParse($field, $subfields);
    }

    public function getUCMeetingName()
    {
        $field = '111';
        $subfields = ['a', 'c', 'n', 'd', 'e', 'g', 'j', 'q', 'u'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCUniformTitle()
    {
        $fields = [
            ['130', ['a', 'd', 'f', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's']],
            ['240', ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's']],
            ['243', ['a', 'd', 'f', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's']],
        ];
        return $this->uniformTitleParse($fields);
    }

    public function getUCEdition()
    {
        $fields = [
            ['250', ['a', 'b']],
            ['254', ['a']],
        ];
        return $this->multiSimpleParse($fields);
    }

    public function getUCImprint()
    {
        $fields = [
            ['260', ['a', 'b', 'c', 'd', 'e', 'f', 'g']],
            ['264', ['a', 'b', 'c']],
        ];
        return $this->multiSimpleParse($fields);
    }

    public function getUCDescription()
    {
        $field = '300';
        $subfields = ['a', 'b', 'c', 'e', 'f', 'g', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCSeries()
    {
        $fields = [
            ['440', ['a', 'n', 'p', 'v', 'x']],
            ['490', ['a', 'v', 'x']],
            ['800', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v']],
            ['810', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'v']],
            ['811', ['a', 'c', 'd', 'e', 'f', 'g', 'h', 'k', 'l', 'n', 'p', 'q', 's', 't', 'u', 'v']],
            ['830', ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'v']],
            ['930', ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'v']],
        ];
        return $this->seriesParse($fields);
    }

    public function getUCSeriesResults()
    {
        $fields = [
            ['490', ['a', 'v', 'x']],
        ];
        return $this->seriesParse($fields);
    }

    public function getUCVolumeNumber()
    {
        $fields = [
            ['490', ['v']],
        ];
        return $this->seriesParse($fields);
    }

    public function getUCSubject()
    {
        $fields = [
            ['600', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z']],
            ['610', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z']],
            ['611', ['a', 'c', 'd', 'e', 'f', 'g', 'k', 'l', 'n', 'p', 'q', 's', 't', 'u', 'v', 'x', 'y', 'z']],
            ['630', ['a', 'd', 'f', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'v', 'x', 'y', 'z']],
            ['650', ['a', 'b', 'c', 'd', 'e', 'v', 'x', 'y', 'z']],
            ['651', ['a', 'v', 'x', 'y', 'z']],
            ['654', ['a', 'b', 'c', 'v', 'y', 'z']],
            ['655', ['a', 'b', 'c', 'v', 'x', 'y', 'z']],
        ];
        return $this->subjectParse($fields);
    }

    public function getUCCartographicData()
    {
        $field = '255';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCScale()
    {
        $field = '507';
        $subfields = ['a', 'b'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCLocalNote()
    {
        $field = '590';
        $subfields = ['3', 'a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCRecordURL()
    {
        return 'http://pi.lib.uchicago.edu/1001/cat/bib/' . $this->getUniqueID();
    }

    public function getUCVaryingFormOftitle()
    {
        $field = '246';
        $subfields = ['a', 'b', 'f', 'g', 'i', 'n', 'p'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCTitleVaries()
    {
        $field = '247';
        $subfields = ['a', 'b', 'd', 'e', 'f', 'g', 'h', 'n', 'p', 'x'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCOtherTitle()
    {
        $field = '740';
        $subfields = ['a', 'p', 'n'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCOtherUniformTitles()
    {
        $fields = [
            ['700', ['a', 'b', 'c', 'd', 'f', 'g', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'x']],
            ['710', ['a', 'b', 'c', 'd', 'f', 'g', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'x']],
            ['711', ['a', 'c', 'd', 'e', 'f', 'g', 'i', 'k', 'l', 'n', 'p', 'q', 's', 't', 'u', 'x']],
            ['730', ['a', 'd', 'f', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's']],
            ['793', ['a', 'd', 'f', 'g', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's']],
        ];
        return $this->otherUniformTitlesParse($fields);
    }

    // Subfield t must be included for 700, 710, and 711. This is necessary for distinguishing from Other uniform titles. We won't display it.
    public function getUCOtherAuthorsContributors()
    {
        $fields = [
            ['700', ['a', 'b', 'c', 'd', 'e', 'q', 'u', 't']],
            ['710', ['a', 'b', 'c', 'd', 'e', 'g', 'n', 'u', 't']],
            ['711', ['a', 'c', 'd', 'e', 'g', 'j', 'n', 'q', 'u', 't']],
            ['790', ['a', 'b', 'c', 'd', 'e', 'q', 'u']],
            ['791', ['a', 'b', 'c', 'd', 'e', 'g', 'n', 'u']],
            ['792', ['a', 'c', 'd', 'e', 'g', 'n', 'q', 'u']],
        ];
        return $this->otherAuthorsAndContributorsParse($fields);
    }

    public function getUCFrequency()
    {
        $fields = [
            ['310', ['a', 'b']],
            ['315', ['a', 'b']],
            ['321', ['a', 'b']],
        ];
        return $this->multiSimpleParse($fields);
    }

    public function getUCISBN()
    {
        $field = '020';
        $subfields = ['a', 'c', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCISSN()
    {
        $field = '022';
        $subfields = ['a', 'y', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCInstrumentation()
    {
        $field = '382';
        $subfields = ['a', 'b', 'd', 'e', 'n', 'p', 'v'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCDateVolume()
    {
        $field = '362';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCComputerFileChars()
    {
        $field = '256';
        $subfields = ['a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCPhysicalMedium()
    {
        $field = '340';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', 'h', 'i', 'j', 'k', 'm', 'n', 'o', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCGeospatialData()
    {
        $field = '342';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCPlanarCoordinateData()
    {
        $field = '343';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCSoundCharacteristics()
    {
        $field = '344';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCProjectionCharacteristics()
    {
        $field = '345';
        $subfields = ['a', 'b', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCVideoCharacteristics()
    {
        $field = '346';
        $subfields = ['a', 'b', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCDigitalFileCharacteristics()
    {
        $field = '347';
        $subfields = ['a', 'b', 'c', 'd', 'e', 'f', '3'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCLanguageScript()
    {
        $field = '540';
        $subfields = ['a', 'b', 'c', 'd', 'u'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCProvenance()
    {
        $fields = [
            ['541', ['a', 'b', 'c', 'd', 'e', 'f', 'h', 'n', 'o']],
            ['561', ['3', 'a']],
            ['562', ['3', 'a', 'b', 'c', 'd', 'e']],
            ['563', ['3', 'a']],
        ];
        return $this->multiSimpleParse($fields);
    }

    public function getUCNotes()
    {
        $fields = [
            ['500', ['a']],
            ['501', ['a']],
            ['502', ['a']],
            ['504', ['a']],
            ['506', ['a', 'b', 'c', 'd', 'e', 'f', 'u']],
            ['508', ['a']],
            ['510', ['a', 'b', 'c', 'u', 'x']],
            ['511', ['a']],
            ['513', ['a', 'b']],
            ['514', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'm', 'u', 'z']],
            ['515', ['a']],
            ['516', ['a']],
            ['518', ['a', 'd', 'o', 'p', '3']],
            ['525', ['a']],
            ['530', ['a', 'b', 'c', 'd', 'u']],
            ['533', ['a', 'b', 'c', 'd', 'e', 'f', 'm', 'n']],
            ['534', ['a', 'b', 'c', 'e', 'f', 'k', 'l', 'm', 'n', 'p', 't', 'x', 'z']],
            ['535', ['a', 'b', 'c', 'd', 'g']],
            ['536', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']],
            ['538', ['a', 'i', 'u']],
            ['544', ['a', 'b', 'c', 'd', 'e', 'n']],
            ['545', ['a', 'b', 'u']],
            ['546', ['a', 'b']],
            ['547', ['a']],
            ['550', ['a']],
            ['552', ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'u']],
            ['580', ['a']],
            ['583', ['a', 'b', 'c', 'd', 'e', 'f', 'h', 'i', 'j', 'k', 'l', 'n', 'o', 'u', 'x', 'z']],
            ['588', ['a']],
            ['598', ['a']],
        ];
        return $this->multiSimpleParse($fields);
    }

    public function getUCSummary()
    {
        $field = '520';
        $subfields = ['a', 'b', 'u'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCTargetAudience()
    {
        $field = '521';
        $subfields = ['a', 'b'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCGeographicCoverage()
    {
        $field = '522';
        $subfields = ['a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCCiteAs()
    {
        $field = '524';
        $subfields = ['a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCStudyPI()
    {
        $field = '526';
        $subfields = ['a', 'b', 'c', 'd', 'i', 'x', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCCIFindingAidsNote()
    {
        $field = '555';
        $subfields = ['a', 'b', 'c', 'd', 'u'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCDocumentation()
    {
        $field = '556';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCCaseFileCharacteristics()
    {
        $field = '565';
        $subfields = ['a', 'b', 'c', 'd', 'e'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCMethodology()
    {
        $field = '567';
        $subfields = ['a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCPublications()
    {
        $field = '581';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCAwards()
    {
        $field = '586';
        $subfields = ['a'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCOtherForm()
    {
        $field = '776';
        $subfields = ['a', 'b', 'c', 'd', 'g', 'h', 'i', 'k', 'm', 'n', 'o', 'r', 's', 't', 'u', 'x', 'y', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCStandardNo()
    {
        $field = '024';
        $subfields = ['a', 'c', 'd', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCPublisherNo()
    {
        $field = '028';
        $subfields = ['a', 'b'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCCODEN()
    {
        $field = '030';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCGPOItemNo()
    {
        $field = '074';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }

    public function getUCGovtDocsClassification()
    {
        $field = '086';
        $subfields = ['a', 'z'];
        return $this->simpleParse($field, $subfields);
    }
}
