<?php

declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\ImageHelper;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * HEIC support depends on the server (imagick built with libheif): the expected results follow it
 *
 * @uses \App\Utility\ImageHelper
 */
class ImageHelperTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'image_helper_test' . DS;
        @mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function testIsHeicMimeType(): void
    {
        foreach (['image/heic', 'image/HEIF', 'image/heic-sequence', 'image/heif-sequence'] as $type) {
            $this->assertTrue(ImageHelper::isHeicMimeType($type), $type);
        }
        $this->assertFalse(ImageHelper::isHeicMimeType('image/jpeg'));
        $this->assertFalse(ImageHelper::isHeicMimeType(''));
    }

    public function testIsHeicFile(): void
    {
        file_put_contents($this->dir . 'a.HEIC', 'x');
        file_put_contents($this->dir . 'b.jpg', 'x');

        $this->assertTrue(ImageHelper::isHeicFile($this->dir . 'a.HEIC'));
        $this->assertFalse(ImageHelper::isHeicFile($this->dir . 'b.jpg'));
        // Only existing files
        $this->assertFalse(ImageHelper::isHeicFile($this->dir . 'missing.heic'));
    }

    public function testSupportInfoMatchesIsHeicSupported(): void
    {
        $info = ImageHelper::getHeicSupportInfo();

        $this->assertSame(ImageHelper::isHeicSupported(), $info['supported']);
        $this->assertNotSame('', $info['reason']);
        if (!$info['supported']) {
            $this->assertNotEmpty($info['requirements']);
        }
    }

    public function testInstructions(): void
    {
        I18n::setLocale('en');
        $expected = ImageHelper::isHeicSupported() ? 'You can upload HEIC images' : 'HEIC images are not supported';

        $this->assertStringStartsWith($expected, ImageHelper::getHeicInstructions());
        I18n::setLocale(I18n::getDefaultLocale());
    }

    public function testConvertWithoutSupportOrWithInvalidFile(): void
    {
        file_put_contents($this->dir . 'a.heic', 'not an image');

        $this->assertFalse(ImageHelper::convertHeicToJpeg($this->dir . 'a.heic', $this->dir . 'a.jpg'));
        $this->assertFileDoesNotExist($this->dir . 'a.jpg');
    }
}
