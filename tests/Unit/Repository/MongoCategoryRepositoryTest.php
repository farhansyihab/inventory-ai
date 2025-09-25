<?php
declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Repository\MongoCategoryRepository;
use App\Utility\Logger;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MongoCategoryRepositoryTest extends TestCase
{
    private MongoCategoryRepository $repository;
    private Logger $logger;
    private Collection $mockCollection;
    private Database $mockDatabase;

    protected function setUp(): void
    {
        // Mock Logger
        $this->logger = $this->createMock(Logger::class);

        // Mock MongoDB Collection
        $this->mockCollection = $this->createMock(Collection::class);

        // Create repository instance
        $this->repository = new MongoCategoryRepository($this->logger);

        // Inject mock collection using reflection
        $reflection = new ReflectionClass($this->repository);
        $property = $reflection->getProperty('collection');
        $property->setAccessible(true);
        $property->setValue($this->repository, $this->mockCollection);
    }

    public function testCreateIndexes(): void
    {
        $this->mockCollection->expects($this->once())
            ->method('createIndexes')
            ->willReturn(['slug_1', 'active_1']);

        $result = $this->repository->createIndexes();

        $this->assertIsArray($result);
        $this->assertContains('slug_1', $result);
    }

    public function testFindByIdSuccess(): void
    {
        $objectId = new ObjectId();
        $document = [
            '_id' => $objectId,
            'name' => 'Test Category',
            'slug' => 'test-category',
            'active' => true
        ];

        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => $objectId])
            ->willReturn($document);

        $result = $this->repository->findById((string) $objectId);

        $this->assertIsArray($result);
        $this->assertEquals('Test Category', $result['name']);
        $this->assertEquals('test-category', $result['slug']);
    }

    public function testFindByIdNotFound(): void
    {
        $objectId = new ObjectId();

        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => $objectId])
            ->willReturn(null);

        $result = $this->repository->findById((string) $objectId);

        $this->assertNull($result);
    }

    public function testFindByIdWithException(): void
    {
        $objectId = new ObjectId();

        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->willThrowException(new RuntimeException('Connection failed'));

        $this->logger->expects($this->once())
            ->method('error');

        $result = $this->repository->findById((string) $objectId);

        $this->assertNull($result);
    }

    public function testCreateCategorySuccess(): void
    {
        $categoryData = [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Test description',
            'active' => true
        ];

        $insertResult = $this->createMock(\MongoDB\InsertOneResult::class);
        $insertResult->method('getInsertedId')
            ->willReturn(new ObjectId());

        $this->mockCollection->expects($this->once())
            ->method('insertOne')
            ->willReturn($insertResult);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->repository->create($categoryData);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testCreateCategoryMissingRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidData = [
            'description' => 'Missing name and slug'
        ];

        $this->repository->create($invalidData);
    }

    public function testUpdateCategorySuccess(): void
    {
        $objectId = new ObjectId();
        $updateData = ['name' => 'Updated Name'];

        $updateResult = $this->createMock(\MongoDB\UpdateResult::class);
        $updateResult->method('getModifiedCount')
            ->willReturn(1);

        $this->mockCollection->expects($this->once())
            ->method('updateOne')
            ->willReturn($updateResult);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->repository->update((string) $objectId, $updateData);

        $this->assertTrue($result);
    }

    public function testUpdateCategoryNoChanges(): void
    {
        $objectId = new ObjectId();
        $updateData = ['name' => 'Same Name'];

        $updateResult = $this->createMock(\MongoDB\UpdateResult::class);
        $updateResult->method('getModifiedCount')
            ->willReturn(0);

        $this->mockCollection->expects($this->once())
            ->method('updateOne')
            ->willReturn($updateResult);

        $result = $this->repository->update((string) $objectId, $updateData);

        $this->assertFalse($result);
    }

    public function testDeleteCategorySuccess(): void
    {
        $objectId = new ObjectId();

        $deleteResult = $this->createMock(\MongoDB\DeleteResult::class);
        $deleteResult->method('getDeletedCount')
            ->willReturn(1);

        $this->mockCollection->expects($this->once())
            ->method('deleteOne')
            ->willReturn($deleteResult);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->repository->delete((string) $objectId);

        $this->assertTrue($result);
    }

    public function testFindBySlug(): void
    {
        $document = [
            '_id' => new ObjectId(),
            'name' => 'Test Category',
            'slug' => 'test-category',
            'active' => true
        ];

        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['slug' => 'test-category', 'active' => true])
            ->willReturn($document);

        $result = $this->repository->findBySlug('test-category');

        $this->assertIsArray($result);
        $this->assertEquals('test-category', $result['slug']);
    }

    public function testFindActiveCategories(): void
    {
        // Create a mock CursorInterface
        $mockCursor = $this->createMock(CursorInterface::class);
        
        // Setup iterator behavior for the cursor
        $documents = [
            ['_id' => new ObjectId(), 'name' => 'Cat1', 'active' => true],
            ['_id' => new ObjectId(), 'name' => 'Cat2', 'active' => true],
        ];
        
        $mockCursor->expects($this->exactly(3))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, true, false);
            
        $mockCursor->expects($this->exactly(2))
            ->method('current')
            ->willReturnOnConsecutiveCalls($documents[0], $documents[1]);
            
        $mockCursor->expects($this->exactly(2))
            ->method('next');

        $this->mockCollection->expects($this->once())
            ->method('find')
            ->with(['active' => true], ['sort' => ['name' => 1]])
            ->willReturn($mockCursor);

        $result = $this->repository->findActive();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testFindByParentId(): void
    {
        $parentId = '507f1f77bcf86cd799439011';

        // Create a mock CursorInterface
        $mockCursor = $this->createMock(CursorInterface::class);
        
        $documents = [
            ['_id' => new ObjectId(), 'name' => 'Child1', 'parentId' => $parentId]
        ];
        
        $mockCursor->expects($this->exactly(2))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, false);
            
        $mockCursor->expects($this->once())
            ->method('current')
            ->willReturn($documents[0]);
            
        $mockCursor->expects($this->once())
            ->method('next');

        $this->mockCollection->expects($this->once())
            ->method('find')
            ->with(['parentId' => $parentId, 'active' => true], ['sort' => ['name' => 1]])
            ->willReturn($mockCursor);

        $result = $this->repository->findByParentId($parentId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testFindRootCategories(): void
    {
        // Create a mock CursorInterface
        $mockCursor = $this->createMock(CursorInterface::class);
        
        $documents = [
            ['_id' => new ObjectId(), 'name' => 'Root1', 'parentId' => null]
        ];
        
        $mockCursor->expects($this->exactly(2))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, false);
            
        $mockCursor->expects($this->once())
            ->method('current')
            ->willReturn($documents[0]);
            
        $mockCursor->expects($this->once())
            ->method('next');

        $this->mockCollection->expects($this->once())
            ->method('find')
            ->with(['parentId' => null, 'active' => true], ['sort' => ['name' => 1]])
            ->willReturn($mockCursor);

        $result = $this->repository->findRootCategories();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testSlugExists(): void
    {
        $this->mockCollection->expects($this->once())
            ->method('countDocuments')
            ->willReturn(1);

        $result = $this->repository->slugExists('existing-slug');

        $this->assertTrue($result);
    }

    public function testSlugExistsWithExcludeId(): void
    {
        $excludeId = '507f1f77bcf86cd799439011';

        $this->mockCollection->expects($this->once())
            ->method('countDocuments')
            ->with([
                'slug' => 'test-slug',
                '_id' => ['$ne' => new ObjectId($excludeId)]
            ])
            ->willReturn(0);

        $result = $this->repository->slugExists('test-slug', $excludeId);

        $this->assertFalse($result);
    }

    public function testBulkUpdateStatus(): void
    {
        $categoryIds = ['507f1f77bcf86cd799439011', '507f1f77bcf86cd799439012'];

        $updateResult = $this->createMock(\MongoDB\UpdateResult::class);
        $updateResult->method('getModifiedCount')
            ->willReturn(2);

        $this->mockCollection->expects($this->once())
            ->method('updateMany')
            ->willReturn($updateResult);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->repository->bulkUpdateStatus($categoryIds, false);

        $this->assertTrue($result);
    }

    public function testCountDocuments(): void
    {
        $this->mockCollection->expects($this->once())
            ->method('countDocuments')
            ->with(['active' => true])
            ->willReturn(5);

        $result = $this->repository->count(['active' => true]);

        $this->assertEquals(5, $result);
    }

    // Helper method untuk membuat mock cursor dengan data
    private function createMockCursor(array $documents): CursorInterface
    {
        $mockCursor = $this->createMock(CursorInterface::class);
        
        $iterator = new \ArrayIterator($documents);
        
        $mockCursor->expects($this->exactly(count($documents) + 1))
            ->method('valid')
            ->willReturnCallback(function() use ($iterator) {
                return $iterator->valid();
            });
            
        $mockCursor->expects($this->exactly(count($documents)))
            ->method('current')
            ->willReturnCallback(function() use ($iterator) {
                return $iterator->current();
            });
            
        $mockCursor->expects($this->exactly(count($documents)))
            ->method('next')
            ->willReturnCallback(function() use ($iterator) {
                $iterator->next();
            });
            
        $mockCursor->expects($this->once())
            ->method('rewind')
            ->willReturnCallback(function() use ($iterator) {
                $iterator->rewind();
            });

        return $mockCursor;
    }
}