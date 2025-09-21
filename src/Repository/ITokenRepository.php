<?php
declare(strict_types=1);

namespace App\Repository;

use DateTime;

/**
 * Interface untuk Token Repository
 * Menangani penyimpanan dan manajemen refresh tokens
 */
interface ITokenRepository
{
    /**
     * Store refresh token hash
     * 
     * @param string $tokenHash Hash dari refresh token
     * @param string $userId User ID pemilik token
     * @param DateTime $expiresAt Waktu kadaluarsa token
     * @return bool True jika berhasil disimpan
     */
    public function storeRefreshToken(string $tokenHash, string $userId, DateTime $expiresAt): bool;

    /**
     * Revoke refresh token
     * 
     * @param string $tokenHash Hash dari refresh token
     * @return bool True jika berhasil di-revoke
     */
    public function revokeRefreshToken(string $tokenHash): bool;

    /**
     * Check if refresh token is revoked
     * 
     * @param string $tokenHash Hash dari refresh token
     * @return bool True jika token di-revoke
     */
    public function isRefreshTokenRevoked(string $tokenHash): bool;

    /**
     * Find refresh token by hash
     * 
     * @param string $tokenHash Hash dari refresh token
     * @return array|null Token data atau null jika tidak ditemukan
     */
    public function findRefreshToken(string $tokenHash): ?array;

    /**
     * Clean up expired refresh tokens
     * 
     * @return int Number of tokens cleaned up
     */
    public function cleanupExpiredTokens(): int;
}