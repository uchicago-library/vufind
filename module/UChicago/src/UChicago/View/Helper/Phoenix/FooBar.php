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
class FooBar extends AbstractHelper {

    public function __construct($config=false) {
         $this->rainbowConfig = $config;
    }

    public function happy() {
        return 'Unicorns';
    }

    public function rainbowDash() {
        $config = $this->rainbowConfig;
        return $config->unicorn;
    }
}
