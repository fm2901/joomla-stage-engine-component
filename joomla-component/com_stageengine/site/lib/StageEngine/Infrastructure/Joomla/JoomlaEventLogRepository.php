<?php

declare(strict_types=1);

namespace StageEngine\Infrastructure\Joomla;

use Joomla\Database\DatabaseDriver;
use StageEngine\Application\Contracts\EventLogRepository;

final class JoomlaEventLogRepository implements EventLogRepository
{
    public function __construct(private readonly DatabaseDriver $db)
    {
    }

    public function append(int $companyId, string $eventType, array $payload = []): void
    {
        $obj = (object) [
            'company_id' => $companyId,
            'event_type' => $eventType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->db->insertObject('#__company_events', $obj, 'id');
    }

    public function exists(int $companyId, string $eventType): bool
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('COUNT(1)')
            ->from($this->db->quoteName('#__company_events'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId)
            ->where($this->db->quoteName('event_type') . ' = ' . $this->db->quote($eventType));
        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    public function existsSince(int $companyId, string $eventType, \DateTimeImmutable $since): bool
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('COUNT(1)')
            ->from($this->db->quoteName('#__company_events'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId)
            ->where($this->db->quoteName('event_type') . ' = ' . $this->db->quote($eventType))
            ->where($this->db->quoteName('created_at') . ' >= ' . $this->db->quote($since->format('Y-m-d H:i:s')));
        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    public function history(int $companyId): array
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__company_events'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId)
            ->order($this->db->quoteName('created_at') . ' DESC');
        $this->db->setQuery($query);
        $rows = $this->db->loadAssocList() ?? [];

        foreach ($rows as &$row) {
            $row['payload'] = json_decode((string) $row['payload'], true) ?: [];
        }

        return $rows;
    }

    public function lastTransitionStage(int $companyId): ?string
    {
        $companyId = (int) $companyId;
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('payload'))
            ->from($this->db->quoteName('#__company_events'))
            ->where($this->db->quoteName('company_id') . ' = ' . $companyId)
            ->where($this->db->quoteName('event_type') . ' = ' . $this->db->quote('stage_transitioned'))
            ->order($this->db->quoteName('id') . ' DESC');
        $this->db->setQuery($query, 0, 1);
        $payload = $this->db->loadResult();

        if (!is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) && isset($decoded['to']) ? (string) $decoded['to'] : null;
    }
}
