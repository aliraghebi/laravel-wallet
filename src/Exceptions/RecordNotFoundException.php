<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use UnderflowException;

final class RecordNotFoundException extends UnderflowException implements ExceptionInterface {
    /**
     * @param  non-empty-array<string>  $missingKeys
     */
    public function __construct(
        string $message,
        int $code,
        private readonly array $missingKeys
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return non-empty-array<string>
     */
    public function getMissingKeys(): array {
        return $this->missingKeys;
    }
}
