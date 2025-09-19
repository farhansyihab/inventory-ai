<?php
declare(strict_types=1);

namespace App\Repository;

/**
 * Interface untuk repository pattern
 * Menyediakan contract dasar untuk CRUD operations
 * 
 * @template T Entity type
 */
interface IRepository
{
    /**
     * Find document by ID
     * 
     * @param string $id Document ID
     * @return array|null Document data atau null jika tidak ditemukan
     */
    public function findById(string $id): ?array;

    /**
     * Find documents berdasarkan filter
     * 
     * @param array $filter Query filter
     * @param array $options Find options
     * @return array Array of documents
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new document
     * 
     * @param array $data Document data
     * @return string ID dari document yang dibuat
     */
    public function create(array $data): string;

    /**
     * Update document by ID
     * 
     * @param string $id Document ID
     * @param array $data Update data
     * @return bool True jika update berhasil
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete document by ID
     * 
     * @param string $id Document ID
     * @return bool True jika delete berhasil
     */
    public function delete(string $id): bool;

    /**
     * Count documents berdasarkan filter
     * 
     * @param array $filter Query filter
     * @return int Jumlah documents
     */
    public function count(array $filter = []): int;

    /**
     * Find one document berdasarkan filter
     * 
     * @param array $filter Query filter
     * @return array|null Document data atau null
     */
    public function findOne(array $filter = []): ?array;
}