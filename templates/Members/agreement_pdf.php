<?php
require_once(ROOT . DS . 'vendor' . DS . "tecnickcom" . DS . "tcpdf" . DS . "tcpdf.php");

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$this->Pdf->Init($pdf);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Angel Admin');
$pdf->SetTitle(__('Registration') . ' ' . $member->id);
$pdf->SetSubject(__('Registration') . ' ' . $member->id);
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

$pdf->AddPage();
$this->Pdf->logo();

$this->Pdf->setFontTitle(1);
$pdf->SetY(30);
$pdf->Cell(0, 0, __('Registration of {0} for year {1}-{2}', $member->FullName, $reg->year, $reg->year + 1), 0, 1);

$this->Pdf->setFontText(false, size:7);
$pdf->ln(5);

foreach ($agreements as $agr)
{
  $value = $agr->value;
  $subtitle = '';
  if (preg_match('/<strong>(.*?)<\/strong>/i', $value, $matches)) {
    $subtitle = $matches[1];
    $value = preg_replace('/<strong>.*?<\/strong>/i', '', $value);
  }
  $plainValue = strip_tags($value);

  if ($subtitle) {
    $this->Pdf->setFontTitle(2);
    $pdf->MultiCell(180, 0, $subtitle, align:"L");
    $pdf->ln(1);
    $this->Pdf->setFontText(false, size: 7);
  }
  $pdf->MultiCell(180, 0, $plainValue . "\r\n");
  $pdf->ln(2);
}
$pdf->ln(2);

// Function to get image dimensions from base64 data
function getImageDimensionsFromBase64($base64Data) {
    if (empty($base64Data)) {
        return ['width' => 0, 'height' => 0];
    }
    
    // Create a temporary image to get dimensions
    $img = imagecreatefromstring($base64Data);
    if ($img === false) {
        return ['width' => 0, 'height' => 0];
    }
    
    $width = imagesx($img);
    $height = imagesy($img);
    imagedestroy($img);
    
    return ['width' => $width, 'height' => $height];
}

// Function to determine if signature is horizontal based on dimensions
function isHorizontalSignature($width, $height) {
    // If width is greater than height, it's horizontal (desktop)
    // If height is greater than width, it's vertical (mobile/tablet)
    return $width > $height;
}

// Process member signature
$imgdataMember = $this->my->DataFromBlob($reg->signature_member);
$imgdataParent = $this->my->DataFromBlob($reg->signature_parent);

if (!empty($imgdataMember)) {
    $dimensionsMember = getImageDimensionsFromBase64($imgdataMember);
    $isHorizontal = isHorizontalSignature($dimensionsMember['width'], $dimensionsMember['height']);
}

if($isHorizontal)
{
    // Desktop signature - horizontal orientation
    // No rotation needed, place normally
    $pdf->SetY($pdf->GetY() + 5); // Add some space
    $pdf->Image('@'.$imgdataMember, '', '', 60, 30); // width=60, height=30 for horizontal

    $pdf->translate(100, 0);
    if(!empty($imgdataParent))
        $pdf->Image('@'.$imgdataParent, '', '', 60, 30); // width=60, height=30 for horizontal
}
else
{
    $pdf->rotate(-90);
    $pdf->translate(0, -65);

    $pdf->Image('@'.$imgdataMember,'','', 40);

    $pdf->translate(0, -100);
    if(!empty($imgdataParent))
      $pdf->Image('@'.$imgdataParent,'','', 40);
}

$pdf->Output($outputPath, $outputType);
