<?php
namespace App\Utility;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

/**
 * Image Helper Utility
 *
 * Handles image conversion and processing, including HEIC to JPEG conversion
 */
class ImageHelper
{
    private static $manager = null;

    /**
     * Get ImageManager instance
     */
    private static function getManager()
    {
        if (self::$manager === null) {
            // Try ImageMagick first (better HEIC support), fallback to GD
            try {
                if (extension_loaded('imagick')) {
                    self::$manager = new ImageManager(new ImagickDriver());
                } else {
                    self::$manager = new ImageManager(new GdDriver());
                }
            } catch (\Exception $e) {
                self::$manager = new ImageManager(new GdDriver());
            }
        }
        return self::$manager;
    }

    /**
     * Check if HEIC format is supported
     */
    public static function isHeicSupported(): bool
    {
        try {
            // Check if ImageMagick is loaded and supports HEIC
            if (!extension_loaded('imagick')) {
                return false;
            }

            $formats = \Imagick::queryFormats();
            return in_array('HEIC', $formats) || in_array('HEIF', $formats);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed HEIC support information
     */
    public static function getHeicSupportInfo(): array
    {
        $info = [
            'supported' => false,
            'reason' => '',
            'requirements' => []
        ];

        if (!extension_loaded('imagick')) {
            $info['reason'] = 'ImageMagick PHP extension not installed';
            $info['requirements'][] = 'Install php-imagick extension';
            return $info;
        }

        try {
            $formats = \Imagick::queryFormats();
            $heicSupported = in_array('HEIC', $formats);
            $heifSupported = in_array('HEIF', $formats);

            if (!$heicSupported && !$heifSupported) {
                $info['reason'] = 'ImageMagick installed but HEIC/HEIF formats not supported';
                $info['requirements'][] = 'Rebuild ImageMagick with libheif support';
                $info['requirements'][] = 'Or install ImageMagick from a repository that includes HEIC support';
                return $info;
            }

            $info['supported'] = true;
            $info['reason'] = 'HEIC support fully available';

        } catch (\Exception $e) {
            $info['reason'] = 'Error checking ImageMagick formats: ' . $e->getMessage();
        }

        return $info;
    }

    /**
     * Convert HEIC file to JPEG
     *
     * @param string $heicPath Path to HEIC file
     * @param string $jpegPath Output path for JPEG file
     * @param int $quality JPEG quality (1-100)
     * @return bool Success status
     */
    public static function convertHeicToJpeg(string $heicPath, string $jpegPath, int $quality = 90): bool
    {
        if (!self::isHeicSupported()) {
            return false;
        }

        try {
            $manager = self::getManager();
            $image = $manager->read($heicPath);
            $image->toJpeg($quality)->save($jpegPath);
            return true;
        } catch (\Exception $e) {
            error_log("HEIC conversion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if file is HEIC format by extension
     *
     * @param string $filepath Path to file
     * @return bool
     */
    public static function isHeicFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        return in_array($extension, ['heic', 'heif']);
    }

    /**
     * Get the MIME type that should be used for HEIC files
     * Different browsers/systems may use different MIME types
     *
     * @param string $mimeType The MIME type from the uploaded file
     * @return bool
     */
    public static function isHeicMimeType(string $mimeType): bool
    {
        $heicMimeTypes = [
            'image/heic',
            'image/heif',
            'image/heic-sequence',
            'image/heif-sequence'
        ];

        return in_array(strtolower($mimeType), $heicMimeTypes);
    }

    /**
     * Get user instructions for HEIC support
     */
    public static function getHeicInstructions(): string
    {
        $supportInfo = self::getHeicSupportInfo();

        if ($supportInfo['supported']) {
            return __('You can upload HEIC images from iOS devices. They will be automatically converted to JPEG.');
        } else {
            return __('HEIC images are not supported. Please convert to JPEG before uploading.');
        }
    }
}
