<?php

declare(strict_types=1);

namespace App\Test\TestCase\Console;

use App\Console\Installer;
use Cake\TestSuite\TestCase;
use Composer\IO\BufferIO;
use Composer\Util\Filesystem;

class InstallerTest extends TestCase
{
    private const SOURCE = 'vendor/tinymce/tinymce';
    private const TARGET = 'webroot/js/tinymce';

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = TMP . 'tests' . DS . 'installer_' . uniqid();
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
        parent::tearDown();
    }

    private function write(string $path, string $content): void
    {
        $file = $this->root . '/' . $path;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $content);
    }

    public function testPublicAssetsServeTheEditorLoadedByTheTemplates(): void
    {
        $this->assertSame(self::TARGET, Installer::PUBLIC_ASSETS[self::SOURCE]);
        $this->assertStringContainsString('/js/tinymce/tinymce.min.js', (string)file_get_contents(ROOT . '/templates/element/Contents/editor.php'));
    }

    public function testCopyIncludesSubdirectories(): void
    {
        $this->write(self::SOURCE . '/tinymce.min.js', 'new');
        $this->write(self::SOURCE . '/plugins/link/plugin.min.js', 'link');

        Installer::copyPublicAssets($this->root, new BufferIO());

        $this->assertStringEqualsFile($this->root . '/' . self::TARGET . '/tinymce.min.js', 'new');
        $this->assertStringEqualsFile($this->root . '/' . self::TARGET . '/plugins/link/plugin.min.js', 'link');
    }

    public function testCopyReplacesTheOlderVersionAndRemovesItsFiles(): void
    {
        $this->write(self::SOURCE . '/tinymce.min.js', 'new');
        $this->write(self::TARGET . '/tinymce.min.js', 'old');
        $this->write(self::TARGET . '/plugins/removed/plugin.min.js', 'old');

        Installer::copyPublicAssets($this->root, new BufferIO());

        $this->assertStringEqualsFile($this->root . '/' . self::TARGET . '/tinymce.min.js', 'new');
        $this->assertFileDoesNotExist($this->root . '/' . self::TARGET . '/plugins/removed/plugin.min.js');
    }

    public function testOnlyRuntimeFilesAreCopied(): void
    {
        $this->write(self::SOURCE . '/tinymce.min.js', 'new');
        $this->write(self::SOURCE . '/composer.json', '{}');
        $this->write(self::SOURCE . '/CHANGELOG.md', 'versions');

        Installer::copyPublicAssets($this->root, new BufferIO());

        $this->assertFileExists($this->root . '/' . self::TARGET . '/tinymce.min.js');
        $this->assertFileDoesNotExist($this->root . '/' . self::TARGET . '/composer.json');
        $this->assertFileDoesNotExist($this->root . '/' . self::TARGET . '/CHANGELOG.md');
    }

    public function testRuntimeFilesExistInThePackage(): void
    {
        foreach (Installer::PUBLIC_ASSET_FILES[self::SOURCE] as $file) {
            $this->assertFileExists(ROOT . '/' . self::SOURCE . '/' . $file);
        }
    }

    public function testNoTemporaryDirectoryIsLeft(): void
    {
        $this->write(self::SOURCE . '/tinymce.min.js', 'new');
        $this->write(self::TARGET . '/tinymce.min.js', 'old');

        Installer::copyPublicAssets($this->root, new BufferIO());

        $this->assertDirectoryDoesNotExist($this->root . '/' . self::TARGET . '.new');
        $this->assertDirectoryDoesNotExist($this->root . '/' . self::TARGET . '.old');
    }

    public function testFailedCopyKeepsThePublicCopyAndWarns(): void
    {
        $unreadable = self::SOURCE . '/plugins/link/plugin.min.js';
        $this->write(self::SOURCE . '/tinymce.min.js', 'new');
        $this->write($unreadable, 'link');
        $this->write(self::TARGET . '/tinymce.min.js', 'old');
        chmod($this->root . '/' . $unreadable, 0);
        if (is_readable($this->root . '/' . $unreadable)) {
            $this->markTestSkipped('Files are always readable by this user');
        }
        $io = new BufferIO();

        // @ for the warning of copy(): Composer turns it into an exception, not PHPUnit
        @Installer::copyPublicAssets($this->root, $io);

        $this->assertStringEqualsFile($this->root . '/' . self::TARGET . '/tinymce.min.js', 'old');
        $this->assertDirectoryDoesNotExist($this->root . '/' . self::TARGET . '.new');
        $this->assertStringContainsString('Could not copy', $io->getOutput());
    }

    public function testMissingPackageKeepsThePublicCopyAndWarns(): void
    {
        $this->write(self::TARGET . '/tinymce.min.js', 'old');
        $io = new BufferIO();

        Installer::copyPublicAssets($this->root, $io);

        $this->assertStringEqualsFile($this->root . '/' . self::TARGET . '/tinymce.min.js', 'old');
        $this->assertStringContainsString('Package assets not found', $io->getOutput());
    }
}
