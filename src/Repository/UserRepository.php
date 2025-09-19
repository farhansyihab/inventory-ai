<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;  // <- TAMBAH INI
use App\Model\User;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;

/**
 * UserRepository dengan DI support dan domain-centric approach
 */
class UserRepository implements IRepository
{
    private Collection $collection;
    private LoggerInterface $logger;

/**
 *     public function __construct(Collection $collection, ?LoggerInterface $logger = null)
 *  {
 *      $this->collection = $collection;
 *      $this->logger = $logger ?? new NullLogger();
 *  }
 */

    public function __construct(?\MongoDB\Collection $collection = null)
    {
        $this->collection = $collection ?? MongoDBManager::getCollection('users');
    }    

    public function findById(string $id): ?array
    {
        try {
            $document = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findById failed', [
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
            // Ensure timestamps
            $data['createdAt'] = $data['createdAt'] ?? new UTCDateTime();
            $data['updatedAt'] = $data['updatedAt'] ?? new UTCDateTime();

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
            $data['updatedAt'] = new UTCDateTime();
            
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
        }
    }

    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            $success = $result->getDeletedCount() > 0;
            
            if ($success) {
                $this->logger->info('User deleted', ['id' => $id]);
            }
            
            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.delete failed', [
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

    /**
     * Domain-centric methods
     */
    public function findUserById(string $id): ?User
    {
        $document = $this->findById($id);
        return $document ? User::fromDocument($document) : null;
    }

    public function findUserByUsername(string $username): ?User
    {
        try {
            $document = $this->collection->findOne(['username' => $username]);
            return $document ? User::fromDocument($document) : null;
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
            return $document ? User::fromDocument($document) : null;
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
                // Create new user
                return $this->create($document);
            } else {
                // Update existing user
                $success = $this->update($user->getId(), $document);
                return $success ? $user->getId() : '';
            }
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.saveUser failed', [
                'user' => (string) $user,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteUser(User $user): bool
    {
        if ($user->getId() === null) {
            return false;
        }
        
        return $this->delete($user->getId());
    }

    /**
     * Convert MongoDB document to array
     */
    private function documentToArray($document): array
    {
        $array = (array) $document;
        
        // Convert ObjectId to string
        if (isset($array['_id']) && $array['_id'] instanceof ObjectId) {
            $array['_id'] = (string) $array['_id'];
        }
        
        // Convert UTCDateTime to DateTime
        if (isset($array['createdAt']) && $array['createdAt'] instanceof UTCDateTime) {
            $array['createdAt'] = $array['createdAt']->toDateTime();
        }
        
        if (isset($array['updatedAt']) && $array['updatedAt'] instanceof UTCDateTime) {
            $array['updatedAt'] = $array['updatedAt']->toDateTime();
        }
        
        return $array;
    }

    /**
     * Create indexes for users collection
     */
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