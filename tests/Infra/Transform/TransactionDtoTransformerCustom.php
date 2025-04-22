<?php

namespace ArsamMe\Wallet\Test\Infra\Transform;

use ArsamMe\Wallet\Internal\Dto\TransactionDtoInterface;
use ArsamMe\Wallet\Internal\Transform\TransactionDtoTransformer;
use ArsamMe\Wallet\Internal\Transform\TransactionDtoTransformerInterface;

final readonly class TransactionDtoTransformerCustom implements TransactionDtoTransformerInterface {
    public function __construct(
        private TransactionDtoTransformer $transactionDtoTransformer
    ) {}

    public function extract(TransactionDtoInterface $dto): array {
        $bankMethod = null;
        if ($dto->getMeta() !== null) {
            $bankMethod = $dto->getMeta()['bank_method'] ?? null;
        }

        return array_merge($this->transactionDtoTransformer->extract($dto), [
            'bank_method' => $bankMethod,
        ]);
    }
}
