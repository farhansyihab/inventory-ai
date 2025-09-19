<?php
declare(strict_types=1);

namespace App\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use InvalidArgumentException;

/**
 * User entity dengan robust mapping dan validation
 */
class User
{
    private ?string $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $role;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';

    public const VALID_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER, 
        self::ROLE_STAFF
    ];

    public function __construct(
        string $username,
        string $email,
        string $passwordHash,
        string $role = self::ROLE_STAFF,
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setPasswordHash($passwordHash);
        $this->setRole($role);
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    // Getters
    public function getId(): ?string { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    // Setters with validation
    public function setUsername(string $username): void 
    {
        $username = trim($username);
        if (empty($username)) {
            throw new InvalidArgumentException('Username cannot be empty');
        }
        if (strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters');
        }
        $this->username = $username;
    }

    public function setEmail(string $email): void 
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        $this->email = $email;
    }

    public function setPasswordHash(string $hash): void 
    {
        if (empty($hash)) {
            throw new InvalidArgumentException('Password hash cannot be empty');
        }
        $this->passwordHash = $hash;
    }

    public function setRole(string $role): void 
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid role. Must be one of: %s',
                implode(', ', self::VALID_ROLES)
            ));
        }
        $this->role = $role;
    }

    public function setUpdatedAt(DateTime $updatedAt): void 
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Convert to document format untuk MongoDB
     */
    public function toDocument(): array
    {
        $document = [
            'username' => $this->username,
            'email' => $this->email,
            'passwordHash' => $this->passwordHash,
            'role' => $this->role,
            'createdAt' => new UTCDateTime($this->createdAt->getTimestamp() * 1000),
            'updatedAt' => new UTCDateTime($this->updatedAt->getTimestamp() * 1000),
        ];

        if ($this->id !== null) {
            $document['_id'] = new ObjectId($this->id);
        }

        return $document;
    }

    /**
     * Create User dari MongoDB document
     */
    public static function fromDocument($document): self
    {
        if (is_array($document)) {
            $document = (object) $document;
        }

        $id = null;
        if (isset($document->_id)) {
            $id = $document->_id instanceof ObjectId 
                ? (string) $document->_id 
                : $document->_id;
        }

        // Handle various date formats
        $createdAt = self::parseDate($document->createdAt ?? null);
        $updatedAt = self::parseDate($document->updatedAt ?? null);

        return new self(
            $document->username ?? '',
            $document->email ?? '',
            $document->passwordHash ?? '',
            $document->role ?? self::ROLE_STAFF,
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
            return new DateTime('@' . ($dateValue / 1000));
        }

        if (is_string($dateValue)) {
            return new DateTime($dateValue);
        }

        return new DateTime();
    }

    /**
     * Validate user data integrity
     */
    public function validate(): void
    {
        $this->setUsername($this->username);
        $this->setEmail($this->email);
        $this->setPasswordHash($this->passwordHash);
        $this->setRole($this->role);
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user has manager role
     */
    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /**
     * Check if user has staff role
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function __toString(): string
    {
        return sprintf(
            'User[id=%s, username=%s, email=%s, role=%s]',
            $this->id ?? 'null',
            $this->username,
            $this->email,
            $this->role
        );
    }
}