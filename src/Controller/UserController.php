<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use App\Utility\Logger;

class UserController extends BaseController
{
    private UserService $userService;

    public function __construct(?UserService $userService = null, ?Logger $logger = null)
    {
        parent::__construct($logger);
        $this->userService = $userService ?? new UserService(new \App\Repository\UserRepository(), new Logger());
    }

    /**
     * Get all users (with pagination)
     */
    public function listUsers(): void
    {
        try {
            // TODO: Add authentication and authorization check
            // if (!$this->isAuthenticated() || !$this->userIsAdmin()) {
            //     $this->unauthorizedResponse();
            //     return;
            // }

            $pagination = $this->getPaginationParams();
            $filter = $this->getRequestData();

            // Remove pagination parameters from filter
            unset($filter['page'], $filter['limit']);

            $users = $this->userService->find($filter, [
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset'],
                'sort' => ['createdAt' => -1]
            ]);

            $total = $this->userService->count($filter);

            $this->successResponse([
                'users' => $users,
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $pagination['limit'])
                ]
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("UserController::listUsers unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Get user by ID
     */
    public function getUser(string $id): void
    {
        try {
            // TODO: Add authentication and authorization check
            // $currentUserId = $this->getAuthUserId();
            // if (!$currentUserId || ($currentUserId !== $id && !$this->userIsAdmin())) {
            //     $this->unauthorizedResponse();
            //     return;
            // }

            $user = $this->userService->findById($id);
            
            if (!$user) {
                $this->notFoundResponse('User not found');
                return;
            }

            $this->successResponse([
                'user' => $user
            ], 'User retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("UserController::getUser unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Create new user (admin only)
     */
    public function createUser(): void
    {
        try {
            // TODO: Add authentication and authorization check
            // if (!$this->isAuthenticated() || !$this->userIsAdmin()) {
            //     $this->unauthorizedResponse();
            //     return;
            // }

            $requestData = $this->getRequestData();

            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'role'];
            $errors = $this->validateRequiredFields($requiredFields);
            
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
                return;
            }

            $user = $this->userService->create($requestData);

            $this->logAction('user_created', [
                'userId' => $user['id'],
                'username' => $user['username']
            ]);

            $this->successResponse([
                'user' => $user
            ], 'User created successfully', 201);

        } catch (\Exception $e) {
            $this->logger->error("UserController::createUser unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(string $id): void
    {
        try {
            // TODO: Add authentication and authorization check
            // $currentUserId = $this->getAuthUserId();
            // if (!$currentUserId || ($currentUserId !== $id && !$this->userIsAdmin())) {
            //     $this->unauthorizedResponse();
            //     return;
            // }

            $requestData = $this->getRequestData();

            // Remove sensitive fields that shouldn't be updated here
            unset($requestData['password'], $requestData['passwordHash']);

            $success = $this->userService->update($id, $requestData);

            if ($success) {
                $user = $this->userService->findById($id);
                $this->logAction('user_updated', ['userId' => $id]);
                $this->successResponse([
                    'user' => $user
                ], 'User updated successfully');
            } else {
                $this->errorResponse('User update failed', [], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error("UserController::updateUser unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser(string $id): void
    {
        try {
            // TODO: Add authentication and authorization check
            // if (!$this->isAuthenticated() || !$this->userIsAdmin()) {
            //     $this->unauthorizedResponse();
            //     return;
            // }

            // Prevent self-deletion
            // $currentUserId = $this->getAuthUserId();
            // if ($currentUserId === $id) {
            //     $this->errorResponse('Cannot delete your own account', [], 400);
            //     return;
            // }

            $success = $this->userService->delete($id);

            if ($success) {
                $this->logAction('user_deleted', ['userId' => $id]);
                $this->successResponse([], 'User deleted successfully');
            } else {
                $this->errorResponse('User deletion failed', [], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error("UserController::deleteUser unexpected error: " . $e->getMessage());
            $this->errorResponse('Internal server error', [], 500);
        }
    }
}