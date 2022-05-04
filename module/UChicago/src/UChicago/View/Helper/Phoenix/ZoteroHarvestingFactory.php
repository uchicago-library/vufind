<?php
namespace UChicago\View\Helper\Phoenix;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ZoteroHarvestingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
	$bibHarvestingConfig = null;
        $config = $container->get('VuFind\Config\PluginManager')->get('config');
        $config = !isset($config->BibHarvesting) ? false : $config->BibHarvesting;
        return new \UChicago\View\Helper\Phoenix\ZoteroHarvesting($config);
    }
}
