<?php

$this->layout = "pdf";

require_once(ROOT . DS . 'vendor' . DS . "tecnickcom" . DS . "tcpdf" . DS . "tcpdf.php");

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$this->Pdf->Init($pdf);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Angel Admin');
$pdf->SetTitle(__('Invoice'));
$pdf->SetSubject(__('Invoice'));
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

// set auto page breaks
$pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------
// set default font subsetting mode
$pdf->setFontSubsetting(true);

foreach ($bills as $bill)
{
  $this->Pdf->WriteInvoice($bill);
}

// ---------------------------------------------------------
$pdf->Output($outputPath, $outputType);
