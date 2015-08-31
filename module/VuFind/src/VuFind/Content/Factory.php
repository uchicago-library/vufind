<?php
/**
 * Factory for instantiating content loaders
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2009.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Content
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace VuFind\Content;
use Zend\ServiceManager\ServiceManager;

/**
 * Factory for instantiating content loaders
 *
 * @category VuFind2
 * @package  Content
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Create Author Notes loader
     *
     * @param ServiceManager $sm Service manager
     *
     * @return mixed
     */
    public static function getAuthorNotes(ServiceManager $sm)
    {
        $loader = $sm->getServiceLocator()
            ->get('VuFind\ContentAuthorNotesPluginManager');
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $providers = isset($config->Content->authorNotes)
            ? $config->Content->authorNotes : '';
        return new Loader($loader, $providers);
    }

    /**
     * Create Excerpts loader
     *
     * @param ServiceManager $sm Service manager
     *
     * @return mixed
     */
    public static function getExcerpts(ServiceManager $sm)
    {
        $loader = $sm->getServiceLocator()
            ->get('VuFind\ContentExcerptsPluginManager');
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $providers = isset($config->Content->excerpts)
            ? $config->Content->excerpts : '';
        return new Loader($loader, $providers);
    }

    /**
     * Create Reviews loader
     *
     * @param ServiceManager $sm Service manager
     *
     * @return mixed
     */
    public static function getReviews(ServiceManager $sm)
    {
        $loader = $sm->getServiceLocator()
            ->get('VuFind\ContentReviewsPluginManager');
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $providers = isset($config->Content->reviews)
            ? $config->Content->reviews : '';
        return new Loader($loader, $providers);
    }

    /**
     * Create Summaries loader
     *
     * @param ServiceManager $sm Service manager
     *
     * @return mixed
     */
    public static function getSummaries(ServiceManager $sm)
    {
        $loader = $sm->getServiceLocator()
            ->get('VuFind\ContentSummariesPluginManager');
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $providers = isset($config->Content->summaries)
            ? $config->Content->summaries : '';
        return new Loader($loader, $providers);
    }

    /**
     * Create TOC loader
     *
     * @param ServiceManager $sm Service manager
     *
     * @return mixed
     */
    public static function getTOC(ServiceManager $sm)
    {
        $loader = $sm->getServiceLocator()
            ->get('VuFind\ContentTOCPluginManager');
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $providers = isset($config->Content->toc)
            ? $config->Content->toc : '';
        return new Loader($loader, $providers);
    }
}
