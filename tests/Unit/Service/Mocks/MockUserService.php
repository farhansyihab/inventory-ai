<?php
// tests/Unit/Service/Mocks/MockUserService.php

namespace Tests\Unit\Service\Mocks;

use App\Service\UserService;
use App\Repository\UserRepository;
use App\Utility\Logger;

class MockUserService extends UserService
{
    public function __construct()
    {
        $userRepo = new MockUserRepository();
        $logger = new MockLogger();
        
        parent::__construct($userRepo, $logger);
    }

    public function count(array $filter = []): int 
    { 
        return 50; 
    }
    
    public function countByRole(string $role): int 
    { 
        return match($role) {
            'admin' => 2,
            'manager' => 5,
            'staff' => 43,
            default => 0
        };
    }
    
    public function getActiveUserCount(): int
    {
        return 45;
    }
    
    public function getUsersByRole(string $role): array
    {
        return [];
    }
}