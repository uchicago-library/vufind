<?
namespace UChicago\View\Helper\Phoenix;
use Laminas\View\Helper\AbstractHelper;

/**
 * Happy unicorns view helper. 
 *
 * @category View_Helpers
 * @package ServiceLinks
 * @author Brad Busenius <bbusenius@uchicago.edu>
 */
class ZoteroHarvesting extends AbstractHelper {

    public function __construct($config=false) {
         $this->rainbowConfig = $config;
    }

    public function happy() {
        return '<meta name="DC.Type" content="Dissertations"> <meta name="DC.Creator" content="Teichman, Matthew"> <meta name="DC.Title" content="Characterizing kinds: A semantics for generic sentences /"> <meta name="DC.Coverage" content="Ann Arbor :"> <meta name="DC.Date" content="2015"> <meta name="DC.Language" content="English">';
    }

    public function rainbowDash() {
        $config = $this->rainbowConfig;
        return $config->zotero;
    }
}
