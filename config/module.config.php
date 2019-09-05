<?php

use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\Factory\LimitSenderFactory;
use Zfegg\SmsSender\Factory\SmsCodeCaptchaFactory;
use Zfegg\SmsSender\LimitSender;
use Zfegg\SmsSender\Provider\NullProvider;

return [
    'service_manager' => [
        'factories' => [
            LimitSender::class => LimitSenderFactory::class,
            NullProvider::class => ReflectionBasedAbstractFactory::class,
        ]
    ],
    'validators' => [
        'factories' => [
            SmsCode::class => SmsCodeCaptchaFactory::class,
        ]
    ]
];
