<?php

namespace Zfegg\SmsSender\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zfegg\SmsSender\Listener\LimitSendListener;

class LimitSendListenerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $options = $options ?: [];

        if (isset($config['sms_sender_config'][LimitSendListener::class])) {
            $options = $options + $config['sms_sender_config'][LimitSendListener::class];
        }

        return new LimitSendListener(
            $container->get($options['cache']),
            $options['day_send_times'],
            $options['waiting_time']
        );
    }
}
