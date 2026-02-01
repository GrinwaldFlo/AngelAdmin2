<?php
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\Log\Log;
use App\Utility\ImageHelper;

class ImageComponent extends Component
{
    public $name = 'Image';
    private $__errors = array();

    /**
     * Validate uploaded file to ensure it's a processable image (JPEG or HEIC)
     * 
     * @param mixed $fileobject Uploaded file object with getClientMediaType() and getStream() methods
     * @return array Validation result with 'valid', 'isHeic', 'isJpeg', and 'errorMessage' keys
     */
    public function validateUploadedImage($fileobject): array
    {
        $clientMediaType = $fileobject->getClientMediaType();
        $isJpeg = $clientMediaType === "image/jpeg";
        $isHeic = ImageHelper::isHeicMimeType($clientMediaType);
        
        // First check MIME type
        if (!$isJpeg && !$isHeic) {
            return [
                'valid' => false,
                'isHeic' => false,
                'isJpeg' => false,
                'errorMessage' => $this->getSupportedFormatsMessage()
            ];
        }
        
        // Get the uploaded file path for content validation
        $tempFile = $fileobject->getStream()->getMetadata('uri');
        if (!$tempFile) {
            // Create a temporary file to validate content
            $tempFile = tempnam(sys_get_temp_dir(), 'image_validation_');
            $fileobject->moveTo($tempFile);
            $needsCleanup = true;
        } else {
            $needsCleanup = false;
        }
        
        // Validate actual file content
        $contentValidation = $this->validateImageContent($tempFile, $isJpeg, $isHeic);
        
        // Clean up temporary file if we created one
        if ($needsCleanup && file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        if (!$contentValidation['valid']) {
            return [
                'valid' => false,
                'isHeic' => $isHeic,
                'isJpeg' => $isJpeg,
                'errorMessage' => $contentValidation['errorMessage']
            ];
        }
        
        return [
            'valid' => true,
            'isHeic' => $isHeic,
            'isJpeg' => $isJpeg,
            'errorMessage' => $this->getSupportedFormatsMessage()
        ];
    }

    /**
     * Validate image file content to ensure it's actually a processable image
     * 
     * @param string $filePath Path to the image file
     * @param bool $expectedJpeg Whether the file is expected to be JPEG
     * @param bool $expectedHeic Whether the file is expected to be HEIC
     * @return array Validation result with 'valid' and 'errorMessage' keys
     */
    public function validateImageContent(string $filePath, bool $expectedJpeg = true, bool $expectedHeic = false): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'errorMessage' => __('Image file is not accessible for validation')
            ];
        }
        
        // Check file size (basic sanity check)
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            return [
                'valid' => false,
                'errorMessage' => __('Image file appears to be empty or corrupted')
            ];
        }
        
        // For JPEG validation
        if ($expectedJpeg) {
            return $this->validateJpegContent($filePath);
        }
        
        // For HEIC validation
        if ($expectedHeic) {
            return $this->validateHeicContent($filePath);
        }
        
        return [
            'valid' => false,
            'errorMessage' => __('Unknown image format')
        ];
    }

    /**
     * Validate JPEG file content
     * 
     * @param string $filePath Path to the JPEG file
     * @return array Validation result
     */
    public function validateJpegContent(string $filePath): array
    {
        // Use getimagesize for basic validation
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            Log::warning('JPEG validation failed: getimagesize returned false for {file}', [
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('File is not a valid JPEG image')
            ];
        }
        
        // Check if it's actually a JPEG
        if ($imageInfo[2] !== IMAGETYPE_JPEG) {
            Log::warning('JPEG validation failed: wrong image type {type} for {file}', [
                'type' => $imageInfo[2],
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('File claims to be JPEG but has different format')
            ];
        }
        
        // Check image dimensions are reasonable
        if ($imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            Log::warning('JPEG validation failed: invalid dimensions {width}x{height} for {file}', [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('JPEG image has invalid dimensions')
            ];
        }
        
        // Additional validation: try to create image resource
        $imageResource = @imagecreatefromjpeg($filePath);
        if ($imageResource === false) {
            Log::warning('JPEG validation failed: cannot create image resource for {file}', [
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('JPEG image cannot be processed - file may be corrupted')
            ];
        }
        
        // Clean up
        imagedestroy($imageResource);
        
        Log::debug('JPEG validation successful for {file} ({width}x{height})', [
            'file' => $filePath,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'scope' => 'image'
        ]);
        
        return [
            'valid' => true,
            'errorMessage' => null
        ];
    }

    /**
     * Validate HEIC file content
     * 
     * @param string $filePath Path to the HEIC file
     * @return array Validation result
     */
    public function validateHeicContent(string $filePath): array
    {
        // First check if HEIC is supported on this server
        if (!ImageHelper::isHeicSupported()) {
            return [
                'valid' => false,
                'errorMessage' => __('HEIC format is not supported on this server')
            ];
        }
        
        // Read file header to validate HEIC signature
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            Log::warning('HEIC validation failed: cannot open file {file}', [
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('Cannot read uploaded HEIC file')
            ];
        }
        
        // Read first 12 bytes to check HEIC signature
        $header = fread($handle, 12);
        fclose($handle);
        
        if (strlen($header) < 12) {
            Log::warning('HEIC validation failed: file too small ({size} bytes) for {file}', [
                'size' => strlen($header),
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('HEIC file is too small or corrupted')
            ];
        }
        
        // HEIC files start with specific signatures
        // Check for 'ftyp' at offset 4-7 and HEIC brands
        $ftypSignature = substr($header, 4, 4);
        if ($ftypSignature !== 'ftyp') {
            Log::warning('HEIC validation failed: invalid signature "{signature}" for {file}', [
                'signature' => $ftypSignature,
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('File is not a valid HEIC image - invalid signature')
            ];
        }
        
        // Check HEIC brand (next 4 bytes after ftyp)
        $brand = substr($header, 8, 4);
        $validBrands = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'hevm', 'hevs', 'mif1'];
        if (!in_array($brand, $validBrands)) {
            Log::warning('HEIC validation failed: unsupported brand "{brand}" for {file}', [
                'brand' => $brand,
                'file' => $filePath,
                'scope' => 'image'
            ]);
            return [
                'valid' => false,
                'errorMessage' => __('File is not a valid HEIC image - unsupported brand: {0}', $brand)
            ];
        }
        
        // Try to process with ImageMagick if available for deeper validation
        if (extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($filePath);
                $width = $imagick->getImageWidth();
                $height = $imagick->getImageHeight();
                $imagick->destroy();
                
                if ($width <= 0 || $height <= 0) {
                    Log::warning('HEIC validation failed: invalid dimensions {width}x{height} for {file}', [
                        'width' => $width,
                        'height' => $height,
                        'file' => $filePath,
                        'scope' => 'image'
                    ]);
                    return [
                        'valid' => false,
                        'errorMessage' => __('HEIC image has invalid dimensions')
                    ];
                }
                
                Log::debug('HEIC validation successful for {file} ({width}x{height}, brand: {brand})', [
                    'file' => $filePath,
                    'width' => $width,
                    'height' => $height,
                    'brand' => $brand,
                    'scope' => 'image'
                ]);
                
            } catch (\Exception $e) {
                Log::warning('HEIC validation failed: ImageMagick error for {file}: {error}', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'scope' => 'image'
                ]);
                return [
                    'valid' => false,
                    'errorMessage' => __('HEIC image cannot be processed: {0}', $e->getMessage())
                ];
            }
        } else {
            Log::debug('HEIC validation successful for {file} (basic validation only, brand: {brand})', [
                'file' => $filePath,
                'brand' => $brand,
                'scope' => 'image'
            ]);
        }
        
        return [
            'valid' => true,
            'errorMessage' => null
        ];
    }

    /**
     * Get supported image formats message
     * 
     * @return string User-friendly message about supported formats
     */
    public function getSupportedFormatsMessage(): string
    {
        if (ImageHelper::isHeicSupported()) {
            return __('JPEG and HEIC pictures only');
        } else {
            return __('JPEG pictures only');
        }
    }

    /**
     * Get accepted MIME types for file input
     * 
     * @return string Comma-separated list of accepted MIME types
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
     * 
     * @return array Array of accepted file extensions
     */
    public function getAcceptedExtensions(): array
    {
        $extensions = ['jpg', 'jpeg'];
        
        if (ImageHelper::isHeicSupported()) {
            $extensions = array_merge($extensions, ['heic', 'heif']);
        }
        
        return $extensions;
    }

    public function rotateFromExif($filename)
    {
        if (!file_exists($filename)) {
            Log::error('File not found: {filename}', ['filename' => $filename, 'scope' => 'image']);
            return false;
        }

        $image = imagecreatefromjpeg($filename);
        if (!$image) {
            Log::error('Failed to create image from JPEG: {filename}', ['filename' => $filename, 'scope' => 'image']);
            return false;
        }

        // Check if EXIF extension is loaded
        if (!extension_loaded('exif') || !function_exists('exif_read_data')) {
            Log::error('EXIF extension not available', ['scope' => 'image']);
            imagedestroy($image);
            return false;
        }

        $exif = @exif_read_data($filename);

        // Debug output - remove this in production
        Log::debug('EXIF data for {filename}', ['filename' => $filename, 'scope' => 'image', 'exif' => $exif]);

        if ($exif === false || empty($exif)) {
            Log::info('No EXIF data found in image: {filename}', ['filename' => $filename, 'scope' => 'image']);
            imagedestroy($image);
            return false;
        }

        // Try to find orientation in multiple locations
        $ort = null;
        if (isset($exif['Orientation'])) {
            $ort = $exif['Orientation'];
            Log::info('Orientation found in root: {orientation}', ['orientation' => $ort, 'scope' => 'image']);
        } elseif (isset($exif['IFD0']['Orientation'])) {
            $ort = $exif['IFD0']['Orientation'];
            Log::info('Orientation found in IFD0: {orientation}', ['orientation' => $ort, 'scope' => 'image']);
        } elseif (isset($exif['EXIF']['Orientation'])) {
            $ort = $exif['EXIF']['Orientation'];
            Log::info('Orientation found in EXIF: {orientation}', ['orientation' => $ort, 'scope' => 'image']);
        } else {
            Log::info('Orientation not found - image is likely already correctly oriented or doesn\'t need rotation', ['scope' => 'image']);
            imagedestroy($image);
            return true; // Not an error - just no rotation needed
        }

        // Apply rotation based on orientation
        $rotatedImage = $this->applyOrientation($image, $ort);
        
        if ($rotatedImage) {
            // Save the rotated image
            $result = imagejpeg($rotatedImage, $filename);
            imagedestroy($rotatedImage);
            
            if ($result) {
                Log::info('Image successfully rotated and saved: {filename}', ['filename' => $filename, 'scope' => 'image']);
                return true;
            } else {
                Log::error('Failed to save rotated image: {filename}', ['filename' => $filename, 'scope' => 'image']);
                return false;
            }
        }
        
        imagedestroy($image);
        return false;
    }

    /**
     * Apply orientation transformation to image
     */
    private function applyOrientation($image, $orientation)
    {
        switch ($orientation) {
            case 1: // nothing
                Log::debug('No rotation needed (orientation: 1)', ['scope' => 'image']);
                return $image;

            case 2: // horizontal flip
                Log::info('Applying horizontal flip (orientation: 2)', ['scope' => 'image']);
                return imageflip($image, IMG_FLIP_HORIZONTAL) ? $image : false;

            case 3: // 180 rotate
                Log::info('Applying 180° rotation (orientation: 3)', ['scope' => 'image']);
                return imagerotate($image, 180, 0);

            case 4: // vertical flip
                Log::info('Applying vertical flip (orientation: 4)', ['scope' => 'image']);
                return imageflip($image, IMG_FLIP_VERTICAL) ? $image : false;

            case 5: // vertical flip + 90 rotate right
                Log::info('Applying vertical flip + 90° rotation (orientation: 5)', ['scope' => 'image']);
                if (imageflip($image, IMG_FLIP_VERTICAL)) {
                    return imagerotate($image, -90, 0);
                }
                return false;

            case 6: // 90 rotate right
                Log::info('Applying 90° clockwise rotation (orientation: 6)', ['scope' => 'image']);
                return imagerotate($image, -90, 0);

            case 7: // horizontal flip + 90 rotate right
                Log::info('Applying horizontal flip + 90° rotation (orientation: 7)', ['scope' => 'image']);
                if (imageflip($image, IMG_FLIP_HORIZONTAL)) {
                    return imagerotate($image, -90, 0);
                }
                return false;

            case 8: // 90 rotate left
                Log::info('Applying 90° counter-clockwise rotation (orientation: 8)', ['scope' => 'image']);
                return imagerotate($image, 90, 0);

            default:
                Log::warning('Unknown orientation value: {orientation}', ['orientation' => $orientation, 'scope' => 'image']);
                return $image;
        }
    }

    /**
     * Enhanced version that can diagnose EXIF issues
     */
    public function diagnoseExif($filename)
    {
        Log::info('=== EXIF Diagnostic for: {filename} ===', ['filename' => $filename, 'scope' => 'image']);
        
        if (!file_exists($filename)) {
            Log::error('❌ File not found', ['scope' => 'image', 'filename' => $filename]);
            return false;
        }

        // Check EXIF extension
        if (!extension_loaded('exif')) {
            Log::error('❌ EXIF extension not loaded', ['scope' => 'image']);
            return false;
        }

        if (!function_exists('exif_read_data')) {
            Log::error('❌ exif_read_data function not available', ['scope' => 'image']);
            return false;
        }

        Log::info('✅ EXIF extension is available', ['scope' => 'image']);

        // Check image type
        $imageInfo = getimagesize($filename);
        if (!$imageInfo) {
            Log::error('❌ Not a valid image file', ['scope' => 'image', 'filename' => $filename]);
            return false;
        }

        Log::info('✅ Valid image: {width}x{height}, type: {type}', [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => image_type_to_mime_type($imageInfo[2]),
            'scope' => 'image'
        ]);

        // Read EXIF data
        $exif = @exif_read_data($filename);
        
        if ($exif === false) {
            Log::error('❌ Failed to read EXIF data', ['scope' => 'image', 'filename' => $filename]);
            return false;
        }

        if (empty($exif)) {
            Log::warning('⚠️  EXIF data is empty', ['scope' => 'image', 'filename' => $filename]);
            return false;
        }

        Log::info('✅ EXIF data found', ['scope' => 'image']);

        // Check for orientation
        $orientationFound = false;
        if (isset($exif['Orientation'])) {
            Log::info('✅ Orientation in root: {orientation}', ['orientation' => $exif['Orientation'], 'scope' => 'image']);
            $orientationFound = true;
        }
        
        if (isset($exif['IFD0']['Orientation'])) {
            Log::info('✅ Orientation in IFD0: {orientation}', ['orientation' => $exif['IFD0']['Orientation'], 'scope' => 'image']);
            $orientationFound = true;
        }

        if (!$orientationFound) {
            Log::info('⚠️  No orientation data found', ['scope' => 'image']);
            Log::info('This is common when:', ['scope' => 'image']);
            Log::info('- Image was edited/processed by software that strips EXIF', ['scope' => 'image']);
            Log::info('- Camera doesn\'t record orientation', ['scope' => 'image']);
            Log::info('- Image was already rotated to correct orientation', ['scope' => 'image']);
        }

        // Show available EXIF sections
        Log::debug('Available EXIF sections:', ['scope' => 'image', 'exif_sections' => array_keys($exif)]);
        foreach (array_keys($exif) as $section) {
            if (is_array($exif[$section])) {
                Log::debug('- {section} ({count} fields)', ['section' => $section, 'count' => count($exif[$section]), 'scope' => 'image']);
            } else {
                $value = is_string($exif[$section]) ? $exif[$section] : gettype($exif[$section]);
                Log::debug('- {section}: {value}', ['section' => $section, 'value' => $value, 'scope' => 'image']);
            }
        }

        return true;
    }

    /**
     * Determines image type, calculates scaled image size, and returns resized image. If no width or height is
     * specified for the new image, the dimensions of the original image will be used, resulting in a copy
     * of the original image.
     *
     * @param string $original absolute path to original image file
     * @param string $new_filename absolute path to new image file to be created
     * @param integer $new_width (optional) width to scale new image (default 0)
     * @param integer $new_height (optional) height to scale image (default 0)
     * @param integer $quality quality of new image (default 100, resizePng will recalculate this value)
     *
     * @access public
     *
     * @return returns new image on success, false on failure. use ImageComponent::getErrors() to get an array
     * of errors on failure
     */
    public function resize($original, $new_filename, $new_width = 0, $new_height = 0, $quality = 100)
    {
        if (!($image_params = getimagesize($original))) {
            $this->__errors[] = 'Original file is not a valid image: ' . $original;
            return false;
        }

        $width = $image_params[0];
        $height = $image_params[1];

        if (0 != $new_width && 0 == $new_height) {
            $scaled_width = $new_width;
            $scaled_height = floor($new_width * $height / $width);
        } elseif (0 != $new_height && 0 == $new_width) {
            $scaled_height = $new_height;
            $scaled_width = floor($new_height * $width / $height);
        } elseif (0 == $new_width && 0 == $new_height) { //assume we want to create a new image the same exact size
            $scaled_width = $width;
            $scaled_height = $height;
        } else { //assume we want to create an image with these exact dimensions, most likely resulting in distortion
            $scaled_width = $new_width;
            $scaled_height = $new_height;
        }

        //create image
        $ext = $image_params[2];
        switch ($ext) {
            case IMAGETYPE_GIF:
                $return = $this->__resizeGif($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality);
                break;
            case IMAGETYPE_JPEG:
                $return = $this->__resizeJpeg($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality);
                break;
            case IMAGETYPE_PNG:
                $return = $this->__resizePng($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality);
                break;
            default:
                $return = $this->__resizeJpeg($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality);
                break;
        }

        return $return;
    }

    private function __resizeGif($original, $new_filename, $scaled_width, $scaled_height, $width, $height)
    {
        $error = false;

        if (!($src = imagecreatefromgif($original))) {
            $this->__errors[] = 'There was an error creating your resized image (gif).';
            $error = true;
        }

        if (!($tmp = imagecreatetruecolor($scaled_width, $scaled_height))) {
            $this->__errors[] = 'There was an error creating your true color image (gif).';
            $error = true;
        }

        if (!imagecopyresampled($tmp, $src, 0, 0, 0, 0, $scaled_width, $scaled_height, $width, $height)) {
            $this->__errors[] = 'There was an error creating your true color image (gif).';
            $error = true;
        }

        if (!($new_image = imagegif($tmp, $new_filename))) {
            $this->__errors[] = 'There was an error writing your image to file (gif).';
            $error = true;
        }

        imagedestroy($tmp);

        if (false == $error) {
            return $new_image;
        }

        return false;
    }

    private function __resizeJpeg($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality)
    {
        $error = false;

        if (!($src = imagecreatefromjpeg($original))) {
            $this->__errors[] = 'There was an error creating your resized image (jpg).';
            $error = true;
        }

        if (!($tmp = imagecreatetruecolor($scaled_width, $scaled_height))) {
            $this->__errors[] = 'There was an error creating your true color image (jpg).';
            $error = true;
        }

        if (!imagecopyresampled($tmp, $src, 0, 0, 0, 0, $scaled_width, $scaled_height, $width, $height)) {
            $this->__errors[] = 'There was an error creating your true color image (jpg).';
            $error = true;
        }

        if (!($new_image = imagejpeg($tmp, $new_filename, $quality))) {
            $this->__errors[] = 'There was an error writing your image to file (jpg).';
            $error = true;
        }

        imagedestroy($tmp);

        if (false == $error) {
            return $new_image;
        }

        return false;
    }

    private function __resizePng($original, $new_filename, $scaled_width, $scaled_height, $width, $height, $quality)
    {
        $error = false;
        /**
         * we need to recalculate the quality for imagepng()
         * the quality parameter in imagepng() is actually the compression level,
         * so the higher the value (0-9), the lower the quality. this is pretty much
         * the opposite of how imagejpeg() works.
         */
        $quality = ceil($quality / 10); // 0 - 100 value
        if (0 == $quality) {
            $quality = 9;
        } else {
            $quality = ($quality - 1) % 9;
        }


        if (!($src = imagecreatefrompng($original))) {
            $this->__errors[] = 'There was an error creating your resized image (png).';
            $error = true;
        }

        if (!($tmp = imagecreatetruecolor($scaled_width, $scaled_height))) {
            $this->__errors[] = 'There was an error creating your true color image (png).';
            $error = true;
        }

        imagealphablending($tmp, false);

        if (!imagecopyresampled($tmp, $src, 0, 0, 0, 0, $scaled_width, $scaled_height, $width, $height)) {
            $this->__errors[] = 'There was an error creating your true color image (png).';
            $error = true;
        }

        imagesavealpha($tmp, true);

        if (!($new_image = imagepng($tmp, $new_filename, $quality))) {
            $this->__errors[] = 'There was an error writing your image to file (png).';
            $error = true;
        }

        imagedestroy($tmp);

        if (false == $error) {
            return $new_image;
        }

        return false;
    }

}
