<?php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class ExifDiagnosticCommand extends Command
{
    public static function defaultName(): string
    {
        return 'exif_diagnostic';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Diagnose EXIF functionality and check for orientation data')
            ->addOption('image', [
                'short' => 'i',
                'help' => 'Path to a specific image file to test',
                'default' => null
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>EXIF Diagnostic Tool</info>');
        $io->hr();

        // Check if EXIF extension is loaded
        if (!extension_loaded('exif')) {
            $io->out('<error>? EXIF extension is NOT loaded!</error>');
            $io->out('To fix this issue:');
            $io->out('1. Install the PHP EXIF extension');
            $io->out('2. Add "extension=exif" to your php.ini file');
            $io->out('3. Restart your web server');
            return static::CODE_ERROR;
        }

        $io->out('<success>? EXIF extension is loaded</success>');

        // Check if exif_read_data function exists
        if (!function_exists('exif_read_data')) {
            $io->out('<error>? exif_read_data function is not available!</error>');
            return static::CODE_ERROR;
        }

        $io->out('<success>? exif_read_data function is available</success>');

        // Get EXIF configuration
        $io->out('');
        $io->out('<info>EXIF Configuration:</info>');
        $io->out('- Memory limit: ' . ini_get('memory_limit'));
        $io->out('- Max execution time: ' . ini_get('max_execution_time'));
        $io->out('- File uploads: ' . (ini_get('file_uploads') ? 'enabled' : 'disabled'));
        $io->out('- Max file size: ' . ini_get('upload_max_filesize'));

        // Test with a specific image if provided
        $imagePath = $args->getOption('image');
        if ($imagePath) {
            $io->out('');
            $io->out('<info>Testing specific image: ' . $imagePath . '</info>');
            $this->testImageExif($io, $imagePath);
        } else {
            // Look for sample images in common directories
            $samplePaths = [
                'webroot/img/',
                'webroot/files/',
                'tmp/',
                'tests/Fixture/'
            ];

            $io->out('');
            $io->out('<info>Looking for sample images to test...</info>');
            
            $tested = false;
            foreach ($samplePaths as $path) {
                if (is_dir($path)) {
                    $files = glob($path . '*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
                    if (!empty($files)) {
                        $testFile = $files[0];
                        $io->out('Found sample image: ' . $testFile);
                        $this->testImageExif($io, $testFile);
                        $tested = true;
                        break;
                    }
                }
            }

            if (!$tested) {
                $io->out('<warning>??  No sample images found to test. Upload a test image and use --image option.</warning>');
            }
        }

        // Provide recommendations
        $io->out('');
        $io->out('<info>Recommendations:</info>');
        $io->out('1. If EXIF data is missing, check if images were processed by photo editing software');
        $io->out('2. Some cameras/phones might not include orientation data');
        $io->out('3. EXIF data can be stripped during image optimization');
        $io->out('4. Consider using ImageMagick as an alternative to GD for better EXIF support');

        return static::CODE_SUCCESS;
    }

    private function testImageExif(ConsoleIo $io, string $imagePath): void
    {
        if (!file_exists($imagePath)) {
            $io->out('<error>? Image file not found: ' . $imagePath . '</error>');
            return;
        }

        // Check if it's a valid image
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            $io->out('<error>? Not a valid image file: ' . $imagePath . '</error>');
            return;
        }

        $io->out('<success>? Valid image file detected</success>');
        $io->out('- Dimensions: ' . $imageInfo[0] . 'x' . $imageInfo[1]);
        $io->out('- Type: ' . image_type_to_mime_type($imageInfo[2]));

        // Read EXIF data
        $exifData = @exif_read_data($imagePath);
        
        if ($exifData === false) {
            $io->out('<error>? Failed to read EXIF data from image</error>');
            $io->out('Possible reasons:');
            $io->out('- Image has no EXIF data');
            $io->out('- EXIF data is corrupted');
            $io->out('- Image format doesn\'t support EXIF');
            return;
        }

        if (empty($exifData)) {
            $io->out('<warning>??  EXIF data is empty</warning>');
            return;
        }

        $io->out('<success>? EXIF data found</success>');
        
        // Check for orientation specifically
        $orientation = null;
        if (isset($exifData['Orientation'])) {
            $orientation = $exifData['Orientation'];
            $io->out('<success>? Orientation found in root: ' . $orientation . '</success>');
        } elseif (isset($exifData['IFD0']['Orientation'])) {
            $orientation = $exifData['IFD0']['Orientation'];
            $io->out('<success>? Orientation found in IFD0: ' . $orientation . '</success>');
        } else {
            $io->out('<warning>??  No orientation data found</warning>');
        }

        if ($orientation) {
            $orientationLabels = [
                1 => 'Normal (no rotation)',
                2 => 'Horizontal flip',
                3 => '180° rotation',
                4 => 'Vertical flip',
                5 => 'Vertical flip + 90° rotation',
                6 => '90° clockwise rotation',
                7 => 'Horizontal flip + 90° rotation',
                8 => '90° counter-clockwise rotation'
            ];
            
            $label = $orientationLabels[$orientation] ?? 'Unknown orientation value';
            $io->out('- Orientation meaning: ' . $label);
        }

        // Show some key EXIF fields
        $io->out('');
        $io->out('<info>Key EXIF fields present:</info>');
        $keyFields = ['Make', 'Model', 'DateTime', 'Software', 'Orientation', 'ColorSpace', 'ExifImageWidth', 'ExifImageLength'];
        
        foreach ($keyFields as $field) {
            if (isset($exifData[$field])) {
                $io->out('- ' . $field . ': ' . $exifData[$field]);
            } elseif (isset($exifData['IFD0'][$field])) {
                $io->out('- ' . $field . ' (IFD0): ' . $exifData['IFD0'][$field]);
            }
        }

        // Show available sections
        $io->out('');
        $io->out('<info>EXIF sections found:</info>');
        foreach (array_keys($exifData) as $section) {
            if (is_array($exifData[$section])) {
                $count = count($exifData[$section]);
                $io->out('- ' . $section . ' (' . $count . ' fields)');
            } else {
                $io->out('- ' . $section . ': ' . (is_string($exifData[$section]) ? $exifData[$section] : gettype($exifData[$section])));
            }
        }
    }
}
