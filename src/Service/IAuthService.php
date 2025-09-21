<?php
//src/Service/IAuthService.php
declare(strict_types=1);

namespace App\Service;

/**
 * Interface untuk Authentication Service
 */
interface IAuthService
{
    /**
     * Register new user
     * 
     * @param array $userData User registration data
     * @return array User data dengan tokens
     */
    public function register(array $userData): array;

    /**
     * Login user
     * 
     * @param string $username Username atau email
     * @param string $password Password
     * @return array User data dengan tokens
     */
    public function login(string $username, string $password): array;

    /**
     * Refresh access token menggunakan refresh token
     * 
     * @param string $refreshToken Refresh token
     * @return array New access token dan refresh token
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Logout user (revoke refresh token)
     * 
     * @param string $refreshToken Refresh token
     * @return bool True jika berhasil logout
     */
    public function logout(string $refreshToken): bool;

    /**
     * Verify user credentials
     * 
     * @param string $username Username atau email
     * @param string $password Password
     * @return array|false User data atau false jika invalid
     */
    public function verifyCredentials(string $username, string $password): array|false;

    /**
     * Change user password
     * 
     * @param string $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool True jika berhasil
     */
    public function changePassword(string $userId, string $currentPassword, string $newPassword): bool;
}