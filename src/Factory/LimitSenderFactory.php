<?php

namespace Zfegg\SmsSender\Factory;

use Interop\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zfegg\SmsSender\LimitSender;
use Zfegg\SmsSender\Provider\ProviderInterface;

class LimitSenderFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['zfegg'] ?? [];
        $options = $config[LimitSender::class] ?? [];

        return new LimitSender(
            isset($options['provider'])
                ? $container->get($options['provider'])
                : $container->get(ProviderInterface::class),
            isset($options['cache'])
                ? $container->get($options['cache'])
                : $container->get(CacheInterface::class),
            $options['day_send_times'] ?? 10,
            $options['waiting_time'] ?? 60
        );
    }
}
