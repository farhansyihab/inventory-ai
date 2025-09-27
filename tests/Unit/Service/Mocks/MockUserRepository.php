<?php
// tests/Unit/Service/Mocks/MockUserRepository.php

namespace Tests\Unit\Service\Mocks;

use App\Repository\UserRepository;
use App\Model\User;

class MockUserRepository extends UserRepository
{
    public function findUserById(string $id): ?User 
    { 
        return null; 
    }
    
    public function findUserByUsername(string $username): ?User 
    { 
        return null; 
    }
    
    public function findUserByEmail(string $email): ?User 
    { 
        return null; 
    }
    
    public function saveUser(User $user): string 
    { 
        return 'mock_id'; 
    }
    
    public function deleteUser(User $user): bool 
    { 
        return true; 
    }
    
    public function usernameExists(string $username): bool 
    { 
        return false; 
    }
    
    public function emailExists(string $email): bool 
    { 
        return false; 
    }
    
    public function count(array $filter = []): int 
    { 
        return 0; 
    }
    
    // Tambahkan method yang mungkin diperlukan
    public function createIndexes(): array 
    { 
        return []; 
    }
}