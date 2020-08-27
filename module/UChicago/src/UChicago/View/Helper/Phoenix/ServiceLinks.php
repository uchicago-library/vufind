<?
namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class for managing phoenix theme's conditional service links 
 *
 * @category View_Helpers
 * @package ServiceLinks
 * @author Brad Busenius <bbusenius@uchicago.edu>
 */
class ServiceLinks extends AbstractHelper {
     
    public function __construct($config=false) {
         $this->linkConfig = $config; 
    }

    /**
     * Helper function for creating the link markup 
     *
     * @param link, url for the href    
     * @param text, display text for the link
     * @param classes, optional array of css classes to append
     * defaults to empty array.
     * @param dataAttributes, optional array of html data attributes
     * defaults to empty array.
     * @param tagline, a helper text to append after the link
     * defaults to an empty string
     *        	
     * @return html string
     */
    protected function getServiceLinkTemplate($link, $text, $classes=[], $dataAttributes=[], $tagline='') {
        $da = '';
        if ($dataAttributes) {
            foreach ($dataAttributes as $a => $v) {
                $da .= 'data-' . $a . '="' . $v . '"';
            }
        }
        if ($tagline) {
          $tagline = ' <span class="service-tagline">' . $tagline . '</span>';
        }
        return '<a href="' . $link . '" class="' . (empty($classes) ? 'service' : '') . implode(' ', $classes) . '" ' . $da . '>' . $text . '</a>' . $tagline;
    }

    /**
     * Helper function returns a designated part of the location string.
     * Kuali OLE has a 5 tier hierarchy of locations. At UChicago we only use 3.
     * These are separated by /s e.g. UC/JRL/Gen. Each part separated by a / correlates
     * to a level. Level 1 is the institution, level 3 is the library, and level 5 is 
     * the shelving location (we don't use levels 2 and 4). 
     *
     * @param locationString, a location string hierarchy with blocks separated by /
     * @param level, string institution, library or shelving. Refers to the section of the location string you want 
     *        	
     * @return a simple location string converted to lower case for matching
     */
    public function getLocation($locationString, $level) {
        $blocks = explode('/', $locationString);
        if($level == 'institution') {
            $location = $blocks[0];
        }
        elseif($level == 'library') {
            $location = $blocks[1];
        }
        elseif($level == 'shelving') {
            $location = $blocks[2];
        }
        else {
            error_log('You did not set the right parameters in your call to getLocation()');
        }
        return strtolower($location);
    }

    /**
     * Placeholders that are allowed in the config.ini. These are
     * used to create dynamic fallback URLs that override default 
     * URLs when a fallback is needed.
     */
    public $allowedPlaceholders = array ('BARCODE', 'ID');

