<?php
namespace UChicago\View\Helper\Phoenix;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class KnowledgeTrackerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        return new \UChicago\View\Helper\Phoenix\KnowledgeTracker();
    }
}
