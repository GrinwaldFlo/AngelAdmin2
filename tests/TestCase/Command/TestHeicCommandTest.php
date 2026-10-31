<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Utility\ImageHelper;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Command\TestHeicCommand
 */
class TestHeicCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testReportsHeicSupport(): void
    {
        $this->exec('test_heic');

        $this->assertExitSuccess();
        $this->assertOutputContains('Testing HEIC Support...');
        $info = ImageHelper::getHeicSupportInfo();
        $this->assertOutputContains('Reason: ' . $info['reason']);
        if ($info['supported']) {
            $this->assertOutputContains('ready to handle HEIC images');
        } else {
            $this->assertOutputContains('  • ' . $info['requirements'][0]);
            $this->assertOutputContains('HEIC images will be rejected during upload.');
        }
    }
}
