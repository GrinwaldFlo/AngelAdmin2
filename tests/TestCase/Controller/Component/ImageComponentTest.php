<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\ImageComponent;
use App\Utility\ImageHelper;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * Checks of the uploaded pictures. Processing a JPEG needs the GD extension.
 *
 * @uses \App\Controller\Component\ImageComponent
 */
class ImageComponentTest extends TestCase
{
    private string $dir;

    private ImageComponent $Image;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        $this->dir = TMP . 'image_component_test' . DS;
        @mkdir($this->dir);
        $this->Image = new ImageComponent(new ComponentRegistry(new Controller(new ServerRequest())));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function requireGd(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to process the pictures');
        }
    }

    private function file(string $name, string $content): string
    {
        file_put_contents($this->dir . $name, $content);

        return $this->dir . $name;
    }

    private function upload(string $path, string $type): UploadedFile
    {
        return new UploadedFile($path, filesize($path), UPLOAD_ERR_OK, basename($path), $type);
    }

    public function testFormatsDependOnHeicSupport(): void
    {
        if (ImageHelper::isHeicSupported()) {
            $this->assertSame('JPEG and HEIC pictures only', $this->Image->getSupportedFormatsMessage());
            $this->assertSame(['jpg', 'jpeg', 'heic', 'heif'], $this->Image->getAcceptedExtensions());
            $this->assertStringContainsString('image/heic', $this->Image->getAcceptedMimeTypes());
        } else {
            $this->assertSame('JPEG pictures only', $this->Image->getSupportedFormatsMessage());
            $this->assertSame(['jpg', 'jpeg'], $this->Image->getAcceptedExtensions());
            $this->assertSame('image/jpeg', $this->Image->getAcceptedMimeTypes());
        }
    }

    public function testUploadOfAnotherTypeIsRefused(): void
    {
        $result = $this->Image->validateUploadedImage($this->upload($this->file('a.pdf', '%PDF'), 'application/pdf'));

        $this->assertFalse($result['valid']);
        $this->assertFalse($result['isJpeg']);
        $this->assertSame($this->Image->getSupportedFormatsMessage(), $result['errorMessage']);
    }

    public function testFakeJpegIsRefused(): void
    {
        $result = $this->Image->validateUploadedImage($this->upload($this->file('a.jpg', 'not a picture'), 'image/jpeg'));

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['isJpeg']);
        $this->assertSame('File is not a valid JPEG image', $result['errorMessage']);
    }

    public function testPngNamedJpegIsRefused(): void
    {
        $png = $this->file('a.jpg', (string)file_get_contents(WWW_ROOT . 'img' . DS . 'logo.png'));

        $this->assertSame('File claims to be JPEG but has different format', $this->Image->validateJpegContent($png)['errorMessage']);
    }

    public function testValidJpeg(): void
    {
        $this->requireGd();
        $jpeg = WWW_ROOT . 'img' . DS . 'cheerPlaceholder.jpg';

        $this->assertTrue($this->Image->validateImageContent($jpeg)['valid']);
        $this->assertTrue($this->Image->validateUploadedImage($this->upload($this->file('a.jpg', (string)file_get_contents($jpeg)), 'image/jpeg'))['valid']);
    }

    public function testValidateImageContentOfMissingOrEmptyFile(): void
    {
        $this->assertSame('Image file is not accessible for validation', $this->Image->validateImageContent($this->dir . 'missing.jpg')['errorMessage']);
        $this->assertSame('Image file appears to be empty or corrupted', $this->Image->validateImageContent($this->file('empty.jpg', ''))['errorMessage']);
        $this->assertSame('Unknown image format', $this->Image->validateImageContent($this->file('a.bin', 'x'), false, false)['errorMessage']);
    }

    public function testValidateHeicContent(): void
    {
        $tooSmall = $this->file('small.heic', 'abc');
        $wrongSignature = $this->file('wrong.heic', str_repeat('x', 12));
        $wrongBrand = $this->file('brand.heic', "\0\0\0\x18ftypavif");

        if (!ImageHelper::isHeicSupported()) {
            $this->assertSame('HEIC format is not supported on this server', $this->Image->validateHeicContent($tooSmall)['errorMessage']);

            return;
        }
        $this->assertSame('HEIC file is too small or corrupted', $this->Image->validateHeicContent($tooSmall)['errorMessage']);
        $this->assertSame('File is not a valid HEIC image - invalid signature', $this->Image->validateHeicContent($wrongSignature)['errorMessage']);
        $this->assertSame('File is not a valid HEIC image - unsupported brand: avif', $this->Image->validateHeicContent($wrongBrand)['errorMessage']);
    }

    public function testResize(): void
    {
        $this->requireGd();
        $resized = $this->dir . 'small.jpg';

        $this->Image->resize(WWW_ROOT . 'img' . DS . 'cheerPlaceholder.jpg', $resized, 0, 100, 90);

        $this->assertSame(100, getimagesize($resized)[1]);
    }

    public function testRotateFromExifWithoutExif(): void
    {
        $this->requireGd();
        $copy = $this->file('copy.jpg', (string)file_get_contents(WWW_ROOT . 'img' . DS . 'cheerPlaceholder.jpg'));
        $size = getimagesize($copy);

        $this->Image->rotateFromExif($copy);

        $this->assertSame([$size[0], $size[1]], array_slice(getimagesize($copy), 0, 2));
    }
}
