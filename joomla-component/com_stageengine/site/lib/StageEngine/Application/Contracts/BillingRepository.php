<?php

declare(strict_types=1);

namespace StageEngine\Application\Contracts;

interface BillingRepository
{
    public function hasInvoice(int $companyId): bool;

    public function hasPayment(int $companyId): bool;

    public function createInvoice(int $companyId, float $amount): void;

    public function registerPaymentForLatestInvoice(int $companyId): void;
}
