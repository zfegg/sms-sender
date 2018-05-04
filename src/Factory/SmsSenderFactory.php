<?php

namespace Zfegg\SmsSender\Factory;

use Interop\Container\ContainerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zfegg\SmsSender\Provider\ProviderInterface;
use Zfegg\SmsSender\SmsSender;

/**
 * Class SmsFactory
 *
 * @package Zfegg\SmsSender
 */
class SmsSenderFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        if (empty($options) && isset($config['sms_sender_config'])) {
            $options = $config['sms_sender_config'];
        }

        if (!isset($options['provider'])) {
            throw new \RuntimeException('SmsSender provider configuration error.');
        }

        $provider  = $this->getService($container, $options['provider'], ProviderInterface::class);
        $smsSender = new SmsSender($provider);

        if (isset($options['listeners'])) {
            $events = $container->has('EventManager') ? $container->get('EventManager') : $smsSender->getEventManager();

            foreach ($options['listeners'] as $listener) {
                /** @var ListenerAggregateInterface $listener */
                $listener = $this->getService($container, $listener, ListenerAggregateInterface::class);
                $listener->attach($events);
            }

            $smsSender->setEventManager($events);
        }

        return $smsSender;
    }

    private function getService(ContainerInterface $container, $config, $instance)
    {
        $service = null;
        if (is_string($config)) {
            $service = $container->get($config);
        } elseif (is_array($config)) {
            $className = $config['name'];
            $options   = isset($config['options']) ? $config['options'] : [];

            if ($container->has($className)) {
                if (method_exists($container, 'build')) {
                    $service = $container->build($className, $options);
                } else {
                    $service = $container->get($className);
                }
            } else {
                $service = new $className($options);
            }
        }

        if (!$service instanceof $instance) {
            throw new \RuntimeException('Config result must be an instanceof ' . $instance . '.');
        }

        return $service;
    }
}
