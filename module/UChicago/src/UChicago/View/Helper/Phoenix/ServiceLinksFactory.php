<?php
namespace UChicago\View\Helper\Phoenix;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceLinksFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $config = $container->get('VuFind\Config\PluginManager')->get('config');
        $config = !isset($config->ServiceLinks) ? false : $config->ServiceLinks;
        return new \UChicago\View\Helper\Phoenix\ServiceLinks($config);
    }
}
