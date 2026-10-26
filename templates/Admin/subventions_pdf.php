<?php

$this->layout = "pdf";

$membersCity = [];
foreach ($subventionsData["membersCityList"] as $city)
{
  $membersCity[$city] = $subventionsData["membersCity"][$city];
}

$pdf = $this->Pdf->createSubventions($site, $contents, $membersCity);
$pdf->Output($outputPath, $outputType);
