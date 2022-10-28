<?php
/**
 * Factory for record driver data formatting view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2016.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace UChicago\View\Helper\Phoenix;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatterFactory extends \VuFind\View\Helper\Root\RecordDataFormatterFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException&\Throwable if any other error occurs
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $helper = new $requestedName();
        $helper->setDefaults(
            'collection-info', [$this, 'getDefaultCollectionInfoSpecs']
        );
        $helper->setDefaults(
            'collection-record', [$this, 'getDefaultCollectionRecordSpecs']
        );
        $helper->setDefaults('core', [$this, 'getDefaultCoreSpecs']);
        $helper->setDefaults('hidden', [$this, 'getDefaultHiddenSpecs']);
        $helper->setDefaults('description', [$this, 'getDefaultDescriptionSpecs']);
        return $helper;
    }

    /**
     * Get the callback function for processing authors.
     *
     * @return callable
     */
    protected function getAuthorFunction()
    {
        return function ($data, $options) {
            // Lookup array of singular/plural labels (note that Other is always
            // plural right now due to lack of translation strings).
            $labels = [
                'primary' => ['Main Author', 'Main Authors'],
                'corporate' => ['Corporate Author', 'Corporate Authors'],
                'secondary' => ['Other Authors', 'Other Authors'],
            ];
            // Lookup array of schema labels.
            $schemaLabels = [
                'primary' => 'author',
                'corporate' => 'creator',
                'secondary' => 'contributor',
            ];
            // Lookup array of sort orders.
            $order = ['primary' => 1, 'corporate' => 2, 'secondary' => 3];

            // Sort the data:
            $final = [];
            foreach ($data as $type => $values) {
                $final[] = [
                    'label' => $labels[$type][count($values) == 1 ? 0 : 1],
                    'values' => [$type => $values],
                    'options' => [
                        'pos' => $options['pos'] + $order[$type],
                        'renderType' => 'RecordDriverTemplate',
                        'template' => 'data-authors.phtml',
                        'context' => [
                            'type' => $type,
                            'schemaLabel' => $schemaLabels[$type],
                            'requiredDataFields' => [
                                ['name' => 'role', 'prefix' => 'CreatorRoles::']
                            ],
                        ],
                    ],
                ];
            }
            return $final;
        };
    }


    /**
     * Get default specifications for displaying data in collection-info metadata.
     *
     * @return array
     */
    public function getDefaultCollectionInfoSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setMultiLine(
            'Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction()
        );
        $spec->setLine('Summary', 'getSummary');
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine(
            'Language', 'getLanguages', null,
            ['itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
             'itemSuffix' => '</span></span>']
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['itemPrefix' => '<span property="bookEdition">',
             'itemSuffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setLine('Notes', 'getGeneralNotes');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine(
            'ISBN', 'getISBNs', null,
            ['itemPrefix' => '<span property="isbn">', 'itemSuffix' => '</span>']
        );
        $spec->setLine(
            'ISSN', 'getISSNs', null,
            ['itemPrefix' => '<span property="issn">', 'itemSuffix' => '</span>']
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-record metadata.
     *
     * @return array
     */
    public function getDefaultCollectionRecordSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setLine('Summary', 'getSummary');
        $spec->setMultiLine(
            'Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction()
        );
        $spec->setLine(
            'Language', 'getLanguages', null,
            ['itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
             'itemSuffix' => '</span></span>']
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Author / Creator', 'getUCAuthorCreator', 'data-uc-author.phtml'
        );
        $spec->setTemplateLine(
            'Corporate author / creator', 'getUCCorporateAuthorCreator', 'data-uc-author.phtml'
        );
        $spec->setTemplateLine(
            'Meeting name', 'getUCMeetingName', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Uniform title', 'getUCUniformTitle', 'data-uc-uniform-title.phtml'
        );
        $spec->setTemplateLine(
            'Edition', 'getUCEdition', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Imprint', 'getUCImprint', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Description', 'getUCDescription', 'data-uc-simple.phtml'
        );
        $spec->setLine(
            'Language', 'getLanguages', null,
            ['itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
             'itemSuffix' => '</span></span>']
        );
        $spec->setTemplateLine(
            'Series', 'getUCSeries', 'data-uc-series.phtml'
        );
        $spec->setTemplateLine(
            'Subject', 'getUCSubject', 'data-uc-subject.phtml'
        );
        $spec->setTemplateLine(
            'Cartographic data', 'getUCCartographicData', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Scale', 'getUCScale', 'data-uc-simple.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Local Note', 'getUCLocalNote', 'data-uc-local-note.phtml'
        );
        $spec->setTemplateLine(
            'URL for this record', 'getUCRecordURL', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying bib data that is displayed at the
     * top with the core data but hidden by default.
     *
     * @return array
     */
    public function getDefaultHiddenSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Varying Form of Title', 'getUCVaryingFormOftitle', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Title varies', 'getUCTitleVaries', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Other title', 'getUCOtherTitle', 'data-uc-simple.phtml'
        );
        $spec->setTemplateLine(
            'Other uniform titles', 'getUCOtherUniformTitles', 'data-uc-uniform-title.phtml'
        );
        $spec->setTemplateLine(
            'Other authors / contributors', 'getUCOtherAuthorsContributors', 'data-uc-other-author.phtml'
        );
        $spec->setTemplateLine(
            'Frequency', 'getUCFrequency', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'ISBN', 'getUCISBN', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'ISSN', 'getUCISSN', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Instrumentation', 'getUCInstrumentation', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Date / volume', 'getUCDateVolume', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Computer file characteristics', 'getUCComputerFileChars', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Physical medium', 'getUCPhysicalMedium', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Geospatial data', 'getUCGeospatialData', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Planar coordinate data', 'getUCPlanarCoordinateData', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Sound characteristics', 'getUCSoundCharacteristics', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Projection characteristics', 'getUCProjectionCharacteristics', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Video characteristics', 'getUCVideoCharacteristics', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Digital file characteristics', 'getUCDigitalFileCharacteristics', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Language / Script', 'getUCLanguageScript', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Provenance', 'getUCProvenance', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Notes', 'getUCNotes', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Summary', 'getUCSummary', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Target Audience', 'getUCTargetAudience', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Geographic coverage', 'getUCGeographicCoverage', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Cite as', 'getUCCiteAs', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Study Program Information', 'getUCStudyPI', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Cumulative Index / Finding Aids Note', 'getUCCIFindingAidsNote', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Documentation', 'getUCDocumentation', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Case File Characteristics', 'getUCCaseFileCharacteristics', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Methodology', 'getUCMethodology', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Publications', 'getUCPublications', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Awards', 'getUCAwards', 'data-uc-simple.phtml' 
        );
        $spec->setTemplateLine(
            'Other form', 'getUCOtherForm', 'data-uc-simple.phtml' 
        );
        $spec->settemplateline(
            'Standard no.', 'getUCStandardNo', 'data-uc-simple.phtml' 
        );
        $spec->settemplateline(
            'Publisher\'s no.', 'getUCPublisherNo', 'data-uc-simple.phtml' 
        );
        $spec->settemplateline(
            'CODEN', 'getUCCODEN', 'data-uc-simple.phtml' 
        );
        $spec->settemplateline(
            'GPO item no.', 'getUCGPOItemNo', 'data-uc-simple.phtml' 
        );
        $spec->settemplateline(
            'Govt.docs classification', 'getUCGovtDocsClassification', 'data-uc-simple.phtml' 
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultDescriptionSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine('Summary', true, 'data-summary.phtml');
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('Format', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine(
            'ISBN', 'getISBNs', null,
            ['itemPrefix' => '<span property="isbn">', 'itemSuffix' => '</span>']
        );
        $spec->setLine(
            'ISSN', 'getISSNs', null,
            ['itemPrefix' => '<span property="issn">', 'itemSuffix' => '</span>']
        );
        $spec->setLine(
            'DOI', 'getCleanDOI', null,
            ['itemPrefix' => '<span property="identifier">',
             'itemSuffix' => '</span>']
        );
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        return $spec->getArray();
    }
}
