<?php
declare(strict_types=1);

namespace App\Model\Report;

use DateTime;
use DateTimeInterface;
use JsonSerializable;
use App\Utility\Logger;

/**
 * ReportResult - Model untuk menyimpan hasil generate laporan
 * Mengikuti pola yang konsisten dengan DashboardMetrics dan ReportDefinition
 */
class ReportResult implements JsonSerializable
{
    private string $id;
    private ReportDefinition $definition;
    private array $summary;
    private array $details;
    private array $insights;
    private array $recommendations;
    private Metadata $metadata;
    private DateTime $generatedAt;
    private ?string $errorMessage;
    private array $pagination;

    public function __construct(
        ReportDefinition $definition,
        array $summary = [],
        array $details = [],
        array $insights = [],
        array $recommendations = [],
        ?Metadata $metadata = null,
        ?DateTime $generatedAt = null,
        ?string $errorMessage = null,
        ?string $id = null,
        array $pagination = []
    ) {
        $this->id = $id ?? uniqid('report_', true);
        $this->definition = $definition;
        $this->summary = $summary;
        $this->details = $details;
        $this->insights = $insights;
        $this->recommendations = $recommendations;
        $this->metadata = $metadata ?? new Metadata();
        $this->generatedAt = $generatedAt ?? new DateTime();
        $this->errorMessage = $errorMessage;
        $this->pagination = $pagination;

        $this->validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDefinition(): ReportDefinition
    {
        return $this->definition;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }

    public function getDetails(int $page = 1, int $perPage = 50): PaginatedResult
    {
        if (empty($this->details)) {
            return new PaginatedResult([], 0, $page, $perPage);
        }

        // Jika sudah ada pagination data, gunakan yang ada
        if (!empty($this->pagination)) {
            $totalItems = $this->pagination['totalItems'] ?? count($this->details);
            $totalPages = max(1, ceil($totalItems / $perPage));
            $currentPage = min(max(1, $page), $totalPages);
            
            $offset = ($currentPage - 1) * $perPage;
            $paginatedData = array_slice($this->details, $offset, $perPage);

            return new PaginatedResult(
                $paginatedData,
                $totalItems,
                $currentPage,
                $perPage,
                $totalPages
            );
        }

        // Simple pagination untuk small datasets
        $totalItems = count($this->details);
        $totalPages = max(1, ceil($totalItems / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = array_slice($this->details, $offset, $perPage);

        return new PaginatedResult(
            $paginatedData,
            $totalItems,
            $currentPage,
            $perPage,
            $totalPages
        );
    }

    public function getAllDetails(): array
    {
        return $this->details;
    }

    public function getInsights(): array
    {
        return $this->insights;
    }

    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getGeneratedAt(): DateTime
    {
        return $this->generatedAt;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getPagination(): array
    {
        return $this->pagination;
    }

    public function setSummary(array $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function setInsights(array $insights): self
    {
        $this->insights = $insights;
        return $this;
    }

    public function setRecommendations(array $recommendations): self
    {
        $this->recommendations = $recommendations;
        return $this;
    }

    public function setMetadata(Metadata $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function setPagination(array $pagination): self
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * Validasi hasil laporan
     */
    public function validate(): bool
    {
        $errors = [];

        // Validasi summary structure
        if (!isset($this->summary['recordCount'])) {
            $errors[] = 'Summary must contain recordCount';
        }

        // Validasi metadata
        try {
            $this->metadata->validate();
        } catch (\InvalidArgumentException $e) {
            $errors[] = 'Metadata validation failed: ' . $e->getMessage();
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'ReportResult validation failed: ' . implode('; ', $errors)
            );
        }

        return true;
    }

    /**
     * Check jika laporan berhasil generate
     */
    public function isSuccess(): bool
    {
        return $this->errorMessage === null;
    }

    /**
     * Check jika laporan memiliki data
     */
    public function hasData(): bool
    {
        return !empty($this->details) || !empty($this->summary);
    }

    /**
     * Get record count dari summary
     */
    public function getRecordCount(): int
    {
        return $this->summary['recordCount'] ?? 0;
    }

    /**
     * Get execution time dalam milliseconds
     */
    public function getExecutionTime(): float
    {
        return $this->metadata->getExecutionTime();
    }

    /**
     * Convert to array untuk serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'definition' => $this->definition->toArray(),
            'summary' => $this->summary,
            'details' => $this->details,
            'insights' => $this->insights,
            'recommendations' => $this->recommendations,
            'metadata' => $this->metadata->toArray(),
            'generatedAt' => $this->generatedAt->format(DateTimeInterface::ATOM),
            'errorMessage' => $this->errorMessage,
            'pagination' => $this->pagination,
            'success' => $this->isSuccess(),
            'hasData' => $this->hasData(),
            'recordCount' => $this->getRecordCount()
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
        $definition = ReportDefinition::fromArray($data['definition']);
        $metadata = Metadata::fromArray($data['metadata'] ?? []);
        
        $generatedAt = isset($data['generatedAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['generatedAt'])
            : null;

        return new self(
            $definition,
            $data['summary'] ?? [],
            $data['details'] ?? [],
            $data['insights'] ?? [],
            $data['recommendations'] ?? [],
            $metadata,
            $generatedAt,
            $data['errorMessage'] ?? null,
            $data['id'] ?? null,
            $data['pagination'] ?? []
        );
    }

    /**
     * Create success result
     */
    public static function createSuccess(
        ReportDefinition $definition,
        array $summary,
        array $details,
        array $insights = [],
        array $recommendations = [],
        float $executionTime = 0.0
    ): self {
        $metadata = new Metadata($executionTime, count($details), 'success');
        
        return new self(
            $definition,
            $summary,
            $details,
            $insights,
            $recommendations,
            $metadata
        );
    }

    /**
     * Create error result
     */
    public static function createError(
        ReportDefinition $definition,
        string $errorMessage,
        float $executionTime = 0.0
    ): self {
        $metadata = new Metadata($executionTime, 0, 'error');
        
        return new self(
            $definition,
            ['recordCount' => 0, 'error' => true],
            [],
            [],
            [],
            $metadata,
            null,
            $errorMessage
        );
    }

    /**
     * Get summary string untuk logging
     */
    public function getSummaryText(): string
    {
        if (!$this->isSuccess()) {
            return sprintf('Report failed: %s', $this->errorMessage);
        }

        return sprintf(
            'Report generated: %d records, %.2fms execution time',
            $this->getRecordCount(),
            $this->getExecutionTime()
        );
    }

    public function __toString(): string
    {
        return $this->getSummaryText();
    }
}

/**
 * Metadata class untuk menyimpan metadata laporan
 */
class Metadata implements JsonSerializable
{
    private float $executionTime; // dalam milliseconds
    private int $recordCount;
    private string $status;
    private array $additionalInfo;

    public function __construct(
        float $executionTime = 0.0,
        int $recordCount = 0,
        string $status = 'pending',
        array $additionalInfo = []
    ) {
        $this->executionTime = $executionTime;
        $this->recordCount = $recordCount;
        $this->status = $status;
        $this->additionalInfo = $additionalInfo;
        $this->validate();
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getRecordCount(): int
    {
        return $this->recordCount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAdditionalInfo(): array
    {
        return $this->additionalInfo;
    }

    public function setExecutionTime(float $executionTime): self
    {
        $this->executionTime = $executionTime;
        return $this;
    }

    public function setRecordCount(int $recordCount): self
    {
        $this->recordCount = $recordCount;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setAdditionalInfo(array $additionalInfo): self
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    public function addInfo(string $key, $value): self
    {
        $this->additionalInfo[$key] = $value;
        return $this;
    }

    public function validate(): void
    {
        $validStatuses = ['pending', 'processing', 'success', 'error', 'cancelled'];
        if (!in_array($this->status, $validStatuses)) {
            throw new \InvalidArgumentException(
                "Invalid status: {$this->status}. Valid statuses: " . implode(', ', $validStatuses)
            );
        }

        if ($this->executionTime < 0) {
            throw new \InvalidArgumentException('Execution time cannot be negative');
        }

        if ($this->recordCount < 0) {
            throw new \InvalidArgumentException('Record count cannot be negative');
        }
    }

    public function toArray(): array
    {
        return [
            'executionTime' => $this->executionTime,
            'recordCount' => $this->recordCount,
            'status' => $this->status,
            'additionalInfo' => $this->additionalInfo
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['executionTime'] ?? 0.0,
            $data['recordCount'] ?? 0,
            $data['status'] ?? 'pending',
            $data['additionalInfo'] ?? []
        );
    }
}

/**
 * PaginatedResult class untuk hasil pagination
 */
class PaginatedResult implements JsonSerializable
{
    private array $data;
    private int $totalItems;
    private int $currentPage;
    private int $perPage;
    private int $totalPages;

    public function __construct(
        array $data,
        int $totalItems,
        int $currentPage = 1,
        int $perPage = 50,
        ?int $totalPages = null
    ) {
        $this->data = $data;
        $this->totalItems = $totalItems;
        $this->currentPage = max(1, $currentPage);
        $this->perPage = max(1, $perPage);
        $this->totalPages = $totalPages ?? max(1, ceil($totalItems / $perPage));
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'totalItems' => $this->totalItems,
                'currentPage' => $this->currentPage,
                'perPage' => $this->perPage,
                'totalPages' => $this->totalPages,
                'hasNextPage' => $this->hasNextPage(),
                'hasPreviousPage' => $this->hasPreviousPage(),
                'nextPage' => $this->getNextPage(),
                'previousPage' => $this->getPreviousPage()
            ]
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}