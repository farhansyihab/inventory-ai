<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use App\Model\User;
use DateTime;

class UserTest extends TestCase
{
    public function testUserCreationWithValidData(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_STAFF, $user->getRole());
        $this->assertInstanceOf(DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
    }

    public function testUserValidationWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'testuser',
            'invalid-email',
            password_hash('password123', PASSWORD_BCRYPT)
        );
    }

    public function testUserValidationWithShortUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'ab', // Too short
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT)
        );
    }

    public function testUserValidationWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            'invalid_role' // Invalid role
        );
    }

    public function testUserToDocumentConversion(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_ADMIN
        );

        $document = $user->toDocument();

        $this->assertIsArray($document);
        $this->assertEquals('testuser', $document['username']);
        $this->assertEquals('test@example.com', $document['email']);
        $this->assertEquals(User::ROLE_ADMIN, $document['role']);
        $this->assertArrayHasKey('createdAt', $document);
        $this->assertArrayHasKey('updatedAt', $document);
    }

    public function testUserFromDocumentCreation(): void
    {
        $document = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'passwordHash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => User::ROLE_MANAGER,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $user = User::fromDocument($document);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_MANAGER, $user->getRole());
    }

    public function testUserRoleMethods(): void
    {
        $adminUser = new User('admin', 'admin@test.com', 'hash', User::ROLE_ADMIN);
        $managerUser = new User('manager', 'manager@test.com', 'hash', User::ROLE_MANAGER);
        $staffUser = new User('staff', 'staff@test.com', 'hash', User::ROLE_STAFF);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($adminUser->isManager());
        $this->assertFalse($adminUser->isStaff());

        $this->assertFalse($managerUser->isAdmin());
        $this->assertTrue($managerUser->isManager());
        $this->assertFalse($managerUser->isStaff());

        $this->assertFalse($staffUser->isAdmin());
        $this->assertFalse($staffUser->isManager());
        $this->assertTrue($staffUser->isStaff());
    }

    public function testUserToArrayConversion(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );

        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals(User::ROLE_STAFF, $array['role']);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }
}