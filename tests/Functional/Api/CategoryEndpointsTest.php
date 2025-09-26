<?php
declare(strict_types=1);

namespace Tests\Functional\Api;

use App\Controller\CategoryController;
use App\Service\CategoryService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class CategoryEndpointsTest extends TestCase
{
    private CategoryController $categoryController;
    private CategoryService $mockCategoryService;
    private Logger $mockLogger;

    protected function setUp(): void
    {
        $this->mockCategoryService = $this->createMock(CategoryService::class);
        $this->mockLogger = $this->createMock(Logger::class);
        
        $this->categoryController = new CategoryController(
            $this->mockCategoryService,
            $this->mockLogger
        );

        // Enable test mode to capture responses instead of outputting
        $this->categoryController->enableTestMode();
    }

    public function testListCategoriesSuccess(): void
    {
        $mockCategories = [
            ['_id' => '1', 'name' => 'Category 1'],
            ['_id' => '2', 'name' => 'Category 2']
        ];

        $this->mockCategoryService->method('find')
            ->willReturn($mockCategories);

        $this->mockCategoryService->method('count')
            ->willReturn(2);

        // Simulate request data
        $this->categoryController->setRequestData(['page' => '1', 'limit' => '10']);

        $this->categoryController->listCategories();
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertEquals('Categories retrieved successfully', $response['message']);
        $this->assertCount(2, $response['data']['categories']);
    }

    public function testGetCategorySuccess(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';
        $mockCategory = [
            '_id' => $categoryId,
            'name' => 'Test Category',
            'slug' => 'test-category'
        ];

        $this->mockCategoryService->method('findById')
            ->with($categoryId)
            ->willReturn($mockCategory);

        $this->categoryController->getCategory($categoryId);
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertEquals($mockCategory, $response['data']['category']);
    }

    public function testGetCategoryNotFound(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';

        $this->mockCategoryService->method('findById')
            ->with($categoryId)
            ->willReturn(null);

        $this->categoryController->getCategory($categoryId);
        $response = $this->categoryController->getLastResponse();

        $this->assertEquals(404, $response['statusCode']);
        $this->assertStringContainsString('not found', $response['message']);
    }

    public function testCreateCategorySuccess(): void
    {
        $categoryData = [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Test description'
        ];

        $createdCategory = array_merge($categoryData, ['_id' => '507f1f77bcf86cd799439012']);

        $this->mockCategoryService->method('create')
            ->with($categoryData)
            ->willReturn($createdCategory);

        // Simulate authenticated request
        $this->categoryController->setRequestData($categoryData);

        $this->categoryController->createCategory();
        $response = $this->categoryController->getLastResponse();

        $this->assertEquals(201, $response['statusCode']);
        $this->assertTrue($response['success']);
    }

    public function testUpdateCategorySuccess(): void
    {
        $categoryId = '507f1f77bcf86cd799439011';
        $updateData = ['name' => 'Updated Category'];

        $this->mockCategoryService->method('categoryExists')
            ->with($categoryId)
            ->willReturn(true);

        $this->mockCategoryService->method('update')
            ->with($categoryId, $updateData)
            ->willReturn(true);

        $this->mockCategoryService->method('findById')
            ->with($categoryId)
            ->willReturn(array_merge($updateData, ['_id' => $categoryId]));

        $this->categoryController->setRequestData($updateData);
        $this->categoryController->updateCategory($categoryId);
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertEquals('Updated Category', $response['data']['category']['name']);
    }

    public function testGetCategoryTree(): void
    {
        $mockTree = [
            [
                '_id' => 'root1',
                'name' => 'Root Category',
                'children' => []
            ]
        ];

        $this->mockCategoryService->method('getCategoryTree')
            ->willReturn($mockTree);

        $this->categoryController->getCategoryTree();
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('tree', $response['data']);
    }

    public function testGetSubcategories(): void
    {
        $parentId = '507f1f77bcf86cd799439011';
        $mockSubcategories = [
            ['_id' => 'child1', 'name' => 'Child 1']
        ];

        $this->mockCategoryService->method('categoryExists')
            ->with($parentId)
            ->willReturn(true);

        $this->mockCategoryService->method('getSubcategories')
            ->with($parentId)
            ->willReturn($mockSubcategories);

        $this->categoryController->getSubcategories($parentId);
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertCount(1, $response['data']['subcategories']);
    }

    public function testBulkUpdateStatus(): void
    {
        // $this->markTestIncomplete('Bulk update status not fully implemented yet');
        $requestData = [
            'categoryIds' => ['id1', 'id2'],
            'active' => true
        ];

        $mockResult = ['success' => true, 'processed' => 2];

        $this->mockCategoryService->method('bulkUpdateStatus')
            ->with(['id1', 'id2'], true)
            ->willReturn($mockResult);

        $this->categoryController->setRequestData($requestData);
        $this->categoryController->bulkUpdateStatus();
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['success']);
    }

    public function testGetStatistics(): void
    {
        $mockStats = [
            'totalCategories' => 10,
            'activeCategories' => 8,
            'maxDepth' => 3
        ];

        $this->mockCategoryService->method('getCategoryStatistics')
            ->willReturn($mockStats);

        $this->categoryController->getStatistics();
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertEquals(10, $response['data']['statistics']['totalCategories']);
    }

    public function testSearchCategories(): void
    {
        $mockResults = [
            ['_id' => '1', 'name' => 'Test Category', 'slug' => 'test-category']
        ];

        $this->mockCategoryService->method('find')
            ->willReturn($mockResults);

        $this->categoryController->setRequestData(['q' => 'test']);
        $this->categoryController->searchCategories();
        $response = $this->categoryController->getLastResponse();

        $this->assertTrue($response['success']);
        $this->assertCount(1, $response['data']['results']);
    }




}