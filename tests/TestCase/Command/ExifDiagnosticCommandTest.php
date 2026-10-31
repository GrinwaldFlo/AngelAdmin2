<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Command\ExifDiagnosticCommand
 */
class ExifDiagnosticCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('exif')) {
            $this->markTestSkipped('The exif extension is not loaded');
        }
    }

    public function testImageWithoutExif(): void
    {
        $this->exec('exif_diagnostic --image ' . WWW_ROOT . 'img' . DS . 'logo.png');

        $this->assertExitSuccess();
        $this->assertOutputContains('EXIF extension is loaded');
        $this->assertOutputContains('Valid image file detected');
        $this->assertOutputContains('Recommendations:');
    }

    public function testMissingImage(): void
    {
        $this->exec('exif_diagnostic -i ' . TMP . 'missing.jpg');

        $this->assertExitSuccess();
        $this->assertOutputContains('Image file not found');
    }

    public function testSampleImage(): void
    {
        $this->exec('exif_diagnostic');

        $this->assertExitSuccess();
        $this->assertOutputContains('Looking for sample images to test...');
    }
}
