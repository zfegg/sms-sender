<?php

namespace Zfegg\SmsSender;

class Result
{
    private $ok;
    private $error;
    private $params;

    public function __construct(bool $ok = true, string $error = '', array $params = [])
    {
        $this->ok = $ok;
        $this->error = $error;
        $this->params = $params;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
