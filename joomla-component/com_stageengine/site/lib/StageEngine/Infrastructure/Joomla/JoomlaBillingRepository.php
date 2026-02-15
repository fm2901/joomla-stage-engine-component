<?php

declare(strict_types=1);

namespace StageEngine\Infrastructure\Joomla;

use Joomla\Database\DatabaseDriver;
use StageEngine\Application\Contracts\BillingRepository;

final class JoomlaBillingRepository implements BillingRepository
{
    public function __construct(private readonly DatabaseDriver $db)
    {
    }

    public function hasInvoice(int $companyId): bool
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('COUNT(1)')
            ->from($this->db->quoteName('#__invoices'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId);
        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    public function hasPayment(int $companyId): bool
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('COUNT(1)')
            ->from($this->db->quoteName('#__payments', 'p'))
            ->innerJoin(
                $this->db->quoteName('#__invoices', 'i') .
                ' ON ' . $this->db->quoteName('i.id') . ' = ' . $this->db->quoteName('p.invoice_id')
            )
            ->where($this->db->quoteName('i.company_id') . ' = ' . $companyId);
        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    public function createInvoice(int $companyId, float $amount): void
    {
        $obj = (object) [
            'company_id' => $companyId,
            'amount' => $amount,
            'status' => 'issued',
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->db->insertObject('#__invoices', $obj, 'id');
    }

    public function registerPaymentForLatestInvoice(int $companyId): void
    {
        $invoiceId = $this->latestInvoiceId($companyId);

        if ($invoiceId === null) {
            return;
        }

        $obj = (object) [
            'invoice_id' => $invoiceId,
            'paid_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->db->insertObject('#__payments', $obj, 'id');
    }

    private function latestInvoiceId(int $companyId): ?int
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__invoices'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId)
            ->order($this->db->quoteName('id') . ' DESC');
        $this->db->setQuery($query, 0, 1);
        $id = $this->db->loadResult();

        return $id === null ? null : (int) $id;
    }
}
