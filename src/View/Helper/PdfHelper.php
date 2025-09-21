<?php
/* src/View/Helper/PdfHelper.php */
namespace App\View\Helper;
use Cake\View\Helper;
use Cake\Core\Configure;
use Sprain\SwissQrBill as QrBill;

class PdfHelper extends Helper
{
    var $xheadertext = 'PDF created using CakePHP and TCPDF';
    var $xheadercolor = array(0, 0, 200);
    var $xfootertext = '';
    //var $xfooterfont = PDF_FONT_NAME_MAIN;
    var $xfooterfontsize = 8;
    var $txtFont = 'helvetica'; // looks better, finer, and more condensed than 'dejavusans'
    var $pdf;
    function Init($inPdf)
    {
        $this->pdf = $inPdf;
    }

    /**
     * Overwrites the default header
     * set the text in the view using
     *    $fpdf->xheadertext = 'YOUR ORGANIZATION';
     * set the fill color in the view using
     *    $fpdf->xheadercolor = array(0,0,100); (r, g, b)
     * set the font in the view using
     *    $fpdf->setHeaderFont(array('YourFont','',fontsize));
     */
    /**
     *
     * @param \App\Model\Entity\Site $site
     */
    function Header($site)
    {
        $txt = $site->account_designation . "\r\n";
        $txt = $txt . $site->sender . "\r\n";
        $txt = $txt . $site->address . "\r\n";
        $txt = $txt . $site->postcode . " " . $site->city . "\r\n";
        $txt = $txt . $site->sender_email . "\r\n";
        $txt = $txt . $site->sender_phone . "\r\n";

        $txt = $txt . "\r\n\r\nRenens le " . date("d.m.Y");

        $this->setFontText();
        $this->pdf->SetXY(15, 15);
        $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 2);

