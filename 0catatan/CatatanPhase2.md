# 🎯 Phase 2: Core Features Development

Berdasarkan analisis Phase 1 yang telah **100% selesai**, mari kita lanjutkan ke Phase 2 dengan fokus pada pengembangan fitur inti. Berikut adalah rencana implementasi untuk Phase 2:

## 📋 Rencana Phase 2 (7 Hari)

### 📅 **Hari 1-2: Authentication System**

1. **Implementasi IService Interface**
    
2. **UserService dengan business logic**
    
3. **JWT Token Service**
    
4. **AuthService untuk authentication flow**
    

### 📅 **Hari 3-4: User Management**

1. **UserController dengan endpoints**
    
2. **AuthController untuk login/register**
    
3. **Middleware authentication**
    
4. **Role-based authorization**
    

### 📅 **Hari 5-7: Inventory Management Foundation**

1. **Inventory Model & Repository**
    
2. **InventoryService dengan CRUD operations**
    
3. **Basic validation rules**
    
4. **Integration testing**
    

## 🚀 Mulai dengan: Implementasi IService Interface

Mari kita mulai dengan membuat `src/Service/IService.php` yang merupakan interface inti untuk semua service layer:
```
php
<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use RuntimeException;

/**
 * Base Service Interface
 * Provides common contract for all service classes
 * 
 * @template T Entity type
 */
interface IService
{
    /**
     * Find entity by ID
     * 
     * @param string $id Entity ID
     * @return array|null Entity data or null if not found
     * @throws InvalidArgumentException If ID format is invalid
     */
    public function findById(string $id): ?array;

    /**
     * Find entities by criteria
     * 
     * @param array $filter Query filter
     * @param array $options Find options (sort, limit, skip, etc.)
     * @return array Array of entities
     * @throws RuntimeException If database operation fails
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new entity
     * 
     * @param array $data Entity data
     * @return array Created entity data with ID
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If creation fails
     */
    public function create(array $data): array;

    /**
     * Update entity by ID
     * 
     * @param string $id Entity ID
     * @param array $data Update data
     * @return bool True if update successful
     * @throws InvalidArgumentException If ID format is invalid or validation fails
     * @throws RuntimeException If update operation fails
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete entity by ID
     * 
     * @param string $id Entity ID
     * @return bool True if delete successful
     * @throws InvalidArgumentException If ID format is invalid
     * @throws RuntimeException If delete operation fails
     */
    public function delete(string $id): bool;

    /**
     * Count entities by criteria
     * 
     * @param array $filter Query filter
     * @return int Number of matching entities
     * @throws RuntimeException If count operation fails
     */
    public function count(array $filter = []): int;

    /**
     * Validate entity data
     * 
     * @param array $data Entity data to validate
     * @return bool True if valid
     * @throws InvalidArgumentException If validation fails with detailed errors
     */
    public function validate(array $data): bool;

    /**
     * Find one entity by criteria
     * 
     * @param array $filter Query filter
     * @return array|null Entity data or null if not found
     * @throws RuntimeException If database operation fails
     */
    public function findOne(array $filter = []): ?array;
}
```

## 🔄 Langkah Selanjutnya:

1. **Buat BaseService abstract class** (opsional untuk code reuse)
    
2. **Implementasi UserService** yang mengimplementasi IService
    
3. **Buat JWT Token Service** untuk authentication
    
4. **Implementasi AuthService** untuk login/register logic
    

