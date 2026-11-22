<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\QrBillBuilder;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * @uses \App\Service\QrBillBuilder
 */
class QrBillBuilderTest extends TestCase
{
    public function testNormalizeIban(): void
    {
        $this->assertSame('CH9300762011623852957', QrBillBuilder::normalizeIban(' ch93 0076 2011 6238 5295 7 '));
    }

    public function testIsValidIban(): void
    {
        $this->assertTrue(QrBillBuilder::isValidIban('CH93 0076 2011 6238 5295 7'));
        $this->assertTrue(QrBillBuilder::isValidIban('LI21088100002324013AA'));
        $this->assertFalse(QrBillBuilder::isValidIban('CH9300762011623852958'), 'wrong checksum');
        $this->assertFalse(QrBillBuilder::isValidIban('DE89370400440532013000'), 'not CH/LI');
        $this->assertFalse(QrBillBuilder::isValidIban('CH4431999123000889012'), 'QR-IBAN needs a reference');
        $this->assertFalse(QrBillBuilder::isValidIban(''));
    }

    public function testBuild(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to create the QR code');
        }
        $qrCode = QrBillBuilder::build(
            QrBillBuilder::structuredAddress('Coach Name', 'Rue du Lac 1', '1000', 'Lausanne'),
            'CH9300762011623852957',
            42.5,
            QrBillBuilder::combinedAddress('Club', 'Rue du Club 2', '1000 Lausanne'),
            'Coach 2026'
        );

        $text = $qrCode->getText();
        $this->assertStringContainsString('CH9300762011623852957', $text);
        $this->assertStringContainsString('42.50', $text);
        $this->assertStringContainsString('Coach Name', $text);
        $this->assertStringContainsString('Coach 2026', $text);
        $this->assertStringStartsWith('data:image/svg+xml', $qrCode->getDataUri());
    }

    public function testBuildWithInvalidIbanThrows(): void
    {
        $this->expectException(RuntimeException::class);

        QrBillBuilder::build(
            QrBillBuilder::structuredAddress('Coach Name', 'Rue du Lac 1', '1000', 'Lausanne'),
            'DE89370400440532013000',
            10,
            null,
            'Coach'
        );
    }
}
