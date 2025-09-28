<?php
declare(strict_types=1);

namespace App\Model\Report;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;

/**
 * DateRangeFilter - Class terpisah untuk filter tanggal
 */
class DateRangeFilter implements JsonSerializable
{
    private DateTime $startDate;
    private DateTime $endDate;
    private string $timezone;

    public function __construct(
        DateTime $startDate,
        DateTime $endDate,
        string $timezone = 'UTC'
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->timezone = $timezone;
        $this->validate();
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function validate(): void
    {
        if ($this->startDate > $this->endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }

        // Validasi timezone
        if (!in_array($this->timezone, DateTimeZone::listIdentifiers())) {
            throw new \InvalidArgumentException("Invalid timezone: {$this->timezone}");
        }
    }

    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate->format(DateTimeInterface::ATOM),
            'endDate' => $this->endDate->format(DateTimeInterface::ATOM),
            'timezone' => $this->timezone
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        $startDate = DateTime::createFromFormat(DateTimeInterface::ATOM, $data['startDate']);
        $endDate = DateTime::createFromFormat(DateTimeInterface::ATOM, $data['endDate']);

        if (!$startDate || !$endDate) {
            throw new \InvalidArgumentException('Invalid date format in DateRangeFilter');
        }

        return new self(
            $startDate,
            $endDate,
            $data['timezone'] ?? 'UTC'
        );
    }

    /**
     * Create date range untuk hari ini
     */
    public static function today(string $timezone = 'UTC'): self
    {
        $start = new DateTime('today', new DateTimeZone($timezone));
        $end = new DateTime('tomorrow', new DateTimeZone($timezone));
        $end->modify('-1 second'); // Sampai akhir hari ini

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk kemarin
     */
    public static function yesterday(string $timezone = 'UTC'): self
    {
        $start = new DateTime('yesterday', new DateTimeZone($timezone));
        $end = new DateTime('today', new DateTimeZone($timezone));
        $end->modify('-1 second');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk minggu ini
     */
    public static function thisWeek(string $timezone = 'UTC'): self
    {
        $start = new DateTime('monday this week', new DateTimeZone($timezone));
        $end = new DateTime('sunday this week', new DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk minggu lalu
     */
    public static function lastWeek(string $timezone = 'UTC'): self
    {
        $start = new DateTime('monday last week', new DateTimeZone($timezone));
        $end = new DateTime('sunday last week', new DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk bulan ini
     */
    public static function thisMonth(string $timezone = 'UTC'): self
    {
        $start = new DateTime('first day of this month', new DateTimeZone($timezone));
        $end = new DateTime('last day of this month', new DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk bulan lalu
     */
    public static function lastMonth(string $timezone = 'UTC'): self
    {
        $start = new DateTime('first day of last month', new DateTimeZone($timezone));
        $end = new DateTime('last day of last month', new DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk 7 hari terakhir
     */
    public static function createLast7Days(string $timezone = 'UTC'): self
    {
        $end = new DateTime('today', new DateTimeZone($timezone));
        $start = clone $end;
        $start->modify('-7 days');

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk 30 hari terakhir
     */
    public static function createLast30Days(string $timezone = 'UTC'): self
    {
        $end = new DateTime('today', new DateTimeZone($timezone));
        $start = clone $end;
        $start->modify('-30 days');

        return new self($start, $end, $timezone);
    }

    /**
     * Get jumlah hari dalam range
     */
    public function getDayCount(): int
    {
        $interval = $this->startDate->diff($this->endDate);
        return $interval->days + 1; // Include both start and end date
    }

    /**
     * Check jika tanggal tertentu berada dalam range
     */
    public function containsDate(DateTime $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s to %s (%s)',
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d'),
            $this->timezone
        );
    }
}