### Pembuatan `src/Service/UserService.php` sebagai implementasi pertama:
```
php
<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class UserService implements IService
{
    private UserRepository $userRepository;
    private Logger $logger;

    public function __construct(UserRepository $userRepository, Logger $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function findById(string $id): ?array
    {
        try {
            $user = $this->userRepository->findUserById($id);
            return $user ? $user->toArray() : null;
        } catch (\Exception $e) {
            $this->logger->error("UserService::findById failed: " . $e->getMessage());
            throw new RuntimeException("Failed to find user: " . $e->getMessage());
        }
    }

    public function find(array $filter = [], array $options = []): array
    {
        try {
            $users = $this->userRepository->find($filter, $options);
            return array_map(fn($userData) => $this->convertToArray($userData), $users);
        } catch (\Exception $e) {
            $this->logger->error("UserService::find failed: " . $e->getMessage());
            throw new RuntimeException("Failed to find users: " . $e->getMessage());
        }
    }

    public function create(array $data): array
    {
        $this->validate($data);

        try {
            // Hash password jika ada
            if (isset($data['password'])) {
                $data['passwordHash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password']);
            }

            $user = new User(
                $data['username'],
                $data['email'],
                $data['passwordHash'],
                $data['role'] ?? User::ROLE_STAFF
            );

            $userId = $this->userRepository->saveUser($user);
            
            $this->logger->info("User created successfully", ['userId' => $userId]);
            
            return $this->findById($userId);
        } catch (\Exception $e) {
            $this->logger->error("UserService::create failed: " . $e->getMessage());
            throw new RuntimeException("Failed to create user: " . $e->getMessage());
        }
    }

    public function update(string $id, array $data): bool
    {
        $this->validate($data, false); // Validation for update (might skip required fields)

        try {
            $existingUser = $this->userRepository->findUserById($id);
            if (!$existingUser) {
                throw new InvalidArgumentException("User not found with ID: " . $id);
            }

            // Update fields
            if (isset($data['username'])) {
                $existingUser->setUsername($data['username']);
            }
            if (isset($data['email'])) {
                $existingUser->setEmail($data['email']);
            }
            if (isset($data['role'])) {
                $existingUser->setRole($data['role']);
            }
            if (isset($data['password'])) {
                $existingUser->setPasswordHash(password_hash($data['password'], PASSWORD_BCRYPT));
            }

            $success = $this->userRepository->saveUser($existingUser);
            
            if ($success) {
                $this->logger->info("User updated successfully", ['userId' => $id]);
            }
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error("UserService::update failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update user: " . $e->getMessage());
        }
    }

    public function delete(string $id): bool
    {
        try {
            $success = $this->userRepository->delete($id);
            
            if ($success) {
                $this->logger->info("User deleted successfully", ['userId' => $id]);
            }
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error("UserService::delete failed: " . $e->getMessage());
            throw new RuntimeException("Failed to delete user: " . $e->getMessage());
        }
    }

    public function count(array $filter = []): int
    {
        try {
            return $this->userRepository->count($filter);
        } catch (\Exception $e) {
            $this->logger->error("UserService::count failed: " . $e->getMessage());
            throw new RuntimeException("Failed to count users: " . $e->getMessage());
        }
    }

    public function validate(array $data, bool $isCreate = true): bool
    {
        $errors = [];

        if ($isCreate) {
            // Validation for create
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif (strlen($data['username']) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            }

            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }

            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
        } else {
            // Validation for update
            if (isset($data['username']) && empty($data['username'])) {
                $errors['username'] = 'Username cannot be empty';
            }

            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }

            if (isset($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
        }

        if (isset($data['role']) && !in_array($data['role'], User::VALID_ROLES)) {
            $errors['role'] = 'Invalid role. Must be one of: ' . implode(', ', User::VALID_ROLES);
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException("Validation failed: " . json_encode($errors));
        }

        return true;
    }

    public function findOne(array $filter = []): ?array
    {
        try {
            $userData = $this->userRepository->findOne($filter);
            return $userData ? $this->convertToArray($userData) : null;
        } catch (\Exception $e) {
            $this->logger->error("UserService::findOne failed: " . $e->getMessage());
            throw new RuntimeException("Failed to find user: " . $e->getMessage());
        }
    }

    private function convertToArray(array $userData): array
    {
        // Remove sensitive data
        unset($userData['passwordHash']);
        return $userData;
    }

    // Additional domain-specific methods
    public function findByUsername(string $username): ?array
    {
        try {
            $user = $this->userRepository->findUserByUsername($username);
            return $user ? $this->convertToArray($user->toArray()) : null;
        } catch (\Exception $e) {
            $this->logger->error("UserService::findByUsername failed: " . $e->getMessage());
            throw new RuntimeException("Failed to find user by username: " . $e->getMessage());
        }
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $user = $this->userRepository->findUserByEmail($email);
            return $user ? $this->convertToArray($user->toArray()) : null;
        } catch (\Exception $e) {
            $this->logger->error("UserService::findByEmail failed: " . $e->getMessage());
            throw new RuntimeException("Failed to find user by email: " . $e->getMessage());
        }
    }
}
```

