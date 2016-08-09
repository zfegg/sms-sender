<?php

use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\Listener\ValidatorListener;
use Zfegg\SmsSender\Provider\ProviderInterface;
use Zfegg\SmsSender\SmsSender;

require __DIR__ . '/../vendor/autoload.php';

class ExampleProvider implements ProviderInterface
{
    public function __construct(array $options)
    {

    }

    /**
     * @param $phoneNumber
     * @param $content
     * @return mixed
     */
    public function send($phoneNumber, $content)
    {
        if ($content == 'send error test') {
            throw new \RuntimeException('An exception.');
        }

        return true;
    }
}

$smsSender = new SmsSender(new ExampleProvider(['app_id' => 'test']));

$result = $smsSender->send('13000000000', 'TestContent');
var_dump($result); //bool(true)

$result = $smsSender->send('13000000000', 'send error test');
var_dump(
    $result,                            //bool(false)
    $smsSender->getEvent()->getError(), //'An exception.'
    get_class($smsSender->getEvent()->getParam('exception'))  //RuntimeException
);


//Usage ValidatorListener
$validatorListener = new ValidatorListener();
$validatorListener->attach($smsSender->getEventManager());

$result = $smsSender->send('ErrorPhoneNumberFormat', 'TestContent');
var_dump(
    $result,                           //bool(false)
    $smsSender->getEvent()->getError() //"The input does not match a phone number format"
);


//Usage LimitSendListener
$limitSendListener = new LimitSendListener([
    'waitingTime' => 1,  //每个号码每秒限发送一次
    'cache'          => [
        'adapter' => 'Memory',
    ]
]);
$limitSendListener->attach($smsSender->getEventManager());

$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump($result); //bool(true)

$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump(
    $result, //bool(false)
    $smsSender->getEvent()->getError() //请等待1秒后在试
);
sleep(2);
$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump($result); //bool(true)
