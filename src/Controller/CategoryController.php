<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CategoryService;
use App\Utility\Logger;
use Exception;

class CategoryController extends BaseController
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService, Logger $logger = null)
    {
        parent::__construct($logger);
        $this->categoryService = $categoryService;
        
        $this->logger->info('CategoryController initialized', [
            'service' => get_class($categoryService)
        ]);
    }

    // ==================== RESTFUL API ENDPOINTS ====================

    /**
     * GET /categories - List all categories with pagination and filtering
     */
    public function listCategories(): void
    {
        try {
            $this->logAction('list_categories_started');
            
            // Get pagination parameters
            $pagination = $this->getPaginationParams();
            $page = $pagination['page'];
            $limit = $pagination['limit'];
            
            // Get filtering parameters
            $filter = $this->buildFilterFromRequest();
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => $this->getSortingParams()
            ];

            $this->logger->debug('CategoryController: Listing categories', [
                'page' => $page,
                'limit' => $limit,
                'filter' => $filter
            ]);

            // Get categories
            $categories = $this->categoryService->find($filter, $options);
            $totalCount = $this->categoryService->count($filter);

            // Build pagination metadata
            $totalPages = ceil($totalCount / $limit);
            $paginationMeta = [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1
            ];

            $this->logAction('list_categories_success', [
                'count' => count($categories),
                'total' => $totalCount
            ]);

            $this->successResponse([
                'categories' => $categories,
                'pagination' => $paginationMeta
            ], 'Categories retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('list_categories_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve categories: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /categories/{id} - Get a specific category by ID
     */
    public function getCategory(string $id): void
    {
        try {
            $this->logAction('get_category_started', ['id' => $id]);

            $this->validateCategoryId($id);

            $category = $this->categoryService->findById($id);
            
            if (!$category) {
                $this->logAction('get_category_not_found', ['id' => $id]);
                $this->notFoundResponse('Category not found');
                return;
            }

            $this->logAction('get_category_success', ['id' => $id]);

            $this->successResponse([
                'category' => $category
            ], 'Category retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_category_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve category: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * GET /categories/slug/{slug} - Get a category by slug
     */
    public function getCategoryBySlug(string $slug): void
    {
        try {
            $this->logAction('get_category_by_slug_started', ['slug' => $slug]);

            if (empty($slug)) {
                $this->errorResponse('Slug parameter is required', [], 400);
                return;
            }

            $category = $this->categoryService->findBySlug($slug);
            
            if (!$category) {
                $this->logAction('get_category_by_slug_not_found', ['slug' => $slug]);
                $this->notFoundResponse('Category not found');
                return;
            }

            $this->logAction('get_category_by_slug_success', ['slug' => $slug]);

            $this->successResponse([
                'category' => $category
            ], 'Category retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_category_by_slug_error', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve category: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * POST /categories - Create a new category
     */
    public function createCategory(): void
    {
        try {
            $this->logAction('create_category_started');

            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->unauthorizedResponse('Authentication required');
                return;
            }

            // Get and validate request data
            $requestData = $this->getRequestData();
            $validation = $this->validateCreateCategoryData($requestData);
            
            if (!$validation['valid']) {
                $this->logAction('create_category_validation_failed', [
                    'errors' => $validation['errors']
                ]);
                $this->validationErrorResponse($validation['errors'], 'Category validation failed');
                return;
            }

            // Create category
            $category = $this->categoryService->create($requestData);

            $this->logAction('create_category_success', [
                'id' => $category['_id'] ?? 'unknown',
                'name' => $category['name'] ?? 'unknown'
            ]);

            $this->successResponse([
                'category' => $category
            ], 'Category created successfully', 201);

        } catch (Exception $e) {
            $this->logAction('create_category_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to create category: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * PUT /categories/{id} - Update an existing category
     */
    public function updateCategory(string $id): void
    {
        try {
            $this->logAction('update_category_started', ['id' => $id]);

            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->unauthorizedResponse('Authentication required');
                return;
            }

            $this->validateCategoryId($id);

            // Check if category exists
            if (!$this->categoryService->categoryExists($id)) {
                $this->notFoundResponse('Category not found');
                return;
            }

            // Get and validate request data
            $requestData = $this->getRequestData();
            $validation = $this->validateUpdateCategoryData($requestData);
            
            if (!$validation['valid']) {
                $this->logAction('update_category_validation_failed', [
                    'id' => $id,
                    'errors' => $validation['errors']
                ]);
                $this->validationErrorResponse($validation['errors'], 'Category validation failed');
                return;
            }

            // Update category
            $success = $this->categoryService->update($id, $requestData);
            
            if (!$success) {
                $this->errorResponse('Failed to update category', [], 500);
                return;
            }

            // Get updated category
            $updatedCategory = $this->categoryService->findById($id);

            $this->logAction('update_category_success', ['id' => $id]);

            $this->successResponse([
                'category' => $updatedCategory
            ], 'Category updated successfully');

        } catch (Exception $e) {
            $this->logAction('update_category_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to update category: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * DELETE /categories/{id} - Delete a category
     */
    public function deleteCategory(string $id): void
    {
        try {
            $this->logAction('delete_category_started', ['id' => $id]);

            // Check authentication and authorization
            if (!$this->isAuthenticated()) {
                $this->unauthorizedResponse('Authentication required');
                return;
            }

            $this->validateCategoryId($id);

            // Check if category exists
            if (!$this->categoryService->categoryExists($id)) {
                $this->notFoundResponse('Category not found');
                return;
            }

            // Delete category
            $success = $this->categoryService->delete($id);
            
            if (!$success) {
                $this->errorResponse('Failed to delete category', [], 500);
                return;
            }

            $this->logAction('delete_category_success', ['id' => $id]);

            $this->successResponse([], 'Category deleted successfully');

        } catch (Exception $e) {
            $this->logAction('delete_category_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to delete category: ' . $e->getMessage(), [], 400);
        }
    }

    // ==================== SPECIALIZED ENDPOINTS ====================

    /**
     * GET /categories/tree - Get complete category hierarchy tree
     */
    public function getCategoryTree(): void
    {
        try {
            $this->logAction('get_category_tree_started');

            $categoryTree = $this->categoryService->getCategoryTree();

            $this->logAction('get_category_tree_success', [
                'rootCategories' => count($categoryTree)
            ]);

            $this->successResponse([
                'tree' => $categoryTree
            ], 'Category tree retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_category_tree_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve category tree: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /categories/{id}/subcategories - Get subcategories of a category
     */
    public function getSubcategories(string $id): void
    {
        try {
            $this->logAction('get_subcategories_started', ['id' => $id]);

            $this->validateCategoryId($id);

            // Check if category exists
            if (!$this->categoryService->categoryExists($id)) {
                $this->notFoundResponse('Category not found');
                return;
            }

            $subcategories = $this->categoryService->getSubcategories($id);

            $this->logAction('get_subcategories_success', [
                'id' => $id,
                'count' => count($subcategories)
            ]);

            $this->successResponse([
                'parentId' => $id,
                'subcategories' => $subcategories,
                'count' => count($subcategories)
            ], 'Subcategories retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_subcategories_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve subcategories: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * GET /categories/roots - Get root categories (no parent)
     */
    public function getRootCategories(): void
    {
        try {
            $this->logAction('get_root_categories_started');

            $rootCategories = $this->categoryService->getRootCategories();

            $this->logAction('get_root_categories_success', [
                'count' => count($rootCategories)
            ]);

            $this->successResponse([
                'rootCategories' => $rootCategories,
                'count' => count($rootCategories)
            ], 'Root categories retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_root_categories_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve root categories: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /categories/{id}/path - Get category path (ancestors)
     */
    public function getCategoryPath(string $id): void
    {
        try {
            $this->logAction('get_category_path_started', ['id' => $id]);

            $this->validateCategoryId($id);

            // Check if category exists
            if (!$this->categoryService->categoryExists($id)) {
                $this->notFoundResponse('Category not found');
                return;
            }

            $path = $this->categoryService->getCategoryPath($id);

            $this->logAction('get_category_path_success', [
                'id' => $id,
                'pathLength' => count($path)
            ]);

            $this->successResponse([
                'categoryId' => $id,
                'path' => $path,
                'depth' => count($path)
            ], 'Category path retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_category_path_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve category path: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * POST /categories/{id}/move - Move category to new parent
     */
    public function moveCategory(string $id): void
    {
        try {
            $this->logAction('move_category_started', ['id' => $id]);

            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->unauthorizedResponse('Authentication required');
                return;
            }

            $this->validateCategoryId($id);

            $requestData = $this->getRequestData();
            $newParentId = $requestData['newParentId'] ?? null;

            if (empty($newParentId)) {
                $this->errorResponse('newParentId is required', [], 400);
                return;
            }

            $this->validateCategoryId($newParentId);

            $movedCategory = $this->categoryService->moveCategory($id, $newParentId);

            $this->logAction('move_category_success', [
                'id' => $id,
                'newParentId' => $newParentId
            ]);

            $this->successResponse([
                'category' => $movedCategory
            ], 'Category moved successfully');

        } catch (Exception $e) {
            $this->logAction('move_category_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to move category: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * PATCH /categories/bulk-status - Bulk update category status
     */
    public function bulkUpdateStatus(): void
    {
        try {
            $this->logAction('bulk_update_status_started');

            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->unauthorizedResponse('Authentication required');
                return;
            }

            $requestData = $this->getRequestData();
            $categoryIds = $requestData['categoryIds'] ?? [];
            $active = filter_var($requestData['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            if (empty($categoryIds)) {
                $this->errorResponse('categoryIds array is required', [], 400);
                return;
            }

            if (!is_array($categoryIds)) {
                $this->errorResponse('categoryIds must be an array', [], 400);
                return;
            }

            // Validate all category IDs
            foreach ($categoryIds as $categoryId) {
                $this->validateCategoryId($categoryId);
            }

            $result = $this->categoryService->bulkUpdateStatus($categoryIds, $active);

            $this->logAction('bulk_update_status_success', [
                'processed' => count($categoryIds),
                'active' => $active
            ]);

            $this->successResponse($result, 'Bulk status update completed');

        } catch (Exception $e) {
            $this->logAction('bulk_update_status_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to update category status: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * GET /categories/statistics - Get category statistics
     */
    public function getStatistics(): void
    {
        try {
            $this->logAction('get_statistics_started');

            $statistics = $this->categoryService->getCategoryStatistics();

            $this->logAction('get_statistics_success', [
                'totalCategories' => $statistics['totalCategories'] ?? 0
            ]);

            $this->successResponse([
                'statistics' => $statistics
            ], 'Category statistics retrieved successfully');

        } catch (Exception $e) {
            $this->logAction('get_statistics_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to retrieve category statistics: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /categories/search - Search categories by name or slug
     */
    public function searchCategories(): void
    {
        try {
            $this->logAction('search_categories_started');

            $query = $this->getRequestValue('q', '');
            $searchField = $this->getRequestValue('field', 'both'); // 'name', 'slug', or 'both'

            if (empty($query)) {
                $this->errorResponse('Search query is required', [], 400);
                return;
            }

            $filter = [];
            $searchQuery = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $query);
            $searchRegex = ['$regex' => $searchQuery, '$options' => 'i'];

            switch ($searchField) {
                case 'name':
                    $filter['name'] = $searchRegex;
                    break;
                case 'slug':
                    $filter['slug'] = $searchRegex;
                    break;
                case 'both':
                default:
                    $filter['$or'] = [
                        ['name' => $searchRegex],
                        ['slug' => $searchRegex]
                    ];
                    break;
            }

            $categories = $this->categoryService->find($filter, ['limit' => 50]);

            $this->logAction('search_categories_success', [
                'query' => $query,
                'field' => $searchField,
                'results' => count($categories)
            ]);

            $this->successResponse([
                'query' => $query,
                'field' => $searchField,
                'results' => $categories,
                'count' => count($categories)
            ], 'Search completed successfully');

        } catch (Exception $e) {
            $this->logAction('search_categories_error', [
                'error' => $e->getMessage()
            ]);
            $this->errorResponse('Failed to search categories: ' . $e->getMessage(), [], 400);
        }
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validate category ID format
     */
    private function validateCategoryId(string $id): void
    {
        if (empty($id)) {
            throw new Exception('Category ID is required');
        }

        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            throw new Exception('Invalid category ID format');
        }
    }

    /**
     * Validate data for category creation
     */
    private function validateCreateCategoryData(array $data): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['name', 'slug'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "{$field} is required";
            }
        }

        // Basic field validation
        if (isset($data['name']) && strlen(trim($data['name'])) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        }

        if (isset($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens';
        }

        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors[] = 'Description cannot exceed 500 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate data for category update
     */
    private function validateUpdateCategoryData(array $data): array
    {
        $errors = [];

        if (empty($data)) {
            $errors[] = 'No data provided for update';
        }

        // Field validation
        if (isset($data['name']) && strlen(trim($data['name'])) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        }

        if (isset($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens';
        }

        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors[] = 'Description cannot exceed 500 characters';
        }

        if (isset($data['active']) && !is_bool($data['active'])) {
            $errors[] = 'Active must be a boolean value';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Build filter from request parameters
     */
    private function buildFilterFromRequest(): array
    {
        $filter = [];

        // Active filter
        $active = $this->getRequestValue('active', '');
        if ($active !== '') {
            $filter['active'] = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        }

        // Parent ID filter
        $parentId = $this->getRequestValue('parentId', '');
        if ($parentId === 'null' || $parentId === 'root') {
            $filter['parentId'] = null;
        } elseif (!empty($parentId)) {
            if (preg_match('/^[a-f\d]{24}$/i', $parentId)) {
                $filter['parentId'] = $parentId;
            }
        }

        // Depth filter
        $depth = $this->getRequestValue('depth', '');
        if (is_numeric($depth)) {
            $filter['depth'] = (int)$depth;
        }

        // Search filter
        $search = $this->getRequestValue('search', '');
        if (!empty($search)) {
            $searchRegex = ['$regex' => preg_quote($search), '$options' => 'i'];
            $filter['$or'] = [
                ['name' => $searchRegex],
                ['slug' => $searchRegex],
                ['description' => $searchRegex]
            ];
        }

        return $filter;
    }

    /**
     * Log controller actions
     */
    private function logAction(string $action, array $context = []): void
    {
        $userId = $this->getAuthUserId();
        
        $logContext = array_merge([
            'action' => $action,
            'userId' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context);

        $this->logger->info("CategoryController: {$action}", $logContext);
    }
}