    /**
     * Configuration array for shelving location related logic that conditional service links 
     * depend on. This is a middle step towards having these values drawn from a database.
     * Some keys are used as whitelists and some keys are used as blacklists. 
     * Make sure you know what the conditional logic requires before you update.
     */
    protected $lookupLocation = [
                        'scrcManuscript' =>
                            ['Arch',
                             'ArcSer',
                             'Mss',
                             'MssCdx'],
                        'scrcInMansueto' => 
                            ['ACASA',
                             'ATK',
                             'amdrama',
                             'amnewsp',
                             'arch',
                             'ARCHASR',
                             'ArcMon',
                             'atk',
                             'aust',
                             'drama',
                             'eb',
                             'hcb',
                             'incun',
                             'JzMon',
                             'linc',
                             'lincke',
                             'mopora',
                             'Mss',
                             'MSSASR',
                             'msscdx',
                             'msscr',
                             'RaCrInc',
                             'Rare',
                             'RAREASR',
                             'rarecr',
                             'RARECRASR',
                             'rege',
                             'rosen',
                             'UCPress'],
                        /*Whitelist*/
                        'borrowDirect' => 
                             ['ArtResA',
                             'EckRes',
                             'JRLRES',
                             'LawRes',
                             'LawResC',
                             'LawResP',
                             'Res',
                             'Resup',
                             'ResupC',
                             'ResupD',
                             'ResupE',
                             'ResupS',
                             'SSAdRes',
                             'SciRes'],
                        'cantFindIt' =>
                            ['Art420',
                             'ArtResA',
                             'CDEV',
                             'CJK',
                             'CJKRef',
                             'CJKRfHY',
                             'CJKSPer',
                             'CMC',
                             'EckX',
                             'Film',
                             'Gen',
                             'GenHY',
                             'Law',
                             'LawAid',
                             'LawCity',
                             'LawCS',
                             'LawDisp',
                             'LawPer',
                             'LawRef',
                             'LawResP',
                             'LawRR',
                             'MapCl',
                             'MapRef',
                             'Mic',
                             'MidEMic',
                             'Pam',
                             'PerBio',
                             'PerPhy',
                             'RR',
                             'RR2Per',
                             'RR4',
                             'RR4Cla',
                             'RR4J',
                             'RR5',
                             'RR5EA',
                             'RR5EPer',
                             'RR5Per',
                             'RRExp',
                             'Sci',
                             'SciDDC',
                             'SciLg',
                             'SciMicor',
                             'SciRef',
                             'SciHY',
                             'SFilm',
                             'Slav',
                             'SMedia',
                             'SMicDDC',
                             'SOA',
                             'SRefPer',
                             'SSAdBdP',
                             'SSAdMic',
                             'SSAdPam',
                             'SSAdPer',
                             'SSAdRef',
                             'SSAdX',
                             'W',
                             'WCJK'],
                        /* Whitelist */
                        'hold' =>
                            ['XClosedCJK',
                             'XClosedGen'],
                        'inProcessAtMansueto' =>
                            ['SciASR'],
                        'offsite' =>
                            ['btaaspr'],
                        /*Whitelist*/
                        'paging' =>
                            ['Art420',
                             'ArtResA',
                             'CDEV',
                             'CJK',
                             'CJKRef',
                             'CJKRfHY',
                             'CJKSPer',
                             'CMC',
                             'EckX',
                             'Film',
                             'Games',
                             'Gen',
                             'GenHY',
                             'JRLRES',
                             'Law',
                             'LawAid',
                             'LawCity',
                             'LawCS',
                             'LawDisp',
                             'LawPer',
                             'LawRef',
                             'LawResP',
                             'LawRR',
                             'LawA',
                             'LawC',
                             'LawAnxN',
                             'LawAnxS',
                             'LawStor',
                             'LawMic',
                             'MapRef',
                             'Mic',
                             'MidEMic',
                             'Pam',
                             'PerBio',
                             'PerPhy',
                             'RR',
                             'RR2Per',
                             'RR4',
                             'RR4Cla',
                             'RR4J',
                             'RR5',
                             'RR5EA',
                             'RR5EPer',
                             'RR5Per',
                             'RRExp',
                             'Sci',
                             'SciDDC',
                             'SciLg',
                             'SciMicor',
                             'SciRef',
                             'SciRR',
                             'SciHY',
                             'SFilm',
                             'Slav',
                             'SMedia',
                             'SMicDDC',
                             'SOA',
                             'SRefPer',
                             'SSAdBdP',
                             'SSAdMed',
                             'SSAdMic',
                             'SSAdPam',
                             'SSAdPer',
                             'SSAdX',
                             'W',
                             'WCJK'],
                        'scanAndDeliver' => 
                            ['ArtResA',
                             'CDEV',
                             'CJK',
                             'CJKRar',
                             'CJKRef',
                             'CJKRfHY',
                             'CJKSPer',
                             'CJKSpHY',
                             'CJKSpl',
                             'CMC',
                             'EckLg',
                             'EckPer',
                             'EckRef',
                             'EckX',
                             'Gen',
                             'GenHY',
                             'JRLASR',
                             'JRLRES',
                             'Law',
                             'LawA',
                             'LawAid',
                             'LawAnxN',
                             'LawAnxS',
                             'LawASR',
                             'LawC',
                             'LawCity',
                             'LawCS',
                             'LawDisp',
                             'LawFul',
                             'LawMic',
                             'LawPer',
                             'LawRef',
                             'LawResP',
                             'LawRR',
                             'LawStor',
                             'LawSupr',
                             'MapCl',
                             'Mansueto',
                             'Pam',
                             'PerBio',
                             'PerPhy',
                             'RR',
                             'RR2Per',
                             'RR4',
                             'RR4Cla',
                             'RR4J',
                             'RR5',
                             'RR5EA',
                             'RR5EPer',
                             'RR5Per',
                             'RRExp',
                             'Sci',
                             'SciASR',
                             'SciASR',
                             'SciDDC',
                             'SciLg',
                             'SciRef',
                             'SciRes',
                             'SciRR',
                             'SciHY',
                             'SOA',
                             'SRefPer',
                             'SSAdBdP',
                             'SSAdDpY',
                             'SSAdMic',
                             'SSAdPam',
                             'SSAdPer',
                             'SSAdRef',
                             'SSAdRes',
                             'SSAdX',
                             'Stor',
                             'W',
                             'WCJK',
                             'XClosedCJK',
                             'XClosedGen'],
                        'dllStorage' => 
                            ['LawA',
                             'LawAnxS',
                             'LawC',
                             'LawMic',
                             'LawRar',
                             'LawStor',
                             'LawAnxN'],
                        'recall' => 
                            ['Art420',
                             'EckRes',
                             'JRLRES',
                             'LawRes',
                             'LawResC',
                             'LawResP',
                             'Res',
                             'Resup',
                             'ResupC',
                             'ResupD',
                             'ResupE',
                             'ResupS',
                             'SSAdRes',
                             'SciRes'],
                        /*Whitelist*/
                        'uBorrow' => 
                             ['ArtResA',
                             'EckRes',
                             'JRLRES',
                             'LawRes',
                             'LawResC',
                             'LawResP',
                             'Res',
                             'Resup',
                             'ResupC',
                             'ResupD',
                             'ResupE',
                             'ResupS',
                             'SSAdRes',
                             'SciRes'],
                        /*Blacklist*/
                        'getIt' =>
                            ['ITS'] //<--Library location
                        ];

