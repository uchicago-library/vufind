<?php
namespace UChicago\Mailer;
use Zend\Mail\Transport\Smtp, Zend\Mail\Transport\SmtpOptions,
    Zend\ServiceManager\ServiceLocatorInterface;

class Factory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $sm Service manager
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        // Load configurations:
        $config = $sm->get('VuFind\Config')->get('config');

        // Create mail transport:
        $settings = array (
            'host' => $config->Mail->host, 'port' => $config->Mail->port
        );
        if (isset($config->Mail->username) && isset($config->Mail->password)) {
            $settings['connection_class'] = 'login';
            $settings['connection_config'] = array(
                'username' => $config->Mail->username,
                'password' => $config->Mail->password
            );
        }
        $transport = new Smtp();
        $transport->setOptions(new SmtpOptions($settings));

        // Create service:
        return new \UChicago\Mailer\Mailer($transport);
    }
}
