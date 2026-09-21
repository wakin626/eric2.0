<?php
namespace App\Helpers;

class SpreadsheetReader {
    public static function read($filePath) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return self::readCsv($filePath);
        }
        if ($ext === 'xlsx') {
            return self::readXlsx($filePath);
        }
        throw new \Exception("Unsupported file type: {$ext}. Use .csv or .xlsx");
    }

    private static function readCsv($filePath) {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open CSV file.");
        }
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return self::toAssociative($rows);
    }

    private static function readXlsx($filePath) {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception("Cannot open XLSX file. It may be corrupted.");
        }

        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml')) {
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            $ssDoc = new \SimpleXMLElement($ssXml);
            $ns = $ssDoc->getNamespaces(true);
            $defaultNs = $ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $siList = $ssDoc->children($defaultNs);
            foreach ($siList as $si) {
                $tNodes = $si->children($defaultNs);
                $value = '';
                foreach ($tNodes as $t) {
                    $value .= (string)$t;
                }
                $sharedStrings[] = $value;
            }
        }

        $sheetName = 'xl/worksheets/sheet1.xml';
        $rows = [];

        if ($zip->locateName($sheetName)) {
            $sheetXml = $zip->getFromName($sheetName);
            $doc = new \SimpleXMLElement($sheetXml);
            $ns = $doc->getNamespaces(true);
            $sheetNs = $ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

            $sheetData = null;
            foreach ($doc->children() as $child) {
                $localName = $child->getName();
                if ($localName === 'sheetData') {
                    $sheetData = $child;
                    break;
                }
            }
            if ($sheetData === null) {
                $sheetData = $doc->children($sheetNs)->sheetData ?? null;
            }

            if ($sheetData) {
                foreach ($sheetData->row as $row) {
                    $cells = [];
                    foreach ($row->c as $c) {
                        $ref = (string)$c['r'];
                        $type = (string)($c['t'] ?? '');
                        $v = (string)($c->v ?? '');

                        if ($type === 's') {
                            $idx = intval($v);
                            $cells[$ref] = $sharedStrings[$idx] ?? '';
                        } elseif ($type === 'b') {
                            $cells[$ref] = $v === '1' ? 'TRUE' : 'FALSE';
                        } else {
                            $cells[$ref] = $v;
                        }
                    }
                    $rowData = [];
                    $maxCol = 0;
                    foreach ($cells as $ref => $val) {
                        $colLetter = preg_replace('/\d+/', '', $ref);
                        $colIdx = self::colToIndex($colLetter);
                        $maxCol = max($maxCol, $colIdx);
                    }
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $letter = self::indexToCol($i);
                        $ref = $letter . (string)$row['r'];
                        $rowData[] = $cells[$ref] ?? '';
                    }
                    $rows[] = $rowData;
                }
            }
        }

        $zip->close();
        return self::toAssociative($rows);
    }

    private static function toAssociative($rows) {
        if (empty($rows)) {
            return [];
        }
        $headers = array_map(function($h) { return strtolower(trim($h)); }, $rows[0]);
        $result = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $nonEmpty = count(array_filter($row, function($v) { return $v !== ''; }));
            if ($nonEmpty === 0) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $idx => $header) {
                if ($header === '') continue;
                $assoc[$header] = $row[$idx] ?? '';
            }
            if (!empty($assoc)) {
                $result[] = $assoc;
            }
        }
        return $result;
    }

    private static function colToIndex($col) {
        $col = strtoupper($col);
        $result = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $result = $result * 26 + (ord($col[$i]) - 64);
        }
        return $result - 1;
    }

    private static function indexToCol($n) {
        $result = '';
        while ($n >= 0) {
            $result = chr(65 + ($n % 26)) . $result;
            $n = intdiv($n, 26) - 1;
        }
        return $result;
    }
}
