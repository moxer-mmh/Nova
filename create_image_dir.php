<?php
// Script to create the books images directory and copy sample images

// Define the images directory
$booksDir = __DIR__ . '/frontend/assets/images/books/';

// Create the directory if it doesn't exist
if (!is_dir($booksDir)) {
    if (mkdir($booksDir, 0755, true)) {
        echo "<p>Successfully created directory: $booksDir</p>";
    } else {
        echo "<p>Failed to create directory: $booksDir</p>";
        echo "<p>Error: " . error_get_last()['message'] . "</p>";
    }
} else {
    echo "<p>Directory already exists: $booksDir</p>";
}

// Check if directory is writable
if (is_writable($booksDir)) {
    echo "<p>Directory is writable: $booksDir</p>";
} else {
    echo "<p>Directory is NOT writable: $booksDir</p>";
    echo "<p>Current permissions: " . substr(sprintf('%o', fileperms($booksDir)), -4) . "</p>";
    
    // Try to set permissions
    if (chmod($booksDir, 0755)) {
        echo "<p>Successfully set directory permissions to 0755</p>";
    } else {
        echo "<p>Failed to set directory permissions</p>";
    }
}

// Create placeholder sample images
$sampleImages = [
    'petit_prince.jpg' => '#FFD700', // Gold color for Le Petit Prince
    'etranger.jpg' => '#708090',     // Slate gray for L'Étranger
    'dune.jpg' => '#CD853F',         // Peru color for Dune
    'placeholder.jpg' => '#CCCCCC'   // Light gray for generic placeholder
];

foreach ($sampleImages as $filename => $color) {
    $imagePath = $booksDir . $filename;
    
    if (!file_exists($imagePath)) {
        // Create a simple colored image
        $image = imagecreatetruecolor(300, 400);
        $colorCode = imagecolorallocate(
            $image,
            hexdec(substr($color, 1, 2)),
            hexdec(substr($color, 3, 2)),
            hexdec(substr($color, 5, 2))
        );
        imagefill($image, 0, 0, $colorCode);
        
        // Add some text to the image
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $text = pathinfo($filename, PATHINFO_FILENAME);
        imagestring($image, 5, 100, 180, $text, $textColor);
        
        // Save the image
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);
        
        echo "<p>Created sample image: $filename</p>";
    } else {
        echo "<p>Sample image already exists: $filename</p>";
    }
}

echo "<p>Book images setup complete!</p>";
echo "<p><a href='/Nova/'>Return to homepage</a></p>";
?>
