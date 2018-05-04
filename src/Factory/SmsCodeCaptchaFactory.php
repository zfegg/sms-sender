<?php

namespace Zfegg\SmsSender\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zfegg\SmsSender\Captcha\SmsCode;

/**
 * Class SmsCodeValidatorFactory
 *
 * @package Zfegg\SmsSender\Factory
 * @author  moln.xie@gmail.com
 */
class SmsCodeCaptchaFactory implements FactoryInterface
{
    protected $createOptions = [];

    public function __construct($options = [])
    {
        $this->createOptions = $options;
    }

    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        if (isset($options['cache'])) {
            if (is_string($options['cache'])) {
                $options['cache'] = $services->get($options['cache']);
            }
        }

        return new SmsCode($options);
    }
}
