# HEIC Image Support

This application now supports HEIC (High Efficiency Image Container) format commonly used by iOS devices. When HEIC support is enabled, users can upload HEIC images which will be automatically converted to JPEG format.

## Current Status

Run the following command to check HEIC support status:

```bash
bin/cake test_heic
```

## Enabling HEIC Support

### Requirements

1. **ImageMagick PHP Extension**: Install the `php-imagick` extension
2. **ImageMagick with HEIC support**: ImageMagick must be compiled with libheif support

### Installation Instructions

#### Ubuntu/Debian
```bash
# Install ImageMagick with HEIC support
sudo apt update
sudo apt install imagemagick libheif-dev libheif1

# Install PHP ImageMagick extension
sudo apt install php-imagick

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

#### CentOS/RHEL
```bash
# Install EPEL repository if not already installed
sudo yum install epel-release

# Install ImageMagick and dependencies
sudo yum install ImageMagick ImageMagick-devel libheif libheif-devel

# Install PHP ImageMagick extension
sudo yum install php-imagick

# Restart web server
sudo systemctl restart httpd
```

#### macOS (using Homebrew)
```bash
# Install ImageMagick with HEIC support
brew install imagemagick libheif

# Install PHP ImageMagick extension (if using Homebrew PHP)
brew install php-imagick
```

#### Docker
If running in Docker, add to your Dockerfile:
```dockerfile
RUN apt-get update && apt-get install -y \
    imagemagick \
    libheif-dev \
    libheif1 \
    php-imagick \
    && rm -rf /var/lib/apt/lists/*
```

### Verification

After installation, run the test command again:
```bash
bin/cake test_heic
```

You should see "HEIC Support Status: Supported" if everything is configured correctly.

## How It Works

1. **File Upload**: Users can upload HEIC files through the normal photo upload interface
2. **Detection**: The application detects HEIC files by MIME type
3. **Conversion**: HEIC files are automatically converted to JPEG format
4. **Processing**: The converted JPEG is processed like any other JPEG upload (resizing, EXIF rotation, etc.)
5. **Storage**: Only the JPEG version is stored on the server

## Supported Formats

When HEIC support is enabled:
- JPEG (.jpg, .jpeg)
- HEIC (.heic)
- HEIF (.heif)

When HEIC support is disabled:
- JPEG (.jpg, .jpeg) only

## Troubleshooting

### Common Issues

1. **"ImageMagick extension not installed"**
   - Install the php-imagick extension as described above

2. **"ImageMagick installed but HEIC/HEIF formats not supported"**
   - ImageMagick needs to be compiled with libheif support
   - Try installing from a different repository or rebuilding ImageMagick

3. **"Failed to convert HEIC image"**
   - Check that the uploaded file is a valid HEIC image
   - Check server logs for detailed error messages

### Checking ImageMagick HEIC Support

You can also check directly from command line:
```bash
# List supported formats
convert -list format | grep -i heic

# Should show something like:
# HEIC* HEIC      rw-   High Efficiency Image Format
```

## Technical Details

- **Library Used**: Intervention Image with ImageMagick driver
- **Conversion Quality**: 90% JPEG quality (configurable)
- **Temporary Files**: Safely handled and cleaned up after conversion
- **Error Handling**: Graceful fallback with user-friendly error messages
- **Performance**: Conversion happens once during upload, no runtime overhead

## Security Considerations

- Only allows known HEIC MIME types
- Validates file extensions
- Uses temporary files that are automatically cleaned up
- Conversion process is isolated and error-handled
- Converted files go through the same security checks as regular JPEG uploads
