<?php
declare(strict_types=1);

namespace App\Model\Report;

use DateTime;
use DateTimeInterface;
use JsonSerializable;
use App\Utility\Logger;

/**
 * ExportJob - Model untuk melacak status dan informasi export jobs
 * Mengikuti pola yang konsisten dengan model lainnya
 */
class ExportJob implements JsonSerializable
{
    private string $id;
    private string $format;
    private string $status;
    private ?string $filePath;
    private ?int $fileSize;
    private DateTime $createdAt;
    private ?DateTime $startedAt;
    private ?DateTime $completedAt;
    private ?string $errorMessage;
    private array $metadata;
    private float $progress;
    private ?string $downloadUrl;

    public function __construct(
        string $id,
        string $format,
        string $status = 'pending',
        ?string $filePath = null,
        ?int $fileSize = null,
        ?DateTime $createdAt = null,
        ?DateTime $startedAt = null,
        ?DateTime $completedAt = null,
        ?string $errorMessage = null,
        array $metadata = [],
        float $progress = 0.0,
        ?string $downloadUrl = null
    ) {
        $this->id = $id;
        $this->format = $format;
        $this->status = $status;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
        $this->errorMessage = $errorMessage;
        $this->metadata = $metadata;
        $this->progress = max(0.0, min(100.0, $progress));
        $this->downloadUrl = $downloadUrl;

        $this->validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->validate();
        return $this;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function setStartedAt(?DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function setCompletedAt(?DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setProgress(float $progress): self
    {
        $this->progress = max(0.0, min(100.0, $progress));
        return $this;
    }

    public function setDownloadUrl(?string $downloadUrl): self
    {
        $this->downloadUrl = $downloadUrl;
        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Validasi export job
     */
    public function validate(): void
    {
        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
        if (!in_array($this->status, $validStatuses)) {
            throw new \InvalidArgumentException(
                "Invalid status: {$this->status}. Valid statuses: " . implode(', ', $validStatuses)
            );
        }

        if ($this->progress < 0 || $this->progress > 100) {
            throw new \InvalidArgumentException('Progress must be between 0 and 100');
        }

        // Validasi format file
        $validFormats = ['json', 'csv', 'pdf', 'excel', 'xml'];
        if (!in_array($this->format, $validFormats)) {
            throw new \InvalidArgumentException(
                "Invalid format: {$this->format}. Valid formats: " . implode(', ', $validFormats)
            );
        }

        // Validasi status consistency
        if ($this->status === 'completed' && $this->filePath === null) {
            throw new \InvalidArgumentException('Completed export job must have a file path');
        }

        if ($this->status === 'failed' && $this->errorMessage === null) {
            throw new \InvalidArgumentException('Failed export job must have an error message');
        }
    }

    /**
     * Check jika job sudah selesai (completed/failed/cancelled)
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check jika job sedang processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check jika job berhasil completed
     */
    public function isSuccess(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Estimate waktu remaining berdasarkan progress
     */
    public function getEstimatedTimeRemaining(): ?int
    {
        if (!$this->startedAt || $this->progress <= 0) {
            return null;
        }

        if ($this->isFinished()) {
            return 0;
        }

        $elapsed = time() - $this->startedAt->getTimestamp();
        $totalEstimated = $elapsed / ($this->progress / 100);
        return (int)max(0, $totalEstimated - $elapsed);
    }

    /**
     * Get duration dalam seconds
     */
    public function getDuration(): ?int
    {
        if (!$this->startedAt) {
            return null;
        }

        $endTime = $this->completedAt ?? new DateTime();
        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }

    /**
     * Convert to array untuk serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'format' => $this->format,
            'status' => $this->status,
            'filePath' => $this->filePath,
            'fileSize' => $this->fileSize,
            'fileSizeFormatted' => $this->fileSize ? $this->formatFileSize($this->fileSize) : null,
            'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM),
            'startedAt' => $this->startedAt?->format(DateTimeInterface::ATOM),
            'completedAt' => $this->completedAt?->format(DateTimeInterface::ATOM),
            'errorMessage' => $this->errorMessage,
            'metadata' => $this->metadata,
            'progress' => $this->progress,
            'downloadUrl' => $this->downloadUrl,
            'isFinished' => $this->isFinished(),
            'isProcessing' => $this->isProcessing(),
            'isSuccess' => $this->isSuccess(),
            'estimatedTimeRemaining' => $this->getEstimatedTimeRemaining(),
            'duration' => $this->getDuration()
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
        $createdAt = isset($data['createdAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['createdAt'])
            : null;

        $startedAt = isset($data['startedAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['startedAt'])
            : null;

        $completedAt = isset($data['completedAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['completedAt'])
            : null;

        return new self(
            $data['id'],
            $data['format'],
            $data['status'] ?? 'pending',
            $data['filePath'] ?? null,
            $data['fileSize'] ?? null,
            $createdAt,
            $startedAt,
            $completedAt,
            $data['errorMessage'] ?? null,
            $data['metadata'] ?? [],
            $data['progress'] ?? 0.0,
            $data['downloadUrl'] ?? null
        );
    }

    /**
     * Create new export job
     */
    public static function create(string $format, array $metadata = []): self
    {
        $id = 'export_' . uniqid() . '_' . time();
        return new self($id, $format, 'pending', null, null, null, null, null, null, $metadata);
    }

    /**
     * Mark job sebagai started
     */
    public function markAsStarted(): self
    {
        $this->status = 'processing';
        $this->startedAt = new DateTime();
        $this->progress = 0.0;
        return $this;
    }

    /**
     * Update progress
     */
    public function updateProgress(float $progress, array $metadata = []): self
    {
        $this->progress = max(0.0, min(100.0, $progress));
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Mark job sebagai completed
     */
    public function markAsCompleted(string $filePath, int $fileSize, ?string $downloadUrl = null): self
    {
        $this->status = 'completed';
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->downloadUrl = $downloadUrl;
        $this->progress = 100.0;
        $this->completedAt = new DateTime();
        return $this;
    }

    /**
     * Mark job sebagai failed
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->status = 'failed';
        $this->errorMessage = $errorMessage;
        $this->completedAt = new DateTime();
        return $this;
    }

    /**
     * Format file size untuk display
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get summary string untuk logging
     */
    public function getSummary(): string
    {
        if ($this->isSuccess()) {
            return sprintf(
                'Export %s completed: %s (%s)',
                $this->format,
                $this->filePath ? basename($this->filePath) : 'unknown',
                $this->fileSize ? $this->formatFileSize($this->fileSize) : '0B'
            );
        }

        if ($this->isFinished()) {
            return sprintf(
                'Export %s %s: %s',
                $this->format,
                $this->status,
                $this->errorMessage ?? 'No error message'
            );
        }

        return sprintf(
            'Export %s %s: %.1f%% progress',
            $this->format,
            $this->status,
            $this->progress
        );
    }

    public function __toString(): string
    {
        return $this->getSummary();
    }
}