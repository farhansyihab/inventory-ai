<?php
declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AuthService;
use App\Service\UserService;
use App\Service\ITokenService;
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $userServiceMock;
    private $tokenServiceMock;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->userServiceMock = $this->createMock(UserService::class);
        $this->tokenServiceMock = $this->createMock(ITokenService::class);
        $this->loggerMock = $this->createMock(Logger::class);
        
        $this->authService = new AuthService(
            $this->userServiceMock,
            $this->tokenServiceMock,
            $this->loggerMock
        );
    }

    public function testValidatePasswordStrengthWithValidPassword(): void
    {
        $errors = $this->authService->validatePasswordStrength('StrongPass123!');
        $this->assertEmpty($errors);
    }

    public function testValidatePasswordStrengthWithWeakPassword(): void
    {
        $errors = $this->authService->validatePasswordStrength('weak');
        $this->assertNotEmpty($errors);
        $this->assertContains('Password must be at least 8 characters long', $errors);
    }

    public function testLoginThrowsExceptionWithInvalidCredentials(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Login failed: Invalid username or password');

        $this->userServiceMock
            ->method('findByUsername')
            ->with('invaliduser')
            ->willReturn(null);

        $this->userServiceMock
            ->method('findByEmail')
            ->with('invaliduser')
            ->willReturn(null);

        $this->authService->login('invaliduser', 'wrongpassword');
    }

    public function testLoginSuccessWithUsername(): void
    {
        $userData = [
            'id' => 'user123',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'staff',
            'passwordHash' => password_hash('password123', PASSWORD_BCRYPT)
        ];

        $this->userServiceMock
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn(['id' => 'user123', 'username' => 'testuser']);

        $this->userServiceMock
            ->method('findById')
            ->with('user123')
            ->willReturn($userData);

        $this->tokenServiceMock
            ->method('generateAccessToken')
            ->willReturn('access_token_123');

        $this->tokenServiceMock
            ->method('generateRefreshToken')
            ->willReturn('refresh_token_123');

        $this->tokenServiceMock
            ->method('getAccessTokenExpiry')
            ->willReturn(3600);

        $result = $this->authService->login('testuser', 'password123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertEquals('testuser', $result['user']['username']);
    }


    public function testLoginSuccessWithEmail(): void
    {
        $userData = [
            'id' => 'user123',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'staff',
            'passwordHash' => password_hash('password123', PASSWORD_BCRYPT)
        ];

        $this->userServiceMock
            ->method('findByUsername')
            ->with('test@example.com')
            ->willReturn(null);

        $this->userServiceMock
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(['id' => 'user123', 'email' => 'test@example.com']);

        $this->userServiceMock
            ->method('findById')
            ->with('user123')
            ->willReturn($userData);

        $this->tokenServiceMock
            ->method('generateAccessToken')
            ->willReturn('access_token_123');

        $this->tokenServiceMock
            ->method('generateRefreshToken')
            ->willReturn('refresh_token_123');

        $this->tokenServiceMock
            ->method('getAccessTokenExpiry')
            ->willReturn(3600);

        $result = $this->authService->login('test@example.com', 'password123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('tokens', $result);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Login failed: Invalid username or password');

        $userData = [
            'id' => 'user123',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'staff',
            'passwordHash' => password_hash('correctpassword', PASSWORD_BCRYPT)
        ];

        $this->userServiceMock
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn(['id' => 'user123', 'username' => 'testuser']);

        $this->userServiceMock
            ->method('findById')
            ->with('user123')
            ->willReturn($userData);

        $this->authService->login('testuser', 'wrongpassword');
    }

    public function testLogoutSuccess(): void
    {
        $this->tokenServiceMock
            ->method('revokeRefreshToken')
            ->with('refresh_token_123')
            ->willReturn(true);

        $result = $this->authService->logout('refresh_token_123');
        $this->assertTrue($result);
    }

    public function testLogoutFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $this->tokenServiceMock
            ->method('revokeRefreshToken')
            ->with('refresh_token_123')
            ->willThrowException(new \Exception('Token not found'));

        $this->authService->logout('refresh_token_123');
    }
}