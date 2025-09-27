<?php
// src/Model/DashboardMetrics.php

namespace App\Model;

use DateTime;
use DateTimeInterface;
use JsonSerializable;

class DashboardMetrics implements JsonSerializable
{
    private DateTime $generatedAt;
    private array $inventory;
    private array $users;
    private array $ai;
    private array $system;
    private array $trends;
    private array $alerts;

    public function __construct(
        DateTime $generatedAt,
        array $inventory = [],
        array $users = [],
        array $ai = [],
        array $system = [],
        array $trends = [],
        array $alerts = []
    ) {
        $this->generatedAt = $generatedAt;
        $this->inventory = $inventory;
        $this->users = $users;
        $this->ai = $ai;
        $this->system = $system;
        $this->trends = $trends;
        $this->alerts = $alerts;
    }

    public function getGeneratedAt(): DateTime
    {
        return $this->generatedAt;
    }

    public function getInventory(): array
    {
        return $this->inventory;
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getAi(): array
    {
        return $this->ai;
    }

    public function getSystem(): array
    {
        return $this->system;
    }

    public function getTrends(): array
    {
        return $this->trends;
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function setInventory(array $inventory): self
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function setUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    public function setAi(array $ai): self
    {
        $this->ai = $ai;
        return $this;
    }

    public function setSystem(array $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function setTrends(array $trends): self
    {
        $this->trends = $trends;
        return $this;
    }

    public function setAlerts(array $alerts): self
    {
        $this->alerts = $alerts;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(DateTimeInterface::ATOM),
            'inventory' => $this->inventory,
            'users' => $this->users,
            'ai' => $this->ai,
            'system' => $this->system,
            'trends' => $this->trends,
            'alerts' => $this->alerts
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isValid(): bool
    {
        return !empty($this->inventory) || !empty($this->users) || !empty($this->ai) || !empty($this->system);
    }

    public function getSummary(): string
    {
        $parts = [];
        
        if (!empty($this->inventory)) {
            $parts[] = sprintf(
                "Inventory: %d items (%d low stock, %d out of stock)",
                $this->inventory['totalItems'] ?? 0,
                $this->inventory['lowStockCount'] ?? 0,
                $this->inventory['outOfStockCount'] ?? 0
            );
        }

        if (!empty($this->users)) {
            $parts[] = sprintf("Users: %d active", $this->users['activeUsers'] ?? 0);
        }

        if (!empty($this->ai)) {
            $parts[] = sprintf("AI: %.1f%% success rate", $this->ai['successRate'] ?? 0);
        }

        return implode(' | ', $parts);
    }
}