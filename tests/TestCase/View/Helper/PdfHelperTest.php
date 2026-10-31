<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\Bill;
use App\Model\Entity\Member;
use App\Model\Entity\Site;
use App\Test\TestSuite\RecordingPdf;
use App\View\Helper\PdfHelper;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use RuntimeException;

/**
 * Documents are checked through the texts written (RecordingPdf), entities are built in memory
 *
 * @uses \App\View\Helper\PdfHelper
 */
class PdfHelperTest extends TestCase
{
    private const HASH = 'pdfhelpertest000000000000000000000000000';

    private PdfHelper $Pdf;

    private RecordingPdf $Recorder;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        // The PNG logo needs the GD extension, not always installed with the CLI
        Configure::write('App.logo', 'cheerPlaceholder.jpg');
        $this->Pdf = new PdfHelper(new View());
        $this->Recorder = new RecordingPdf();
        $this->Pdf->Init($this->Recorder);
        @mkdir($this->memberPath(), 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->memberPath() . '*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->memberPath());
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function memberPath(): string
    {
        return WWW_ROOT . 'img/members/' . self::HASH . '/';
    }

    /**
     * The QR code of the invoices needs the GD extension
     */
    private function requireGd(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to create the QR code');
        }
    }

    private function site(string $iban = 'CH9300762011623852957'): Site
    {
        return new Site([
            'id' => 1, 'city' => 'Lausanne', 'address' => 'Rue du Lac 1', 'postcode' => '1000', 'account_designation' => 'Club Lausanne',
            'sender' => 'Treasurer', 'sender_email' => 'club@example.com', 'sender_phone' => '021 000 00 00', 'iban' => $iban, 'bic' => 'POFICHBEXXX',
            'reminder_penalty' => 5, 'add_invoice_num' => 1000,
        ], ['guard' => false]);
    }

    private function member(string $first, string $last, ?string $birth = null): Member
    {
        return new Member([
            'id' => 7, 'first_name' => $first, 'last_name' => $last, 'address' => 'Avenue 2', 'postcode' => 1020, 'city' => 'Renens',
            'hash' => self::HASH, 'date_birth' => $birth ? new Date($birth) : null,
        ], ['guard' => false]);
    }

    private function bill(int $amount, array $fields = []): Bill
    {
        return new Bill($fields + [
            'id' => 5, 'label' => 'Fee 2026', 'amount' => $amount, 'paid' => false, 'reminder' => 0,
            'due_date' => new Date('2026-12-31'), 'due_date_ori' => new Date('2026-11-30'),
            'site' => $this->site(), 'member' => $this->member('Ana', 'Muster'),
        ], ['guard' => false]);
    }

    public function testSetFontText(): void
    {
        $this->Pdf->setFontText();
        $this->assertEquals(9, $this->Recorder->getFontSizePt());
        $this->assertSame('', $this->Recorder->getFontStyle());

        $this->Pdf->setFontText(true, true, 2);
        $this->assertEquals(16, $this->Recorder->getFontSizePt());
        $this->assertSame('BI', $this->Recorder->getFontStyle());

        $this->Pdf->setFontText(false, false, 7);
        $this->assertEquals(7, $this->Recorder->getFontSizePt());
    }

    public function testSetFontTitle(): void
    {
        $this->Pdf->setFontTitle();
        $this->assertEquals(13, $this->Recorder->getFontSizePt());
        $this->assertSame('B', $this->Recorder->getFontStyle());

        $this->Pdf->setFontTitle(2);
        $this->assertEquals(12, $this->Recorder->getFontSizePt());

        $this->Pdf->setFontTitle(9, true);
        $this->assertEquals(11, $this->Recorder->getFontSizePt());
    }

    public function testHeaderShowsTheSiteAndItsCity(): void
    {
        $this->Recorder->AddPage();

        $this->Pdf->Header($this->site());

        $this->assertStringContainsString("Club Lausanne\r\nTreasurer\r\nRue du Lac 1\r\n1000 Lausanne", $this->Recorder->texts[0]);
        $this->assertStringContainsString('Lausanne le ' . date('d.m.Y'), $this->Recorder->texts[0]);
    }

