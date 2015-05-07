<?php
/**
 * Feedback Controller
 *
 * PHP version 5
 *
 * @category VuFind2
 * @package  Controller
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace UChicago\Controller;
use Zend\Mail as Mail,
    Zend\Http\Client as Client,
    Zend\ServiceManager\ServiceManager;

/**
 * Feedback Class
 *
 * Controls the Feedback
 *
 * @category VuFind2
 * @package  Controller
 * @author   Brad Busenius <bbusenius@uchicago.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 *
 * Adds Knowledge Tracker support to the core VuFind FeebackController.
 */
class FeedbackController extends \VuFind\Controller\FeedbackController
{

    /**
     * The current page
     */
    public $pageUrl; 

    /**
     * The referring page
     */
    public $refUrl; 

    /**
     * Knowledge Tracker library ID
     */
    public $knowledgeTrackerLibId; 

    /**
     * A special ID for the Knowledge Tracker form.
     */
    public $abineguid; 
   

    /**
     * Constructor, sets the page URL and referring URL for the class.
     * The current page URL is set using $_SERVER['HTTP_REFERER'] because 
     * the method is fired asynchronously in a modal window. Thus the 
     * "referrer" is a pseudo-referrer. The previous page or referring url 
     * is accessed from the $_SESSION variable set in header.phtml.
     *
     * @param string pageUrl, url of the current page
     * @param string refUrl, the referring url
     */
    public function __construct()
    {
        $this->pageUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $this->refUrl = isset($_SESSION['Referrer']) ? $_SESSION['Referrer'] : '';
    }
  
    /**
     * Get the current page url.
     *
     * @returns string 
     */ 
    public function getPageUrl() {
        return $this->pageUrl;
    }

    /**
     * Get the referring page url.
     *
     * @returns string 
     */ 
    public function getRefUrl() {
        return $this->refUrl;
    }

    /**
     * Display Knowledge Tracker home form.
     *
     * @return \Zend\View\Model\ViewModel
     */
   public function knowledgeTrackerAction()
    {
        // Get the config files
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');

        // Set variables from the config
        $libId = $config['KnowledgeTracker']['library_id'];
        $formUrl = $config['KnowledgeTracker']['form_url'];
        $abineguid = $config['KnowledgeTracker']['abineguid'];

        // Create the view
        return $this->createViewModel(array('pageUrl' => $this->getPageUrl(), 'refUrl' => $this->getRefUrl(), 'libId' => $libId, 'formUrl' => $formUrl, 'abineguid' => $abineguid ));
    }


    /**
     * Dispatches a POST request for the javascript lightbox.
     *
     * @return POST request via php curl.
     */
   public function knowledgeTrackerFormAction()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');
        $post = $this->getRequest()->getPost();

        /*Don't use the zend framework for http requests. 
        The zend http client is garbage. Use php curl instead.*/
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      => $post,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_URL             => $config['KnowledgeTracker']['form_url'],
            CURLOPT_SSL_VERIFYPEER  => false, # We should fix this!
            CURLOPT_SSL_VERIFYHOST  => false, # Ditto
        );
        curl_setopt_array($ch, $curlConfig);
        curl_exec($ch);
        curl_close($ch);

        return null;
    }

}
