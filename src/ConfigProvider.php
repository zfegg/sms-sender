<?php

namespace Zfegg\SmsSender;

use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\Factory\LimitSenderFactory;
use Zfegg\SmsSender\Factory\PostSmsCaptchaHandlerFactory;
use Zfegg\SmsSender\Factory\SmsCodeCaptchaFactory;
use Zfegg\SmsSender\Handler\PostSmsCaptchaHandler;
use Zfegg\SmsSender\LimitSender;
use Zfegg\SmsSender\Provider\NullProvider;

class ConfigProvider
{

    public function __invoke()
    {
        return [
            'dependencies' => [
                'factories' => [
                    LimitSender::class => LimitSenderFactory::class,
                    NullProvider::class => ReflectionBasedAbstractFactory::class,
                    PostSmsCaptchaHandler::class => PostSmsCaptchaHandlerFactory::class,
                ]
            ],
            'validators' => [
                'factories' => [
                    SmsCode::class => SmsCodeCaptchaFactory::class,
                ]
            ]
        ];
    }
}