    public function testWriteSubvention(): void
    {
        $members = [$this->member('Zoe', 'Bernard', '2015-03-04'), $this->member('Ana', 'Alpha'), $this->member('Bob', 'bernard')];

        $this->Pdf->WriteSubvention('Renens', $this->site(), [1 => 'Intro text', 2 => 'Second <b>text</b>', 3 => "Best regards\nThe club"], $members);
        $texts = $this->Recorder->allTexts();

        $this->assertStringContainsString("Municipal Registry\nRenens", $texts);
        $this->assertStringContainsString('<p>Intro text</p>', $texts);
        $this->assertStringContainsString('Second <b>text</b>', $texts);
        $this->assertStringContainsString('<p>Best regards<br>The club</p>', $texts);
        // Sorted by last then first name, whatever the case
        $alpha = strpos($texts, 'Alpha Ana');
        $bob = strpos($texts, 'bernard Bob');
        $zoe = strpos($texts, 'Bernard Zoe');
        $this->assertTrue($alpha < $bob && $bob < $zoe);
        $this->assertStringContainsString('04.03.2015', $texts);
        $this->assertStringContainsString('Number of members: 3', $texts);
        $this->assertStringContainsString('CH9300762011623852957', $texts);
    }

    public function testWriteSubventionPlainTextIsEscaped(): void
    {
        $this->Pdf->WriteSubvention('Renens', $this->site(), [1 => "a < b\n\nnext"], []);

        $this->assertStringContainsString('<p>a &lt; b</p><p>next</p>', $this->Recorder->allTexts());
        $this->assertStringContainsString('Number of members: 0', $this->Recorder->allTexts());
    }

    public function testCreateSubventionsOneLetterPerCity(): void
    {
        $pdf = $this->Pdf->createSubventions($this->site(), [1 => 'Intro'], [
            'Renens' => [$this->member('Ana', 'Muster')],
            'Morges' => [$this->member('Bob', 'Muster')],
        ]);

        $this->assertSame(2, $pdf->getNumPages());
        $this->assertStringStartsWith('%PDF', $pdf->Output('', 'S'));
    }

    public function testWriteInvoice(): void
    {
        $this->requireGd();
        $bill = $this->bill(50);

        $this->Pdf->WriteInvoice($bill);
        $texts = $this->Recorder->allTexts();

        $this->assertStringContainsString('Invoice No 1005 - Fee 2026', $texts);
        $this->assertStringContainsString("Ana Muster\r\nAvenue 2\r\n1020 Renens", $texts);
        $this->assertStringContainsString('50 CHF', $texts);
        $this->assertStringContainsString('Late payments will result in a reminder fee of 5 CHF', $texts);
        $this->assertStringNotContainsString('reminder', strtolower(implode('', array_slice($this->Recorder->texts, 0, 4))));
        $this->assertStringNotContainsString('Facture payée', $texts);
        // The QR code file is only temporary
        $this->assertFileDoesNotExist($bill->QRPath);
    }

    public function testWriteInvoiceReminderAndPaid(): void
    {
        $this->requireGd();
        $this->Pdf->WriteInvoice($this->bill(50, ['reminder' => 2, 'paid' => true]));
        $texts = $this->Recorder->allTexts();

        $this->assertStringContainsString('Reminder N°2', $texts);
        $this->assertStringContainsString('If you already paid this invoice, please contact us.', $texts);
        $this->assertStringContainsString('Facture payée', $texts);

        $recorder = new RecordingPdf();
        $this->Pdf->Init($recorder);
        $this->Pdf->WriteInvoice($this->bill(50, ['reminder' => 1]));
        $this->assertStringContainsString('First reminder', $recorder->allTexts());
    }

    public function testWriteInvoiceWithoutAmountHasNoQrCode(): void
    {
        $bill = $this->bill(0);

        $this->Pdf->WriteInvoice($bill);

        $this->assertStringContainsString('Invoice No 1005', $this->Recorder->allTexts());
        $this->assertFileDoesNotExist($bill->QRPath);
    }

    public function testCreateQr(): void
    {
        $this->requireGd();
        $bill = $this->bill(50);

        $this->Pdf->CreateQR($bill);

        $this->assertFileExists($bill->QRPath);
        $this->assertStringContainsString('<svg', (string)file_get_contents($bill->QRPath));
    }

    public function testCreateQrWithInvalidIbanThrows(): void
    {
        $bill = $this->bill(50, ['site' => $this->site('CH00 wrong')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('1005');

        $this->Pdf->CreateQR($bill);
    }

    public function testColoredTable(): void
    {
        $this->Recorder->AddPage();

        $this->Pdf->ColoredTable(['A', 'B', 'C', 'D'], [['x', 'y', 1000, 2]]);

        $this->assertSame(['A', 'B', 'C', 'D', 'x', 'y', '1,000', '2', ''], $this->Recorder->texts);
    }

    public function testWriteAt(): void
    {
        $this->Recorder->AddPage();

        $this->Pdf->writeAt('Hello', 20, 30);

        $this->assertSame(['Hello'], $this->Recorder->texts);
    }
}
