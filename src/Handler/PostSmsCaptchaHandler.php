<?php

namespace Zfegg\SmsSender\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\LimitSender;

class PostSmsCaptchaHandler implements RequestHandlerInterface
{

    private $smsSender;

    private $types = [];

    private $responseFactory;

    private $smsCode;

    public function __construct(
        LimitSender $smsSender,
        SmsCode $smsCode,
        array $types,
        ProblemDetailsResponseFactory $responseFactory
    ) {
        $this->smsSender = $smsSender;
        $this->smsCode = $smsCode;
        $this->types = $types;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validateRequest($request);

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $code = $this->smsCode->generate($data['phone_number']);

        $content = str_replace('{code}', $code[0], $this->types[$data['type']]);
        $result = $this->smsSender->send($data['phone_number'], $content);

        if (! $result->isOk()) {
            return $this->responseFactory->createResponse(
                $request,
                403,
                $result->getError(),
                '',
                '',
                $result->getParams()
            );
        }

        return new JsonResponse(
            [
                'lock_time' => $this->smsSender->getWaitingTime(),
            ]
        );
    }

    protected function validateRequest(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        if (empty($data['type']) || ! isset($this->types[$data['type']])) {
            return $this->responseFactory->createResponse($request, 422, 'Invalid arg type');
        }

        if (empty($data['phone_number']) || ! preg_match('/^1\d{10}$/', $data['phone_number'])) {
            return $this->responseFactory->createResponse($request, 422, 'Invalid arg phone_number');
        }

        return $data;
    }
}
