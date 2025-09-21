<?php
declare(strict_types=1);

namespace App\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use InvalidArgumentException;

/**
 * Inventory Item entity
 */
class Inventory
{
    private ?string $id;
    private string $name;
    private string $description;
    private int $quantity;
    private float $price;
    private ?string $categoryId;
    private ?string $supplierId;
    private int $minStockLevel;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $name,
        string $description,
        int $quantity,
        float $price,
        ?string $categoryId = null,
        ?string $supplierId = null,
        int $minStockLevel = 0,
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->setName($name);
        $this->setDescription($description);
        $this->setQuantity($quantity);
        $this->setPrice($price);
        $this->setCategoryId($categoryId);
        $this->setSupplierId($supplierId);
        $this->setMinStockLevel($minStockLevel);
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    // Getters
    public function getId(): ?string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPrice(): float { return $this->price; }
    public function getCategoryId(): ?string { return $this->categoryId; }
    public function getSupplierId(): ?string { return $this->supplierId; }
    public function getMinStockLevel(): int { return $this->minStockLevel; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    // Setters with validation
    public function setName(string $name): void 
    {
        $name = trim($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Inventory name cannot be empty');
        }
        if (strlen($name) < 2) {
            throw new InvalidArgumentException('Inventory name must be at least 2 characters');
        }
        $this->name = $name;
    }

    public function setDescription(string $description): void 
    {
        $description = trim($description);
        if (empty($description)) {
            throw new InvalidArgumentException('Description cannot be empty');
        }
        $this->description = $description;
    }

    public function setQuantity(int $quantity): void 
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
        $this->quantity = $quantity;
    }

    public function setPrice(float $price): void 
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }
        $this->price = round($price, 2);
    }

    public function setCategoryId(?string $categoryId): void 
    {
        $this->categoryId = $categoryId;
    }

    public function setSupplierId(?string $supplierId): void 
    {
        $this->supplierId = $supplierId;
    }

    public function setMinStockLevel(int $minStockLevel): void 
    {
        if ($minStockLevel < 0) {
            throw new InvalidArgumentException('Minimum stock level cannot be negative');
        }
        $this->minStockLevel = $minStockLevel;
    }

    public function setUpdatedAt(DateTime $updatedAt): void 
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Check if item is low stock
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minStockLevel;
    }

    /**
     * Check if item is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    /**
     * Convert to document format untuk MongoDB
     */
    public function toDocument(): array
    {
        $document = [
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'categoryId' => $this->categoryId,
            'supplierId' => $this->supplierId,
            'minStockLevel' => $this->minStockLevel,
            'createdAt' => new UTCDateTime($this->createdAt->getTimestamp() * 1000),
            'updatedAt' => new UTCDateTime($this->updatedAt->getTimestamp() * 1000),
        ];

        if ($this->id !== null) {
            try {
                $document['_id'] = new ObjectId($this->id);
            } catch (\Throwable $e) {
                // ignore invalid objectid
            }
        }

        return $document;
    }

    /**
     * Create Inventory dari MongoDB document
     */
    public static function fromDocument($document): self
    {
        if (is_array($document)) {
            $document = (object) $document;
        }

        $id = null;
        if (isset($document->_id)) {
            $id = $document->_id instanceof ObjectId ? (string) $document->_id : (string) $document->_id;
        }

        $createdAt = self::parseDate($document->createdAt ?? null);
        $updatedAt = self::parseDate($document->updatedAt ?? null);

        return new self(
            $document->name ?? '',
            $document->description ?? '',
            $document->quantity ?? 0,
            $document->price ?? 0.0,
            $document->categoryId ?? null,
            $document->supplierId ?? null,
            $document->minStockLevel ?? 0,
            $id,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Parse various date formats to DateTime
     */
    private static function parseDate($dateValue): DateTime
    {
        if ($dateValue instanceof UTCDateTime) {
            return $dateValue->toDateTime();
        }
        if ($dateValue instanceof DateTime) {
            return $dateValue;
        }
        if (is_numeric($dateValue)) {
            $dt = new DateTime();
            $dt->setTimestamp((int) $dateValue);
            return $dt;
        }
        if (is_string($dateValue) && $dateValue !== '') {
            return new DateTime($dateValue);
        }
        return new DateTime();
    }

    /**
     * Validate inventory data integrity
     */
    public function validate(): void
    {
        $this->setName($this->name);
        $this->setDescription($this->description);
        $this->setQuantity($this->quantity);
        $this->setPrice($this->price);
        $this->setMinStockLevel($this->minStockLevel);
    }

    /**
     * Calculate total value of inventory item
     */
    public function getTotalValue(): float
    {
        return $this->quantity * $this->price;
    }

    public function __toString(): string
    {
        return sprintf(
            'Inventory[id=%s, name=%s, quantity=%d, price=%.2f]',
            $this->id ?? 'null',
            $this->name,
            $this->quantity,
            $this->price
        );
    }

    /**
     * Clean, serializable array useful for APIs / logging
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'categoryId' => $this->categoryId,
            'supplierId' => $this->supplierId,
            'minStockLevel' => $this->minStockLevel,
            'isLowStock' => $this->isLowStock(),
            'isOutOfStock' => $this->isOutOfStock(),
            'totalValue' => $this->getTotalValue(),
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}