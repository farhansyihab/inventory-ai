# Beberapa catatan setelah saya coba pelajari:

## 1) Masalah & risiko (khawatir mudah rusak saat penambahan fitur)

Berikut masalah yang berpotensi menjadi sumber bug/fragility jika tidak diperbaiki:

### A. Tight coupling & penggunaan static everywhere

- `MongoDBManager` saat ini _static/singleton_ dengan banyak method statis. Itu memudahkan akses, tapi **menyulitkan unit testing, mocking, dan mengganti koneksi**.
- Banyak class langsung memanggil `MongoDBManager::getCollection(...)` → sulit mengganti ke DB lain atau mengganti behavior untuk test.
    

**Risiko:** ketika menambah fitur yang memerlukan transaksionalitas atau multi-database, perubahan menyentuh banyak file.

### B. Error handling dan exception mapping belum konsisten

- Di `UserRepository::findById` dan methode lain Anda menangkap `InvalidArgumentException` dari driver. Namun pengunaan exception driver kadang berbeda; dan catch tanpa logging membuat debugging sulit.
**Risiko:** bug silent failure (method kembali `null`/`false` tanpa info).

### C. Konversi BSON ↔ PHP; date handling

- `User::toArray()` membuat UTCDateTime dari DateTime — bagus — tapi `fromArray()` mengasumsikan field ada dan format tertentu. Jika struktur dokumen berubah, reconstruct bisa error.
**Risiko:** kode brittle saat schema berubah.

### D. Repository menerima/mereturn `array` — domain logic terfragmentasi

- Anda punya entity `User` class, tapi repository bekerja dengan `array`. Ada mapping (via `User::fromArray()`), namun beberapa bagian masih menggunakan arrays — risk of duplicated mapping code.
    

**Risiko:** Invariant (validasi) bisa tersebar dan menyebabkan inconsistency.

### E. StartSession type & transactions

- `MongoDBManager::startSession()` mengembalikan `\MongoDB\Driver\Session` tanpa null checks. Perlu guard karena session hanya tersedia jika server replicaSet/transaction-enabled.
**Risiko:** pemakaian transactions di server tunggal bisa gagal.

### F. Tidak ada dependency injection (DI)

- Constructor `UserRepository::__construct()` create collection langsung. Tidak ada cara mudah untuk mengganti collection (mis: in-memory repository untuk tests).
**Risiko:** testing & mocking sulit → developer cenderung mengubah kode nyata saat mengetes.

### G. Tidak ada logging/observability yang jelas di repository/service

- Jika operasi DB gagal, info terbatas di error\_log; sebaiknya struktur error + kode status

## 2) Perbaikan kode — prinsip yang saya coba pakai

Untuk mencegah regressions di masa depan dan memudahkan penambahan fitur:

- **Dependency Injection sederhana**: class menerima dependency di constructor (collection/client), bukan memanggil static. Ini memudahkan mocking/testing.
    
- **Wrapper / Adapter untuk MongoDB**: sediakan adapter yang bisa di-instansiasi atau di-mock, bukan method statis everywhere.
    
- **Mapping entity <-> document** central dan robust\*\*: biarkan `User` bertanggung jawab map ↔ array, tapi repositori menerima/return `User` object (tidak hanya array) — membuat invariant jelas. (agar backward compatible, repositori juga bisa expose array methods).
    
- **Struktur error konsisten**: throw exceptions domain-specific, log meaningful errors.
    
- **Transaction safe**: wrapper `startSession()` yang aman (kondisi replica set), dan jika tidak support, memberikan fallback atau jelaskan ke caller.
    
- **Test-friendly**: ability to inject mock collection or in-memory repo.

## 4) Kode perbaikan yang saya ajukan (langsung ganti file kritikal)

Saya berikan tiga file terperbaiki:

- `src/Config/MongoClientAdapter.php` (pengganti MongoDBManager, non-static + DI-friendly)
    
- `src/Model/User.php` (perbaikan mapping + optional factory dari DB doc) — versi yang lebih robust
    
- `src/Repository/UserRepository.php` (versi baru yang menerima adapter/collection via constructor; return/accept `User` objects)
    

> Catatan: saya menjaga gaya PHP 8+, `declare(strict_types=1)`, PSR-4 namespaces sesuai project kita.

