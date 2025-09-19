<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;
use App\Model\User;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use Throwable;

/**
 * UserRepository - DI-friendly, domain-centric, safe update/create
 */
class UserRepository implements IRepository
{
    private Collection $collection;
    private LoggerInterface $logger;

    public function __construct(?Collection $collection = null, ?LoggerInterface $logger = null)
    {
        $this->collection = $collection ?? MongoDBManager::getCollection('users');
        $this->logger = $logger ?? new NullLogger();
    }

    public function findById(string $id): ?array
    {
        try {
            $objectId = new ObjectId($id);
            $document = $this->collection->findOne(['_id' => $objectId]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findById failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        } catch (Throwable $e) {
            // e.g. invalid ObjectId string
            $this->logger->warning('UserRepository.findById invalid id or unexpected', [
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
            foreach ($cursor as $document) {
                $results[] = $this->documentToArray($document);
            }
            return $results;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.find failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function create(array $data): string
    {
        try {
            // Normalize timestamps -> ensure UTCDateTime
            $data['createdAt'] = $this->normalizeToUTCDateTime($data['createdAt'] ?? null);
            $data['updatedAt'] = $this->normalizeToUTCDateTime($data['updatedAt'] ?? null);

            $result = $this->collection->insertOne($data);
            $insertedId = (string) $result->getInsertedId();

            $this->logger->info('User created', [
                'id' => $insertedId,
                'username' => $data['username'] ?? 'unknown'
            ]);

            return $insertedId;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.create failed', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to create user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            // remove _id to avoid immutable id update error
            if (isset($data['_id'])) {
                unset($data['_id']);
            }

            // set/update updatedAt and normalize any DateTime fields to UTCDateTime
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
                $this->logger->info('User updated', ['id' => $id]);
            } else {
                $this->logger->warning('User update not found', ['id' => $id]);
            }

            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.update failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('UserRepository.update unexpected error', [
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
            if ($success) $this->logger->info('User deleted', ['id' => $id]);
            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.delete failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('UserRepository.delete unexpected', [
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
            $this->logger->error('UserRepository.count failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function findOne(array $filter = []): ?array
    {
        try {
            $document = $this->collection->findOne($filter);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findOne failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /* ---------------- Domain-centric helpers ---------------- */

    public function findUserById(string $id): ?User
    {
        $document = $this->findById($id);
        return $document ? User::fromDocument($document) : null;
    }

    public function findUserByUsername(string $username): ?User
    {
        try {
            $document = $this->collection->findOne(['username' => $username]);
            return $document ? User::fromDocument((array)$document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findUserByUsername failed', [
                'username' => $username,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function findUserByEmail(string $email): ?User
    {
        try {
            $document = $this->collection->findOne(['email' => $email]);
            return $document ? User::fromDocument((array)$document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findUserByEmail failed', [
                'email' => $email,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function saveUser(User $user): string
    {
        try {
            $document = $user->toDocument();

            if ($user->getId() === null) {
                return $this->create($document);
            } else {
                // remove _id before update to prevent MongoDB error
                if (isset($document['_id'])) unset($document['_id']);
                $success = $this->update($user->getId(), $document);
                return $success ? $user->getId() : '';
            }
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.saveUser failed', [
                'user' => (string)$user,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteUser(User $user): bool
    {
        if ($user->getId() === null) return false;
        return $this->delete($user->getId());
    }

    /* ---------------- internal helpers ---------------- */

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
        if ($value instanceof UTCDateTime) return $value;
        if ($value instanceof \DateTime) return new UTCDateTime($value->getTimestamp() * 1000);
        return new UTCDateTime();
    }

    public function createIndexes(): array
    {
        $indexes = [
            ['key' => ['username' => 1], 'unique' => true],
            ['key' => ['email' => 1], 'unique' => true],
            ['key' => ['role' => 1]],
            ['key' => ['createdAt' => 1]]
        ];

        try {
            $result = $this->collection->createIndexes($indexes);
            $this->logger->info('User indexes created');
            return ['success' => true, 'result' => $result];
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.createIndexes failed', [
                'exception' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
