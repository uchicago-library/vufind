<?php

namespace UChicago\View\Helper\Phoenix;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper class to add links for a feedback link.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   John Jung <jej@uchicago.edu>
 */
class FeedbackLink extends \Zend\View\Helper\AbstractHelper
{

	public function __construct()
	{
	}

    public function feedbackLink() {

        $referer = rawurlencode($_SERVER['HTTP_REFERER']);
        $current = rawurlencode($_SERVER['SCRIPT_URI'] . '?' . $_SERVER['QUERY_STRING']);

        $href = sprintf('http://www.lib.uchicago.edu/e/ask/catalog.html?url=%s&referer=%s', $current, $referer);

        return sprintf("<a href='%s' class='external'>Leave Feedback</a>", $href);
    }
}
