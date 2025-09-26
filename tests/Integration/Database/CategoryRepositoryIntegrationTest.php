<?php
declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Repository\MongoCategoryRepository;
use App\Config\MongoDBManager;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;
use MongoDB\Collection;

class CategoryRepositoryIntegrationTest extends TestCase
{
    private MongoCategoryRepository $categoryRepo;
    private Collection $collection;
    private Logger $logger;

    protected function setUp(): void
    {
        // Initialize MongoDB connection
        MongoDBManager::initialize();
        $this->collection = MongoDBManager::getCollection('categories');
        
        // Create logger
        $this->logger = new Logger(__DIR__ . '/../../../logs/test.log');
        
        $this->categoryRepo = new MongoCategoryRepository($this->logger);
        
        // Clear test data before each test
        $this->collection->deleteMany([]);
    }

    public function testCreateAndFindCategory(): void
    {
        $categoryData = [
            'name' => 'Integration Test Category',
            'slug' => 'integration-test',
            'description' => 'Test category for integration testing',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $categoryId = $this->categoryRepo->create($categoryData);
        
        $this->assertNotEmpty($categoryId);
        $this->assertTrue(\MongoDB\BSON\ObjectId::isValid($categoryId));

        $foundCategory = $this->categoryRepo->findById($categoryId);
        
        $this->assertNotNull($foundCategory);
        $this->assertEquals('Integration Test Category', $foundCategory['name']);
        $this->assertEquals('integration-test', $foundCategory['slug']);
    }

    public function testUpdateCategory(): void
    {
        // First create a category
        $categoryData = [
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $categoryId = $this->categoryRepo->create($categoryData);

        // Update the category
        $updateData = ['name' => 'Updated Name'];
        $result = $this->categoryRepo->update($categoryId, $updateData);

        $this->assertTrue($result);

        // Verify the update
        $updatedCategory = $this->categoryRepo->findById($categoryId);
        $this->assertEquals('Updated Name', $updatedCategory['name']);
    }

    public function testDeleteCategory(): void
    {
        $categoryData = [
            'name' => 'Category to Delete',
            'slug' => 'delete-me',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $categoryId = $this->categoryRepo->create($categoryData);

        // Verify category exists
        $category = $this->categoryRepo->findById($categoryId);
        $this->assertNotNull($category);

        // Delete the category
        $result = $this->categoryRepo->delete($categoryId);
        $this->assertTrue($result);

        // Verify category is deleted
        $deletedCategory = $this->categoryRepo->findById($categoryId);
        $this->assertNull($deletedCategory);
    }

    public function testFindBySlug(): void
    {
        $categoryData = [
            'name' => 'Slug Test Category',
            'slug' => 'unique-slug-123',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $this->categoryRepo->create($categoryData);

        $foundCategory = $this->categoryRepo->findBySlug('unique-slug-123');
        
        $this->assertNotNull($foundCategory);
        $this->assertEquals('Slug Test Category', $foundCategory['name']);
        $this->assertEquals('unique-slug-123', $foundCategory['slug']);
    }

    public function testFindActiveCategories(): void
    {
        // Create active and inactive categories
        $activeCategory = [
            'name' => 'Active Category',
            'slug' => 'active-cat',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $inactiveCategory = [
            'name' => 'Inactive Category',
            'slug' => 'inactive-cat',
            'active' => false,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $this->categoryRepo->create($activeCategory);
        $this->categoryRepo->create($inactiveCategory);

        $activeCategories = $this->categoryRepo->findActive();

        $this->assertCount(1, $activeCategories);
        $this->assertEquals('Active Category', $activeCategories[0]['name']);
    }

    public function testCategoryTreeStructure(): void
    {
        // Create hierarchical categories
        $rootCategory = [
            'name' => 'Root Category',
            'slug' => 'root',
            'parentId' => null,
            'depth' => 0,
            'path' => [],
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $rootId = $this->categoryRepo->create($rootCategory);

        $childCategory = [
            'name' => 'Child Category',
            'slug' => 'child',
            'parentId' => $rootId,
            'depth' => 1,
            'path' => [$rootId],
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $this->categoryRepo->create($childCategory);

        $tree = $this->categoryRepo->getCategoryTree();

        $this->assertCount(1, $tree); // One root category
        $this->assertCount(1, $tree[0]['children']); // Root has one child
        $this->assertEquals('Child Category', $tree[0]['children'][0]['name']);
    }

    public function testSlugExists(): void
    {
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'existing-slug',
            'active' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];

        $categoryId = $this->categoryRepo->create($categoryData);

        // Test slug exists
        $exists = $this->categoryRepo->slugExists('existing-slug');
        $this->assertTrue($exists);

        // Test slug doesn't exist
        $notExists = $this->categoryRepo->slugExists('non-existent-slug');
        $this->assertFalse($notExists);

        // Test slug exists excluding current category
        $existsExcluding = $this->categoryRepo->slugExists('existing-slug', $categoryId);
        $this->assertFalse($existsExcluding);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->collection->deleteMany([]);
    }
}