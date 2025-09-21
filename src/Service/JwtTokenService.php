<?php
declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Utility\Logger;
use App\Repository\ITokenRepository;
use RuntimeException;
use DateTime;

class JwtTokenService implements ITokenService
{
    private string $secretKey;
    private string $algorithm;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;
    private Logger $logger;
    private ITokenRepository $tokenRepository;

    public function __construct(
        string $secretKey,
        string $algorithm,
        int $accessTokenExpiry,
        int $refreshTokenExpiry,
        Logger $logger,
        ITokenRepository $tokenRepository
    ) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->accessTokenExpiry = $accessTokenExpiry;
        $this->refreshTokenExpiry = $refreshTokenExpiry;
        $this->logger = $logger;
        $this->tokenRepository = $tokenRepository;
    }

    public function generateAccessToken(array $user): string
    {
        try {
            $issuedAt = time();
            $expire = $issuedAt + $this->accessTokenExpiry;

            $payload = [
                'iss' => $_SERVER['SERVER_NAME'] ?? 'inventory-ai',
                'iat' => $issuedAt,
                'exp' => $expire,
                'sub' => $user['id'],
                'userId' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'type' => 'access'
            ];

            return JWT::encode($payload, $this->secretKey, $this->algorithm);
        } catch (\Exception $e) {
            $this->logger->error("JwtTokenService::generateAccessToken failed: " . $e->getMessage());
            throw new RuntimeException("Failed to generate access token: " . $e->getMessage());
        }
    }

    public function generateRefreshToken(array $user): string
    {
        try {
            $issuedAt = time();
            $expire = $issuedAt + $this->refreshTokenExpiry;

            $payload = [
                'iss' => $_SERVER['SERVER_NAME'] ?? 'inventory-ai',
                'iat' => $issuedAt,
                'exp' => $expire,
                'sub' => $user['id'],
                'userId' => $user['id'],
                'type' => 'refresh'
            ];

            $refreshToken = JWT::encode($payload, $this->secretKey, $this->algorithm);

            // Store refresh token hash in repository
            $tokenHash = hash('sha256', $refreshToken);
            $expiresAt = (new DateTime())->setTimestamp($expire);

            $this->tokenRepository->storeRefreshToken($tokenHash, $user['id'], $expiresAt);

            return $refreshToken;
        } catch (\Exception $e) {
            $this->logger->error("JwtTokenService::generateRefreshToken failed: " . $e->getMessage());
            throw new RuntimeException("Failed to generate refresh token: " . $e->getMessage());
        }
    }

    public function verifyAccessToken(string $token): array|false
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Check if token is access token
            if ($payload['type'] !== 'access') {
                $this->logger->warning("Invalid token type for access token verification");
                return false;
            }

            return $payload;
        } catch (\Exception $e) {
            $this->logger->warning("JwtTokenService::verifyAccessToken failed: " . $e->getMessage());
            return false;
        }
    }

    public function verifyRefreshToken(string $token): array|false
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Check if token is refresh token
            if ($payload['type'] !== 'refresh') {
                $this->logger->warning("Invalid token type for refresh token verification");
                return false;
            }

            // Check if token is revoked
            $tokenHash = hash('sha256', $token);
            if ($this->tokenRepository->isRefreshTokenRevoked($tokenHash)) {
                $this->logger->warning("Refresh token has been revoked");
                return false;
            }

            return $payload;
        } catch (\Exception $e) {
            $this->logger->warning("JwtTokenService::verifyRefreshToken failed: " . $e->getMessage());
            return false;
        }
    }

    public function revokeRefreshToken(string $token): bool
    {
        try {
            $tokenHash = hash('sha256', $token);
            return $this->tokenRepository->revokeRefreshToken($tokenHash);
        } catch (\Exception $e) {
            $this->logger->error("JwtTokenService::revokeRefreshToken failed: " . $e->getMessage());
            throw new RuntimeException("Failed to revoke refresh token: " . $e->getMessage());
        }
    }

    public function isRefreshTokenRevoked(string $token): bool
    {
        try {
            $tokenHash = hash('sha256', $token);
            return $this->tokenRepository->isRefreshTokenRevoked($tokenHash);
        } catch (\Exception $e) {
            $this->logger->error("JwtTokenService::isRefreshTokenRevoked failed: " . $e->getMessage());
            throw new RuntimeException("Failed to check refresh token revocation status: " . $e->getMessage());
        }
    }

    /**
     * Get token expiration time in seconds
     */
    public function getAccessTokenExpiry(): int
    {
        return $this->accessTokenExpiry;
    }

    /**
     * Get refresh token expiration time in seconds
     */
    public function getRefreshTokenExpiry(): int
    {
        return $this->refreshTokenExpiry;
    }
}