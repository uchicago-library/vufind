<?php
namespace UChicago\View\Helper\Phoenix;
use VuFind\Exception\Date as DateException;

class Citation extends \VuFind\View\Helper\Root\Citation
{
    /**
     * Retrieve a citation in a particular format
     * 
     * Returns the citation in the format specified
     * 
     * @param string $format Citation format ('APA' or 'MLA')
     *
     * @return string        Formatted citation
     */
    public function getCitation($format)
    {
        // Construct method name for requested format:
        $method = 'getCitation' . $format;

        // Avoid calls to inappropriate/missing methods:
        if (!empty($format) && method_exists($this, $method)) {
            return $this->$method();
        }

        // Return blank string if no valid method found:
        return '';
    }

    /**
     * Get Chicago Style citation.
     *
     * This function returns a Chicago Style citation using a modified version
     * of the MLA logic.
     *
     * @return string
     */
    public function getCitationChicago()
    {
        return $this->getCitationMLA(9, ', no. ', 'chicago');
    }

    /**
     * Get MLA citation.
     *
     * This function assigns all the necessary variables and then returns an MLA
     * citation. By adjusting the parameters below, it can also render a Chicago
     * Style citation.
     *
     * @param int    $etAlThreshold   The number of authors to abbreviate with 'et
     * al.'
     * @param string $volNumSeparator String to separate volume and issue number
     * in citation.
     *
     * @return string
     */
    public function getCitationMLA($etAlThreshold = 4, $volNumSeparator = '.', $citationStyle = 'mla')
    {
        $format = '';
        if ($citationStyle == 'mla') {
        $formats = $this->driver->getFormats();
            if (in_array('Print', $formats)) {
                $format = 'Print';
            } else if (in_array('Audio cassette', $formats)) {
                $format = 'Audiocassette';
            } else if (in_array('CD', $formats)) {
                $format = 'CD';
            } else if (in_array('DVD', $formats)) {
                $format = 'DVD';
            } else if (in_array('E-Resource', $formats)) {
                $format = 'Web';
            } else if (in_array('Laserdisc', $formats)) {
                $format = 'Laserdisc';
            } else if (in_array('LP', $formats)) {
                $format = 'LP';
            } else if (in_array('Video cassette', $formats)) {
                $format = 'Videocassette';
            }
        } 
        $mla = array(
            'title' => $this->getMLATitle(),
            'authors' => $this->getMLAAuthors($etAlThreshold)
        );
        $mla['format'] = $format;
        $mla['periodAfterTitle'] = !$this->isPunctuated($mla['title']);

        // Behave differently for books vs. journals:
        $partial = $this->getView()->plugin('partial');
        if (empty($this->details['journal'])) {
            $mla['publisher'] = $this->getPublisher();
            $mla['year'] = $this->getYear();
            $mla['edition'] = $this->getEdition();
            return $partial('Citation/' . $citationStyle . '.phtml', $mla);
        } else {
            // Add other journal-specific details:
            $mla['pageRange'] = $this->getPageRange();
            $mla['journal'] =  $this->capitalizeTitle($this->details['journal']);
            $mla['numberAndDate'] = $this->getMLANumberAndDate($volNumSeparator);
            return $partial('Citation/' . $citationStyle . '-article.phtml', $mla);
        }
    }
}
