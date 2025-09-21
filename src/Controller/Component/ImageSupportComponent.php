<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Utility\ImageHelper;

/**
 * Image Support Component
 * 
 * Provides information about supported image formats
 */
class ImageSupportComponent extends Component
{
    /**
     * Get supported image formats information
     */
    public function getSupportedFormats(): array
    {
        return [
            'jpeg' => true,
            'heic' => ImageHelper::isHeicSupported(),
            'accepted_types' => $this->getAcceptedMimeTypes(),
            'accepted_extensions' => $this->getAcceptedExtensions()
        ];
    }

    /**
     * Get accepted MIME types for file input
     */
    public function getAcceptedMimeTypes(): string
    {
        $types = ['image/jpeg'];
        
        if (ImageHelper::isHeicSupported()) {
            $types = array_merge($types, [
                'image/heic',
                'image/heif',
                'image/heic-sequence',
                'image/heif-sequence'
            ]);
        }
        
        return implode(',', $types);
    }

    /**
     * Get accepted file extensions
     */
    public function getAcceptedExtensions(): array
    {
        $extensions = ['jpg', 'jpeg'];
        
        if (ImageHelper::isHeicSupported()) {
            $extensions = array_merge($extensions, ['heic', 'heif']);
        }
        
        return $extensions;
    }

    /**
     * Get user-friendly format description
     */
    public function getFormatDescription(): string
    {
        if (ImageHelper::isHeicSupported()) {
            return __('JPEG and HEIC pictures only');
        } else {
            return __('JPEG pictures only');
        }
    }
}
