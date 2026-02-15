<?php

declare(strict_types=1);

namespace StageEngine\Infrastructure\Joomla;

use Joomla\Database\DatabaseDriver;
use StageEngine\Application\Contracts\CompanyRepository;
use StageEngine\Domain\Company;

final class JoomlaCompanyRepository implements CompanyRepository
{
    public function __construct(private readonly DatabaseDriver $db)
    {
    }

    public function find(int $companyId): ?Company
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__companies'))
            ->where($this->db->quoteName('id') . ' = ' . $companyId);
        $this->db->setQuery($query);
        $row = $this->db->loadAssoc();

        if ($row === null) {
            return null;
        }

        return new Company((int) $row['id'], (string) $row['name'], (string) $row['stage']);
    }

    public function first(): ?Company
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__companies'))
            ->order($this->db->quoteName('id') . ' ASC');
        $this->db->setQuery($query, 0, 1);
        $row = $this->db->loadAssoc();

        if ($row === null) {
            return null;
        }

        return new Company((int) $row['id'], (string) $row['name'], (string) $row['stage']);
    }

    public function all(): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__companies'))
            ->order($this->db->quoteName('name') . ' ASC');
        $this->db->setQuery($query);
        $rows = $this->db->loadAssocList() ?? [];

        $items = [];
        foreach ($rows as $row) {
            $items[] = new Company((int) $row['id'], (string) $row['name'], (string) $row['stage']);
        }

        return $items;
    }

    public function create(string $name, string $initialStage): Company
    {
        $obj = (object) [
            'name' => $name,
            'stage' => $initialStage,
            'cached_stage' => $initialStage,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->db->insertObject('#__companies', $obj, 'id');

        return new Company((int) $obj->id, $name, $initialStage);
    }

    public function updateCachedStage(int $companyId, string $stage): void
    {
        $companyId = (int) $companyId;
        $quotedStage = $this->db->quote($stage);
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__companies'))
            ->set($this->db->quoteName('stage') . ' = ' . $quotedStage)
            ->set($this->db->quoteName('cached_stage') . ' = ' . $quotedStage)
            ->where($this->db->quoteName('id') . ' = ' . $companyId);
        $this->db->setQuery($query)->execute();
    }
}
