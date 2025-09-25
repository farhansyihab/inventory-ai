<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;
use App\Utility\Logger;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use InvalidArgumentException;

/**
 * MongoDB implementation untuk Category Repository
 */
class MongoCategoryRepository implements ICategoryRepository
{
    private Collection $collection;
    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->collection = MongoDBManager::getCollection('categories');
        $this->logger = $logger ?? new Logger();
        $this->createIndexes();
    }

    public function createIndexes(): array
    {
        try {
            $indexes = [
                ['key' => ['slug' => 1], 'unique' => true, 'name' => 'slug_1'],
                ['key' => ['active' => 1], 'name' => 'active_1'],
                ['key' => ['parentId' => 1], 'name' => 'parentId_1'],
                ['key' => ['depth' => 1], 'name' => 'depth_1'],
                ['key' => ['parentId' => 1, 'active' => 1], 'name' => 'parentId_active_1'],
                ['key' => ['path' => 1], 'name' => 'path_1'],
                ['key' => ['createdAt' => 1], 'name' => 'createdAt_1'],
                ['key' => ['name' => 1], 'name' => 'name_1'],
            ];

            $result = $this->collection->createIndexes($indexes);
            
            // Return array of index names
            return array_map(function($index) {
                return $index['name'];
            }, $indexes);
            
        } catch (MongoDBException $e) {
            $this->logger->error('Category index creation failed', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }


    public function findById(string $id): ?array
    {
        try {
            $document = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoDBException $e) {
            $this->logger->error('Category findById failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function find(array $filter = [], array $options = []): array
    {
        try {
            $cursor = $this->collection->find($filter, $options);
            $results = [];
            
            // Pastikan cursor diiterasi dengan benar
            foreach ($cursor as $document) {
                $results[] = $this->documentToArray($document);
            }
            
            return $results;
        } catch (MongoDBException $e) {
            $this->logger->error('Category find failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function findOne(array $filter = []): ?array
    {
        try {
            $document = $this->collection->findOne($filter);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoDBException $e) {
            $this->logger->error('Category findOne failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function create(array $data): string
    {
        try {
            // Validasi data required
            if (empty($data['name']) || empty($data['slug'])) {
                throw new InvalidArgumentException('Name and slug are required');
            }

            // Set default values
            $data['active'] = $data['active'] ?? true;
            $data['depth'] = $data['depth'] ?? 0;
            $data['path'] = $data['path'] ?? [];
            $data['createdAt'] = new UTCDateTime();
            $data['updatedAt'] = new UTCDateTime();

            $result = $this->collection->insertOne($data);
            
            $this->logger->info('Category created', [
                'id' => (string) $result->getInsertedId(),
                'name' => $data['name']
            ]);
            
            return (string) $result->getInsertedId();
        } catch (MongoDBException $e) {
            $this->logger->error('Category creation failed', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to create category: ' . $e->getMessage());
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            $data['updatedAt'] = new UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );
            
            $success = $result->getModifiedCount() > 0;
            
            if ($success) {
                $this->logger->info('Category updated', ['id' => $id]);
            }
            
            return $success;
        } catch (MongoDBException $e) {
            $this->logger->error('Category update failed', [
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
                $this->logger->info('Category deleted', ['id' => $id]);
            }
            
            return $success;
        } catch (MongoDBException $e) {
            $this->logger->error('Category deletion failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function count(array $filter = []): int
    {
        try {
            return $this->collection->countDocuments($filter);
        } catch (MongoDBException $e) {
            $this->logger->error('Category count failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findOne(['slug' => $slug, 'active' => true]);
    }

    public function findActive(): array
    {
        return $this->find(['active' => true], ['sort' => ['name' => 1]]);
    }

    public function findByParentId(?string $parentId): array
    {
        $filter = ['parentId' => $parentId, 'active' => true];
        return $this->find($filter, ['sort' => ['name' => 1]]);
    }

    public function findRootCategories(): array
    {
        return $this->findByParentId(null);
    }

    public function getCategoryTree(): array
    {
        try {
            // Aggregation pipeline untuk membangun tree structure
            $pipeline = [
                ['$match' => ['active' => true]],
                ['$sort' => ['depth' => 1, 'name' => 1]],
                // Group by parentId dan collect children
                [
                    '$group' => [
                        '_id' => '$parentId',
                        'categories' => ['$push' => '$$ROOT']
                    ]
                ]
            ];

            $cursor = $this->collection->aggregate($pipeline);
            $tree = [];
            
            foreach ($cursor as $document) {
                $parentId = $document['_id'];
                if ($parentId === null) {
                    $tree = array_merge($tree, $this->documentToArray($document)['categories'] ?? []);
                }
            }
            
            return $tree;
        } catch (MongoDBException $e) {
            $this->logger->error('Category tree generation failed', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function findByDepth(int $depth): array
    {
        return $this->find(['depth' => $depth, 'active' => true]);
    }

    public function updatePath(string $categoryId, array $path, int $depth): bool
    {
        return $this->update($categoryId, [
            'path' => $path,
            'depth' => $depth
        ]);
    }

    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        $filter = ['slug' => $slug];
        if ($excludeId) {
            $filter['_id'] = ['$ne' => new ObjectId($excludeId)];
        }
        
        return $this->count($filter) > 0;
    }

    public function getCategoriesWithCounts(): array
    {
        // Implementation untuk join dengan inventory items
        // Ini akan diimplementasi lebih lengkap di session berikutnya
        return $this->findActive();
    }

    public function bulkUpdateStatus(array $categoryIds, bool $active): bool
    {
        try {
            $objectIds = array_map(fn($id) => new ObjectId($id), $categoryIds);
            
            $result = $this->collection->updateMany(
                ['_id' => ['$in' => $objectIds]],
                ['$set' => ['active' => $active, 'updatedAt' => new UTCDateTime()]]
            );
            
            $this->logger->info('Bulk category status update', [
                'count' => $result->getModifiedCount(),
                'active' => $active
            ]);
            
            return $result->getModifiedCount() > 0;
        } catch (MongoDBException $e) {
            $this->logger->error('Bulk category update failed', [
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Convert MongoDB document to array
     */
    private function documentToArray(mixed $document): array
    {
        if (!is_array($document) && !is_object($document)) {
            return [];
        }

        $data = [];
        foreach ($document as $key => $value) {
            if ($key === '_id') {
                $data['id'] = (string) $value;
            } elseif ($value instanceof UTCDateTime) {
                $data[$key] = $value->toDateTime()->format('c');
            } elseif ($value instanceof ObjectId) {
                $data[$key] = (string) $value;
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Normalize value to UTCDateTime
     */
    private function normalizeToUTCDateTime(mixed $value): UTCDateTime
    {
        if ($value instanceof UTCDateTime) {
            return $value;
        } elseif ($value instanceof DateTime) {
            return new UTCDateTime($value->getTimestamp() * 1000);
        } elseif (is_string($value)) {
            return new UTCDateTime(strtotime($value) * 1000);
        } else {
            return new UTCDateTime();
        }
    }
}