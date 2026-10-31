<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\ZipComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ZipArchive;

/**
 * @uses \App\Controller\Component\ZipComponent
 */
class ZipComponentTest extends TestCase
{
    private string $dir;

    private ZipComponent $Zip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'zip_component_test' . DS;
        @mkdir($this->dir);
        $this->Zip = new ZipComponent(new ComponentRegistry(new Controller(new ServerRequest())));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function file(string $name, string $content = 'x'): string
    {
        file_put_contents($this->dir . $name, $content);

        return $this->dir . $name;
    }

    /**
     * @return string[] names of the files in the zip
     */
    private function entries(string $zipPath): array
    {
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        return $names;
    }

    public function testCreateInAFolderNamedAfterTheZip(): void
    {
        $zipPath = $this->dir . 'bills.zip';

        $this->assertTrue($this->Zip->create($zipPath, [$this->file('a.pdf'), $this->file('b.pdf'), $this->dir . 'missing.pdf']));

        $this->assertSame(['bills' . DS . 'a.pdf', 'bills' . DS . 'b.pdf'], $this->entries($zipPath));
    }

    public function testCreateWithoutExistingFile(): void
    {
        $zipPath = $this->file('old.zip', 'old');

        $this->assertFalse($this->Zip->create($zipPath, [$this->dir . 'missing.pdf']));
        $this->assertFalse($this->Zip->create($zipPath, 'not an array'));
        // The old zip is removed
        $this->assertFileDoesNotExist($zipPath);
    }

    public function testCreateReplacesTheOldZip(): void
    {
        $zipPath = $this->dir . 'bills.zip';
        $this->Zip->create($zipPath, [$this->file('a.pdf')]);

        $this->Zip->create($zipPath, [$this->file('b.pdf')]);

        $this->assertSame(['bills' . DS . 'b.pdf'], $this->entries($zipPath));
    }
}
