<?php

namespace ZfeggTest\SmsSender\Handler;

use Prophecy\Argument;
use Laminas\Diactoros\ServerRequestFactory;
use Zfegg\SmsSender\Handler\PostSmsCaptchaHandler;
use Zfegg\SmsSender\Provider\ProviderInterface;
use Zfegg\SmsSender\Result;
use ZfeggTest\SmsSender\SetUpContainer;

class PostSmsCaptchaHandlerTest extends SetUpContainer
{

    public function testHandle()
    {
        /** @var PostSmsCaptchaHandler $handler */
        $handler = $this->container->get(PostSmsCaptchaHandler::class);

        $req = (new ServerRequestFactory())->createServerRequest('POST', '/');
        $req = $req->withParsedBody([
            'type' => 'test1',
            'phone_number' => '15000000000',
        ]);
        $response = $handler->handle($req);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('lock_time', $response->getPayload());
    }

    public function testProviderSendError()
    {
        $provider = $this->prophesize(ProviderInterface::class);

        $provider->send('15000000000', Argument::type('string'))
            ->willReturn(new Result(false, 'test error'))
            ->shouldBeCalled();

        $this->container->setService(ProviderInterface::class, $provider->reveal());
        /** @var PostSmsCaptchaHandler $handler */
        $handler = $this->container->get(PostSmsCaptchaHandler::class);

        $req = (new ServerRequestFactory())->createServerRequest('POST', '/');
        $req = $req->withParsedBody([
            'type' => 'test1',
            'phone_number' => '15000000000',
        ]);
        $response = $handler->handle($req);

        $this->assertEquals(403, $response->getStatusCode());
    }
}