    /**
     * Configuration array for status codes that conditional service links 
     * depend on. This is a middle step towards having these values drawn from a database.
     * Some keys are used as whitelists and some keys are used as blacklists. 
     * Make sure you know what the conditional logic requires before you update.
     */
    protected $lookupStatus = [
                        /*Whitelist*/
                        'uBorrow' =>
                            ['DECLARED-LOST',
                             'FLAGGED-FOR-RESERVE',
                             'INPROCESS',
                             'INPROCESS-CRERAR',
                             'INPROCESS-LAW',
                             'INPROCESS-REGENSTEIN',
                             'INTRANSIT',
                             'INTRANSIT-FOR-HOLD',
                             'LOANED',
                             'LOST',
                             'LOST-AND-PAID',
                             'MISSING',
                             'MISSING-FROM-MANSUETO',
                             'ONHOLD',
                             'ONORDER',
                             'RETRIEVING-FROM-MANSUETO',
                             'UNAVAILABLE'], 
                         /*Whitelist*/
                         'borrowDirect' =>
                            ['DECLARED-LOST',
                             'FLAGGED-FOR-RESERVE',
                             'INPROCESS',
                             'INPROCESS-CRERAR',
                             'INPROCESS-LAW',
                             'INPROCESS-REGENSTEIN',
                             'INTRANSIT',
                             'INTRANSIT-FOR-HOLD',
                             'LOANED',
                             'LOST',
                             'LOST-AND-PAID',
                             'MISSING',
                             'MISSING-FROM-MANSUETO', 
                             'ONHOLD',
                             'ONORDER',
                             'RETRIEVING-FROM-MANSUETO',
                             'UNAVAILABLE'],
                         'getIt' =>
                            ['DECLARED-LOST',
                             'FLAGGED-FOR-RESERVE',
                             'INPROCESS',
                             'INPROCESS-CRERAR',
                             'INPROCESS-LAW',
                             'INPROCESS-REGENSTEIN',
                             'INTRANSIT',
                             'INTRANSIT-FOR-HOLD',
                             'LOANED',
                             'LOST',
                             'LOST-AND-PAID',
                             'MISSING',
                             'MISSING-FROM-MANSUETO', 
                             'ONHOLD',
                             'ONORDER',
                             'RETRIEVING-FROM-MANSUETO',
                             'UNAVAILABLE'],
                        'cantFindIt' =>
                            ['AVAILABLE',
                             'RECENTLY-RETURNED'],
                        'inProcessAtMansueto' =>
                            ['INPROCESS-MANSUETO'],
                        'scanAndDeliver' =>
                            ['AVAILABLE',
                             'AVAILABLE-AT-MANSUETO',
                             'INPROCESS-MANSUETO',
                             'RECENTLY-RETURNED'],
                        'dllStorage' =>
                            ['AVAILABLE',
                             'RECENTLY-RETURNED'], 
                        'asr' =>
                            ['AVAILABLE-AT-MANSUETO'],
                        /*Whitelist*/
                        'hold' =>
                            ['INPROCESS',
                             //'INTRANSIT', //Commented out for COVID-19 implementation
                             'ONORDER'],
                        /*Whitelist*/
                        'paging' =>
                            ['AVAILABLE',
                             'RECENTLY-RETURNED'],
                        /*Blacklist*/
                        'recall' => 
                            ['ANAL',
                             'AVAILABLE',
                             'BTAASPR',
                             'DECLARED-LOST',
                             'FLAGGED-FOR-RESERVE',
                             'INPROCESS',
                             'INPROCESS-MANSUETO',
                             'INTRANSIT',
                             'LOST',
                             'LOST-AND-PAID',
                             'MISSING',
                             'MISSING-FROM-MANSUETO',
                             'ONORDER',
                             'RECENTLY-RETURNED',
                             'RETRIEVING-FROM-MANSUETO',
                             'UNAVAILABLE']]; 
 
