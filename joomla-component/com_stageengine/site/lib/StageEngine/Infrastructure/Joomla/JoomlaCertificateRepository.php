<?php

declare(strict_types=1);

namespace StageEngine\Infrastructure\Joomla;

use Joomla\Database\DatabaseDriver;
use StageEngine\Application\Contracts\CertificateRepository;

final class JoomlaCertificateRepository implements CertificateRepository
{
    public function __construct(private readonly DatabaseDriver $db)
    {
    }

    public function hasCertificate(int $companyId): bool
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('COUNT(1)')
            ->from($this->db->quoteName('#__certificates'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId);
        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    public function issue(int $companyId): void
    {
        $obj = (object) [
            'company_id' => $companyId,
            'issued_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->db->insertObject('#__certificates', $obj, 'id');
    }
}
