<?php

declare(strict_types=1);

namespace StageEngine\Application\Contracts;

interface CertificateRepository
{
    public function hasCertificate(int $companyId): bool;

    public function issue(int $companyId): void;
}
