<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\UserService;
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class AuthController extends BaseController
{
    private AuthService $authService;
    private UserService $userService;

    public function __construct(?AuthService $authService = null, ?UserService $userService = null, ?Logger $logger = null)
    {
        parent::__construct($logger);
        $this->authService = $authService ?? new AuthService(
            new UserService(new \App\Repository\UserRepository(), new Logger()),
            new \App\Service\JwtTokenService(
                $_ENV['JWT_SECRET'] ?? 'fallback-secret',
                $_ENV['JWT_ALGORITHM'] ?? 'HS256',
                (int)($_ENV['JWT_ACCESS_EXPIRY'] ?? 3600),
                (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 2592000),
                new Logger(),
                new \App\Repository\MongoTokenRepository()
            ),
            new Logger()
        );
        $this->userService = $userService ?? new UserService(new \App\Repository\UserRepository(), new Logger());
    }

    /**
     * Register new user
     */
    public function register(): void
    {
        try {
            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['username', 'email', 'password'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            // Validate password strength
            $passwordErrors = $this->authService->validatePasswordStrength($requestData['password']);
            if (!empty($passwordErrors)) {
                $this->validationErrorResponse(['password' => $passwordErrors]);
                return;
            }

            $result = $this->authService->register($requestData);
            
            $this->logAction('user_registered', [
                'username' => $requestData['username'],
                'email' => $requestData['email']
            ]);

            $this->successResponse([
                'user' => $result['user'],
                'tokens' => $result['tokens']
            ], 'User registered successfully', 201);

        } catch (InvalidArgumentException $e) {
            $this->validationErrorResponse(['general' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("AuthController::register unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Login user
     */
    public function login(): void
    {
        try {
            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['username', 'password'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            $result = $this->authService->login(
                $requestData['username'],
                $requestData['password']
            );

            $this->logAction('user_login', [
                'username' => $requestData['username']
            ]);

            $this->successResponse([
                'user' => $result['user'],
                'tokens' => $result['tokens']
            ], 'Login successful');

        } catch (InvalidArgumentException $e) {
            $this->errorResponse('Invalid username or password', [], 401);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("AuthController::login unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken(): void
    {
        try {
            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['refreshToken'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            $tokens = $this->authService->refreshToken($requestData['refreshToken']);

            $this->logAction('token_refreshed');

            $this->successResponse([
                'tokens' => $tokens
            ], 'Token refreshed successfully');

        } catch (InvalidArgumentException $e) {
            $this->errorResponse('Invalid refresh token', [], 401);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("AuthController::refreshToken unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        try {
            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['refreshToken'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            $success = $this->authService->logout($requestData['refreshToken']);

            if ($success) {
                $this->logAction('user_logout');
                $this->successResponse([], 'Logout successful');
            } else {
                $this->errorResponse('Logout failed', [], 400);
            }

        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("AuthController::logout unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(): void
    {
        try {
            // TODO: Implement JWT authentication to get user ID from token
            $userId = $this->getAuthUserId();
            
            if (!$userId) {
                $this->unauthorizedResponse();
                return;
            }

            $user = $this->userService->findById($userId);
            
            if (!$user) {
                $this->notFoundResponse('User not found');
                return;
            }

            $this->successResponse([
                'user' => $user
            ], 'Profile retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("AuthController::profile unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(): void
    {
        try {
            $userId = $this->getAuthUserId();
            
            if (!$userId) {
                $this->unauthorizedResponse();
                return;
            }

            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['currentPassword', 'newPassword'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            // Validate new password strength
            $passwordErrors = $this->authService->validatePasswordStrength($requestData['newPassword']);
            if (!empty($passwordErrors)) {
                $this->validationErrorResponse(['newPassword' => $passwordErrors]);
                return;
            }

            $success = $this->authService->changePassword(
                $userId,
                $requestData['currentPassword'],
                $requestData['newPassword']
            );

            if ($success) {
                $this->logAction('password_changed', ['userId' => $userId]);
                $this->successResponse([], 'Password changed successfully');
            } else {
                $this->errorResponse('Password change failed', [], 400);
            }

        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("AuthController::changePassword unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }
}