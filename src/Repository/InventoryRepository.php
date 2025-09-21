<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;
use App\Model\Inventory;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use Throwable;

class InventoryRepository implements IInventoryRepository
{
    private \MongoDB\Collection $collection;
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->collection = MongoDBManager::getCollection('inventory');
        $this->logger = $logger ?? new NullLogger();
    }

    public function find(array $filter = [], array $options = []): array
    {
        try {
            $cursor = $this->collection->find($filter, $options);
            $results = [];
            foreach ($cursor as $document) {
                $results[] = $this->documentToArray($document);
            }
            return $results;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::find failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function findById(string $id): ?array
    {
        try {
            $objectId = new ObjectId($id);
            $document = $this->collection->findOne(['_id' => $objectId]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::findById failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        } catch (Throwable $e) {
            $this->logger->warning('InventoryRepository::findById invalid id', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function findOne(array $filter = []): ?array
    {
        try {
            $document = $this->collection->findOne($filter);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::findOne failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function create(array $data): string
    {
        try {
            // Normalize timestamps
            $data['createdAt'] = $this->normalizeToUTCDateTime($data['createdAt'] ?? null);
            $data['updatedAt'] = $this->normalizeToUTCDateTime($data['updatedAt'] ?? null);

            $result = $this->collection->insertOne($data);
            $insertedId = (string) $result->getInsertedId();

            $this->logger->info('Inventory item created', [
                'id' => $insertedId,
                'name' => $data['name'] ?? 'unknown'
            ]);

            return $insertedId;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::create failed', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to create inventory item: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            // Remove _id to avoid immutable id update error
            if (isset($data['_id'])) {
                unset($data['_id']);
            }

            // Set/update updatedAt and normalize any DateTime fields to UTCDateTime
            $data['updatedAt'] = new UTCDateTime();
            foreach ($data as $k => $v) {
                if ($v instanceof \DateTime) {
                    $data[$k] = new UTCDateTime($v->getTimestamp() * 1000);
                }
            }

            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );

            $success = $result->getMatchedCount() > 0;
            if ($success) {
                $this->logger->info('Inventory item updated', ['id' => $id]);
            } else {
                $this->logger->warning('Inventory item update not found', ['id' => $id]);
            }

            return $success;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::update failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('InventoryRepository::update unexpected error', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            $success = $result->getDeletedCount() > 0;
            if ($success) {
                $this->logger->info('Inventory item deleted', ['id' => $id]);
            }
            return $success;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::delete failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('InventoryRepository::delete unexpected', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function count(array $filter = []): int
    {
        try {
            return (int) $this->collection->countDocuments($filter);
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::count failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function findLowStock(int $threshold = 0): array
    {
        try {
            $filter = [
                'quantity' => ['$lte' => '$minStockLevel']
            ];

            if ($threshold > 0) {
                $filter = [
                    'quantity' => ['$lte' => $threshold]
                ];
            }

            return $this->find($filter);
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::findLowStock failed', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function findOutOfStock(): array
    {
        try {
            return $this->find(['quantity' => 0]);
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::findOutOfStock failed', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function updateQuantity(string $id, int $quantityChange): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                [
                    '$inc' => ['quantity' => $quantityChange],
                    '$set' => ['updatedAt' => new UTCDateTime()]
                ]
            );

            $success = $result->getMatchedCount() > 0;
            if ($success) {
                $this->logger->info('Inventory quantity updated', [
                    'id' => $id,
                    'quantityChange' => $quantityChange
                ]);
            }

            return $success;
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::updateQuantity failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getStats(): array
    {
        try {
            $pipeline = [
                [
                    '$group' => [
                        '_id' => null,
                        'totalItems' => ['$sum' => 1],
                        'totalQuantity' => ['$sum' => '$quantity'],
                        'totalValue' => ['$sum' => ['$multiply' => ['$quantity', '$price']]],
                        'avgPrice' => ['$avg' => '$price'],
                        'lowStockCount' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => ['$lte' => ['$quantity', '$minStockLevel']],
                                    'then' => 1,
                                    'else' => 0
                                ]
                            ]
                        ],
                        'outOfStockCount' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => ['$eq' => ['$quantity', 0]],
                                    'then' => 1,
                                    'else' => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $result = $this->collection->aggregate($pipeline)->toArray();
            return $result[0] ?? [];
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::getStats failed', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function aggregate(array $pipeline): array
    {
        try {
            $result = $this->collection->aggregate($pipeline)->toArray();
            return array_map(fn($doc) => (array) $doc, $result);
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::aggregate failed', [
                'pipeline' => $pipeline,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /* -------------------- Domain-centric methods -------------------- */

    public function saveInventory(Inventory $inventory): string
    {
        try {
            $document = $inventory->toDocument();

            if ($inventory->getId() === null) {
                return $this->create($document);
            } else {
                // remove _id before update to prevent MongoDB error
                if (isset($document['_id'])) {
                    unset($document['_id']);
                }
                $success = $this->update($inventory->getId(), $document);
                return $success ? $inventory->getId() : '';
            }
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::saveInventory failed', [
                'inventory' => (string)$inventory,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to save inventory: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findInventoryById(string $id): ?Inventory
    {
        $document = $this->findById($id);
        return $document ? Inventory::fromDocument($document) : null;
    }

    public function deleteInventory(Inventory $inventory): bool
    {
        if ($inventory->getId() === null) {
            return false;
        }
        return $this->delete($inventory->getId());
    }

    /* -------------------- Internal helpers -------------------- */

    private function documentToArray($document): array
    {
        $array = (array) $document;

        if (isset($array['_id']) && $array['_id'] instanceof ObjectId) {
            $array['_id'] = (string) $array['_id'];
        }

        if (isset($array['createdAt']) && $array['createdAt'] instanceof UTCDateTime) {
            $array['createdAt'] = $array['createdAt']->toDateTime();
        }

        if (isset($array['updatedAt']) && $array['updatedAt'] instanceof UTCDateTime) {
            $array['updatedAt'] = $array['updatedAt']->toDateTime();
        }

        return $array;
    }

    private function normalizeToUTCDateTime($value): UTCDateTime
    {
        if ($value instanceof UTCDateTime) {
            return $value;
        }
        if ($value instanceof \DateTime) {
            return new UTCDateTime($value->getTimestamp() * 1000);
        }
        return new UTCDateTime();
    }

    public function createIndexes(): array
    {
        $indexes = [
            ['key' => ['name' => 1], 'unique' => false],
            ['key' => ['categoryId' => 1]],
            ['key' => ['supplierId' => 1]],
            ['key' => ['quantity' => 1]],
            ['key' => ['price' => 1]],
            ['key' => ['createdAt' => 1]],
            ['key' => ['updatedAt' => 1]],
            // Compound index for frequently queried fields
            ['key' => ['categoryId' => 1, 'quantity' => 1]],
            ['key' => ['supplierId' => 1, 'quantity' => 1]],
        ];

        try {
            $result = $this->collection->createIndexes($indexes);
            $this->logger->info('Inventory indexes created');
            return ['success' => true, 'result' => $result];
        } catch (MongoException $e) {
            $this->logger->error('InventoryRepository::createIndexes failed', [
                'exception' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}