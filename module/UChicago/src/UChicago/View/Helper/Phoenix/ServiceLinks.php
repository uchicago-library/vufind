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
     * Configuration array for shelving location logic that conditional service links 
     * depend on. Keys can represent either a whitelist or a blacklist depending
     * on how they're used. Make sure you know what the conditional logic requires
     * before you update.
     */
    protected $locations = [

    ];


    /**
     * Configuration array for status codes that conditional service links 
     * depend on. Keys can represent either a whitelist or a blacklist depending
     * on how they're used. Make sure you know what the conditional logic requires
     * before you update.
     */
    protected $statuses = [
        'cantFindIt' =>
            ['available',
             'recently-returned']
    ];

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
        switch ($config) {
            // Link is off
            case ($config == 'off') :
                $serviceLink = null;
                break;
            // URL config provided
            case (strlen($config) > 3) :
                $serviceLink = $this->linkConfig->$linkName;
                break;
            // Unavailable
            default:
                $serviceLink = $this->unavailable;
        }
        return $serviceLink;
    }

    /**
     * Parse the FOLIO location code string which looks something like this:
     * "UC/HP/ASR/LawASR". FOLIO locations have 4 levels: 1. Institution,
     * 2. Campus, 3. Library, 4. Location (shelving). This method returns the 
     * location code for the level requested.
     *
     * @param locationString, a location string hierarchy with blocks separated
     * by /s
     * @param level, string institution, library or shelving. Refers to the
     * section of the location string being requested 
     *          
     * @return a simple location string converted to lower case for matching 
     */
    public function getLocationCode($locationString, $level) {
        $blocks = explode('/', $locationString);
        if($level == 'institution') {
            $location = $blocks[0];
        }
        if($level == 'campus') {
            $location = $blocks[1];
        }
        elseif($level == 'library') {
            $location = $blocks[2];
        }
        elseif($level == 'shelving') {
            $location = $blocks[3];
        }
        else {
            error_log('You did not set the right parameters in your call to getLocation()');
        }
        return strtolower($location);
    }

    public function test($holding) {
        $shelvingLocations = [
            'art420', 'artresa', 'cdev', 'cjk', 'cjkref', 'cjkrfhy', 'cjksper', 'cmc',
            'eckx', 'film', 'gen', 'genhy', 'law', 'lawaid', 'lawcity', 'lawcs', 'lawdisp',
            'lawper', 'lawref', 'lawresp', 'lawrr', 'mapcl', 'mapref', 'mic', 'midemic',
            'pam', 'perbio', 'perphy', 'rr', 'rr2per', 'rr4', 'rr4cla', 'rr4j', 'rr5',
            'rr5ea', 'rr5eper', 'rr5per', 'rrexp', 'sci', 'sciddc', 'scihy', 'scilg',
            'scimicor', 'sciref', 'sfilm', 'slav', 'smedia', 'smicddc', 'soa', 'srefper',
            'ssadbdp', 'ssadmic', 'ssadpam', 'ssadper', 'ssadref', 'ssadx', 'w', 'wcjk'
        ];
        $config = explode(':::', $this->getLinkConfig('test'));
        $text = $config[0];
        if ($text == false) {
            return '';
        }
        if (count($config) < 2) {
            return $this->unavailable;
        }
        $serviceLink = $config[1];
        $icon = $config[2] ?? '';
        $classes = $config[3] ?? '';
        $tagline = $config[4] ?? '';
        $status = strtolower($holding['status']);
        $locationCode = $holding['location_code'];
        $hasCorrectStatus = in_array(
            $status,
            $this->statuses['cantFindIt']
        );
        $hasCorrectLocation = in_array(
            $this->getLocationCode($locationCode, 'shelving'),
            $shelvingLocations
        );
        if ($hasCorrectStatus && $hasCorrectLocation && $serviceLink) {
            return $this->template($serviceLink, $text, $icon, $classes, [], $tagline);
        }
    }


    public function maps($location, $callnum, $prefix) {
        $config = explode(':::', $this->getLinkConfig('maps'));
        $text = $config[0];
        if ($text == false) {
            return '';
        }
        if (count($config) < 2) {
            return $this->unavailable;
        }
        $serviceLink = sprintf($config[1], $location, $callnum, $prefix);
        $icon = $config[2] ?? '';
        $classes = $config[3] ?? '';
        $tagline = $config[4] ?? '';
        return $this->template($serviceLink, $text, $icon, $classes, [], $tagline);
    }
}
