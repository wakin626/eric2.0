<?php
namespace App\Helpers;

class Pagination {
    public static function filterBySearch($items, $search) {
        if (empty($search)) return $items;
        $q = strtolower(trim($search));
        return array_filter($items, function($item) use ($q) {
            foreach ($item as $value) {
                if (is_string($value) && stripos($value, $q) !== false) return true;
                if (is_numeric($value) && stripos((string)$value, $q) !== false) return true;
                if (is_array($value)) {
                    foreach ($value as $v) {
                        if (is_string($v) && stripos($v, $q) !== false) return true;
                        if (is_numeric($v) && stripos((string)$v, $q) !== false) return true;
                    }
                }
            }
            return false;
        });
    }

    public static function paginate($items, $perPage = 10) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        
        $total = count($items);
        $totalPages = ceil($total / $perPage);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($items, $offset, $perPage);
        
        return [
            'items' => $paginated,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
        ];
    }

    public static function getPageRange($currentPage, $totalPages, $delta = 2) {
        if ($totalPages <= 1) return [];
        $range = [];
        $left = max(2, $currentPage - $delta);
        $right = min($totalPages - 1, $currentPage + $delta);
        $range[] = 1;
        if ($left > 2) $range[] = '...';
        for ($i = $left; $i <= $right; $i++) $range[] = $i;
        if ($right < $totalPages - 1) $range[] = '...';
        if ($totalPages > 1) $range[] = $totalPages;
        return $range;
    }
}