<?php
namespace App\Helpers;

class CsvExport {
    public static function export($filename, $headers, $rows) {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $csv = '';
        $csv .= self::arrayToCsv($headers);
        foreach ($rows as $row) {
            $csv .= self::arrayToCsv($row);
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($csv));

        echo $csv;
        exit;
    }

    private static function arrayToCsv(array $fields) {
        $output = '';
        foreach ($fields as $field) {
            if ($output !== '') {
                $output .= ',';
            }
            if (preg_match('/[,"\n\r]/', $field)) {
                $output .= '"' . str_replace('"', '""', $field) . '"';
            } else {
                $output .= $field;
            }
        }
        return $output . "\n";
    }
}
