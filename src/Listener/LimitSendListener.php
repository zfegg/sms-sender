<?php

namespace Zfegg\SmsSender\Listener;

use Psr\SimpleCache\CacheInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zfegg\SmsSender\SmsEvent;

/**
 * Class LimitSendListener
 *
 * @package Zfegg\SmsSender\Listener
 * @author  moln.xie@gmail.com
 */
class LimitSendListener extends AbstractListenerAggregate
{
    const FUNCTION_NAME = '__FUNCTION__';

    protected $waitingTime = 60;

    protected $daySendTimes = 10;

    /**
     * @var CacheInterface
     */
    protected $cache;

    protected $errorMessageTemplates = [
        'waitingLock' => '请等待%sec%秒后再试',
        'timesLock'   => '一个手机号每天只能发送%daySendTimes%次短信,您的手机号已超出限制,请次日在试.',
    ];

    public function __construct(CacheInterface $cache, $daySendTimes = 10, $waitingTime = 60)
    {
        $this->cache = $cache;
        $this->setDaySendTimes($daySendTimes);
        $this->setWaitingTime($waitingTime);
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $events->attach(SmsEvent::EVENT_PRE_SEND, [$this, 'onPreSend'], $priority);
    }

    public function onPreSend(SmsEvent $e)
    {
        if ($e->getParam(self::FUNCTION_NAME) != 'limitSend') {
            return;
        }

        $e->setParam(__CLASS__, $this);

        $time        = time();
        $phoneNumber = $e->getPhoneNumber();
        $cache       = $this->cache;
        $limitTime   = $cache->get($phoneNumber);
        $waitingTime = $this->getWaitingTime();
        $waitingSec  = $waitingTime - ($time - $limitTime);

        $e->setParam('waitingSec', $waitingSec);

        if ($limitTime && $waitingSec > 0) {
            $error = $this->getMessage('waitingLock', [
                '%sec%'         => $waitingSec,
                '%phoneNumber%' => $phoneNumber
            ]);

            $e->setError($error);
            $e->stopPropagation(true);
            return;
        }

        $sendTimesCacheId = $phoneNumber . 'Times';
        if (($currentTimes = (int)$cache->get($sendTimesCacheId)) && $currentTimes >= $this->getDaySendTimes()) {
            $error = $this->getMessage('timesLock', [
                '%phoneNumber%' => $phoneNumber
            ]);
            $e->setError($error);
            $e->stopPropagation(true);
            return;
        }

        $currentTimes++;

        //缓存当天发送次数
        $cache->set(
            $sendTimesCacheId,
            $currentTimes,
            strtotime(date('Y-m-d 23:59:59')) - $time
        );

        //缓存本次发送锁定
        $cache->set($phoneNumber, $time, $waitingTime);
    }

    private function getMessage($code, array $variables = [])
    {
        $variables['%daySendTimes%'] = $this->getDaySendTimes();
        $variables['%waitingTime%']  = $this->getWaitingTime();

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
        $this->cache->delete($phoneNumber);
        $this->cache->delete($phoneNumber . 'Times');
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
     * Set waiting time.
     *
     * @param $waitingTime
     * @return $this
     */
    public function setWaitingTime($waitingTime)
    {
        $this->waitingTime = $waitingTime;
        return $this;
    }

    /**
     * Set how many times a day to send.
     *
     * @param int $daySendTimes
     *
     * @return $this
     */
    public function setDaySendTimes($daySendTimes)
    {
        $this->daySendTimes = $daySendTimes;
        return $this;
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
}