        $this->pdf->Image(WWW_ROOT . 'img/' . Configure::read('App.logo'), 170, 10, 30, 0);
        //$this->pdf->ImageSVG($file=WWW_ROOT . 'img/logo.svg', $x=170, $y=10, $w=30, $h='', $link='', $align='', $palign='', $border=0, $fitonpage=false);
    }

    function Logo()
    {
        $this->pdf->Image(WWW_ROOT . 'img/' . Configure::read('App.logo'), 170, 10, 30, 0);
    }

    /**
     * Overwrites the default footer
     * set the text in the view using
     * $fpdf->xfootertext = 'Copyright Â© %d YOUR ORGANIZATION. All rights reserved.';
     */
    function Footer()
    {
        /* $year = date('Y');
          $footertext = sprintf($this->xfootertext, $year);
          $this->SetY(-20);
          $this->SetTextColor(0, 0, 0);
          $this->SetFont($this->xfooterfont,'',$this->xfooterfontsize);
          $this->Cell(0,8, $footertext,'T',1,'C'); */
    }

    public function writeAt($txt, $x, $y)
    {
        $this->pdf->SetXY($x, $y);
        //$this->Cell(0,0, $txt , 0, 1);
        $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 1);
    }

    /**
     * Set the font for the text
     * @param boolean $bold
     * @param boolean $italic
     * @param int $size 0=9, 1=14, 2=16, 3=18, 4=20, 10=7, or any other size
     */
    public function setFontText($bold = false, $italic = false, $size = 0)
    {
        $type = "";
        if ($bold)
            $type = $type . "B";
        if ($italic)
            $type = $type . "I";

        $fontSize = 0;
        switch ($size) {
            case 0:
                $fontSize = 9;
                break;
            case 1:
                $fontSize = 14;
                break;
            case 2:
                $fontSize = 16;
                break;
            case 3:
                $fontSize = 18;
                break;
            case 4:
                $fontSize = 20;
                break;
            default:
                $fontSize = $size;
        }

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->txtFont, $type, $fontSize, '', true);
        //public function SetFont($family, $style='', $size=null, $fontfile='', $subset='default', $out=true)
    }

    public function setFontTitle($Num = 1, $black = false)
    {
        $fontSize = 0;
        switch ($Num) {
            case 1:
                $fontSize = 13;
                break;
            case 2:
                $fontSize = 12;
                break;
            default:
                $fontSize = 11;
        }

        if (!$black) {
            $this->pdf->SetTextColor(95, 0, 255);
        }
        //    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', $fontSize);
        $this->pdf->SetFont($this->txtFont, 'B', $fontSize, '', true);
    }

    public function ColoredTable($header, $data)
    {
        // Colors, line width and bold font
        $this->pdf->SetFillColor(255, 0, 0);
        $this->pdf->SetTextColor(255);
        $this->pdf->SetDrawColor(128, 0, 0);
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetFont('', 'B');
        // Header
        $w = array(40, 35, 40, 45);
        $num_headers = count($header);
        for ($i = 0; $i < $num_headers; ++$i) {
            $this->pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->pdf->Ln();
        // Color and font restoration
        $this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('');
        // Data
        $fill = 0;
        foreach ($data as $row) {
            $this->pdf->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
            $this->pdf->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
            $this->pdf->Ln();
            $fill = !$fill;
        }
        $this->pdf->Cell(array_sum($w), 0, '', 'T');
    }

    public function WriteSubvention($city, $site, $contents, $members)
    {
        $this->pdf->AddPage();
        $this->header($site);

        $txt = "Greffe municipal" . "\r\n" . $city;
        $this->pdf->SetXY(135, 45);
        $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 2);

        // Facture
        $this->setFontTitle(1);
        $this->pdf->SetY(65);
        $this->pdf->Cell(0, 0, __('Subject: Request for Sports Funding'), 0, 1);

        $this->pdf->ln(1);
        $this->setFontText(false);

        $this->pdf->MultiCell(0, 0, $contents[1], 0, 'L', false, 1);

        $this->pdf->MultiCell(0, 0, " ", 0, 'L', false, 1);
        $i = 0;
        $yOri = 0;
        $y1 = 0;
        $y2 = 0;
        foreach ($members as $member) {
            if ($i++ % 2 == 0) {
                $yOri = $this->pdf->GetY();
                $this->pdf->SetX(30);
                $this->pdf->MultiCell(70, 0, "- " . $member->FullName . " " . $member->date_birth, 0, 'L', false, 1);
                $y1 = $this->pdf->GetY();
            } else {
                $this->pdf->SetY($yOri);
                $this->pdf->SetX(100);
                $this->pdf->MultiCell(70, 0, "- " . $member->FullName . " " . $member->date_birth, 0, 'L', false, 1);
                $y2 = $this->pdf->GetY();

                if ($y1 > $y2)
                    $this->pdf->SetY($y1);
            }
        }

        $this->pdf->MultiCell(0, 0, " ", 0, 'L', false, 1);

        $this->pdf->MultiCell(0, 0, $contents[2], 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, " ", 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, " ", 0, 'L', false, 1);

        $this->pdf->SetX(135);
        $this->pdf->MultiCell(0, 0, $contents[3], 0, 'L', false, 1);

        //    $this->pdf->SetX(30);
        $this->pdf->MultiCell(0, 0, " ", 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, __('Beneficiary') . ' : ' . $site->account_designation, 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, __('Address') . ' : ' . $site->address . ' ' . $site->postcode . ' ' . $site->city, 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, __('IBAN') . ' : ' . $site->iban, 0, 'L', false, 1);
        $this->pdf->MultiCell(0, 0, __('BIC') . ' : ' . $site->bic, 0, 'L', false, 1);
    }

    public function WriteInvoice($bill)
    {
        $this->pdf->AddPage();
        $this->header($bill->site);

        // Destinataire
        $txt = $bill->member->FullName . "\r\n" . $bill->member->address . "\r\n" . $bill->member->postcode . " " . $bill->member->city;
        $this->pdf->SetXY(125, 60);
        $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 2);

        // Facture
        $this->setFontTitle(1);
        $this->pdf->SetY(80);
        $this->pdf->Cell(0, 0, __('Invoice No {0} - {1}', $bill->Reference, $bill->label), 0, 1);

        // Informations de rappel
        $this->setFontText(true);
        $txt = "";
        if ($bill->reminder > 0) {
            if ($bill->reminder == 1) {
                $txt = $txt . __('First reminder');
            } else if ($bill->reminder > 1) {
                $txt = $txt . __('Reminder N°{0}', $bill->reminder);
            }
            $txt = $txt . "\r\n" . __x("{0} is a date", "To date, we have not received the {0} payment.", $bill->due_date_ori);
            $txt = $txt . "\r\n" . __("If you already paid this invoice, please contact us.");
            $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 1);
            $this->pdf->ln(5);
        }
        $this->pdf->ln(1);

        // Contenu facture
        $this->setFontText(false);

        $this->pdf->Cell(35, 6, __("Member No") . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->member->id, 0, 1);
        $this->pdf->Cell(35, 6, __('Amount') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->amount . ' CHF', 0, 1);
        $this->pdf->Cell(35, 6, __('Due date') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->due_date, 0, 1);

        $this->pdf->ln(1);
        if (!empty($bill->site->reminder_penalty))
            $this->pdf->Cell(0, 0, __("Late payments will result in a reminder fee of {0} CHF", $bill->site->reminder_penalty), 0, 1);
        $this->pdf->Cell(0, 0, __("In case of difficulty in payment, you can contact me for a payment in several installments"), 0, 1);

        $this->pdf->ln(3);

        $this->setFontTitle(2);
        $this->pdf->MultiCell(0, 0, "Paiement E-Banking:", 0, 'L', false, 1);

        $this->setFontText(false);
        $this->pdf->MultiCell(0, 0, __('Payments via e-banking save us the cost of processing the inpayment slip.'), 0, 'L', false, 1);

        $this->pdf->Cell(35, 6, __('Beneficiary') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->site->account_designation, 0, 1);
        $this->pdf->Cell(35, 6, __('Address') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->site->address . ' ' . $bill->site->postcode . ' ' . $bill->site->city, 0, 1);

        $this->pdf->Cell(35, 6, __('IBAN') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->site->iban, 0, 1);
        $this->pdf->Cell(35, 6, __('BIC') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->site->bic, 0, 1);
        $this->pdf->Cell(35, 6, __('Amount') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->amount . ' CHF', 0, 1);
        $this->pdf->Cell(35, 6, __('Description') . ':', 0, 0);
        $this->pdf->Cell(50, 6, $bill->Reference . ' - ' . $bill->label, 0, 1);

        if ($bill->paid) {
            $this->setFontText(true, false, 4);
            $this->pdf->SetTextColor(255, 0, 0);
            $this->pdf->SetXY(40, 60);
            $txt = "Facture payée";
            $this->pdf->MultiCell(0, 0, $txt, 0, 'L', false, 1);
        }


        $dx = 0;
        $dy = 0;

        $sizePage = array(210, 106);

        //$pdf->AddPage('L', $sizePage);
        $this->setFontText();
        //$pdf->rotate(-90);
