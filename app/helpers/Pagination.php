<?php
namespace App\Helpers;

class Pagination {
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
}