<?php

use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\Factory\LimitSendListenerFactory;
use Zfegg\SmsSender\Factory\SmsCodeCaptchaFactory;
use Zfegg\SmsSender\Factory\SmsSenderFactory;
use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\SmsSender;

return [
    'service_manager' => [
        'factories' => [
            SmsSender::class => SmsSenderFactory::class,
            LimitSendListener::class => LimitSendListenerFactory::class,
        ]
    ],
    'validators' => [
        'factories' => [
            SmsCode::class => SmsCodeCaptchaFactory::class,
        ]
    ]
];
