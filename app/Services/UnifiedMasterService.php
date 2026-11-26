<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Pager\Pager;

/**
 * UnifiedMasterService
 *
 * Combines existing SpiderWaves master tables (mst_*) with newly synced
 * UCC master tables (ucc_*, deleted_at IS NULL) without modifying or deleting
 * any existing data, while deduplicating overlapping records.
 */
class UnifiedMasterService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Build paginated results and configure CI4 Pager for a UNION subquery.
     */
    protected function paginateUnion(string $unionSql, array $params, int $perPage, string $orderBy, int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countRow = $this->db->query("SELECT COUNT(*) AS cnt FROM ({$unionSql}) AS unified", $params)->getRowArray();
        $total = (int)($countRow['cnt'] ?? 0);

        $rows = $this->db->query(
            "SELECT * FROM ({$unionSql}) AS unified ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        )->getResultArray();

        /** @var Pager $pager */
        $pager = \Config\Services::pager();
        $pager->store('default', $page, $perPage, $total);

        return [
            'rows'  => $rows,
            'pager' => $pager,
            'total' => $total,
        ];
    }

    // =========================================================================
    // CUSTOMERS (mst_customer + ucc_customer)
    // =========================================================================

    protected function getCustomerUnionSql(): string
    {
        return "
            SELECT
                m.cust_id,
                CONVERT(m.private_key USING utf8mb4) COLLATE utf8mb4_general_ci AS private_key,
                CONVERT(m.cust_name USING utf8mb4) COLLATE utf8mb4_general_ci AS cust_name,
                CONVERT(m.type USING utf8mb4) COLLATE utf8mb4_general_ci AS type,
                CAST(m.cust_status AS CHAR) COLLATE utf8mb4_general_ci AS cust_status,
                CONVERT(COALESCE(m.mobile_no, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS mobile_no,
                'spiderwaves' AS source
            FROM mst_customer m
            UNION ALL
            SELECT
                u.customer_id AS cust_id,
                u.private_key,
                u.customer_name AS cust_name,
                'cust' AS type,
                CAST(u.is_active AS CHAR) COLLATE utf8mb4_general_ci AS cust_status,
                '' AS mobile_no,
                'ucc' AS source
            FROM ucc_customer u
            WHERE u.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM mst_customer m2
                  WHERE CONVERT(m2.private_key USING utf8mb4) COLLATE utf8mb4_general_ci = u.private_key
                     OR LOWER(TRIM(CONVERT(m2.cust_name USING utf8mb4))) COLLATE utf8mb4_general_ci = LOWER(TRIM(u.customer_name)) COLLATE utf8mb4_general_ci
              )
        ";
    }

    public function getPaginatedCustomers(int $perPage = 10, ?string $search = null, ?string $type = null, ?string $status = null, int $page = 1, string $sort = ''): array
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(cust_name LIKE ? OR mobile_no LIKE ? OR private_key LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($type) && in_array($type, ['cust', 'vendor'], true)) {
            $where[] = "type = ?";
            $params[] = $type;
        }

        if ($status !== null && $status !== '') {
            $where[] = "cust_status = ?";
            $params[] = (string)$status;
        }

        $sql = "SELECT * FROM (" . $this->getCustomerUnionSql() . ") AS c";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $orderBy = match ($sort) {
            'name_asc'   => 'cust_name ASC, cust_id DESC',
            'name_desc'  => 'cust_name DESC, cust_id DESC',
            'id_asc'     => 'cust_id ASC, source ASC',
            'id_desc'    => 'cust_id DESC, source ASC',
            'status_asc' => 'cust_status DESC, cust_name ASC',
            default      => 'source ASC, cust_id DESC',
        };

        return $this->paginateUnion($sql, $params, $perPage, $orderBy, $page);
    }

    public function getCustomerSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_accounts,
                COALESCE(SUM(CASE WHEN type = 'cust' THEN 1 ELSE 0 END), 0) AS total_customers,
                COALESCE(SUM(CASE WHEN type = 'vendor' THEN 1 ELSE 0 END), 0) AS total_vendors,
                COALESCE(SUM(CASE WHEN cust_status = '1' THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN cust_status = '0' THEN 1 ELSE 0 END), 0) AS total_inactive
            FROM (" . $this->getCustomerUnionSql() . ") AS c
        ")->getRowArray();

        return [
            'total_accounts'  => (int)($row['total_accounts'] ?? 0),
            'total_customers' => (int)($row['total_customers'] ?? 0),
            'total_vendors'   => (int)($row['total_vendors'] ?? 0),
            'total_active'    => (int)($row['total_active'] ?? 0),
            'total_inactive'  => (int)($row['total_inactive'] ?? 0),
        ];
    }

    public function getActiveCustomers(?string $type = null): array
    {
        $where = "WHERE cust_status = '1'";
        $params = [];
        if (!empty($type) && in_array($type, ['cust', 'vendor'], true)) {
            $where .= " AND type = ?";
            $params[] = $type;
        }

        return $this->db->query("
            SELECT cust_id, private_key, cust_name, type, mobile_no, source
            FROM (" . $this->getCustomerUnionSql() . ") AS c
            {$where}
            ORDER BY cust_name ASC
        ", $params)->getResultArray();
    }

    public function findCustomerById(int $custId): ?array
    {
        $row = $this->db->query("
            SELECT *
            FROM (" . $this->getCustomerUnionSql() . ") AS c
            WHERE cust_id = ?
            LIMIT 1
        ", [$custId])->getRowArray();

        return $row ?: null;
    }

    public function searchActiveCustomers(string $search = '', int $limit = 30): array
    {
        $where = "WHERE cust_status = '1'";
        $params = [];
        if ($search !== '') {
            $where .= " AND cust_name LIKE ?";
            $params[] = "%{$search}%";
        }
        $limit = max(1, min(100, $limit));

        return $this->db->query("
            SELECT cust_id, private_key, cust_name, type, mobile_no, source
            FROM (" . $this->getCustomerUnionSql() . ") AS c
            {$where}
            ORDER BY cust_name ASC
            LIMIT {$limit}
        ", $params)->getResultArray();
    }

    // =========================================================================
    // MAPPED CUSTOMERS (mst_cust_mapping + ucc_mapped_customer)
    // =========================================================================

    protected function getMappedCustomerUnionSql(): string
    {
        return "
            SELECT
                m.mapped_cust_id,
                CONVERT(m.private_key USING utf8mb4) COLLATE utf8mb4_general_ci AS private_key,
                CONVERT(m.mapped_cust_name USING utf8mb4) COLLATE utf8mb4_general_ci AS mapped_cust_name,
                m.cust_id,
                m.collection_id,
                m.purity,
                CAST(m.mapped_cust_status AS CHAR) COLLATE utf8mb4_general_ci AS mapped_cust_status,
                CONVERT(mc.cust_name USING utf8mb4) COLLATE utf8mb4_general_ci AS cust_name,
                CONVERT(mcol.collection_name USING utf8mb4) COLLATE utf8mb4_general_ci AS collection_name,
                'spiderwaves' AS source
            FROM mst_cust_mapping m
            LEFT JOIN mst_customer mc ON mc.cust_id = m.cust_id
            LEFT JOIN mst_collection mcol ON mcol.collection_id = m.collection_id
            UNION ALL
            SELECT
                u.mapped_customer_id AS mapped_cust_id,
                u.private_key,
                u.mapped_customer_name AS mapped_cust_name,
                u.customer_id AS cust_id,
                u.collection_id,
                COALESCE(up.purity, 0.00) AS purity,
                CAST(u.is_active AS CHAR) COLLATE utf8mb4_general_ci AS mapped_cust_status,
                uc.customer_name AS cust_name,
                ucol.collection_name,
                'ucc' AS source
            FROM ucc_mapped_customer u
            LEFT JOIN ucc_customer uc ON uc.customer_id = u.customer_id AND uc.deleted_at IS NULL
            LEFT JOIN ucc_collection ucol ON ucol.collection_id = u.collection_id AND ucol.deleted_at IS NULL
            LEFT JOIN ucc_purity up ON up.purity_id = u.purity_id AND up.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM mst_cust_mapping m2
                  WHERE CONVERT(m2.private_key USING utf8mb4) COLLATE utf8mb4_general_ci = u.private_key
                     OR LOWER(TRIM(CONVERT(m2.mapped_cust_name USING utf8mb4))) COLLATE utf8mb4_general_ci = LOWER(TRIM(u.mapped_customer_name)) COLLATE utf8mb4_general_ci
              )
        ";
    }

    public function getPaginatedMappings(
        int $perPage = 10,
        ?string $search = null,
        ?int $custId = null,
        ?int $collectionId = null,
        ?string $status = null,
        int $page = 1,
        string $sort = ''
    ): array {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(mapped_cust_name LIKE ? OR cust_name LIKE ? OR private_key LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($custId)) {
            $where[] = "cust_id = ?";
            $params[] = $custId;
        }

        if (!empty($collectionId)) {
            $where[] = "collection_id = ?";
            $params[] = $collectionId;
        }

        if ($status !== null && $status !== '') {
            $where[] = "mapped_cust_status = ?";
            $params[] = (string)$status;
        }

        $sql = "SELECT * FROM (" . $this->getMappedCustomerUnionSql() . ") AS mc";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $orderBy = match ($sort) {
            'mapped_asc'  => 'mapped_cust_name ASC, mapped_cust_id DESC',
            'mapped_desc' => 'mapped_cust_name DESC, mapped_cust_id DESC',
            'cust_asc'    => 'cust_name ASC, mapped_cust_name ASC',
            'cust_desc'   => 'cust_name DESC, mapped_cust_name ASC',
            'purity_asc'  => 'purity ASC, mapped_cust_name ASC',
            'purity_desc' => 'purity DESC, mapped_cust_name ASC',
            'id_asc'      => 'mapped_cust_id ASC, source ASC',
            'id_desc'     => 'mapped_cust_id DESC, source ASC',
            default       => 'source ASC, mapped_cust_id DESC',
        };

        return $this->paginateUnion($sql, $params, $perPage, $orderBy, $page);
    }

    public function getMappedCustomerSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_mappings,
                COALESCE(SUM(CASE WHEN mapped_cust_status = '1' THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN mapped_cust_status = '0' THEN 1 ELSE 0 END), 0) AS total_inactive,
                COUNT(DISTINCT LOWER(TRIM(cust_name))) AS total_customers_mapped,
                COUNT(DISTINCT LOWER(TRIM(collection_name))) AS total_collections_mapped
            FROM (" . $this->getMappedCustomerUnionSql() . ") AS mc
        ")->getRowArray();

        return [
            'total_mappings'           => (int)($row['total_mappings'] ?? 0),
            'total_active'             => (int)($row['total_active'] ?? 0),
            'total_inactive'           => (int)($row['total_inactive'] ?? 0),
            'total_customers_mapped'   => (int)($row['total_customers_mapped'] ?? 0),
            'total_collections_mapped' => (int)($row['total_collections_mapped'] ?? 0),
        ];
    }

    public function getActiveMappings(): array
    {
        return $this->db->query("
            SELECT mapped_cust_id, private_key, mapped_cust_name, cust_id, collection_id, purity, source
            FROM (" . $this->getMappedCustomerUnionSql() . ") AS mc
            WHERE mapped_cust_status = '1'
            ORDER BY mapped_cust_name ASC
        ")->getResultArray();
    }

    public function findMappingById(int $mappedCustId, bool $activeOnly = false): ?array
    {
        $where = "WHERE mapped_cust_id = ?";
        if ($activeOnly) {
            $where .= " AND mapped_cust_status = '1'";
        }

        $row = $this->db->query("
            SELECT *
            FROM (" . $this->getMappedCustomerUnionSql() . ") AS mc
            {$where}
            LIMIT 1
        ", [$mappedCustId])->getRowArray();

        return $row ?: null;
    }

    public function getActiveMappingsForCustomer(int $custId, ?float $purity = null, ?int $collectionId = null): array
    {
        $where = "WHERE mapped_cust_status = '1' AND cust_id = ?";
        $params = [$custId];

        if ($purity !== null && $purity > 0) {
            $where .= " AND purity = ?";
            $params[] = $purity;
        }

        if ($collectionId !== null && $collectionId > 0) {
            $where .= " AND collection_id = ?";
            $params[] = $collectionId;
        }

        return $this->db->query("
            SELECT *
            FROM (" . $this->getMappedCustomerUnionSql() . ") AS mc
            {$where}
            ORDER BY mapped_cust_name ASC
        ", $params)->getResultArray();
    }

    // =========================================================================
    // COLLECTIONS (mst_collection + ucc_collection)
    // =========================================================================

    protected function getCollectionUnionSql(): string
    {
        return "
            SELECT
                m.collection_id,
                CONVERT(m.private_key USING utf8mb4) COLLATE utf8mb4_general_ci AS private_key,
                CONVERT(m.collection_name USING utf8mb4) COLLATE utf8mb4_general_ci AS collection_name,
                CAST(m.collection_status AS CHAR) COLLATE utf8mb4_general_ci AS collection_status,
                COUNT(mm.mapped_cust_id) AS mapping_count,
                'spiderwaves' AS source
            FROM mst_collection m
            LEFT JOIN mst_cust_mapping mm ON mm.collection_id = m.collection_id
            GROUP BY m.collection_id
            UNION ALL
            SELECT
                u.collection_id,
                u.private_key,
                u.collection_name,
                CAST(u.is_active AS CHAR) COLLATE utf8mb4_general_ci AS collection_status,
                COUNT(um.mapped_customer_id) AS mapping_count,
                'ucc' AS source
            FROM ucc_collection u
            LEFT JOIN ucc_mapped_customer um ON um.collection_id = u.collection_id AND um.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM mst_collection m2
                  WHERE CONVERT(m2.private_key USING utf8mb4) COLLATE utf8mb4_general_ci = u.private_key
                     OR LOWER(TRIM(CONVERT(m2.collection_name USING utf8mb4))) COLLATE utf8mb4_general_ci = LOWER(TRIM(u.collection_name)) COLLATE utf8mb4_general_ci
              )
            GROUP BY u.collection_id
        ";
    }

    public function getPaginatedCollections(int $perPage = 10, ?string $search = null, ?string $status = null, int $page = 1): array
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "collection_name LIKE ?";
            $params[] = "%{$search}%";
        }

        if ($status !== null && $status !== '') {
            $where[] = "collection_status = ?";
            $params[] = (string)$status;
        }

        $sql = "SELECT * FROM (" . $this->getCollectionUnionSql() . ") AS col";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        return $this->paginateUnion($sql, $params, $perPage, "source ASC, collection_id DESC", $page);
    }

    public function getActiveCollections(): array
    {
        return $this->db->query("
            SELECT collection_id, private_key, collection_name, source
            FROM (" . $this->getCollectionUnionSql() . ") AS col
            WHERE collection_status = '1'
            ORDER BY collection_name ASC
        ")->getResultArray();
    }

    public function getTotalCollectionsCount(): int
    {
        $row = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM (" . $this->getCollectionUnionSql() . ") AS col
        ")->getRowArray();

        return (int)($row['cnt'] ?? 0);
    }

    // =========================================================================
    // PURITIES (mst_purity + ucc_purity)
    // =========================================================================

    protected function getPurityUnionSql(): string
    {
        return "
            SELECT
                m.purity_id,
                CONVERT(m.private_key USING utf8mb4) COLLATE utf8mb4_general_ci AS private_key,
                CONVERT(m.purity_name USING utf8mb4) COLLATE utf8mb4_general_ci AS purity_name,
                m.purity_value,
                NULL AS gold_multiplier,
                CAST(m.purity_status AS CHAR) COLLATE utf8mb4_general_ci AS purity_status,
                'spiderwaves' AS source
            FROM mst_purity m
            UNION ALL
            SELECT
                u.purity_id,
                u.private_key,
                CONCAT(TRIM(TRAILING '.00' FROM CAST(u.purity AS CHAR)), '%') COLLATE utf8mb4_general_ci AS purity_name,
                u.purity AS purity_value,
                u.gold_multiplier,
                CAST(u.is_active AS CHAR) COLLATE utf8mb4_general_ci AS purity_status,
                'ucc' AS source
            FROM ucc_purity u
            WHERE u.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM mst_purity m2
                  WHERE CONVERT(m2.private_key USING utf8mb4) COLLATE utf8mb4_general_ci = u.private_key
                     OR ABS(m2.purity_value - u.purity) < 0.001
              )
        ";
    }

    public function getPaginatedPurities(int $perPage = 10, ?string $search = null, ?string $status = null, int $page = 1): array
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(purity_name LIKE ? OR CAST(purity_value AS CHAR) LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($status !== null && $status !== '') {
            $where[] = "purity_status = ?";
            $params[] = (string)$status;
        }

        $sql = "SELECT * FROM (" . $this->getPurityUnionSql() . ") AS p";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        return $this->paginateUnion($sql, $params, $perPage, "purity_value ASC, source ASC, purity_id DESC", $page);
    }

    public function getPuritySummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_purities,
                COALESCE(SUM(CASE WHEN purity_status = '1' THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN purity_status = '0' THEN 1 ELSE 0 END), 0) AS total_inactive
            FROM (" . $this->getPurityUnionSql() . ") AS p
        ")->getRowArray();

        return [
            'total_purities' => (int)($row['total_purities'] ?? 0),
            'total_active'   => (int)($row['total_active'] ?? 0),
            'total_inactive' => (int)($row['total_inactive'] ?? 0),
        ];
    }

    public function getActivePurities(): array
    {
        return $this->db->query("
            SELECT purity_id, private_key, purity_name, purity_value, purity_status, source
            FROM (" . $this->getPurityUnionSql() . ") AS p
            WHERE purity_status = '1'
            ORDER BY purity_value ASC
        ")->getResultArray();
    }
}

