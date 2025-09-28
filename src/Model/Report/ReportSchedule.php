<?php
declare(strict_types=1);

namespace App\Model\Report;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use App\Utility\Logger;

/**
 * ReportSchedule - Model untuk menjadwalkan eksekusi laporan berkala
 * Mengikuti pola yang konsisten dengan model lainnya
 */
class ReportSchedule implements JsonSerializable
{
    private string $id;
    private ReportDefinition $definition;
    private string $frequency;
    private array $recipients;
    private array $formats;
    private bool $enabled;
    private ?DateTime $lastRun;
    private ?DateTime $nextRun;
    private array $metadata;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        ReportDefinition $definition,
        string $frequency,
        array $recipients = [],
        array $formats = ['json'],
        bool $enabled = true,
        ?DateTime $lastRun = null,
        ?DateTime $nextRun = null,
        array $metadata = [],
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id ?? uniqid('schedule_', true);
        $this->definition = $definition;
        $this->frequency = $frequency;
        $this->recipients = $recipients;
        $this->formats = $formats;
        $this->enabled = $enabled;
        $this->lastRun = $lastRun;
        $this->nextRun = $nextRun ?? $this->calculateNextRun();
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();

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

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getFormats(): array
    {
        return $this->formats;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getLastRun(): ?DateTime
    {
        return $this->lastRun;
    }

    public function getNextRun(): ?DateTime
    {
        return $this->nextRun;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;
        $this->nextRun = $this->calculateNextRun();
        $this->updatedAt = new DateTime();
        $this->validate();
        return $this;
    }

    public function setRecipients(array $recipients): self
    {
        $this->recipients = $recipients;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function setFormats(array $formats): self
    {
        $this->formats = $formats;
        $this->updatedAt = new DateTime();
        $this->validate();
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        $this->updatedAt = new DateTime();
        if ($enabled) {
            $this->nextRun = $this->calculateNextRun();
        } else {
            $this->nextRun = null;
        }
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function addRecipient(string $email, string $name = ''): self
    {
        $this->recipients[] = [
            'email' => $email,
            'name' => $name ?: $email
        ];
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function removeRecipient(string $email): self
    {
        $this->recipients = array_filter($this->recipients, function($recipient) use ($email) {
            return $recipient['email'] !== $email;
        });
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Validasi report schedule
     */
    public function validate(): void
    {
        $errors = [];

        // Validasi frequency
        $validFrequencies = ['hourly', 'daily', 'weekly', 'monthly', 'custom'];
        if (!in_array($this->frequency, $validFrequencies)) {
            $errors[] = sprintf(
                'Invalid frequency: %s. Valid frequencies: %s',
                $this->frequency,
                implode(', ', $validFrequencies)
            );
        }

        // Validasi recipients
        foreach ($this->recipients as $recipient) {
            if (!isset($recipient['email']) || !filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid recipient email: ' . ($recipient['email'] ?? 'unknown');
            }
        }

        // Validasi formats
        $validFormats = ['json', 'csv', 'pdf', 'excel', 'xml'];
        foreach ($this->formats as $format) {
            if (!in_array($format, $validFormats)) {
                $errors[] = sprintf(
                    'Invalid format: %s. Valid formats: %s',
                    $format,
                    implode(', ', $validFormats)
                );
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'ReportSchedule validation failed: ' . implode('; ', $errors)
            );
        }
    }

    /**
     * Calculate next run time berdasarkan frequency
     */
    public function calculateNextRun(): DateTime
    {
        $now = new DateTime();
        
        return match ($this->frequency) {
            'hourly' => (clone $now)->modify('+1 hour')->setTime($now->format('H'), 0, 0),
            'daily' => (clone $now)->modify('+1 day')->setTime(8, 0, 0), // 8 AM setiap hari
            'weekly' => (clone $now)->modify('next monday')->setTime(9, 0, 0), // Senin jam 9 AM
            'monthly' => (clone $now)->modify('first day of next month')->setTime(10, 0, 0), // Tanggal 1 jam 10 AM
            'custom' => $this->calculateCustomNextRun(),
            default => (clone $now)->modify('+1 day')->setTime(8, 0, 0)
        };
    }

    /**
     * Calculate next run untuk custom frequency
     */
    private function calculateCustomNextRun(): DateTime
    {
        $now = new DateTime();
        $customConfig = $this->metadata['custom_schedule'] ?? [];

        if (isset($customConfig['interval']) && isset($customConfig['unit'])) {
            $interval = (int)$customConfig['interval'];
            $unit = $customConfig['unit'];
            
            return match ($unit) {
                'minutes' => (clone $now)->modify("+{$interval} minutes"),
                'hours' => (clone $now)->modify("+{$interval} hours"),
                'days' => (clone $now)->modify("+{$interval} days"),
                'weeks' => (clone $now)->modify("+{$interval} weeks"),
                'months' => (clone $now)->modify("+{$interval} months"),
                default => (clone $now)->modify('+1 day')
            };
        }

        // Default fallback
        return (clone $now)->modify('+1 day');
    }

    /**
     * Check jika schedule harus dijalankan sekarang
     */
    public function shouldRun(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->nextRun === null) {
            return false;
        }

        $now = new DateTime();
        return $now >= $this->nextRun;
    }

    /**
     * Mark schedule sebagai telah dijalankan
     */
    public function markAsRun(): self
    {
        $this->lastRun = new DateTime();
        $this->nextRun = $this->calculateNextRun();
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Skip run berikutnya dan calculate next run setelahnya
     */
    public function skipNextRun(): self
    {
        $this->nextRun = $this->calculateNextRun();
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Get human readable description dari schedule
     */
    public function getScheduleDescription(): string
    {
        return match ($this->frequency) {
            'hourly' => 'Setiap jam',
            'daily' => 'Setiap hari pukul 08:00',
            'weekly' => 'Setiap Senin pukul 09:00',
            'monthly' => 'Setiap tanggal 1 pukul 10:00',
            'custom' => $this->getCustomScheduleDescription(),
            default => 'Jadwal tidak diketahui'
        };
    }

    /**
     * Get description untuk custom schedule
     */
    private function getCustomScheduleDescription(): string
    {
        $customConfig = $this->metadata['custom_schedule'] ?? [];
        
        if (isset($customConfig['interval']) && isset($customConfig['unit'])) {
            $interval = (int)$customConfig['interval'];
            $unit = $customConfig['unit'];
            
            $unitMap = [
                'minutes' => 'menit',
                'hours' => 'jam',
                'days' => 'hari',
                'weeks' => 'minggu',
                'months' => 'bulan'
            ];
            
            $unitName = $unitMap[$unit] ?? $unit;
            return "Setiap {$interval} {$unitName}";
        }

        return 'Jadwal kustom';
    }

    /**
     * Get status schedule
     */
    public function getStatus(): string
    {
        if (!$this->enabled) {
            return 'disabled';
        }

        if ($this->shouldRun()) {
            return 'pending';
        }

        if ($this->lastRun === null) {
            return 'never_run';
        }

        return 'scheduled';
    }

    /**
     * Convert to array untuk serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'definition' => $this->definition->toArray(),
            'frequency' => $this->frequency,
            'recipients' => $this->recipients,
            'formats' => $this->formats,
            'enabled' => $this->enabled,
            'lastRun' => $this->lastRun?->format(DateTimeInterface::ATOM),
            'nextRun' => $this->nextRun?->format(DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(DateTimeInterface::ATOM),
            'scheduleDescription' => $this->getScheduleDescription(),
            'status' => $this->getStatus(),
            'shouldRun' => $this->shouldRun()
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
        
        $lastRun = isset($data['lastRun']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['lastRun'])
            : null;

        $nextRun = isset($data['nextRun']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['nextRun'])
            : null;

        $createdAt = isset($data['createdAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['createdAt'])
            : null;

        $updatedAt = isset($data['updatedAt']) 
            ? DateTime::createFromFormat(DateTimeInterface::ATOM, $data['updatedAt'])
            : null;

        return new self(
            $definition,
            $data['frequency'],
            $data['recipients'] ?? [],
            $data['formats'] ?? ['json'],
            $data['enabled'] ?? true,
            $lastRun,
            $nextRun,
            $data['metadata'] ?? [],
            $data['id'] ?? null,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Create simple schedule
     */
    public static function createSimple(
        ReportDefinition $definition,
        string $frequency = 'daily',
        array $recipients = [],
        bool $enabled = true
    ): self {
        return new self(
            $definition,
            $frequency,
            $recipients,
            ['json'],
            $enabled
        );
    }

    /**
     * Create schedule dengan custom configuration
     */
    public static function createCustom(
        ReportDefinition $definition,
        int $interval,
        string $unit, // minutes, hours, days, weeks, months
        array $recipients = [],
        bool $enabled = true
    ): self {
        $metadata = [
            'custom_schedule' => [
                'interval' => $interval,
                'unit' => $unit
            ]
        ];

        $schedule = new self(
            $definition,
            'custom',
            $recipients,
            ['json'],
            $enabled,
            null,
            null,
            $metadata
        );

        // Calculate next run untuk custom schedule
        $schedule->nextRun = $schedule->calculateNextRun();
        
        return $schedule;
    }

    /**
     * Get summary string untuk logging
     */
    public function getSummary(): string
    {
        return sprintf(
            'Schedule "%s" (%s) - %s, %d recipients',
            $this->definition->getName(),
            $this->frequency,
            $this->enabled ? 'enabled' : 'disabled',
            count($this->recipients)
        );
    }

    public function __toString(): string
    {
        return $this->getSummary();
    }
}