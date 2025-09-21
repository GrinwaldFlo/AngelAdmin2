<?php
/**
 * EXIF Test Script
 * 
 * Place this file in your webroot and access it via browser to test EXIF functionality
 * Usage: http://yoursite.com/exif_test.php
 */

// Basic EXIF test
echo "<h1>EXIF Extension Test</h1>";

if (!extension_loaded('exif')) {
    echo "<p style='color: red;'>? EXIF extension is NOT loaded!</p>";
    echo "<p>You need to install and enable the PHP EXIF extension.</p>";
    exit;
}

echo "<p style='color: green;'>? EXIF extension is loaded</p>";

if (!function_exists('exif_read_data')) {
    echo "<p style='color: red;'>? exif_read_data function is not available!</p>";
    exit;
}

echo "<p style='color: green;'>? exif_read_data function is available</p>";

// Test with actual images
$testImages = [
    'img/carteId.jpg',
    'files/photo.jpg',
    // Add more paths if you have test images
];

echo "<h2>Testing Images for EXIF Data</h2>";

foreach ($testImages as $imagePath) {
    if (!file_exists($imagePath)) {
        echo "<p>?? Image not found: $imagePath</p>";
        continue;
    }
    
    echo "<h3>Testing: $imagePath</h3>";
    
    $exif = @exif_read_data($imagePath);
    
    if ($exif === false) {
        echo "<p style='color: orange;'>?? No EXIF data or failed to read</p>";
        continue;
    }
    
    if (empty($exif)) {
        echo "<p style='color: orange;'>?? EXIF data is empty</p>";
        continue;
    }
    
    echo "<p style='color: green;'>? EXIF data found</p>";
    
    // Check for orientation
    $orientation = null;
    if (isset($exif['Orientation'])) {
        $orientation = $exif['Orientation'];
        echo "<p style='color: green;'>? Orientation found: $orientation</p>";
    } elseif (isset($exif['IFD0']['Orientation'])) {
        $orientation = $exif['IFD0']['Orientation'];
        echo "<p style='color: green;'>? Orientation found in IFD0: $orientation</p>";
    } else {
        echo "<p style='color: orange;'>?? No orientation data</p>";
    }
    
    // Show key EXIF data
    $keyFields = ['Make', 'Model', 'DateTime', 'Software'];
    echo "<ul>";
    foreach ($keyFields as $field) {
        if (isset($exif[$field])) {
            echo "<li>$field: " . htmlspecialchars($exif[$field]) . "</li>";
        }
    }
    echo "</ul>";
}

echo "<h2>Upload Test Image</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $uploadedFile = $_FILES['test_image'];
    
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $tempPath = $uploadedFile['tmp_name'];
        echo "<h3>Testing uploaded image: " . htmlspecialchars($uploadedFile['name']) . "</h3>";
        
        $exif = @exif_read_data($tempPath);
        
        if ($exif === false) {
            echo "<p style='color: orange;'>?? No EXIF data found in uploaded image</p>";
        } else {
            echo "<p style='color: green;'>? EXIF data found in uploaded image</p>";
            
            if (isset($exif['Orientation'])) {
                echo "<p style='color: green;'>? Orientation: " . $exif['Orientation'] . "</p>";
            } elseif (isset($exif['IFD0']['Orientation'])) {
                echo "<p style='color: green;'>? Orientation (IFD0): " . $exif['IFD0']['Orientation'] . "</p>";
            } else {
                echo "<p style='color: orange;'>?? No orientation data in uploaded image</p>";
            }
            
            // Show all EXIF sections
            echo "<h4>EXIF Sections Found:</h4>";
            echo "<ul>";
            foreach (array_keys($exif) as $section) {
                if (is_array($exif[$section])) {
                    echo "<li>$section (" . count($exif[$section]) . " fields)</li>";
                } else {
                    echo "<li>$section: " . htmlspecialchars(is_string($exif[$section]) ? $exif[$section] : gettype($exif[$section])) . "</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>Upload error: " . $uploadedFile['error'] . "</p>";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <p>Upload a test image to check its EXIF data:</p>
    <input type="file" name="test_image" accept="image/jpeg,image/jpg">
    <button type="submit">Test EXIF</button>
</form>

<h2>How to Get Images with Orientation Data</h2>
<p>To test orientation functionality, you need images that actually contain orientation EXIF data:</p>
<ul>
    <li>Take photos with a modern smartphone (iPhone, Android) and ensure they're not processed by apps</li>
    <li>Use a digital camera with orientation sensor enabled</li>
    <li>Avoid images that have been edited with photo editing software (they often strip EXIF)</li>
    <li>Avoid images downloaded from social media (they strip EXIF for privacy)</li>
</ul>

<h2>Alternative Solutions</h2>
<p>If many of your images lack orientation data, consider:</p>
<ul>
    <li>Using ImageMagick instead of GD library for better EXIF support</li>
    <li>Providing manual rotation controls in your interface</li>
    <li>Using JavaScript libraries to detect orientation on the client side</li>
    <li>Implementing machine learning-based auto-rotation</li>
</ul>
