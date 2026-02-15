<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use StageEngine\Application\Contracts\BillingRepository;
use StageEngine\Application\Contracts\CertificateRepository;
use StageEngine\Application\Contracts\CompanyRepository;
use StageEngine\Application\Contracts\EventLogRepository;
use StageEngine\Application\StageResolver;
use StageEngine\Application\StageTransitionService;
use StageEngine\Domain\Company;
use StageEngine\Domain\Stage;

final class StageTransitionServiceTest extends TestCase
{
    private InMemoryEventLogRepository $events;
    private InMemoryBillingRepository $billing;
    private InMemoryCertificateRepository $certificates;
    private InMemoryCompanyRepository $companies;
    private StageTransitionService $service;

    protected function setUp(): void
    {
        $this->events = new InMemoryEventLogRepository();
        $this->billing = new InMemoryBillingRepository();
        $this->certificates = new InMemoryCertificateRepository();
        $this->companies = new InMemoryCompanyRepository();
        $resolver = new StageResolver($this->events);
        $this->service = new StageTransitionService(
            $resolver,
            $this->events,
            $this->billing,
            $this->certificates,
            $this->companies
        );
    }

    public function testCannotTransitionToAwareWithoutDiscovery(): void
    {
        $company = $this->createCompanyAt(Stage::C1);

        self::assertFalse($this->service->canTransition($company, Stage::C2));
    }

    public function testCannotPlanDemoWithoutDateEvent(): void
    {
        $company = $this->createCompanyAt(Stage::W1);

        self::assertFalse($this->service->canTransition($company, Stage::W2));
    }

    public function testCannotBecomeCommittedWithoutInvoice(): void
    {
        $company = $this->createCompanyAt(Stage::W3);

        self::assertFalse($this->service->canTransition($company, Stage::H1));
    }

    public function testCannotBecomeCustomerWithoutPayment(): void
    {
        $company = $this->createCompanyAt(Stage::H1);

        self::assertFalse($this->service->canTransition($company, Stage::H2));
    }

    public function testCannotBecomeActivatedWithoutCertificate(): void
    {
        $company = $this->createCompanyAt(Stage::H2);

        self::assertFalse($this->service->canTransition($company, Stage::A1));
    }

    private function createCompanyAt(string $stage): Company
    {
        $company = $this->companies->create('Test Co', Stage::C0);

        if ($stage !== Stage::C0) {
            $this->events->append($company->id(), 'stage_transitioned', ['from' => Stage::C0, 'to' => $stage]);
            $this->companies->updateCachedStage($company->id(), $stage);
            $company = $company->withStage($stage);
        }

        return $company;
    }
}

final class InMemoryCompanyRepository implements CompanyRepository
{
    /** @var array<int, Company> */
    private array $items = [];
    private int $autoIncrement = 1;

    public function find(int $companyId): ?Company
    {
        return $this->items[$companyId] ?? null;
    }

    public function first(): ?Company
    {
        return array_values($this->items)[0] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function create(string $name, string $initialStage): Company
    {
        $company = new Company($this->autoIncrement++, $name, $initialStage);
        $this->items[$company->id()] = $company;

        return $company;
    }

    public function updateCachedStage(int $companyId, string $stage): void
    {
        if (!isset($this->items[$companyId])) {
            return;
        }

        $this->items[$companyId] = $this->items[$companyId]->withStage($stage);
    }
}

final class InMemoryEventLogRepository implements EventLogRepository
{
    /** @var array<int, array<int, array{type: string, payload: array<string,mixed>, created_at: \DateTimeImmutable}>> */
    private array $events = [];

    public function append(int $companyId, string $eventType, array $payload = []): void
    {
        $this->events[$companyId][] = [
            'type' => $eventType,
            'payload' => $payload,
            'created_at' => new \DateTimeImmutable(),
        ];
    }

    public function exists(int $companyId, string $eventType): bool
    {
        foreach ($this->events[$companyId] ?? [] as $event) {
            if ($event['type'] === $eventType) {
                return true;
            }
        }

        return false;
    }

    public function existsSince(int $companyId, string $eventType, \DateTimeImmutable $since): bool
    {
        foreach ($this->events[$companyId] ?? [] as $event) {
            if ($event['type'] === $eventType && $event['created_at'] >= $since) {
                return true;
            }
        }

        return false;
    }

    public function history(int $companyId): array
    {
        return [];
    }

    public function lastTransitionStage(int $companyId): ?string
    {
        $events = array_reverse($this->events[$companyId] ?? []);
        foreach ($events as $event) {
            if ($event['type'] === 'stage_transitioned' && isset($event['payload']['to'])) {
                return (string) $event['payload']['to'];
            }
        }

        return null;
    }
}

final class InMemoryBillingRepository implements BillingRepository
{
    private bool $invoice = false;
    private bool $payment = false;

    public function hasInvoice(int $companyId): bool
    {
        return $this->invoice;
    }

    public function hasPayment(int $companyId): bool
    {
        return $this->payment;
    }

    public function createInvoice(int $companyId, float $amount): void
    {
        $this->invoice = true;
    }

    public function registerPaymentForLatestInvoice(int $companyId): void
    {
        $this->payment = true;
    }
}

final class InMemoryCertificateRepository implements CertificateRepository
{
    private bool $issued = false;

    public function hasCertificate(int $companyId): bool
    {
        return $this->issued;
    }

    public function issue(int $companyId): void
    {
        $this->issued = true;
    }
}
