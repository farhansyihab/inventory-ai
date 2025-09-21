<?php
// src/Repository/MongoTokenRepository.php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;
use MongoDB\BSON\UTCDateTime;
use DateTime;
use App\Utility\Logger;

class MongoTokenRepository implements ITokenRepository
{
    private \MongoDB\Collection $collection;
    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->collection = MongoDBManager::getCollection('refresh_tokens');
        $this->logger = $logger ?? new Logger();
    }

    public function storeRefreshToken(string $tokenHash, string $userId, DateTime $expiresAt): bool
    {
        try {
            $document = [
                'tokenHash' => $tokenHash,
                'userId' => $userId,
                'expiresAt' => new UTCDateTime($expiresAt->getTimestamp() * 1000),
                'createdAt' => new UTCDateTime(),
                'revoked' => false
            ];

            $result = $this->collection->insertOne($document);
            
            $this->logger->info("Refresh token stored", [
                'userId' => $userId,
                'tokenHash' => $tokenHash
            ]);

            return $result->isAcknowledged();
        } catch (\Exception $e) {
            $this->logger->error("MongoTokenRepository::storeRefreshToken failed: " . $e->getMessage());
            return false;
        }
    }

    public function revokeRefreshToken(string $tokenHash): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['tokenHash' => $tokenHash],
                ['$set' => ['revoked' => true, 'revokedAt' => new UTCDateTime()]]
            );

            $success = $result->getModifiedCount() > 0;

            if ($success) {
                $this->logger->info("Refresh token revoked", ['tokenHash' => $tokenHash]);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error("MongoTokenRepository::revokeRefreshToken failed: " . $e->getMessage());
            return false;
        }
    }

    public function isRefreshTokenRevoked(string $tokenHash): bool
    {
        try {
            $token = $this->collection->findOne([
                'tokenHash' => $tokenHash,
                'revoked' => true
            ]);

            return $token !== null;
        } catch (\Exception $e) {
            $this->logger->error("MongoTokenRepository::isRefreshTokenRevoked failed: " . $e->getMessage());
            return true; // Assume revoked if there's an error
        }
    }

    public function findRefreshToken(string $tokenHash): ?array
    {
        try {
            $token = $this->collection->findOne(['tokenHash' => $tokenHash]);
            return $token ? (array) $token : null;
        } catch (\Exception $e) {
            $this->logger->error("MongoTokenRepository::findRefreshToken failed: " . $e->getMessage());
            return null;
        }
    }

    public function cleanupExpiredTokens(): int
    {
        try {
            $result = $this->collection->deleteMany([
                'expiresAt' => ['$lt' => new UTCDateTime()],
                'revoked' => false
            ]);

            $deletedCount = $result->getDeletedCount();

            if ($deletedCount > 0) {
                $this->logger->info("Cleaned up expired tokens", ['count' => $deletedCount]);
            }

            return $deletedCount;
        } catch (\Exception $e) {
            $this->logger->error("MongoTokenRepository::cleanupExpiredTokens failed: " . $e->getMessage());
            return 0;
        }
    }
}