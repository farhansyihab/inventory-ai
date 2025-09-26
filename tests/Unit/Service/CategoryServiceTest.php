<?php
declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\CategoryService;
use App\Repository\ICategoryRepository;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;
use Exception;

class CategoryServiceTest extends TestCase
{
    private CategoryService $categoryService;
    private ICategoryRepository $mockCategoryRepo;
    private Logger $mockLogger;

    protected function setUp(): void
    {
        $this->mockCategoryRepo = $this->createMock(ICategoryRepository::class);
        $this->mockLogger = $this->createMock(Logger::class);
        
        $this->categoryService = new CategoryService(
            $this->mockCategoryRepo,
            $this->mockLogger
        );
    }

    public function testFindByIdWithValidId(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';
        $expectedCategory = [
            '_id' => $categoryId,
            'name' => 'Test Category',
            'slug' => 'test-category'
        ];

        $this->mockCategoryRepo->method('findById')
            ->with($categoryId)
            ->willReturn($expectedCategory);

        $result = $this->categoryService->findById($categoryId);

        $this->assertEquals($expectedCategory, $result);
    }

    public function testFindByIdWithInvalidId(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid category ID format');

        $this->categoryService->findById('invalid-id');
    }

    public function testCreateCategorySuccessfully(): void
    {
        $categoryData = [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Test description'
        ];

        $expectedId = '507f1f77bcf86cd799439012';

        $this->mockCategoryRepo->method('slugExists')
            ->with('new-category')
            ->willReturn(false);

        $this->mockCategoryRepo->method('create')
            ->willReturn($expectedId);

        $this->mockCategoryRepo->method('findById')
            ->with($expectedId)
            ->willReturn(array_merge($categoryData, ['_id' => $expectedId]));

        $result = $this->categoryService->create($categoryData);

        $this->assertEquals($expectedId, $result['_id']);
        $this->assertEquals('New Category', $result['name']);
    }

    public function testCreateCategoryWithExistingSlug(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Category slug already exists');

        $categoryData = [
            'name' => 'New Category',
            'slug' => 'existing-slug'
        ];

        $this->mockCategoryRepo->method('slugExists')
            ->with('existing-slug')
            ->willReturn(true);

        $this->categoryService->create($categoryData);
    }

    public function testUpdateCategorySuccessfully(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';
        $updateData = ['name' => 'Updated Category'];

        $this->mockCategoryRepo->method('findById')
            ->with($categoryId)
            ->willReturn(['_id' => $categoryId, 'name' => 'Old Category']);

        $this->mockCategoryRepo->method('update')
            ->with($categoryId, $this->arrayHasKey('updatedAt'))
            ->willReturn(true);

        $result = $this->categoryService->update($categoryId, $updateData);

        $this->assertTrue($result);
    }

    public function testDeleteCategorySuccessfully(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';

        $this->mockCategoryRepo->method('findById')
            ->with($categoryId)
            ->willReturn(['_id' => $categoryId]);

        $this->mockCategoryRepo->method('findByParentId')
            ->with($categoryId)
            ->willReturn([]);

        $this->mockCategoryRepo->method('delete')
            ->with($categoryId)
            ->willReturn(true);

        $result = $this->categoryService->delete($categoryId);

        $this->assertTrue($result);
    }

    public function testDeleteCategoryWithSubcategories(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete category with subcategories');

        $categoryId = '507f1f77bcf86cd799439011';

        $this->mockCategoryRepo->method('findById')
            ->with($categoryId)
            ->willReturn(['_id' => $categoryId]);

        $this->mockCategoryRepo->method('findByParentId')
            ->with($categoryId)
            ->willReturn([['_id' => 'subcategory1']]);

        $this->categoryService->delete($categoryId);
    }

    public function testGetCategoryTree(): void
    {
        $expectedTree = [
            [
                '_id' => 'root1',
                'name' => 'Root Category',
                'children' => [
                    [
                        '_id' => 'child1',
                        'name' => 'Child Category'
                    ]
                ]
            ]
        ];

        $this->mockCategoryRepo->method('getCategoryTree')
            ->willReturn($expectedTree);

        $result = $this->categoryService->getCategoryTree();

        $this->assertEquals($expectedTree, $result);
    }

    public function testGetSubcategories(): void
    {
        $parentId = '507f1f77bcf86cd799439011';
        $expectedSubcategories = [
            ['_id' => 'child1', 'name' => 'Child 1'],
            ['_id' => 'child2', 'name' => 'Child 2']
        ];

        $this->mockCategoryRepo->method('findByParentId')
            ->with($parentId)
            ->willReturn($expectedSubcategories);

        $result = $this->categoryService->getSubcategories($parentId);

        $this->assertEquals($expectedSubcategories, $result);
    }

    public function testValidateCategoryData(): void
    {
        $validData = [
            'name' => 'Valid Category',
            'slug' => 'valid-category'
        ];

        $result = $this->categoryService->validate($validData);

        $this->assertTrue($result);
    }

    public function testBulkUpdateStatus(): void
    {
        $categoryIds = ['id1', 'id2', 'id3'];
        $active = true;

        $this->mockCategoryRepo->method('bulkUpdateStatus')
            ->with($categoryIds, $active)
            ->willReturn(true);

        $result = $this->categoryService->bulkUpdateStatus($categoryIds, $active);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
    }

    public function testGetCategoryStatistics(): void
    {
        $mockStats = [
            ['_id' => 'cat1', 'name' => 'Category 1', 'itemCount' => 5],
            ['_id' => 'cat2', 'name' => 'Category 2', 'itemCount' => 3]
        ];

        $this->mockCategoryRepo->method('getCategoriesWithCounts')
            ->willReturn($mockStats);

        $this->mockCategoryRepo->method('count')
            ->willReturn(2);

        $this->mockCategoryRepo->method('findRootCategories')
            ->willReturn([['_id' => 'root1']]);

        $result = $this->categoryService->getCategoryStatistics();

        $this->assertEquals(2, $result['totalCategories']);
        $this->assertArrayHasKey('categoriesByDepth', $result);
    }

    public function testSlugExists(): void
    {
        $slug = 'test-slug';

        $this->mockCategoryRepo->method('slugExists')
            ->with($slug, null)
            ->willReturn(true);

        $result = $this->categoryService->slugExists($slug);

        $this->assertTrue($result);
    }

    public function testCategoryExists(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';

        $this->mockCategoryRepo->method('findById')
            ->with($categoryId)
            ->willReturn(['_id' => $categoryId]);

        $result = $this->categoryService->categoryExists($categoryId);

        $this->assertTrue($result);
    }
}