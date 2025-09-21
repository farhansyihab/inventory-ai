<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Interface untuk JWT Token Service
 */
interface ITokenService
{
    /**
     * Generate access token untuk user
     * 
     * @param array $user Data user
     * @return string JWT token
     */
    public function generateAccessToken(array $user): string;

    /**
     * Generate refresh token untuk user
     * 
     * @param array $user Data user
     * @return string Refresh token
     */
    public function generateRefreshToken(array $user): string;

    /**
     * Verify dan decode access token
     * 
     * @param string $token JWT token
     * @return array|false Decoded payload atau false jika invalid
     */
    public function verifyAccessToken(string $token): array|false;

    /**
     * Verify refresh token
     * 
     * @param string $token Refresh token
     * @return array|false Decoded payload atau false jika invalid
     */
    public function verifyRefreshToken(string $token): array|false;

    /**
     * Revoke refresh token
     * 
     * @param string $token Refresh token
     * @return bool True jika berhasil di-revoke
     */
    public function revokeRefreshToken(string $token): bool;

    /**
     * Check jika refresh token sudah di-revoke
     * 
     * @param string $token Refresh token
     * @return bool True jika sudah di-revoke
     */
    public function isRefreshTokenRevoked(string $token): bool;

    /**
     * Get access token expiration time in seconds
     */
    public function getAccessTokenExpiry(): int;

    /**
     * Get refresh token expiration time in seconds  
     */
    public function getRefreshTokenExpiry(): int;
}