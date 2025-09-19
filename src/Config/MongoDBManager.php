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
}