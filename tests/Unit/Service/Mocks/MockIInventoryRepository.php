<?php
// tests/Unit/Service/Mocks/MockIInventoryRepository.php

namespace Tests\Unit\Service\Mocks;

use App\Repository\IInventoryRepository;

class MockIInventoryRepository implements IInventoryRepository
{
    public function find(array $filter = [], array $options = []): array { return []; }
    public function findById(string $id): ?array { return null; }
    public function findOne(array $filter = []): ?array { return null; }
    public function create(array $data): string { return 'mock_id'; }
    public function update(string $id, array $data): bool { return true; }
    public function delete(string $id): bool { return true; }
    public function count(array $filter = []): int { return 0; }
    public function findLowStock(int $threshold = 0): array { return []; }
    public function findOutOfStock(): array { return []; }
    public function updateQuantity(string $id, int $quantityChange): bool { return true; }
    public function getStats(): array { return []; }
    public function aggregate(array $pipeline): array { return []; }
}