### pembuatan test file untuk UserService `tests/Unit/Service/UserServiceTest.php`:
```
php
<?php
declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\UserService;
use App\Repository\UserRepository;
use App\Utility\Logger;
use App\Model\User;
use InvalidArgumentException;
use RuntimeException;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $userRepositoryMock;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->loggerMock = $this->createMock(Logger::class);
        
        $this->userService = new UserService(
            $this->userRepositoryMock,
            $this->loggerMock
        );
    }

    public function testFindByIdReturnsUserWhenExists(): void
    {
        $userData = [
            'id' => '507f1f77bcf86cd799439011',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => User::ROLE_STAFF,
            'createdAt' => '2023-01-01T00:00:00+00:00',
            'updatedAt' => '2023-01-01T00:00:00+00:00'
        ];

        $userMock = $this->createMock(User::class);
        $userMock->method('toArray')->willReturn($userData);

        $this->userRepositoryMock
            ->method('findUserById')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($userMock);

        $result = $this->userService->findById('507f1f77bcf86cd799439011');

        $this->assertNotNull($result);
        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertArrayNotHasKey('passwordHash', $result); // Sensitive data removed
    }

    public function testFindByIdReturnsNullWhenUserNotFound(): void
    {
        $this->userRepositoryMock
            ->method('findUserById')
            ->with('nonexistentid')
            ->willReturn(null);

        $result = $this->userService->findById('nonexistentid');

        $this->assertNull($result);
    }

    public function testFindByIdThrowsExceptionOnRepositoryError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find user');

        $this->userRepositoryMock
            ->method('findUserById')
            ->willThrowException(new \Exception('Database error'));

        $this->userService->findById('someid');
    }

    public function testCreateUserValidatesRequiredFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $invalidData = [
            'username' => 'ab', // Too short
            'email' => 'invalid-email',
            'password' => 'short'
        ];

        $this->userService->create($invalidData);
    }

    public function testCreateUserSuccessfully(): void
    {
        $userData = [
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'password' => 'validpassword123',
            'role' => User::ROLE_STAFF
        ];

        $savedUser = $this->createMock(User::class);
        $savedUser->method('toArray')->willReturn([
            'id' => '507f1f77bcf86cd799439011',
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'role' => User::ROLE_STAFF,
            'createdAt' => '2023-01-01T00:00:00+00:00',
            'updatedAt' => '2023-01-01T00:00:00+00:00'
        ]);

        $this->userRepositoryMock
            ->method('saveUser')
            ->willReturn('507f1f77bcf86cd799439011');

        $this->userRepositoryMock
            ->method('findUserById')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($savedUser);

        // Expect logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('User created successfully', $this->arrayHasKey('userId'));

        $result = $this->userService->create($userData);

        $this->assertIsArray($result);
        $this->assertEquals('validuser', $result['username']);
        $this->assertEquals('valid@example.com', $result['email']);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('passwordHash', $result);
    }

    public function testValidateAcceptsValidDataForCreate(): void
    {
        $validData = [
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'password' => 'validpassword123',
            'role' => User::ROLE_STAFF
        ];

        $result = $this->userService->validate($validData, true);
        $this->assertTrue($result);
    }

    public function testValidateRejectsInvalidDataForCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidData = [
            'username' => 'ab', // Too short
            'email' => 'invalid-email',
            'password' => 'short'
        ];

        $this->userService->validate($invalidData, true);
    }

    public function testValidateAcceptsPartialDataForUpdate(): void
    {
        $partialData = [
            'email' => 'new@example.com'
        ];

        $result = $this->userService->validate($partialData, false);
        $this->assertTrue($result);
    }

    public function testFindByUsernameReturnsUserWhenExists(): void
    {
        $userData = [
            'id' => '507f1f77bcf86cd799439011',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => User::ROLE_STAFF
        ];

        $userMock = $this->createMock(User::class);
        $userMock->method('toArray')->willReturn($userData);

        $this->userRepositoryMock
            ->method('findUserByUsername')
            ->with('testuser')
            ->willReturn($userMock);

        $result = $this->userService->findByUsername('testuser');

        $this->assertNotNull($result);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testFindByEmailReturnsUserWhenExists(): void
    {
        $userData = [
            'id' => '507f1f77bcf86cd799439011',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => User::ROLE_STAFF
        ];

        $userMock = $this->createMock(User::class);
        $userMock->method('toArray')->willReturn($userData);

        $this->userRepositoryMock
            ->method('findUserByEmail')
            ->with('test@example.com')
            ->willReturn($userMock);

        $result = $this->userService->findByEmail('test@example.com');

        $this->assertNotNull($result);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $this->userRepositoryMock
            ->method('count')
            ->with(['role' => User::ROLE_STAFF])
            ->willReturn(5);

        $result = $this->userService->count(['role' => User::ROLE_STAFF]);

        $this->assertEquals(5, $result);
    }

    public function testDeleteReturnsTrueWhenSuccessful(): void
    {
        $this->userRepositoryMock
            ->method('delete')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn(true);

        // Expect logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('User deleted successfully', $this->arrayHasKey('userId'));

        $result = $this->userService->delete('507f1f77bcf86cd799439011');

        $this->assertTrue($result);
    }

    public function testUpdateValidatesData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidData = [
            'email' => 'invalid-email'
        ];

        $this->userService->update('someid', $invalidData);
    }

    public function testUpdateThrowsExceptionWhenUserNotFound(): void
    {
        // Ubah ke RuntimeException karena InvalidArgumentException di-wrap
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to update user: User not found with ID: nonexistentid');

        $this->userRepositoryMock
            ->method('findUserById')
            ->with('nonexistentid')
            ->willReturn(null);

        $this->userService->update('nonexistentid', ['username' => 'newusername']);
    }
}
```

Ada masalah kompatibilitas dengan PSR-3 Logger interface. Mari kita perbaiki `src/Utility/Logger.php` terlebih dahulu:
```
php
<?php
declare(strict_types=1);

namespace App\Utility;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Simple file logger implementing PSR-3 LoggerInterface
 */
class Logger extends AbstractLogger
{
    private string $logFile;
    private string $defaultLevel;

    public function __construct(?string $logFile = null, string $defaultLevel = LogLevel::INFO)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';
        $this->defaultLevel = $defaultLevel;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper((string) $level);
        $message = (string) $message;
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        
        $logMessage = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            $level,
            $message,
            $contextStr
        );

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Quick debug log
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Quick info log
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Quick error log
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Quick warning log
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Get log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
```