    /**
     * Method fills all placeholders with their values from $row for 
     * fallback links that override default links. All placeholders
     * must be set in the URL config.ini and follow the format: {ALLCAPS}.
     * They must also be set in the $allowedPlaceholders array.
     *
     * @param string $serviceLink, the link to examine.
     * @param array $row, information about the item.
     *
     * @returns string, the service link with placeholders filled.
     */
    public function fillPlaceholders($serviceLink, $row) {
        foreach ($this->allowedPlaceholders as $placeholder) {
            $serviceLink = preg_replace('/\{'.$placeholder.'\}/', $row[strtolower($placeholder)], $serviceLink);
        }
        return $serviceLink;
    }

    /**
     * Method returns the default service link, an override  
     * link, or null based on configuration in the config.ini. 
     *
     * @param string $linkName, the name of the service link.
     * Must match the name in config.ini.
     *
     * @param string, $defaultUrl, the default url to use if
     * the links aren't turned off or no fallback url is provided. 
     *        	
     * @return string default URL, fallback URL, or null
     */
    protected function getLinkConfig($linkName, $defaultUrl) {
        /*Test configuration*/
        $config = isset($this->linkConfig->$linkName) ? $this->linkConfig->$linkName : -1;
        switch ($config) {
            /*The link has been turned off*/
            case ($config == 'off') :
                $serviceLink = null;
                break;
            /*A fallback url was provided*/
            case (strlen($config) > 3) :
                $serviceLink = $this->linkConfig->$linkName;
                break;
            /*Use the hardd-coded value (link is "on")*/
            default:
                $serviceLink = $defaultUrl;
        }
        return $serviceLink;
    }
 
