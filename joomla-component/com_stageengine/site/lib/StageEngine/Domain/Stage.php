<?php

declare(strict_types=1);

namespace StageEngine\Domain;

final class Stage
{
    public const N0 = 'N0';
    public const C0 = 'C0';
    public const C1 = 'C1';
    public const C2 = 'C2';
    public const W1 = 'W1';
    public const W2 = 'W2';
    public const W3 = 'W3';
    public const H1 = 'H1';
    public const H2 = 'H2';
    public const A1 = 'A1';

    /**
     * Canonical sequence from cold lead to activated customer.
     *
     * @return string[]
     */
    public static function ordered(): array
    {
        return [
            self::C0,
            self::C1,
            self::C2,
            self::W1,
            self::W2,
            self::W3,
            self::H1,
            self::H2,
            self::A1,
        ];
    }

    public static function label(string $stage): string
    {
        $labels = [
            self::N0 => 'Null',
            self::C0 => 'Ice',
            self::C1 => 'Touched',
            self::C2 => 'Aware',
            self::W1 => 'Interested',
            self::W2 => 'Demo Planned',
            self::W3 => 'Demo Done',
            self::H1 => 'Committed',
            self::H2 => 'Customer',
            self::A1 => 'Activated',
        ];

        return $labels[$stage] ?? $stage;
    }

    public static function next(string $stage): ?string
    {
        $ordered = self::ordered();
        $idx = array_search($stage, $ordered, true);

        if ($idx === false || !isset($ordered[$idx + 1])) {
            return null;
        }

        return $ordered[$idx + 1];
    }

    public static function isAtLeast(string $current, string $threshold): bool
    {
        $ordered = self::ordered();
        $currentIdx = array_search($current, $ordered, true);
        $thresholdIdx = array_search($threshold, $ordered, true);

        if ($currentIdx === false || $thresholdIdx === false) {
            return false;
        }

        return $currentIdx >= $thresholdIdx;
    }
}