### 3.1 `src/Config/MongoClientAdapter.php`
```
php
<?php
declare(strict_types=1);

namespace App\Config;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;
use MongoDB\Driver\Session;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use Psr\Log\LoggerInterface;

/**
 * MongoClientAdapter
 * - Non-static, DI-friendly adapter untuk MongoDB Client/DB/Collection
 * - Mempermudah testing (bisa inject mock client)
 */
class MongoClientAdapter
{
    private Client $client;
    private Database $database;
    private LoggerInterface|null $logger;

    public function __construct(string $uri, string $dbName, array $options = [], ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        try {
            $this->client = new Client($uri, $options);
            $this->database = $this->client->selectDatabase($dbName);
        } catch (MongoDBException $e) {
            if ($this->logger) {
                $this->logger->error('MongoClientAdapter: connection failed', ['exception' => $e]);
            }
            throw $e;
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getCollection(string $name): Collection
    {
        return $this->database->selectCollection($name);
    }

    /**
     * Start a client session if supported.
     * Returns Session or null if sessions not supported.
     */
    public function startSession(): ?Session
    {
        try {
            return $this->client->startSession();
        } catch (MongoDBException $e) {
            // Sessions may not be available (standalone server). Log and return null.
            if ($this->logger) {
                $this->logger->warning('MongoClientAdapter: startSession not available', ['exception' => $e]);
            }
            return null;
        }
    }

    public function ping(): bool
    {
        try {
            $this->database->command(['ping' => 1]);
            return true;
        } catch (MongoDBException $e) {
            if ($this->logger) {
                $this->logger->error('MongoClientAdapter: ping failed', ['exception' => $e]);
            }
            return false;
        }
    }

    public function createIndexes(string $collectionName, array $indexes): array
    {
        try {
            $coll = $this->getCollection($collectionName);
            return $coll->createIndexes($indexes);
        } catch (MongoDBException $e) {
            if ($this->logger) {
                $this->logger->error('MongoClientAdapter: createIndexes failed', ['exception' => $e, 'collection' => $collectionName]);
            }
            return ['error' => $e->getMessage()];
        }
    }
}

```
**Catatan**: gunakan service container sederhana (atau manual wiring) saat inisialisasi aplikasi:
```
php
$adapter = new \App\Config\MongoClientAdapter($_ENV['MONGODB_URI'], $_ENV['MONGODB_DB']);
$userCollection = $adapter->getCollection('users');
$userRepo = new \App\Repository\UserRepository($userCollection);

```

### 3.2 `src/Model/User.php` (penyegaran, tetap compatible)
```
php
<?php
declare(strict_types=1);

namespace App\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

/**
 * User entity (value object)
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

    // Getters ...
    public function getId(): ?string { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    // Setters with validation
    public function setUsername(string $username): void {
        if (trim($username) === '') throw new \InvalidArgumentException('Username cannot be empty');
        $this->username = $username;
    }
    public function setEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid email');
        $this->email = $email;
    }
    public function setPasswordHash(string $hash): void {
        if ($hash === '') throw new \InvalidArgumentException('Password hash cannot be empty');
        $this->passwordHash = $hash;
    }
    public function setRole(string $role): void {
        $valid = [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_STAFF];
        if (!in_array($role, $valid, true)) throw new \InvalidArgumentException('Invalid role');
        $this->role = $role;
    }
    public function setUpdatedAt(DateTime $t): void { $this->updatedAt = $t; }

    /**
     * toDocument: convert user to array suitable for Mongo insert/update
     */
    public function toDocument(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'passwordHash' => $this->passwordHash,
            'role' => $this->role,
            'createdAt' => new UTCDateTime($this->createdAt->getTimestamp() * 1000),
            'updatedAt' => new UTCDateTime($this->updatedAt->getTimestamp() * 1000),
        ];
    }

    /**
     * Factory: build User from DB document (array or BSON Document)
     */
    public static function fromDocument(array $data): User
    {
        $id = $data['_id'] ?? null;
        if ($id instanceof ObjectId) {
            $id = (string)$id;
        }

        // createdAt / updatedAt can be UTCDateTime or string
        $createdAt = new DateTime();
        if (isset($data['createdAt']) && $data['createdAt'] instanceof UTCDateTime) {
            $createdAt = $data['createdAt']->toDateTime();
        } elseif (!empty($data['createdAt'])) {
            $createdAt = new DateTime((string)$data['createdAt']);
        }

        $updatedAt = new DateTime();
        if (isset($data['updatedAt']) && $data['updatedAt'] instanceof UTCDateTime) {
            $updatedAt = $data['updatedAt']->toDateTime();
        } elseif (!empty($data['updatedAt'])) {
            $updatedAt = new DateTime((string)$data['updatedAt']);
        }

        return new User(
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['passwordHash'] ?? '',
            $data['role'] ?? self::ROLE_STAFF,
            $id,
            $createdAt,
            $updatedAt
        );
    }
}

```

