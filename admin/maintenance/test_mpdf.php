<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mpdfPath = __DIR__ . '/mpdf/src/Mpdf.php';
if (!file_exists($mpdfPath)) {
    die("mPDF not found at: " . $mpdfPath);
}
require_once $mpdfPath;

try {
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML('<h1>Test PDF</h1><p>mPDF is working!</p>');
    $mpdf->Output('test.pdf', 'D'); // Force download
} catch (\Exception $e) {
    die("Error: " . $e->getMessage());
}