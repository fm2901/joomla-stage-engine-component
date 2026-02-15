<?php

declare(strict_types=1);

namespace StageEngine\Application\Contracts;

interface EventLogRepository
{
    /**
     * @param array<string, mixed> $payload
     */
    public function append(int $companyId, string $eventType, array $payload = []): void;

    public function exists(int $companyId, string $eventType): bool;

    public function existsSince(int $companyId, string $eventType, \DateTimeImmutable $since): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $companyId): array;

    public function lastTransitionStage(int $companyId): ?string;
}
