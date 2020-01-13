<?php

namespace Zfegg\SmsSender\Factory;

use Psr\Container\ContainerInterface;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Laminas\Validator\ValidatorPluginManager;
use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\Handler\PostSmsCaptchaHandler;
use Zfegg\SmsSender\LimitSender;

class PostSmsCaptchaHandlerFactory
{

    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['zfegg'][PostSmsCaptchaHandler::class] ?? [];

        if (empty($config['types'])) {
            throw new \RuntimeException('Need configure "types"');
        }

        return new PostSmsCaptchaHandler(
            $container->get(LimitSender::class),
            $container->get(ValidatorPluginManager::class)->get(SmsCode::class, $config[SmsCode::class] ?? []),
            $config['types'],
            $container->get(ProblemDetailsResponseFactory::class)
        );
    }
}
