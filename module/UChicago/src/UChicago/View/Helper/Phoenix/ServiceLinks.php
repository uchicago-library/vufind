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
     * Text to display if link is unavailable.
     */
    protected $unavailable = 'Link unavailable';

    /**
     * Suffix for location whitelists used in the config. Will be
     * appended to the link name as a convention.  
     */
    protected $locWhitelistSuffix = '_loc_whitelist';

    /**
     * Suffix for location blacklists used in the config. Will be
     * appended to the link name as a convention.  
     */
    protected $locBlacklistSuffix = '_loc_blacklist';

    /**
     * Suffix for status whitelists used in the config. Will be
     * appended to the link name as a convention.  
     */
    protected $statusWhitelistSuffix = '_status_whitelist';

    /**
     * Suffix for status blacklists used in the config. Will be
     * appended to the link name as a convention.  
     */
    protected $statusBlacklistSuffix = '_status_blacklist';

    /**
     * Tokens that are allowed in the config.ini. These are used
     * to create dynamic fallback URLs that override default URLs
     * when a fallback is needed.
     */
    protected $allowedTokens = ['BARCODE', 'ID'];

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
    protected function replaceTokens($link, $row) {
        foreach ($this->allowedTokens as $token) {
            $link = preg_replace('/\{'.$token.'\}/', $row[strtolower($token)], $link);
        }
        return $link;
    }

    /**
     * Detect is a string is a url. TODO - better url validation.
     *
     * @param string $s
     *
     * @returns boolean
     */
    protected function isURL($s) {
        return filter_var($s, FILTER_VALIDATE_URL) != false;
    }

    /**
     * Helper function for creating the link markup 
     *
     * @param link, url for the href    
     * @param text, display text for the link
     * @param icon, font awesome icon if desired, defaults to false
     * @param classes, optional array of css classes to append
     * defaults to empty array.
     * @param dataAttributes, optional array of html data attributes
     * defaults to empty array.
     * @param tagline, a helper text to append after the link
     * defaults to an empty string
     *          
     * @return html string
     */
    protected function template($link, $text, $icon=false, $classes='', $dataAttributes=[], $tagline='') {
        $da = '';
        if ($dataAttributes) {
            foreach ($dataAttributes as $a => $v) {
                $da .= 'data-' . $a . '="' . $v . '"';
            }
        }
        if ($tagline) {
          $tagline = sprintf(' <span class="service-tagline">%s</span>', $tagline);
        }
        if ($icon) {
          $icon = sprintf('<i class="fa %s" aria-hidden="true"></i> ', $icon);
          $text = $icon . $text; 
        }
        $classes = $classes ? 'service ' . $classes : 'service';
        return '<a href="' . $link . '" class="' . $classes . '" ' . $da . '>' . $text . '</a>' . $tagline;
    }

    /**
     * Method returns the default service link, an override  
     * link, or null based on configuration in the config.ini. 
     *
     * @param string $linkName, the name of the service link.
     * Must match the name in config.ini.
     *
     * @return string default URL, fallback URL, or null
     */
    protected function getLinkConfig($linkName) {
        // Test config
        $config = $this->linkConfig->$linkName ?? -1;
        $locWhitelistConfName = $linkName . $this->locWhitelistSuffix;
        $locBlacklistConfName = $linkName . $this->locBlacklistSuffix;
        $statusWhitelistConfName = $linkName . $this->statusWhitelistSuffix;
        $statusBlacklistConfName = $linkName . $this->statusBlacklistSuffix;
        switch ($config) {
            // Link is off
            case ($config == 'off') :
                $serviceLink = null;
                break;
            // URL config provided
            case (strlen($config) > 3) :
                $serviceLink = $this->formatConfig(
                    explode(':::', $this->linkConfig->$linkName),
                    array_map('trim', explode(',', $this->linkConfig->$locWhitelistConfName)),
                    array_map('trim', explode(',', $this->linkConfig->$locBlacklistConfName)),
                    array_map('trim', explode(',', $this->linkConfig->$statusWhitelistConfName)),
                    array_map('trim', explode(',', $this->linkConfig->$statusBlacklistConfName))
                );
                break;
            // Unavailable
            default:
                $serviceLink = $this->unavailable;
        }
        return $serviceLink;
    }


    /**
     * Format link config data into an associative array.
     *
     * @param array $link
     *
     * @param array $locWhitelist
     *
     * @param array $statusWhitelist
     *
     * @return array
     */
    protected function formatConfig($link, $locWhitelist, $locBlacklist, $statusWhitelist, $statusBlacklist) {
        return ['text' => $link[0] ?? '',
                'url' => $link[1] ?? '',
                'icon' => $link[2] ?? '',
                'classes' => $link[3] ?? '',
                'tagline' => $link[4] ?? '',
                'loc_whitelist' => $locWhitelist ?? [],
                'loc_blacklist' => $locBlacklist ?? [],
                'status_whitelist' => $statusWhitelist ?? [],
                'status_blacklist' => $statusBlacklist ?? []];
    }

    /**
     * Detect if a url is off.
     *
     * @param array $config
     *
     * @returns boolean
     */
    protected function enabled($config) {
        return $config != $this->unavailable && $config['url'] != 'off' && $config['url'] != '';
    }


    /**
     * Parse the FOLIO location code string which looks something like this:
     * "JRL-Gen". FOLIO location codes have 2 levels: 1. Library location and
     * 2. Shelving location. This method returns the location code for the level
     * requested.
     *
     * @param locationString, a location string hierarchy with blocks separated
     * by /s
     * @param level, string institution, library or shelving. Refers to the
     * section of the location string being requested 
     *          
     * @return a simple location string converted to lower case for matching 
     */
    public function getLocationCode($locationString, $level) {
        $blocks = explode('-', $locationString);
        $location = '';
        if($level == 'library' && isset($blocks[0])) {
            $location = $blocks[0];
        }
        elseif($level == 'shelving' && isset($blocks[1])) {
            $location = $blocks[1];
        }
        return strtolower($location);
    }

    protected function hasCorrectStatus($status, $config) {
        $notInBlacklist = !in_array(
            $status,
            $config['status_blacklist']
        );
        if ($config['status_whitelist'] != ['']) {
            return in_array(
                $status,
                $config['status_whitelist']
            ) && $notInBlacklist;
        } else {
            return $notInBlacklist;
        }
    }

    protected function hasCorrectLocation($locationCode, $locWhitelistType, $locBlacklistType, $config) {
        $notInBlacklist = !in_array(
            $this->getLocationCode($locationCode, $locBlacklistType),
            $config['loc_blacklist']
        );
        if ($config['loc_whitelist'] != ['']) {
            return in_array(
                $this->getLocationCode($locationCode, $locWhitelistType),
                $config['loc_whitelist']
            ) && $notInBlacklist; 
        } else {
            return $notInBlacklist;
        }
    }

    /**
     * Build a service link based on configurable location and status
     * whitelists and blaclists.
     *
     * @param array $holding.
     *
     * @param string $confName, the name of the link in the config.ini.
     *
     * @return
     */
    public function buildLink($holding, $confName, $serviceLink = '', $locWhitelistType = 'shelving', $locBlacklistType = 'shelving') {
        $config = $this->getLinkConfig($confName);
        if (!$this->enabled($config)) {
            return '';
        }
        $status = strtolower($holding['status']);
        $locationCode = $holding['location_code'];
        $hasCorrectStatus = $this->hasCorrectStatus($status, $config);
        $hasCorrectLocation = $this->hasCorrectLocation($locationCode, $locWhitelistType, $locBlacklistType, $config);
        if ($hasCorrectStatus && $hasCorrectLocation) {
            // Add a special url param to differentiate paging requests from others. 
            if(isset($holding['link']['query'])) {
                $holding['link']['query'] = 'isPickup=true&' . $holding['link']['query'];
            }
            if ($this->isURL($config['url'])) {
                // Override servie link if an override value is provided.
                $serviceLink = $this->replaceTokens($config['url'], $holding);
            }
            return $this->template($serviceLink, $config['text'], $config['icon'], $config['classes'], [], $config['tagline']);
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

    /**
     * Method creates a link to the alternative text request service
     * uc:applications:library:alttext:authorized
     *
     * @param $formats, array of formats.
     * @param $bib, string, unique ID
     * @param $holding array of holdings and item information, defaults to false.
     *
     * @return html string
     */
    public function altText($formats, $bib, $holding=false) {
        $config = $this->getLinkConfig('alttext');
        if (!$this->enabled($config)) {
            return '';
        }
        $formats = $this->urlEncodeArray($formats);
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(['cn', 'mail']));
        $status = $holding ? urlencode($holding['status']) : '';
        $barcode = $holding ? urlencode($holding['barcode']) : '';
        $defaultUrl = 'http://forms2.lib.uchicago.edu/lib/searchform/alt-text-request.php?barcode=' . $barcode . '&amp;bib=' . $bib . '&amp;status=' . $status  . '&amp;' . $patron . $formats;
        $serviceLink = $defaultUrl;
        if ($this->isURL($config['url'])) {
            $serviceLink = $this->replaceTokens($config['url'], $holding);
        }
        if ($this->isMemberOf('uc:applications:library:alttext:authorized')) {
            return $this->template($serviceLink, $config['text'], $config['icon'], $config['classes'], [], $config['tagline']);
        }
    }

    /**
     * Paging request link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function paging($holding) {
        $defaultLink = $this->view->recordLink()->getHoldUrl($holding['link']);
        return $this->buildLink($holding, 'paging', $defaultLink);
    }

    /**
     * Place hold request link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function hold($holding) {
        $defaultLink = $this->view->recordLink()->getHoldUrl($holding['link']);
        return $this->buildLink($holding, 'hold', $defaultLink);
    }

    /**
     * Map link
     *
     * @param string $location.
     *
     * @param string $callnumber.
     *
     * @param string $prefix (callnumber).
     *
     * @return sting representing a link.
     */
    public function maps($location, $callnum, $prefix) {
        $confName = 'maps';
        $config = $this->getLinkConfig($confName);
        if (!$this->enabled($config)) {
            return '';
        }
        $serviceLink = sprintf($config['url'], $location, $callnum, $prefix);
        return $this->template($serviceLink, $config['text'], $config['icon'], $config['classes'], ['location' => $location, 'callnum' => $callnum, 'prefix' => $prefix], $config['tagline']);
    }

    /**
     * Mansueto ASR request link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function mansueto($holding) {
        $defaultLink = '/vufind/MyResearch/Storagerequest?bib=' .  $holding['id'] . '&amp;barcode=' . $holding['barcode'] . '&amp;action=add';
        return $this->buildLink($holding, 'mansueto', $defaultLink, 'shelving', 'library');
    }

    /**
     * Interlibrary Loan (ILL) request link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function ill($holding) {
        return $this->buildLink($holding, 'ill', '', 'shelving', 'library');
    }

    /**
     * Scan and Deliver link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function scanAndDeliver($holding) {
        return $this->buildLink($holding, 'sad', '');
    }

    /**
     * Offsite storage, Relais.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function offsite($holding) {
        return $this->buildLink($holding, 'offsite', '');
    }

    /**
     * SCRC link, Aeon.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function scrc($holding) {
        $config = $this->getLinkConfig('scrc');
        if (!$this->enabled($config)) {
            return '';
        }
        $locationCode = $holding['location_code'];
        $library = $this->getLocationCode($locationCode, 'library');
        $shelvingLoc = $this->getLocationCode($locationCode, 'shelving');
        $defaultLink = $this->replaceTokens('http://forms2.lib.uchicago.edu/lib/aon/aeon-array_OLE.php?bib={ID}&barcode={BARCODE}', $holding);
        if ($library == 'spcl' || ($library == 'asr' && $this->hasCorrectLocation($locationCode, 'shelving', 'shelving', $config))) {
            $genre = (in_array($shelvingLoc, ['arch', 'arcser', 'mss', 'msscd']) ? 'manuscript' : 'monograph');
            $serviceLink = $defaultLink . '&genre=' . $genre;
            if ($this->isURL($config['url'])) {
                $serviceLink = $this->replaceTokens($config['url'], $holding);
            }
            return $this->template($serviceLink, $config['text'], $config['icon'], $config['classes'], [], $config['tagline']);
        }
        return '';
    }

    /**
     * Need help Ask a Librarian link.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function ask($holding, $title = '') {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
            $currentUrl = "https://";
        } else {
            $currentUrl = "http://";
        }
        $currentUrl .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(['cn', 'mail']));
        $title = urlencode('Ask a Librarian Library Catalog: ' . $title);
        $defaultUrl = 'https://www.lib.uchicago.edu/search/forms/need-help-ask-librarian/?bib={ID}&barcode={BARCODE}' . '&amp;subject=' . $title . '&amp;referrer=' . urlencode($currentUrl);
        $defaultUrl = $this->replaceTokens($defaultUrl, $holding);
        if (!empty($patron)) {
            $defaultUrl = $defaultUrl . '&amp;' . $patron;
        }
        return $this->buildLink($holding, 'ask', $defaultUrl, 'shelving', 'library');
    }

    /**
     * Need help Ask a Librarian link at SCRC.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function askSCRC($holding) {
        $defaultUrl = 'https://www.lib.uchicago.edu/search/forms/ask-scrc-or-request-scan/?bib={ID}&amp;barcode={BARCODE}';
        $defaultUrl = $this->replaceTokens($defaultUrl, $holding);
        $patron = $this->urlEncodeArrayAsString($this->getServerVars(['cn', 'mail']));
        if (!empty($patron)) {
            $defaultUrl = $defaultUrl . '&amp;' . $patron;
        }
        return $this->buildLink($holding, 'askscrc', $defaultUrl, 'library');
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
        $config = $this->getLinkConfig('report');
        if (!$this->enabled($config)) {
            return '';
        }

        $patron = $this->urlEncodeArrayAsString($this->getServerVars(['cn', 'mail']));
        $subject = urlencode('Catalog Record Problem: ' . $title);
        $defaultUrl = $config['url'] . '?bib=' . $id . '&amp;subject=' . $subject;
        if (!empty($patron)) {
            $defaultUrl = $defaultUrl . '&amp;' . $patron;
        }
        return $this->template($defaultUrl, $config['text'], $config['icon'], $config['classes'], [], $config['tagline']);
    }


    /**
     * Link to itemServlet for debugging.
     *
     * @param array $holding.
     *
     * @return sting representing a link.
     */
    public function itemServlet($holding) {
        return $this->buildLink($holding, 'itemservlet', '');
    }

}