//$pdf->translate(0, -57); // HP PSC 1315
//$pdf->translate(0, -37); // HP OfficeJet 4620
//$pdf->translate(-5, 0); // HP OfficeJet 4620
//$pdf->translate(-2.5, 0); // Lexmark

        $this->CreateQR($bill);
        //Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)
        //$this->pdf->Image(WWW_ROOT . 'img/bvr_' . $bill->site->id . '.png', 0, 199 - 5, 206, 0, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
        //$this->pdf->Image($bill->QRPath, 66, 212, 45, 0, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
        if (file_exists($bill->QRPath)) {
            $this->pdf->ImageSVG($file = $bill->QRPath, $x = 66, $y = 212, $w = '', $h = 45, $link = '', $align = '', $palign = '', $border = 0, $fitonpage = false);
        }
        $this->setFontTitle(1, true);
        $this->writeAt("Récépissé", 5.0, 200.0);
        $this->setFontText(true, false, 7);
        $this->writeAt("Compte / Payable à", 5.0, 207.0);

        $this->setFontText(false, false, 9);
        $this->writeAt($bill->site->iban, 5.0, 210.0);
        $this->writeAt($bill->site->account_designation, 5.0, 213.5);
        $this->writeAt($bill->site->address, 5.0, 217.0);
        $this->writeAt($bill->site->postcode . ' ' . $bill->site->city, 5.0, 220.5);

        $this->setFontText(true, false, 7);
        $this->writeAt("Payable par", 5.0, 227.0);

        $this->setFontText(false, false, 9);
        $this->writeAt($bill->member->FullName, 5, 230);
        $this->writeAt($bill->member->address, 5, 233.5);
        $this->writeAt($bill->member->postcode . " " . $bill->member->city, 5.0, 237);

        $this->setFontText(true, false, 7);
        $this->writeAt("Monnaie", 5.0, 262.0);
        $this->writeAt("Montant", 20.0, 262.0);

        $this->setFontText(false, false, 9);
        $this->writeAt('CHF', 5.0, 265.0);
        $this->writeAt($bill->amount, 20.0, 265.0);

        $this->setFontText(true, false, 7);
        $this->writeAt("Point de dépot", 40.0, 275.0);

        $this->setFontTitle(1, true);
        $this->writeAt("Section paiment", 65.0, 200.0);

        $this->setFontText(true, false, 8);
        $this->writeAt("Monnaie", 65.0, 262.0);
        $this->writeAt("Montant", 80.0, 262.0);

        $this->setFontText(false, false, 10);
        $this->writeAt('CHF', 65.0, 266.0);
        $this->writeAt($bill->amount, 80.0, 266.0);

        $this->setFontText(true, false, 8);
        $this->writeAt("Compte / Payable à", 115.0, 200.0);

        $this->setFontText(false, false, 10);
        $this->writeAt($bill->site->iban, 115.0, 203.0);
        $this->writeAt($bill->site->account_designation, 115.0, 206.5);
        $this->writeAt($bill->site->address, 115.0, 210.0);
        $this->writeAt($bill->site->postcode . ' ' . $bill->site->city, 115.0, 213.5);

        $this->setFontText(true, false, 8);
        $this->writeAt("Informations supplémentaires", 115.0, 224.0);
        $this->setFontText(false, false, 10);
        $this->writeAt($bill->Reference . " " . $bill->label, 115.0, 227);

        $this->setFontText(true, false, 8);
        $this->writeAt("Payable par", 115.0, 235.0);

        $this->setFontText(false, false, 10);
        $this->writeAt($bill->member->FullName, 115.0, 238);
        $this->writeAt($bill->member->address, 115.0, 241.5);
        $this->writeAt($bill->member->postcode . " " . $bill->member->city, 115.0, 245.0);

        $style = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '6,3', 'color' => array(0, 0, 0));

        $this->pdf->Line(0, 195, 208, 195, $style);
        $this->pdf->Line(60.5, 195, 60.5, 300, $style);

        unlink($bill->QRPath);
    }

    public function CreateQR($bill)
    {
        if ($bill->amount < 1)
            return;
        // Create a new instance of QrBill, containing default headers with fixed values
        $qrBill = QrBill\QrBill::create();

        // Add creditor information
// Who will receive the payment and to which bank account?
        $qrBill->setCreditor(
            QrBill\DataGroup\Element\CombinedAddress::create(
                $bill->site->account_designation,
                $bill->site->address,
                $bill->site->postcode . ' ' . $bill->site->city,
                'CH'
            )
        );

        $qrBill->setCreditorInformation(
            QrBill\DataGroup\Element\CreditorInformation::create(
                $bill->site->iban // This is a special QR-IBAN. Classic IBANs will not be valid here.
            )
        );

        // Add debtor information
// Who has to pay the invoice? This part is optional.
//
// Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
// They are interchangeable for creditor as well as debtor.
        if (!empty($bill->member->FullName) && !empty($bill->member->postcode) && !empty($bill->member->city)) {
            $qrBill->setUltimateDebtor(
                QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                    $bill->member->FullName,
                    $bill->member->address,
                    '',
                    $bill->member->postcode,
                    $bill->member->city,
                    'CH'
                )
            );
        }

        // Add payment amount information
// What amount is to be paid?
        $qrBill->setPaymentAmountInformation(
            QrBill\DataGroup\Element\PaymentAmountInformation::create(
                'CHF',
                $bill->amount
            )
        );

        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_NON
            )
        );

        // Optionally, add some human-readable information about what the bill is for.
        $qrBill->setAdditionalInformation(
            QrBill\DataGroup\Element\AdditionalInformation::create(
                $bill->Reference . ' - ' . $bill->label
            )
        );

        // Now get the QR code image and save it as a file.
        try {
            $qrBill->getQrCode()->writeFile($bill->QRPath);
        } catch (\Exception $e) {
            foreach ($qrBill->getViolations() as $violation) {
                print $violation->getMessage() . "\n";
                debug($bill);
            }
            exit;
        }
    }

}
