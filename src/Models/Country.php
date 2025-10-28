<?php

namespace App\Models;

use App\Database\Database;
use App\Exceptions\NotFoundException;

/**
 * Country Model
 */
class Country
{
    private $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all countries with optional filtering and sorting
     *
     * @param array $filters
     * @param string|null $sort
     * @return array
     */
    public function all($filters = [], $sort = null)
    {
        $sql = "SELECT * FROM countries WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['region'])) {
            $sql .= " AND region = :region";
            $params[':region'] = $filters['region'];
        }

        if (!empty($filters['currency'])) {
            $sql .= " AND currency_code = :currency";
            $params[':currency'] = $filters['currency'];
        }

        // Apply sorting
        if ($sort === 'gdp_desc') {
            $sql .= " ORDER BY estimated_gdp DESC";
        } else {
            $sql .= " ORDER BY name ASC";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Find country by name (case-insensitive)
     *
     * @param string $name
     * @return array|null
     */
    public function findByName($name)
    {
        $sql = "SELECT * FROM countries WHERE LOWER(name) = LOWER(:name) LIMIT 1";
        $result = $this->db->fetchOne($sql, [':name' => $name]);
        
        return $result ?: null;
    }

    /**
     * Upsert country (insert or update based on name)
     *
     * @param array $data
     * @return bool
     */
    public function upsert($data)
    {
        $existing = $this->findByName($data['name']);

        if ($existing) {
            // Update existing record
            return $this->update($existing['id'], $data);
        } else {
            // Insert new record
            return $this->insert($data);
        }
    }

    /**
     * Insert new country
     *
     * @param array $data
     * @return bool
     */
    private function insert($data)
    {
        $sql = "INSERT INTO countries (
            name, capital, region, population, 
            currency_code, exchange_rate, estimated_gdp, flag_url,
            last_refreshed_at, created_at, updated_at
        ) VALUES (
            :name, :capital, :region, :population,
            :currency_code, :exchange_rate, :estimated_gdp, :flag_url,
            datetime('now'), datetime('now'), datetime('now')
        )";

        $params = [
            ':name' => $data['name'],
            ':capital' => $data['capital'] ?? null,
            ':region' => $data['region'] ?? null,
            ':population' => $data['population'],
            ':currency_code' => $data['currency_code'] ?? null,
            ':exchange_rate' => $data['exchange_rate'] ?? null,
            ':estimated_gdp' => $data['estimated_gdp'] ?? null,
            ':flag_url' => $data['flag_url'] ?? null,
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Update existing country
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    private function update($id, $data)
    {
        $sql = "UPDATE countries SET
            capital = :capital,
            region = :region,
            population = :population,
            currency_code = :currency_code,
            exchange_rate = :exchange_rate,
            estimated_gdp = :estimated_gdp,
            flag_url = :flag_url,
            last_refreshed_at = datetime('now'),
            updated_at = datetime('now')
        WHERE id = :id";

        $params = [
            ':id' => $id,
            ':capital' => $data['capital'] ?? null,
            ':region' => $data['region'] ?? null,
            ':population' => $data['population'],
            ':currency_code' => $data['currency_code'] ?? null,
            ':exchange_rate' => $data['exchange_rate'] ?? null,
            ':estimated_gdp' => $data['estimated_gdp'] ?? null,
            ':flag_url' => $data['flag_url'] ?? null,
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Delete country by name
     *
     * @param string $name
     * @return bool
     * @throws NotFoundException
     */
    public function deleteByName($name)
    {
        $country = $this->findByName($name);
        
        if (!$country) {
            throw new NotFoundException("Country '{$name}' not found");
        }

        $sql = "DELETE FROM countries WHERE id = :id";
        return $this->db->execute($sql, [':id' => $country['id']]) > 0;
    }

    /**
     * Get total count of countries
     *
     * @return int
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as total FROM countries";
        $result = $this->db->fetchOne($sql);
        return (int) $result['total'];
    }

    /**
     * Get top N countries by GDP
     *
     * @param int $limit
     * @return array
     */
    public function topByGdp($limit = 5)
    {
        $sql = "SELECT * FROM countries 
                WHERE estimated_gdp IS NOT NULL 
                ORDER BY estimated_gdp DESC 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [':limit' => $limit]);
    }

    /**
     * Update API status
     *
     * @return bool
     */
    public function updateApiStatus()
    {
        $total = $this->count();
        
        $sql = "UPDATE api_status SET
            total_countries = :total,
            last_refreshed_at = datetime('now'),
            updated_at = datetime('now')
        WHERE id = 1";

        return $this->db->execute($sql, [':total' => $total]) > 0;
    }

    /**
     * Get API status
     *
     * @return array|null
     */
    public function getApiStatus()
    {
        $sql = "SELECT * FROM api_status WHERE id = 1";
        $status = $this->db->fetchOne($sql);
        
        if ($status && !empty($status['last_refreshed_at'])) {
            // Convert SQLite datetime to ISO 8601 format
            $status['last_refreshed_at'] = $this->formatTimestamp($status['last_refreshed_at']);
        }
        
        return $status;
    }
    
    /**
     * Format timestamp to ISO 8601
     *
     * @param string|null $timestamp
     * @return string|null
     */
    private function formatTimestamp($timestamp)
    {
        if (empty($timestamp)) {
            return null;
        }
        
        try {
            $dt = new \DateTime($timestamp, new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception $e) {
            return $timestamp; // Return original if parsing fails
        }
    }
}
