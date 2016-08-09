<?php

return [
    'service_manager' => [
        'factories' => [
            \Zfegg\SmsSender\SmsSender::class => \Zfegg\SmsSender\Factory\SmsSenderFactory::class,
        ]
    ],
    'validators' => [
        'factories' => [
            \Zfegg\SmsSender\Captcha\SmsCode::class => \Zfegg\SmsSender\Factory\SmsCodeCaptchaFactory::class,
        ]
    ]
];