    /**
     * Method creates a link to the Aeon service for Special Collections 
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function aeon($row) {
        $manuscript = array_map('strtolower', $this->lookupLocation['scrcManuscript']);
        $whitelist = array_map('strtolower', $this->lookupLocation['scrcInMansueto']);

        if ($this->getLocation($row['locationCodes'], 'library') == 'spcl' or 
           ($this->getLocation($row['locationCodes'], 'library') == 'asr' and in_array(strtolower($this->getLocation($row['locationCodes'], 'shelving')), $whitelist))){
            
            $genre = (in_array($this->getLocation($row['locationCodes'], 'shelving'), $manuscript) ? 'manuscript' : 'monograph');

            $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/aon/aeon-array_OLE.php?genre=' . $genre . '&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
            $serviceLink = $this->getLinkConfig('aeon', $defaultUrl);
            $displayText = '<i class="fa fa-fw fa-folder-open" aria-hidden="true"></i> Request from SCRC';

            if ($serviceLink) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }


    /**
     * Method creates a link to the Mansueto ASR Storage system
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function asr($row) {
        $defaultUrl = '/vufind/MyResearch/Storagerequest?bib=' .  $row['id'] . '&amp;barcode=' . $row['barcode'] . '&amp;action=add';
        $serviceLink = $this->getLinkConfig('mansueto', $defaultUrl); 
        //$displayText = '<i class="fa fa-fw fa-shopping-basket" aria-hidden="true"></i> Request from Mansueto Library';
        $displayText = '<i class="fa fa-truck fa-flip-horizontal" aria-hidden="true"></i> Request for Pickup at Regenstein';
        $blacklist = array_map('strtolower', $this->lookupLocation['scrcInMansueto']);
        if (($serviceLink) and (in_array($row['status'], $this->lookupStatus['asr']) and $this->getLocation($row['locationCodes'], 'library') != 'spcl') and 
            (!in_array(strtolower($this->getLocation($row['locationCodes'], 'shelving')), $blacklist))) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }


    /**
     * Method creates a link to the BorrowDirect consortium 
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function borrowDirect($row) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/borrowdirect_OLE.php?format=php&amp;database=production&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
        $serviceLink = $this->getLinkConfig('borrowDirect', $defaultUrl); 
        $displayText = 'BorrowDirect';
        $shelvingLocations =  array_map('strtolower', $this->lookupLocation['borrowDirect']);
        if ($serviceLink) {
            if (in_array($row['status'], $this->lookupStatus['borrowDirect']) || in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations)) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }


    /**
     * Helper method used to test if the conditions are
     * present for a can't find it link.
     *
     * @param row, array of holdings and item information
     *
     * @return boolean
     */
    protected function isCantFindIt($row){
        $shelvingLocations = array_map('strtolower', $this->lookupLocation['cantFindIt']);
        if (in_array($row['status'], $this->lookupStatus['cantFindIt']) and
            in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations)) {
                return true;
        }
        return false;
    }


    /**
     * Helper method used to test if the conditions are
     * present for the paging link (Pickup at Regenstein).
     *
     * @param row, array of holdings and item information
     *
     * @return boolean
     */
    protected function isPaging($row){
        $shelvingLocations = array_map('strtolower', $this->lookupLocation['paging']);
        $isRightStatus = in_array($row['status'], $this->lookupStatus['paging']);
        $location = $this->getLocation($row['locationCodes'], 'shelving');
        $isMapCollection = $location == 'mapcl';
        $isRightLocation = in_array($location, $shelvingLocations);
        if ($isRightStatus && $isRightLocation) {
                return true;
        }
        return false;
    }

    /**
     * Helper method used to test if the conditions are
     * present for a dllStorage link.
     *
     * @param row, array of holdings and item information
     *
     * @return boolean
     */
    protected function isDllStorage($row){
        $shelvingLocations = array_map('strtolower', $this->lookupLocation['dllStorage']); 
        if (in_array($row['status'], $this->lookupStatus['dllStorage']) and 
            in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations)) {
                return true;
        }
        return false;
    }

    /**
     * Method creates a link to the Can't find it service
     *
     * @param row, array of holdings and item information
     *
     * @return html string
     */
    public function cantFindIt($row) {
        $defaultUrl = $this->view->recordLink()->getHoldUrl($row['link']);
        $serviceLink = $this->fillPlaceholders($this->getLinkConfig('cantFindIt', $defaultUrl), $row);
        $displayText = '<i class="fa fa-search-plus" aria-hidden="true"></i> Can\'t find it?';
        if ($serviceLink and $this->isCantFindIt($row)) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Creates a Request for Pickup at Regenstein link
     *
     * @param row, array of holdings and item information
     *
     * @return html string
     */
    public function requestPickupAtReg($row) {
        // Add a special url param to differentiate pickup at Reg page
        // requests from regular (Can't find it?) page/holds. 
        if(isset($row['link']['query'])) {
            $row['link']['query'] = 'isPickupAtReg=true&' . $row['link']['query'];
        }
        $defaultUrl = $this->view->recordLink()->getHoldUrl($row['link']);
        $serviceLink = $this->fillPlaceholders($this->getLinkConfig('requestPickupAtReg', $defaultUrl), $row);

        $displayText = '<i class="fa fa-truck fa-flip-horizontal" aria-hidden="true"></i> Request for Pickup at Regenstein';
        $closedStacks = array_map('strtolower', $this->lookupLocation['hold']);
        $location = $this->getLocation($row['locationCodes'], 'shelving');

        if ($serviceLink and $this->isPaging($row)) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
        /*For XClosedGen and XClosedCJK*/
        elseif (in_array(strtolower($location), $closedStacks)) {
            $statusBlacklist = $this->lookupStatus['hold']; // Unavailable
            $item_status_blacklist = array_merge($statusBlacklist, ['LOANED']);
            if (!in_array($row['status'], $item_status_blacklist)) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }

    /**
     * Method creates a degugging link to Brad's web api for testing.
     * Should not be used on a production system.
     *        	
     * @return html string
     */
    public function debugging($row) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/itemServlet.php?format=xml&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'] . '&amp;database=testing';
        $serviceLink = $this->getLinkConfig('debugging', $defaultUrl);
        $displayText = '<< Simple API >>';
        if ($serviceLink) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Method creates a link to the DLL Storage service for D'Angelo Law
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function dllStorage($row) {
        // Add a special url param to differentiate DLL Storage page/holds
        // from regular (Can't find it?) page/holds. 
        if(isset($row['link']['query'])) {
            $row['link']['query'] = 'isDllStorage=true&' . $row['link']['query'];
        }
        $defaultUrl = $this->view->recordLink()->getHoldUrl($row['link']);
        $serviceLink = $this->fillPlaceholders($this->getLinkConfig('dllStorage', $defaultUrl), $row);
        $displayText = 'Request from DLL Storage';
        if ($serviceLink and $this->isDllStorage($row)) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Method creates a GetIt link to the Relais service  
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function getIt($row) {
        $libraryLoc = strtolower($this->getLocation($row['locationCodes'], 'library'));
        $locations = array_map('strtolower', $this->lookupLocation['getIt']);
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/requestFromILL.php?database=production&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
        $serviceLink = $this->getLinkConfig('getIt', $defaultUrl); 
        $displayText = '<i class="fa fa-truck fa-flip-horizontal" aria-hidden="true"></i> Request via Interlibrary Loan';
        if (($serviceLink) and in_array($row['status'], $this->lookupStatus['getIt']) and !in_array($libraryLoc, $locations)) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Method creates a link to the special Crerar form 
     * for items INPROCESS-MANSUETO.
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function inProcessAtMansueto($row) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/cant-find-it-crerar.php?barcode=' . $row['barcode'] . '&amp;bib=' . $row['id'];
        $serviceLink = $this->getLinkConfig('inProcessAtMansueto', $defaultUrl);
        $displayText = 'Can\'t find it?';
        $shelvingLocations = array_map('strtolower', $this->lookupLocation['inProcessAtMansueto']);
        if (($serviceLink) and (in_array($row['status'], $this->lookupStatus['inProcessAtMansueto'])) and 
            (in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations))) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Method creates a link to the maps lookup service.
     *
     * @param @location, string
     * @param @callnum, string
     * @param @prefix, string
     *
     * @return html string
     */
    public function maps($location, $callnum, $prefix) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/maplookup/maplookup.php?location=' . $location . '&amp;callnum=' . $callnum . '&amp;callnumPrefix=' . $prefix;
        $serviceLink = $this->getLinkConfig('maps', $defaultUrl); 
        $displayText = '<i class="fa fa-spinner fa-pulse fa-fw"></i> Loading map link';
        if ($serviceLink) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText, ['maplookup service'], ['location' => $location, 'callnum' => $callnum, 'prefix' => $prefix]);
        }
    }

    /**
     * Method creates a link to the login and my research pages.
     *
     * @param string $defaultUrl, the url passed in from the templates.
     * @param boolean $mobile, true if the link is for a mobile view, 
     * defaults to false.
     *        	
     * @return html string 
     */
    public function myAccount($defaultUrl, $mobile=false) {
        $serviceLink = $this->getLinkConfig('myAccount', $defaultUrl); 
        $displayText = $this->view->transEsc("My Account");
        if ($serviceLink) {
            if ($mobile) {
                 return $this->getServiceLinkTemplate($serviceLink, $displayText, ['dropdown-toggle login']);      
            }
            else {
                return $this->getServiceLinkTemplate($serviceLink, $displayText, ['account']);
            }
        }
    }

    /**
     * Creates a link to the Relais service for offsite materials
     *
     * @param row, array of holdings and item information
     *
     * @return html string
     */
    public function offsite($row) {
        $location = strtolower($this->getLocation($row['locationCodes'], 'shelving'));
        if (in_array($location, $this->lookupLocation['offsite'])) {
            $sids = ['btaaspr' => 'SPR',];
            $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/requestFromILL.php?database=production&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
            $defaultUrl .= '&amp;sid=' . $sids[$location];
            $serviceLink = $this->getLinkConfig('offsite', $defaultUrl);
            $displayText = '<i class="fa fa-truck fa-flip-horizontal" aria-hidden="true"></i> Request via Interlibrary Loan';
            if ($serviceLink) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }

    /**
     * Method creates a recall link.
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string 
     */
    public function recall($row) {
        $defaultUrl = $this->view->recordLink()->getHoldUrl($row['link']);
        //$serviceLink = 'http://forms2.lib.uchicago.edu/lib/searchform/recall-template.php?barcode=' . $row['barcode'] . '&bib=' . $row['id'];
        $serviceLink = $this->fillPlaceholders($this->getLinkConfig('recall', $defaultUrl), $row);
        $displayText = '<i class="fa fa-chevron-circle-down" aria-hidden="true"></i> Recall This';

        /*Combined statuses and locations to make a blacklist*/
        $blacklist = array_map('strtolower', array_merge($this->lookupStatus['recall'], $this->lookupLocation['recall']));

        if (($serviceLink) && (!empty($row['status'])) && (!in_array(strtolower($row['status']), $blacklist)) && (!in_array($this->getLocation($row['locationCodes'], 'shelving'), $blacklist)) 
            && !empty($row['barcode'])) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

   /**
     * Method creates a request link for items IN PROCESS or ON ORDER.
     *
     * @param row, array of holdings and item information
     *
     * @return html string
     */
    public function request($row) {
        $defaultUrl = $this->view->recordLink()->getHoldUrl($row['link']);
        $serviceLink = $this->fillPlaceholders($this->getLinkConfig('request', $defaultUrl), $row);
        $displayText = '<i class="fa fa-chevron-circle-down" aria-hidden="true"></i> Place Hold';

        $location = $this->getLocation($row['locationCodes'], 'shelving');

        /*Locations blacklist*/
        $blacklist = $this->lookupLocation['recall'];

        /*Status whitelist*/
        $whitelist = $this->lookupStatus['hold']; // Unavailable
        $location_whitelist = array_map('strtolower', $this->lookupLocation['hold']);
        $location_whitelist = []; // IMPORTANT!: this line temporarily disables place hold link on CLOSED STACK items for Hathi ETAS implementation. Delete this line to revert

        /*Normal Place Hold logic*/
        if (($serviceLink) && (!empty($row['status'])) && (in_array($row['status'], $whitelist)) && (!in_array($location, $blacklist))) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
        /*For XClosedGen and XClosedCJK*/
        elseif (in_array(strtolower($location), $location_whitelist)) {
            // Whitelist becomes blacklist. While normally the "Place Hold" link
            // is only displayed for certain *unavailable* statuses, we show it
            // for XClosedGen and XClosedCJK when the item staus is *Available* or
            // *Recently Returned* (also Available from VuFind's perspective). 
            // That's why the whitelist becomes a blacklist.
            $item_status_blacklist = array_merge($whitelist, ['LOANED']);
            if (!in_array($row['status'], $item_status_blacklist)) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }

    /**
     * Generates a link to the report a record form and forwards
     * along information about the record.
     *
     * @param id, record bib number
     *
     * @returns string
     */
    public function reportRecord($id, $title='')
    {
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(array('cn', 'mail')));
        $subject = urlencode('Catalog Record Problem: ' . $title);
        $url='https://www.lib.uchicago.edu/search/forms/online-catalog-report-problem-record/?bib=' . $id . '&amp;subject=' . $subject;
        if (!empty($patron)) {
            $url = $url . '&amp;' . $patron;
        }
        return $url;
    }


    /**
     * Creates a link to a form where users
     * can request help.
     *
     * @return html string
     */
    public function requestHelp($row, $title='') {
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(array('cn', 'mail')));
        $title = urlencode('Request Help Finding an Online Copy: ' . $title);
        $defaultUrl = 'https://www.lib.uchicago.edu/search/forms/need-help-ask-librarian/?bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'] . '&amp;subject=' . $title;
        if (!empty($patron)) {
            $defaultUrl = $defaultUrl . '&amp;' . $patron;
        }
        $building = $this->getLocation($row['locationCodes'], 'library');
        $serviceLink = $this->getLinkConfig('requestHelp', $defaultUrl);
        $displayText = '<i class="fa fa-comment" aria-hidden="true"></i> Need help? - Ask a Librarian';
        if ($serviceLink && $building != 'spcl') {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }


    /**
     * Creates a link to a form where users can request help
     * from an SCRC librarian.
     *
     * @return html string
     */
    public function requestHelpSCRC($row) {
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(array('cn', 'mail')));
        $defaultUrl = 'https://www.lib.uchicago.edu/search/forms/ask-scrc-or-request-scan/?bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
        if (!empty($patron)) {
            $defaultUrl = $defaultUrl . '&amp;' . $patron;
        }
        $building = $this->getLocation($row['locationCodes'], 'library');
        $serviceLink = $this->getLinkConfig('requestHelpSCRC', $defaultUrl);
        $displayText = '<i class="fa fa-comment" aria-hidden="true"></i> Need help? - Ask SCRC or Request Scans';
        if ($serviceLink && $building == 'spcl') {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }


    /**
     * Creates a link to a page explaining why items
     * are temporarily unavailable.
     *
     * @return html string
     */
    public function requestsUnavailable($row) {
        $defaultUrl = 'https://www.lib.uchicago.edu/borrow/requesting/services-suspended-during-library-closure/';
        $serviceLink = $this->getLinkConfig('requestsUnavailable', $defaultUrl);
        $displayText = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Requests Temporarily Unavailable';
        if ($serviceLink) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }


    /**
     * Method creates a link to the Scan&Deliver service
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function scanAndDeliver($row) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/sandd_OLE.php?database=production&amp;bib=' . $row['id'] . '&amp;barcode=' . $row['barcode'];
        $serviceLink = $this->getLinkConfig('scanAndDeliver', $defaultUrl); 
        $displayText = '<i class="fa fa-fw fa-file-text-o" aria-hidden="true"></i> Scan and Deliver';
        $shelvingLocations =  array_map('strtolower', $this->lookupLocation['scanAndDeliver']);

        if (($serviceLink) and (in_array($row['status'], $this->lookupStatus['scanAndDeliver'])) and
            (in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations))) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText, [], []);
        }
    }

    /**
     * Method creates a link to the Uborrow consortium 
     *
     * @param row, array of holdings and item information 
     *        	
     * @return html string
     */
    public function uBorrow($row) {
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/relais_OLE.php?format=php&database=production&bib=' . $row['id'] . '&barcode=' . $row['barcode'];
        $serviceLink = $this->getLinkConfig('uBorrow', $defaultUrl);
        $displayText = 'UBorrow';
        $shelvingLocations =  array_map('strtolower', $this->lookupLocation['uBorrow']);
        if ($serviceLink) {
            if (in_array($row['status'], $this->lookupStatus['uBorrow']) || in_array($this->getLocation($row['locationCodes'], 'shelving'), $shelvingLocations)) {
                return $this->getServiceLinkTemplate($serviceLink, $displayText);
            }
        }
    }

    /**
     * Gets all the grouper groups for the logged-in user.
     *
     * @return array of grouper groups
     */
    public function getGrouperGroups() {
        if (array_key_exists('ucisMemberOf', $_SERVER)) {
            $groups = explode(';', $_SERVER['ucisMemberOf']);
            $_SESSION['Grouper'] = $groups;
            return  $groups;
        } else if (isset($_SESSION['Grouper'])) {
            return $_SESSION['Grouper'];
        }
        else {
            return [];
        }
    }

    /**
     * Checks to see if the logged in user is a member 
     * of the specified group.
     *
     * @param $groupName, string name of the group for which
     * to see if the logged in user is a member.
     * 
     * @return boolean
     */
    public function isMemberOf($groupName) {
        $groups = $this->getGrouperGroups();
        return in_array($groupName, $groups);
    }

    /**
     * Method creates a link to the alternative text request service
     * uc:applications:library:alttext:authorized
     *
     * @param $formats, array of formats.
     * @param $bib, string, unique ID
     * @param $row, array of holdings and item information, defaults to false.
     *
     * @return html string
     */
    public function altText($formats, $bib, $row=false) {
        $formats = $this->urlEncodeArray($formats);
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(array('cn', 'mail')));
        $status = $row ? urlencode($row['status']) : '';
        $barcode = $row ? urlencode($row['barcode']) : '';
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/alt-text-request.php?barcode=' . $barcode . '&amp;bib=' . $bib . '&amp;status=' . $status  . '&amp;' . $patron . $formats;
        $serviceLink = $this->getLinkConfig('altText', $defaultUrl);
        $displayText = 'Alternative Text Request';
        if (($serviceLink) and ($this->isMemberOf('uc:applications:library:alttext:authorized'))) {
            return $this->getServiceLinkTemplate($serviceLink, $displayText);
        }
    }

    /**
     * Method gets specified values from the $_SERVER variable.
     *
     * @param $config, array of key names to pull from the $_SERVER variable.
     *
     * @return associative array of key => values.
     */
    protected function getServerVars($config) {
        $retval = [];
        foreach ($config as $key => $value) {
            if (isset($_SERVER[$value])) { 
                $retval[$config[$key]] = $_SERVER[$value];
            }
        }
        return $retval;
    }

    /**
     * Method returns associative array as a url encoded string 
     *
     * @param associative array 
     *
     * @return string
     */
    protected function urlEncodeArrayAsString($array) {
        return http_build_query($array, '', '&amp;');
    }

    /**
     * Method returns an array representation for a GET request
     *
     * @param array
     *
     * @return string
     */
    protected function urlEncodeArray($array) {
        $string = '';
        foreach ($array as $item) {
            $string .= '&amp;formats[]=' . urlencode($item);
        }
        return $string;
    }  
}
