<?php


namespace ZfeggTest\SmsSender;

use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Laminas\Diactoros\Response;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Zfegg\SmsSender\ConfigProvider;
use Zfegg\SmsSender\Handler\PostSmsCaptchaHandler;
use Zfegg\SmsSender\Provider\NullProvider;
use Zfegg\SmsSender\Provider\ProviderInterface;

abstract class SetUpContainer extends TestCase
{

    /** @var ServiceManager */
    protected $container;

    protected function setUp()
    {
        $config = (new ConfigProvider())();
        $config = ArrayUtils::merge($config, (new \Laminas\Validator\ConfigProvider())());
        $config = ArrayUtils::merge($config, (new \Mezzio\ProblemDetails\ConfigProvider())());
        $config['zfegg'] = [
            PostSmsCaptchaHandler::class => [
                'types' => [
                    'test1' => 'test1 content {code}',
                    'test2' => 'test2 content {code}',
                ],
            ],
        ];
        $config['dependencies']['aliases'] = [
            ProviderInterface::class => NullProvider::class,
        ];

        $this->container = new ServiceManager($config['dependencies']);
        $this->container->setService('config', $config);
        $this->container->setService(CacheInterface::class, new ArrayCachePool());
        $this->container->setService(ResponseInterface::class, function () {
            return new Response();
        });
    }
}
