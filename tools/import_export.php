<?php
/**
 * import_export.php
 *
 * Fungsi-fungsi untuk mengimpor dan mengekspor data secara dinamis.
 * Bisa digunakan di modul manajemen siswa, guru, ruangan, kelas, dsb.
 *
 * Pastikan PhpSpreadsheet telah diinstal jika ingin menggunakan format XLSX.
 *
 * Contoh penggunaan:
 *   // Ekspor data:
 *   $headers = ['Kolom1', 'Kolom2', 'Kolom3'];
 *   $data = [
 *       ['data1', 'data2', 'data3'],
 *       ['data4', 'data5', 'data6']
 *   ];
 *   exportData($headers, $data, 'csv', 'nama_file_export');
 *
 *   // Impor data:
 *   $importedData = importData($_FILES['import_file'], 3); // 3 = jumlah kolom yang diharapkan
 */

if (!function_exists('exportData')) {
    /**
     * Mengekspor data dalam format CSV atau XLSX.
     *
     * @param array  $headers  Array header kolom.
     * @param array  $data     Array data (setiap baris berupa array).
     * @param string $format   Format ekspor ('csv' atau 'xlsx').
     * @param string $filename Nama file dasar (tanpa ekstensi).
     *
     * @throws Exception Jika format tidak didukung atau library XLSX tidak tersedia.
     */
    function exportData(array $headers, array $data, string $format = 'csv', string $filename = 'data_export')
    {
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$filename}.csv");
            $output = fopen('php://output', 'w');
            // Tulis header
            fputcsv($output, $headers);
            // Tulis data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit();
        } elseif ($format === 'xlsx') {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new Exception("PhpSpreadsheet library is not loaded.");
            }
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Tulis header kolom
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }
            // Tulis data baris
            $rowNumber = 2;
            foreach ($data as $row) {
                $col = 'A';
                foreach ($row as $cell) {
                    $sheet->setCellValue($col . $rowNumber, $cell);
                    $col++;
                }
                $rowNumber++;
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment;filename=\"{$filename}.xlsx\"");
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();
        } else {
            throw new Exception("Format export tidak didukung: {$format}");
        }
    }
}

if (!function_exists('importData')) {
    /**
     * Mengimpor data dari file CSV atau XLSX.
     *
     * @param array $file                 File array dari $_FILES.
     * @param int   $expectedColumnsCount Jumlah kolom yang diharapkan (opsional).
     *
     * @return array Array data (setiap baris berupa array).
     *
     * @throws Exception Jika format file tidak didukung atau library XLSX tidak tersedia.
     */
    function importData(array $file, int $expectedColumnsCount = 0)
    {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $data = [];
        if ($fileExt === 'csv') {
            if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
                // Ambil baris header (jika diperlukan)
                $header = fgetcsv($handle, 1000, ",");
                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    if ($expectedColumnsCount > 0 && count($row) != $expectedColumnsCount) {
                        // Lewati baris yang tidak sesuai jumlah kolom
                        continue;
                    }
                    $data[] = $row;
                }
                fclose($handle);
            }
        } elseif ($fileExt === 'xlsx') {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new Exception("PhpSpreadsheet library is not loaded.");
            }
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            // Ambil header (misalnya baris pertama adalah header)
            $header = array_shift($rows);
            foreach ($rows as $row) {
                if ($expectedColumnsCount > 0 && count($row) != $expectedColumnsCount) {
                    continue;
                }
                $data[] = $row;
            }
        } else {
            throw new Exception("Format import tidak didukung: {$fileExt}");
        }
        return $data;
    }
}
