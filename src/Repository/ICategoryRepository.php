<?php
declare(strict_types=1);

namespace App\Repository;

/**
 * Interface khusus untuk Category Repository
 * Extends IRepository dengan tambahan method khusus kategori
 */
interface ICategoryRepository extends IRepository
{
    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?array;

    /**
     * Find active categories only
     */
    public function findActive(): array;

    /**
     * Find categories by parent ID
     */
    public function findByParentId(?string $parentId): array;

    /**
     * Find root categories (no parent)
     */
    public function findRootCategories(): array;

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): array;

    /**
     * Find categories by depth level
     */
    public function findByDepth(int $depth): array;

    /**
     * Update category path and depth
     */
    public function updatePath(string $categoryId, array $path, int $depth): bool;

    /**
     * Check if slug exists (excluding current category)
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool;

    /**
     * Get categories with item counts
     */
    public function getCategoriesWithCounts(): array;

    /**
     * Bulk update category status
     */
    public function bulkUpdateStatus(array $categoryIds, bool $active): bool;
}