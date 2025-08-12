<?php
require_once 'includes/config.php';

use tecnickcom\tcpdf\TCPDF;

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Children Album');
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Children Album Test PDF', 0, 1, 'C');
$pdf->Output('test.pdf', 'D');