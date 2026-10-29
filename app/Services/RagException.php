<?php

namespace App\Services;

use RuntimeException;

class RagException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 502,
        public readonly bool $unreachable = false,
    ) {
        parent::__construct($message);
    }
}