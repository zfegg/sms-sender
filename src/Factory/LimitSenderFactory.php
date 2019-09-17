<?php

namespace Zfegg\SmsSender\Factory;

use Interop\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zfegg\SmsSender\LimitSender;
use Zfegg\SmsSender\Provider\ProviderInterface;

class LimitSenderFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $config = null)
    {
        $config = $container->get('config')['zfegg'][LimitSender::class] ?? [];

        return new LimitSender(
            isset($config['provider'])
                ? $container->get($config['provider'])
                : $container->get(ProviderInterface::class),
            isset($config['cache'])
                ? $container->get($config['cache'])
                : $container->get(CacheInterface::class),
            $config['day_send_times'] ?? 10,
            $config['waiting_time'] ?? 60
        );
    }
}
