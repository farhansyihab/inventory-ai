<?php
declare(strict_types=1);

namespace App\Model;

use DateTime;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;

/**
 * Category Model - Entity untuk manajemen kategori inventory
 * Mendukung hierarchical categories dengan parent-child relationships
 */
class Category
{
    private ?string $id;
    private string $name;
    private string $slug;
    private string $description;
    private bool $active;
    private ?string $parentId;
    private int $depth;
    private array $path;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        string $description = '',
        bool $active = true,
        ?string $parentId = null,
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->active = $active;
        $this->parentId = $parentId;
        $this->depth = 0;
        $this->path = [];
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
        
        $this->validate();
    }

    public function validate(): void
    {
        // Validasi nama
        if (strlen(trim($this->name)) < 3) {
            throw new InvalidArgumentException("Category name must be at least 3 characters");
        }
        
        if (strlen(trim($this->name)) > 100) {
            throw new InvalidArgumentException("Category name cannot exceed 100 characters");
        }

        // Validasi slug
        if (!preg_match('/^[a-z0-9-]+$/', $this->slug)) {
            throw new InvalidArgumentException("Slug must contain only lowercase letters, numbers, and hyphens");
        }
        
        if (strlen($this->slug) < 2) {
            throw new InvalidArgumentException("Slug must be at least 2 characters");
        }
        
        if (strlen($this->slug) > 50) {
            throw new InvalidArgumentException("Slug cannot exceed 50 characters");
        }

        // Validasi description
        if (strlen($this->description) > 500) {
            throw new InvalidArgumentException("Description cannot exceed 500 characters");
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
        $this->validate();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->updatedAt = new DateTime();
        $this->validate();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTime();
        $this->validate();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
        $this->updatedAt = new DateTime();
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
        $this->updatedAt = new DateTime();
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function setPath(array $path): void
    {
        $this->path = $path;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Convert to MongoDB document format
     */
    public function toDocument(): array
    {
        $document = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'active' => $this->active,
            'parentId' => $this->parentId,
            'depth' => $this->depth,
            'path' => $this->path,
            'createdAt' => new UTCDateTime($this->createdAt->getTimestamp() * 1000),
            'updatedAt' => new UTCDateTime($this->updatedAt->getTimestamp() * 1000)
        ];

        if ($this->id !== null) {
            $document['_id'] = $this->id;
        }

        return $document;
    }

    /**
     * Create Category instance from MongoDB document
     */
    public static function fromDocument(array $document): self
    {
        $category = new self(
            $document['name'],
            $document['slug'],
            $document['description'] ?? '',
            $document['active'] ?? true,
            $document['parentId'] ?? null,
            (string) $document['_id'],
            self::parseDate($document['createdAt']),
            self::parseDate($document['updatedAt'])
        );

        if (isset($document['depth'])) {
            $category->setDepth($document['depth']);
        }

        if (isset($document['path'])) {
            $category->setPath($document['path']);
        }

        return $category;
    }

    /**
     * Parse MongoDB date to DateTime
     */
    private static function parseDate(mixed $dateValue): DateTime
    {
        if ($dateValue instanceof UTCDateTime) {
            return $dateValue->toDateTime();
        } elseif ($dateValue instanceof DateTime) {
            return $dateValue;
        } elseif (is_string($dateValue)) {
            return new DateTime($dateValue);
        } else {
            return new DateTime();
        }
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'active' => $this->active,
            'parentId' => $this->parentId,
            'depth' => $this->depth,
            'path' => $this->path,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c')
        ];
    }

    public function __toString(): string
    {
        return sprintf('Category[id=%s, name=%s, slug=%s]', 
            $this->id ?? 'null', 
            $this->name, 
            $this->slug
        );
    }

    /**
     * Check if category is root (no parent)
     */
    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Check if category has children (based on depth/path structure)
     */
    public function hasChildren(): bool
    {
        return $this->depth > 0; // Simplified - actual implementation would check child count
    }

    /**
     * Generate full path string for display
     */
    public function getFullPath(): string
    {
        return !empty($this->path) ? implode(' > ', $this->path) : $this->name;
    }
}