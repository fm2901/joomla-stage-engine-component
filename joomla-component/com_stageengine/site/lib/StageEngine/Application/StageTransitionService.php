<?php

declare(strict_types=1);

namespace StageEngine\Application;

use StageEngine\Application\Contracts\BillingRepository;
use StageEngine\Application\Contracts\CertificateRepository;
use StageEngine\Application\Contracts\CompanyRepository;
use StageEngine\Application\Contracts\EventLogRepository;
use StageEngine\Domain\Company;
use StageEngine\Domain\Exceptions\DomainException;
use StageEngine\Domain\Stage;

final class StageTransitionService
{
    public function __construct(
        private readonly StageResolver $resolver,
        private readonly EventLogRepository $events,
        private readonly BillingRepository $billing,
        private readonly CertificateRepository $certificates,
        private readonly CompanyRepository $companies
    ) {
    }

    public function canTransition(Company $company, string $target): bool
    {
        $current = $this->resolver->resolve($company->id());
        $next = Stage::next($current);

        if ($next === null || $next !== $target) {
            return false;
        }

        return match ($target) {
            Stage::C1 => $this->events->exists($company->id(), 'decision_maker_call'),
            Stage::C2 => $this->events->exists($company->id(), 'discovery_completed'),
            Stage::W1 => $this->events->exists($company->id(), 'discovery_completed'),
            Stage::W2 => $this->events->exists($company->id(), 'demo_planned'),
            Stage::W3 => $this->events->existsSince(
                $company->id(),
                'demo_completed',
                new \DateTimeImmutable('-60 days')
            ),
            Stage::H1 => $this->billing->hasInvoice($company->id()),
            Stage::H2 => $this->billing->hasPayment($company->id()),
            Stage::A1 => $this->certificates->hasCertificate($company->id()),
            default => false,
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getAvailableActions(Company $company): array
    {
        $current = $this->resolver->resolve($company->id());
        $next = Stage::next($current);

        if ($next === null) {
            return [];
        }

        if (!$this->canTransition($company, $next)) {
            return [];
        }

        return [[
            'code' => $next,
            'label' => sprintf('Перейти в %s (%s)', Stage::label($next), $next),
            'target' => $next,
        ]];
    }

    public function transition(Company $company, string $target): void
    {
        if (!$this->canTransition($company, $target)) {
            throw new DomainException(sprintf(
                'Transition from %s to %s is not allowed',
                $this->resolver->resolve($company->id()),
                $target
            ));
        }

        $from = $this->resolver->resolve($company->id());
        $this->events->append($company->id(), 'stage_transitioned', [
            'from' => $from,
            'to' => $target,
        ]);
        $this->companies->updateCachedStage($company->id(), $target);
    }
}
