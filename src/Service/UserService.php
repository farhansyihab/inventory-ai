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