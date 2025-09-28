<?php
declare(strict_types=1);

namespace App\Model\Report;

use DateTime;
use DateTimeInterface;
use JsonSerializable;
use App\Utility\Logger;

/**
 * ReportDefinition - Model untuk mendefinisikan parameter dan konfigurasi laporan
 * Mengikuti pola yang konsisten dengan DashboardMetrics
 */
class ReportDefinition implements JsonSerializable
{
    private ?string $id;
    private string $type;
    private string $name;
    private string $description;
    private array $filters;
    private array $columns;
    private array $sorting;
    private ?DateRangeFilter $dateRange;
    private ?string $createdBy;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private array $metadata;

    public function __construct(
        string $type,
        string $name,
        string $description = '',
        array $filters = [],
        array $columns = [],
        array $sorting = [],
        ?DateRangeFilter $dateRange = null,
        ?string $createdBy = null,
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->filters = $filters;
        $this->columns = $columns;
        $this->sorting = $sorting;
        $this->dateRange = $dateRange;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
        $this->metadata = $metadata;

        $this->validate();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getSorting(): array
    {
        return $this->sorting;
    }

    public function getDateRange(): ?DateRangeFilter
    {
        return $this->dateRange;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        $this->updatedAt = new DateTime();
        $this->validate();
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
        $this->validate();
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setSorting(array $sorting): self
    {
        $this->sorting = $sorting;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setDateRange(?DateRangeFilter $dateRange): self
    {
        $this->dateRange = $dateRange;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Validasi data report definition
     */
    public function validate(): bool
    {
        $errors = [];

        // Validasi tipe laporan
        $validTypes = ['inventory', 'user_activity', 'ai_performance', 'system_audit', 'custom'];
        if (!in_array($this->type, $validTypes)) {
            $errors[] = sprintf(
                'Invalid report type: %s. Valid types are: %s',
                $this->type,
                implode(', ', $validTypes)
            );
        }

        // Validasi nama
        if (empty(trim($this->name))) {
            $errors[] = 'Report name cannot be empty';
        }

        if (strlen($this->name) > 255) {
            $errors[] = 'Report name cannot exceed 255 characters';
        }

        // Validasi kolom
        foreach ($this->columns as $column) {
            if (!is_string($column)) {
                $errors[] = 'Column names must be strings';
                break;
            }
        }

        // Validasi date range jika ada
        if ($this->dateRange !== null) {
            try {
                $this->dateRange->validate();
            } catch (\InvalidArgumentException $e) {
                $errors[] = 'Invalid date range: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'ReportDefinition validation failed: ' . implode('; ', $errors)
            );
        }

        return true;
    }

    /**
     * Mendapatkan kolom yang difilter (jika ada konfigurasi kolom spesifik)
     */
    public function getFilteredColumns(): array
    {
        if (empty($this->columns)) {
            // Return semua kolom default berdasarkan tipe laporan
            return $this->getDefaultColumnsForType();
        }

        return $this->columns;
    }

    /**
     * Kolom default berdasarkan tipe laporan
     */
    private function getDefaultColumnsForType(): array
    {
        return match ($this->type) {
            'inventory' => [
                'id', 'name', 'description', 'quantity', 'price', 'category', 
                'supplier', 'minStockLevel', 'createdAt', 'updatedAt'
            ],
            'user_activity' => [
                'userId', 'username', 'email', 'role', 'lastLogin', 
                'loginCount', 'sessionDuration', 'actionsPerformed'
            ],
            'ai_performance' => [
                'analysisId', 'analysisType', 'success', 'confidence', 
                'responseTime', 'modelUsed', 'timestamp'
            ],
            'system_audit' => [
                'eventId', 'eventType', 'userId', 'resource', 
                'timestamp', 'details', 'ipAddress'
            ],
            default => ['id', 'name', 'timestamp']
        };
    }

    /**
     * Convert to array untuk serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'filters' => $this->filters,
            'columns' => $this->columns,
            'sorting' => $this->sorting,
            'dateRange' => $this->dateRange?->toArray(),
            'createdBy' => $this->createdBy,
            'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(DateTimeInterface::ATOM),
            'metadata' => $this->metadata
        ];
    }

    /**
     * Implement JsonSerializable
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create dari array data (untuk hydration)
     */
    public static function fromArray(array $data): self
    {
        // Parse date range jika ada
        $dateRange = null;
        if (isset($data['dateRange']) && is_array($data['dateRange'])) {
            $dateRange = DateRangeFilter::fromArray($data['dateRange']);
        }

        // Parse tanggal
        $createdAt = isset($data['createdAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['createdAt'])
            : null;
        
        $updatedAt = isset($data['updatedAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['updatedAt'])
            : null;

        return new self(
            $data['type'] ?? 'custom',
            $data['name'] ?? 'Unnamed Report',
            $data['description'] ?? '',
            $data['filters'] ?? [],
            $data['columns'] ?? [],
            $data['sorting'] ?? [],
            $dateRange,
            $data['createdBy'] ?? null,
            $data['id'] ?? null,
            $createdAt,
            $updatedAt,
            $data['metadata'] ?? []
        );
    }

    /**
     * Helper untuk membuat report definition sederhana
     */
    public static function createSimple(
        string $type, 
        string $name, 
        ?DateRangeFilter $dateRange = null,
        array $filters = []
    ): self {
        return new self(
            $type,
            $name,
            "Automatically generated {$type} report",
            $filters,
            [], // Default columns
            [], // Default sorting
            $dateRange,
            'system' // Default creator
        );
    }

    /**
     * Check jika report definition valid untuk eksekusi
     */
    public function isValidForExecution(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (\InvalidArgumentException $e) {
            Logger::getLogger()->warning('ReportDefinition validation failed', [
                'reportId' => $this->id,
                'reportName' => $this->name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get summary string untuk logging
     */
    public function getSummary(): string
    {
        return sprintf(
            'Report "%s" (%s) - %d filters, %d columns',
            $this->name,
            $this->type,
            count($this->filters),
            count($this->columns)
        );
    }

    public function __toString(): string
    {
        return $this->getSummary();
    }
}

/**
 * Supporting class untuk date range filter
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
        if (!in_array($this->timezone, \DateTimeZone::listIdentifiers())) {
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
        $start = new DateTime('today', new \DateTimeZone($timezone));
        $end = new DateTime('tomorrow', new \DateTimeZone($timezone));
        $end->modify('-1 second'); // Sampai akhir hari ini

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk minggu ini
     */
    public static function thisWeek(string $timezone = 'UTC'): self
    {
        $start = new DateTime('monday this week', new \DateTimeZone($timezone));
        $end = new DateTime('sunday this week', new \DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second'); // Sampai akhir minggu

        return new self($start, $end, $timezone);
    }

    /**
     * Create date range untuk bulan ini
     */
    public static function thisMonth(string $timezone = 'UTC'): self
    {
        $start = new DateTime('first day of this month', new \DateTimeZone($timezone));
        $end = new DateTime('last day of this month', new \DateTimeZone($timezone));
        $end->modify('+1 day')->modify('-1 second'); // Sampai akhir bulan

        return new self($start, $end, $timezone);
    }
}