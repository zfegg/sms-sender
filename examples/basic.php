<?php

require __DIR__ . '/../vendor/autoload.php';

$smsSender = new \Zfegg\SmsSender\LimitSender(
    new \Zfegg\SmsSender\Provider\NullProvider(),
    new \Cache\Adapter\PHPArray\ArrayCachePool()
);

$result = $smsSender->send('13000000000', 'TestContent');
var_dump($result->isOk()); //bool(true)

$result = $smsSender->send('13000000000', 'send error test');
var_dump(
    $result->isOk(),     //bool(false)
    $result->getError(), //'请等待%sec%秒后再试'
    $result->getParams()  // ['waitingSec' => 60]
);


//发送短信验证码
$smsSender->clearLock('13000000000');
$validator = new \Zfegg\SmsSender\Captcha\SmsCode([
    'cache' => new \Cache\Adapter\PHPArray\ArrayCachePool()
]);
$handler = new \Zfegg\SmsSender\Handler\PostSmsCaptchaHandler(
    $smsSender,
    $validator,
    [
        'register' => 'Register captcha code: {code}',
        'login' => 'Login captcha code: {code}',
    ],
    new \Mezzio\ProblemDetails\ProblemDetailsResponseFactory(
        function () {
            return new Laminas\Diactoros\Response();
        }
    )
);

$req = (new \Laminas\Diactoros\ServerRequestFactory())->createServerRequest('POST', '/send-sms-captcha');
$req = $req->withParsedBody([
    'type' => 'register',
    'phone_number' => '13000000000',
]);
$response = $handler->handle($req);
var_dump((string)$response->getBody());


//验证短信验证码
$result = $validator->isValid('1234', ['phone_number' => '13000000000']);
var_dump($result);