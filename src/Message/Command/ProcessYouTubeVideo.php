<?php


namespace App\Message\Command;


use App\Message\AsyncMessage;

class ProcessYouTubeVideo implements AsyncMessage
{
    private $requestId;

    public function __construct(string $requestId)
    {
        $this->requestId = $requestId;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
