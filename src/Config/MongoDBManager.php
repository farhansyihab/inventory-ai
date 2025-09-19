<?php
declare(strict_types=1);

namespace App\Config;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;
use MongoDB\Driver\Session;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * MongoDBManager - Singleton pattern untuk MongoDB connection
 * Versi improved dengan maintain compatibility dan tambahan fitur
 */
class MongoDBManager
{
    private static ?Client $client = null;
    private static ?Database $database = null;
    private static ?LoggerInterface $logger = null;

    public static function initialize(?LoggerInterface $logger = null): void
    {
        self::$logger = $logger ?? new NullLogger();
    }

    public static function getClient(): Client
    {
        if (self::$client === null) {
            $connectionString = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
            
            $options = [
                'connectTimeoutMS' => 30000,
                'socketTimeoutMS' => 30000,
                'serverSelectionTimeoutMS' => 5000,
            ];

            if (!empty($_ENV['MONGODB_USERNAME']) && !empty($_ENV['MONGODB_PASSWORD'])) {
                $options['username'] = $_ENV['MONGODB_USERNAME'];
                $options['password'] = $_ENV['MONGODB_PASSWORD'];
                $options['authSource'] = $_ENV['MONGODB_AUTH_SOURCE'] ?? 'admin';
            }

            self::$client = new Client($connectionString, $options);
            
            if (self::$logger) {
                self::$logger->info('MongoDB client initialized');
            }
        }

        return self::$client;
    }

    public static function getDatabase(): Database
    {
        if (self::$database === null) {
            $databaseName = $_ENV['MONGODB_DB'] ?? 'inventory_ai';
            self::$database = self::getClient()->selectDatabase($databaseName);
            
            if (self::$logger) {
                self::$logger->info('MongoDB database selected', ['database' => $databaseName]);
            }
        }

        return self::$database;
    }

    public static function getCollection(string $name): Collection
    {
        return self::getDatabase()->selectCollection($name);
    }

    public static function ping(): bool
    {
        try {
            self::getDatabase()->command(['ping' => 1]);
            return true;
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB ping failed', ['exception' => $e->getMessage()]);
            }
            return false;
        }
    }

    public static function startSession(): ?Session
    {
        try {
            return self::getClient()->startSession();
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->warning('MongoDB session not available', ['exception' => $e->getMessage()]);
            }
            return null;
        }
    }

    public static function getConnectionInfo(): array
    {
        return [
            'connected' => self::ping(),
            'database' => $_ENV['MONGODB_DB'] ?? 'inventory_ai',
            'uri' => $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017',
            'client_status' => self::$client ? 'initialized' : 'not_initialized',
            'database_status' => self::$database ? 'initialized' : 'not_initialized'
        ];
    }

    /**
     * Create indexes untuk collection tertentu
     * Improved method dengan better error handling dan logging
     */
    public static function createIndexes(string $collectionName, array $indexes): array
    {
        try {
            $collection = self::getCollection($collectionName);
            $result = $collection->createIndexes($indexes);
            
            if (self::$logger) {
                self::$logger->info('MongoDB indexes created', [
                    'collection' => $collectionName,
                    'indexes_count' => count($indexes),
                    'result' => $result
                ]);
            }
            
            return [
                'success' => true,
                'result' => $result,
                'indexes' => array_map(fn($index) => $index['key'] ?? $index, $indexes)
            ];
            
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB createIndexes failed', [
                    'collection' => $collectionName,
                    'exception' => $e->getMessage(),
                    'indexes' => $indexes
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'collection' => $collectionName
            ];
        }
    }

    /**
     * Get database statistics
     */
    public static function getStats(): array
    {
        try {
            $stats = self::getDatabase()->command(['dbStats' => 1])->toArray()[0];
            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB getStats failed', ['exception' => $e->getMessage()]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get collection statistics
     */
    public static function getCollectionStats(string $collectionName): array
    {
        try {
            $collection = self::getCollection($collectionName);
            $stats = $collection->aggregate([['$collStats' => ['storageStats' => []]]])->toArray();
            return [
                'success' => true,
                'stats' => $stats[0] ?? []
            ];
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB getCollectionStats failed', [
                    'collection' => $collectionName,
                    'exception' => $e->getMessage()
                ]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if collection exists
     */
    public static function collectionExists(string $collectionName): bool
    {
        try {
            $collections = self::getDatabase()->listCollections([
                'filter' => ['name' => $collectionName]
            ]);
            return count(iterator_to_array($collections)) > 0;
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB collectionExists check failed', [
                    'collection' => $collectionName,
                    'exception' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Drop collection safely
     */
    public static function dropCollection(string $collectionName): array
    {
        try {
            $collection = self::getCollection($collectionName);
            $result = $collection->drop();
            
            if (self::$logger) {
                self::$logger->info('MongoDB collection dropped', [
                    'collection' => $collectionName,
                    'result' => $result
                ]);
            }
            
            return [
                'success' => true,
                'collection' => $collectionName
            ];
            
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB dropCollection failed', [
                    'collection' => $collectionName,
                    'exception' => $e->getMessage()
                ]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get server information
     */
    public static function getServerInfo(): array
    {
        try {
            $info = self::getClient()->getManager()->getServers()[0]->getInfo();
            return [
                'success' => true,
                'server_info' => $info
            ];
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB getServerInfo failed', ['exception' => $e->getMessage()]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get MongoDB server version
     */
    public static function getServerVersion(): array
    {
        try {
            $buildInfo = self::getDatabase()->command(['buildInfo' => 1])->toArray()[0];
            return [
                'success' => true,
                'version' => $buildInfo['version'] ?? 'unknown',
                'version_array' => $buildInfo['versionArray'] ?? []
            ];
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB getServerVersion failed', ['exception' => $e->getMessage()]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Flush semua koneksi dan reset state (utama untuk testing)
     */
    public static function reset(): void
    {
        self::$client = null;
        self::$database = null;
        
        if (self::$logger) {
            self::$logger->info('MongoDBManager reset');
        }
    }

    /**
     * Get logger instance
     */
    public static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::initialize();
        }
        return self::$logger;
    }

    /**
     * Set custom logger
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }
}