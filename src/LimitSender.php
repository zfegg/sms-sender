<?php


namespace Zfegg\SmsSender;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Zfegg\SmsSender\Event\PreSendEvent;
use Zfegg\SmsSender\Event\SendResultEvent;
use Zfegg\SmsSender\Provider\ProviderInterface;

class LimitSender implements ProviderInterface
{

    protected $waitingTime = 60;

    protected $daySendTimes = 10;

    /**
     * @var CacheInterface
     */
    protected $cache;

    protected $errorMessageTemplates = [
        'waitingLock' => '请等待%sec%秒后再试',
        'timesLock'   => '一个手机号每天只能发送%daySendTimes%次短信,您的手机号已超出限制,请次日再试.',
    ];

    protected $provider;

    protected $events;

    public function __construct(
        ProviderInterface $provider,
        CacheInterface $cache,
        $daySendTimes = 10,
        $waitingTime = 60,
        ?EventDispatcherInterface $events = null
    ) {
        $this->cache = $cache;
        $this->provider = $provider;
        $this->daySendTimes = $daySendTimes;
        $this->waitingTime = $waitingTime;
        $this->events = $events;
    }

    public function send(string $phoneNumber, string $content): Result
    {
        $preSendEvent = new PreSendEvent($phoneNumber, $content);
        $this->trigger($preSendEvent);

        if ($preSendEvent->getResult()) {
            return $preSendEvent->getResult();
        }

        $phoneNumber = $preSendEvent->getPhoneNumber();
        $content = $preSendEvent->getContent();

        $time = time();
        $cache = $this->cache;
        $sendTimeKey = $phoneNumber . 'Time';
        $limitTime = $cache->get($sendTimeKey);
        $waitingTime = $this->getWaitingTime();
        $waitingSec = $waitingTime - ($time - $limitTime);

        $params = ['waitingSec' => $waitingSec];

        if ($limitTime && $waitingSec > 0) {
            $error = $this->getMessage(
                'waitingLock',
                [
                    '%sec%' => $waitingSec,
                    '%phoneNumber%' => $phoneNumber
                ]
            );

            $result = new Result(false, $error, $params);
            $this->trigger(new SendResultEvent($phoneNumber, $content, $result));

            return $result;
        }

        $sendNumCacheId = $phoneNumber . 'Num';
        if (($currentTimes = (int)$cache->get($sendNumCacheId)) && $currentTimes >= $this->getDaySendTimes()) {
            $error = $this->getMessage(
                'timesLock',
                [
                    '%phoneNumber%' => $phoneNumber
                ]
            );

            $result = new Result(false, $error, $params);
            $this->trigger(new SendResultEvent($phoneNumber, $content, $result));

            return $result;
        }

        $result = $this->provider->send($phoneNumber, $content);

        if ($result->isOk()) {
            $currentTimes++;

            //缓存当天发送次数
            $cache->set(
                $sendNumCacheId,
                $currentTimes,
                strtotime(date('Y-m-d 23:59:59')) - $time
            );

            //缓存本次发送锁定
            $cache->set($sendTimeKey, $time, $waitingTime);

            $this->trigger(new SendResultEvent($phoneNumber, $content, $result));
        }

        return $result;
    }

    private function getMessage($code, array $variables = [])
    {
        $variables['%daySendTimes%'] = $this->getDaySendTimes();
        $variables['%waitingTime%'] = $this->getWaitingTime();

        return str_replace(array_keys($variables), $variables, $this->errorMessageTemplates[$code]);
    }

    /**
     * Clear number limit.
     *
     * @param $phoneNumber
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function clearLock($phoneNumber)
    {
        $this->cache->delete($phoneNumber . 'Time');
        $this->cache->delete($phoneNumber . 'Num');
    }

    /**
     * Get waiting time
     *
     * @return int
     */
    public function getWaitingTime()
    {
        return $this->waitingTime;
    }

    /**
     * Get send times
     *
     * @return int
     */
    public function getDaySendTimes()
    {
        return $this->daySendTimes;
    }

    private function trigger(object $event)
    {
        $this->events && $this->events->dispatch($event);
    }
}
