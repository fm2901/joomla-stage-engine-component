<?php

declare(strict_types=1);

namespace StageEngine\Application\Contracts;

use StageEngine\Domain\Company;

interface CompanyRepository
{
    public function find(int $companyId): ?Company;

    public function first(): ?Company;

    /**
     * @return Company[]
     */
    public function all(): array;

    public function create(string $name, string $initialStage): Company;

    public function updateCachedStage(int $companyId, string $stage): void;
}
