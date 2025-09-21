<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use App\Utility\ImageHelper;

/**
 * Test HEIC Support Command
 */
class TestHeicCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Test HEIC image format support on this server');

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Testing HEIC Support...');
        $io->hr();
        
        // Get detailed support information
        $supportInfo = ImageHelper::getHeicSupportInfo();
        
        $io->out('HEIC Support Status: ' . ($supportInfo['supported'] ? '<success>Supported</success>' : '<error>Not Supported</error>'));
        $io->out('Reason: ' . $supportInfo['reason']);
        
        if (!empty($supportInfo['requirements'])) {
            $io->out('');
            $io->out('<warning>Requirements to enable HEIC support:</warning>');
            foreach ($supportInfo['requirements'] as $requirement) {
                $io->out('  • ' . $requirement);
            }
        }
        
        $io->hr();
        
        // Show user instructions
        $instructions = ImageHelper::getHeicInstructions();
        $io->out('<info>User Instructions:</info>');
        $io->out($instructions);
        
        $io->hr();
        
        if ($supportInfo['supported']) {
            $io->out('<success>? Your application is ready to handle HEIC images!</success>');
            $io->out('<info>Users can now upload HEIC photos from iOS devices.</info>');
        } else {
            $io->out('<warning>? HEIC images will be rejected during upload.</warning>');
            $io->out('<info>Users will need to convert HEIC to JPEG manually.</info>');
        }
        
        return 0;
    }
}
