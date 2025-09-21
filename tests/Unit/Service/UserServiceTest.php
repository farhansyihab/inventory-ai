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