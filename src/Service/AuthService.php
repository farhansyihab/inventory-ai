<?php
//src/Service/AuthService.php
declare(strict_types=1);

namespace App\Service;

use App\Utility\Logger;
use App\Repository\UserRepository;
use RuntimeException;
use InvalidArgumentException;

class AuthService implements IAuthService
{
    private UserService $userService;
    private ITokenService $tokenService;
    private Logger $logger;

    public function __construct(
        UserService $userService,
        ITokenService $tokenService,
        Logger $logger
    ) {
        $this->userService = $userService;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    public function register(array $userData): array
    {
        try {
            // Create user
            $user = $this->userService->create($userData);

            // Generate tokens
            $tokens = $this->generateTokens($user);

            $this->logger->info("User registered successfully", [
                'userId' => $user['id'],
                'username' => $user['username']
            ]);

            return [
                'user' => $user,
                'tokens' => $tokens
            ];
        } catch (\Exception $e) {
            $this->logger->error("AuthService::register failed: " . $e->getMessage());
            throw new RuntimeException("Registration failed: " . $e->getMessage());
        }
    }

    public function login(string $username, string $password): array
    {
        try {
            // Verify credentials
            $user = $this->verifyCredentials($username, $password);
            
            if (!$user) {
                throw new InvalidArgumentException("Invalid username or password");
            }

            // Generate tokens
            $tokens = $this->generateTokens($user);

            $this->logger->info("User logged in successfully", [
                'userId' => $user['id'],
                'username' => $user['username']
            ]);

            return [
                'user' => $user,
                'tokens' => $tokens
            ];
        } catch (\Exception $e) {
            $this->logger->error("AuthService::login failed: " . $e->getMessage());
            throw new RuntimeException("Login failed: " . $e->getMessage());
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        try {
            // Verify refresh token
            $payload = $this->tokenService->verifyRefreshToken($refreshToken);
            
            if (!$payload) {
                throw new InvalidArgumentException("Invalid refresh token");
            }

            // Get user data
            $user = $this->userService->findById($payload['userId']);
            
            if (!$user) {
                throw new InvalidArgumentException("User not found");
            }

            // Generate new tokens
            $tokens = $this->generateTokens($user);

            // Revoke old refresh token
            $this->tokenService->revokeRefreshToken($refreshToken);

            $this->logger->info("Token refreshed successfully", [
                'userId' => $user['id']
            ]);

            return $tokens;
        } catch (\Exception $e) {
            $this->logger->error("AuthService::refreshToken failed: " . $e->getMessage());
            throw new RuntimeException("Token refresh failed: " . $e->getMessage());
        }
    }

    public function logout(string $refreshToken): bool
    {
        try {
            $success = $this->tokenService->revokeRefreshToken($refreshToken);
            
            if ($success) {
                $this->logger->info("User logged out successfully");
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error("AuthService::logout failed: " . $e->getMessage());
            throw new RuntimeException("Logout failed: " . $e->getMessage());
        }
    }

    public function verifyCredentials(string $username, string $password): array|false
    {
        try {
            // Try to find user by username or email
            $user = $this->userService->findByUsername($username);
            
            if (!$user) {
                $user = $this->userService->findByEmail($username);
            }

            if (!$user) {
                return false;
            }

            // Get full user data with password hash
            $fullUser = $this->userService->findById($user['id']);
            
            if (!$fullUser || !isset($fullUser['passwordHash'])) {
                return false;
            }

            // Verify password
            if (!password_verify($password, $fullUser['passwordHash'])) {
                return false;
            }

            // Remove sensitive data before returning
            unset($fullUser['passwordHash']);
            
            return $fullUser;
        } catch (\Exception $e) {
            $this->logger->error("AuthService::verifyCredentials failed: " . $e->getMessage());
            return false;
        }
    }

    public function changePassword(string $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            // Get user with password hash
            $user = $this->userService->findById($userId);
            
            if (!$user || !isset($user['passwordHash'])) {
                throw new InvalidArgumentException("User not found");
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['passwordHash'])) {
                throw new InvalidArgumentException("Current password is incorrect");
            }

            // Update password
            $success = $this->userService->update($userId, [
                'password' => $newPassword
            ]);

            if ($success) {
                $this->logger->info("Password changed successfully", ['userId' => $userId]);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error("AuthService::changePassword failed: " . $e->getMessage());
            throw new RuntimeException("Password change failed: " . $e->getMessage());
        }
    }

    /**
     * Generate both access and refresh tokens for user
     */
    private function generateTokens(array $user): array
    {
        return [
            'accessToken' => $this->tokenService->generateAccessToken($user),
            'refreshToken' => $this->tokenService->generateRefreshToken($user),
            'expiresIn' => $this->tokenService->getAccessTokenExpiry()
        ];
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }
}