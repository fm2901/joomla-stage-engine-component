<?php

declare(strict_types=1);

namespace StageEngine\Domain;

final class Company
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private string $stage
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function stage(): string
    {
        return $this->stage;
    }

    public function withStage(string $stage): self
    {
        $clone = clone $this;
        $clone->stage = $stage;

        return $clone;
    }
}