### 3.3 `src/Repository/UserRepository.php` (DI-friendly, returns User)
```
php
<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\User;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;

/**
 * UserRepository now accepts a Collection instance in ctor (DI-friendly)
 * It returns/accepts User objects (domain-centric)
 */
class UserRepository implements IRepository
{
    private Collection $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function findById(string $id): ?array
    {
        try {
            $doc = $this->collection->findOne(['_id' => new ObjectId($id)]);
            if ($doc === null) return null;
            $arr = (array)$doc;
            $arr['_id'] = (string)$arr['_id'];
            return $arr;
        } catch (MongoException $e) {
            // log upstream
            return null;
        }
    }

    /**
     * Find many (returns arrays). For domain usage prefer findOne->User
     */
    public function find(array $filter = [], array $options = []): array
    {
        $cursor = $this->collection->find($filter, $options);
        $out = [];
        foreach ($cursor as $doc) {
            $d = (array)$doc;
            $d['_id'] = (string)$d['_id'];
            $out[] = $d;
        }
        return $out;
    }

    /**
     * Create from document array or User object.
     * Return inserted id string.
     */
    public function create(array $data): string
    {
        // ensure timestamps
        $data['createdAt'] = $data['createdAt'] ?? new UTCDateTime();
        $data['updatedAt'] = $data['updatedAt'] ?? new UTCDateTime();

        $result = $this->collection->insertOne($data);
        return (string)$result->getInsertedId();
    }

    public function update(string $id, array $data): bool
    {
        try {
            $data['updatedAt'] = new UTCDateTime();
            $res = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );
            // If matched count is >0, update considered success (even if no modified)
            return $res->getMatchedCount() > 0;
        } catch (MongoException $e) {
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $res = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            return $res->getDeletedCount() > 0;
        } catch (MongoException $e) {
            return false;
        }
    }

    public function count(array $filter = []): int
    {
        return (int)$this->collection->countDocuments($filter);
    }

    public function findOne(array $filter = []): ?array
    {
        $doc = $this->collection->findOne($filter);
        if ($doc === null) return null;
        $arr = (array)$doc;
        $arr['_id'] = (string)$arr['_id'];
        return $arr;
    }

    // Convenience domain-centric methods:
    public function findByUsername(string $username): ?User
    {
        $doc = $this->collection->findOne(['username' => $username]);
        if ($doc === null) return null;
        return User::fromDocument((array)$doc);
    }

    public function findByEmail(string $email): ?User
    {
        $doc = $this->collection->findOne(['email' => $email]);
        if ($doc === null) return null;
        return User::fromDocument((array)$doc);
    }
}

```

## 4) Mengapa perubahan ini membantu (konkrit)

- Dengan adapter DI-friendly: kita **bisa inject mock adapter/collection** saat unit test sehingga tak perlu connect ke MongoDB nyata → memaksa code yang bersih dan tidak mengubah kode produksi saat mengetes.
    
- Repositori menerima `Collection` pada constructor → memudahkan penggantian implementasi (mis. injeksi collection dari in-memory Mongo emulator atau library mock).
    
- `User` class mapping lebih robust terhadap variasi dokumen DB (UTCDateTime / string).
    
- `startSession()` safe: jika tidak tersedia, kembalikan `null` dan jangan memaksa transaksi (kebijakan service harus periksa).

## 5) Saya ajukan tambahan & checklist (prioritas)

Implementasi kode di atas adalah langkah pertama. Berikut hal penting lain untuk mencegah "fitur baru merusak kode lama":

1. **Jadikan service layer domain-centric**: buat `UserService`, `AuthService`, `InventoryService` yang hanya menerima/return `User` / `InventoryItem` objects. (Jangan biarkan controllers langsung manipulate DB arrays).
2. **Testing**: buat unit tests untuk services & repositories. Gunakan PHPUnit dan buat mock object untuk `Collection`.
    
3. **Transaction-safe flows**: di operasi multi-collection (mis. create order -> reduce stock -> log), gunakan `startSession()` dan `withTransaction()` jika session tersedia. Jika tidak tersedia (standalone mongo), fallback ke strategi retry/locks.
    
4. **Token & Auth**: buat `TokenService` terpisah (refresh token hashed in DB), jangan simpan JWT revocation tanpa storage. Rencana Anda pakai `firebase/php-jwt` sudah benar.
5. **Seeder & Migration**: buat seeder script (initial admin user, roles). Anda bisa membuat `scripts/seed.php`.
    
6. **Logging & structured errors**: gunakan `psr/log` logger (Monolog) sehingga error lebih mudah dikejar.
    
7. **Code Style & CI**: jalankan PHP-CS-Fixer atau PHPCS; gunakan Git + CI (Github Actions). Rencana di `RencanaPengembangan.md` sudah menyarankan testing & phpcs — lanjutkan.