<?php

declare(strict_types=1);

namespace StageEngine\Application;

use StageEngine\Application\Contracts\EventLogRepository;
use StageEngine\Domain\Stage;

final class StageResolver
{
    public function __construct(private readonly EventLogRepository $events)
    {
    }

    public function resolve(int $companyId): string
    {
        return $this->events->lastTransitionStage($companyId) ?? Stage::C0;
    }
}
