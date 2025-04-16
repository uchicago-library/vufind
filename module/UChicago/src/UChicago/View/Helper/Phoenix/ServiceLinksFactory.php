<?php
namespace UChicago\View\Helper\Phoenix;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ServiceLinksFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        // Get configuration for service links
        $config = $container->get('VuFind\Config\PluginManager')->get('config');
        $config = !isset($config->ServiceLinks) ? false : $config->ServiceLinks;

        // Get the Auth view helper
        $viewHelperManager = $container->get('ViewHelperManager');
        $auth = $viewHelperManager->get('auth');

        // Instantiate our helper with the configuration and auth helper
        return new \UChicago\View\Helper\Phoenix\ServiceLinks($config, $auth);
    }
}
