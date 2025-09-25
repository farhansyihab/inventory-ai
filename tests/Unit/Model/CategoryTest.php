<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Model\Category;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use DateTime;

class CategoryTest extends TestCase
{
    public function testCategoryCreationWithValidData(): void
    {
        $category = new Category(
            'Electronics',
            'electronics',
            'Electronic devices and components',
            true,
            null
        );
        
        $this->assertEquals('Electronics', $category->getName());
        $this->assertEquals('electronics', $category->getSlug());
        $this->assertEquals('Electronic devices and components', $category->getDescription());
        $this->assertTrue($category->isActive());
        $this->assertNull($category->getParentId());
        $this->assertEquals(0, $category->getDepth());
        $this->assertIsArray($category->getPath());
        $this->assertInstanceOf(DateTime::class, $category->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $category->getUpdatedAt());
    }

    public function testCategoryCreationWithParentId(): void
    {
        $category = new Category(
            'Smartphones',
            'smartphones',
            'Mobile phones',
            true,
            '507f1f77bcf86cd799439011'
        );
        
        $this->assertEquals('507f1f77bcf86cd799439011', $category->getParentId());
        $this->assertTrue($category->isActive());
    }

    public function testCategoryValidationNameTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name must be at least 3 characters');
        
        new Category('A', 'valid-slug');
    }

    public function testCategoryValidationNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name cannot exceed 100 characters');
        
        $longName = str_repeat('a', 101);
        new Category($longName, 'valid-slug');
    }

    public function testCategoryValidationSlugInvalidCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        
        new Category('Valid Name', 'invalid_slug_with_underscore');
    }

    public function testCategoryValidationSlugTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must be at least 2 characters');
        
        new Category('Valid Name', 'a');
    }

    public function testCategoryValidationSlugTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot exceed 50 characters');
        
        $longSlug = str_repeat('a', 51);
        new Category('Valid Name', $longSlug);
    }

    public function testCategoryValidationDescriptionTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Description cannot exceed 500 characters');
        
        $longDescription = str_repeat('a', 501);
        new Category('Valid Name', 'valid-slug', $longDescription);
    }

    public function testSettersAndGetters(): void
    {
        $category = new Category('Original', 'original', 'Original description');
        
        // Test setName
        $category->setName('Updated Name');
        $this->assertEquals('Updated Name', $category->getName());
        
        // Test setSlug
        $category->setSlug('updated-slug');
        $this->assertEquals('updated-slug', $category->getSlug());
        
        // Test setDescription
        $category->setDescription('Updated description');
        $this->assertEquals('Updated description', $category->getDescription());
        
        // Test setActive
        $category->setActive(false);
        $this->assertFalse($category->isActive());
        
        // Test setParentId
        $category->setParentId('new-parent-id');
        $this->assertEquals('new-parent-id', $category->getParentId());
        
        // Test setDepth
        $category->setDepth(2);
        $this->assertEquals(2, $category->getDepth());
        
        // Test setPath
        $path = ['Electronics', 'Mobile'];
        $category->setPath($path);
        $this->assertEquals($path, $category->getPath());
    }

    public function testToArrayMethod(): void
    {
        $category = new Category(
            'Test Category',
            'test-category',
            'Test description',
            true,
            'parent123'
        );
        
        $array = $category->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('active', $array);
        $this->assertArrayHasKey('parentId', $array);
        $this->assertArrayHasKey('depth', $array);
        $this->assertArrayHasKey('path', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
        
        $this->assertEquals('Test Category', $array['name']);
        $this->assertEquals('test-category', $array['slug']);
    }

    public function testToDocumentMethod(): void
    {
        $category = new Category('Test', 'test', 'Description');
        $document = $category->toDocument();
        
        $this->assertIsArray($document);
        $this->assertArrayHasKey('name', $document);
        $this->assertArrayHasKey('slug', $document);
        $this->assertArrayHasKey('description', $document);
        $this->assertArrayHasKey('active', $document);
        $this->assertArrayHasKey('parentId', $document);
        $this->assertArrayHasKey('depth', $document);
        $this->assertArrayHasKey('path', $document);
        $this->assertArrayHasKey('createdAt', $document);
        $this->assertArrayHasKey('updatedAt', $document);
    }

    public function testFromDocumentMethod(): void
    {
        $document = [
            '_id' => '507f1f77bcf86cd799439011',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
            'active' => true,
            'parentId' => null,
            'depth' => 0,
            'path' => [],
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ];
        
        $category = Category::fromDocument($document);
        
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('507f1f77bcf86cd799439011', $category->getId());
        $this->assertEquals('Test Category', $category->getName());
        $this->assertEquals('test-category', $category->getSlug());
    }

    public function testIsRootMethod(): void
    {
        $rootCategory = new Category('Root', 'root', '', true, null);
        $childCategory = new Category('Child', 'child', '', true, 'parent123');
        
        $this->assertTrue($rootCategory->isRoot());
        $this->assertFalse($childCategory->isRoot());
    }

    public function testHasChildrenMethod(): void
    {
        $category = new Category('Test', 'test', '');
        $category->setDepth(0);
        $this->assertFalse($category->hasChildren());
        
        $category->setDepth(1);
        $this->assertTrue($category->hasChildren());
    }

    public function testGetFullPathMethod(): void
    {
        $category = new Category('Leaf', 'leaf', '');
        $category->setPath(['Electronics', 'Mobile', 'Smartphones']);
        
        $this->assertEquals('Electronics > Mobile > Smartphones', $category->getFullPath());
        
        $category->setPath([]);
        $this->assertEquals('Leaf', $category->getFullPath());
    }

    public function testToStringMethod(): void
    {
        $category = new Category('Test Category', 'test-category', '');
        $string = (string) $category;
        
        $this->assertStringContainsString('Category[id=', $string);
        $this->assertStringContainsString('name=Test Category', $string);
        $this->assertStringContainsString('slug=test-category', $string);
    }

    public function testUpdatedAtIsUpdatedOnModification(): void
    {
        $category = new Category('Test', 'test', '');
        $originalUpdatedAt = $category->getUpdatedAt();
        
        sleep(1); // Ensure time difference
        $category->setName('Updated Name');
        
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $category->getUpdatedAt()->getTimestamp()
        );
    }
}