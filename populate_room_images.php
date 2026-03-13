<?php
/**
 * Populate Room Images Database
 * This script scans the uploads/rooms/individual folder and adds all images to the database
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Populate Room Images</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>📸 Populate Room Images Database</h1>
    <div class='info'><strong>ℹ️ This script will scan your uploads folder and add images to the database</strong></div>
";

try {
    $conn = getDBConnection();
    $uploadsDir = 'uploads/rooms/individual/';
    
    // Get all image files
    $files = scandir($uploadsDir);
    $imageFiles = array_filter($files, function($f) {
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f) && $f !== 'index.php';
    });
    
    echo "<p>Found " . count($imageFiles) . " image files in " . $uploadsDir . "</p>";
    
    if (count($imageFiles) === 0) {
        echo "<div class='error'>❌ No image files found in uploads folder</div>";
        echo "</body></html>";
        exit;
    }
    
    // Clear existing room images (optional - comment out if you want to keep them)
    // $conn->query("DELETE FROM room_images");
    
    echo "<table>";
    echo "<tr><th>Filename</th><th>Room Type</th><th>Pax Group</th><th>Status</th></tr>";
    
    $added = 0;
    $skipped = 0;
    
    foreach ($imageFiles as $filename) {
        // Determine room type and pax group from filename pattern
        // This is a simple heuristic - adjust based on your naming convention
        
        // Default assignment
        $roomType = 'Regular';
        $paxGroup = 2;
        $roomNumber = '101';
        
        // Try to determine from filename
        if (strpos($filename, 'deluxe') !== false || strpos($filename, 'Deluxe') !== false) {
            $roomType = 'Deluxe';
        } elseif (strpos($filename, 'vip') !== false || strpos($filename, 'VIP') !== false) {
            $roomType = 'VIP';
        }
        
        // Check if already exists
        $stmt = $conn->prepare("SELECT id FROM room_images WHERE filename = ?");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows > 0) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($filename) . "</code></td>";
            echo "<td>" . htmlspecialchars($roomType) . "</td>";
            echo "<td>" . $paxGroup . "</td>";
            echo "<td><span style='color: blue;'>⊘ Already exists</span></td>";
            echo "</tr>";
            $skipped++;
            continue;
        }
        
        // Insert into database
        $filePath = $uploadsDir . $filename;
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);
        $sortOrder = $added;
        
        $stmt = $conn->prepare("INSERT INTO room_images (room_number, room_type, pax_group, filename, original_name, file_path, file_size, mime_type, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
        $stmt->bind_param("ssisssisi", $roomNumber, $roomType, $paxGroup, $filename, $filename, $filePath, $fileSize, $mimeType, $sortOrder);
        
        if ($stmt->execute()) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($filename) . "</code></td>";
            echo "<td>" . htmlspecialchars($roomType) . "</td>";
            echo "<td>" . $paxGroup . "</td>";
            echo "<td><span style='color: green;'>✅ Added</span></td>";
            echo "</tr>";
            $added++;
        } else {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($filename) . "</code></td>";
            echo "<td>" . htmlspecialchars($roomType) . "</td>";
            echo "<td>" . $paxGroup . "</td>";
            echo "<td><span style='color: red;'>❌ Error: " . htmlspecialchars($stmt->error) . "</span></td>";
            echo "</tr>";
        }
        $stmt->close();
    }
    
    echo "</table>";
    
    echo "<div class='success'>✅ Added " . $added . " images to database</div>";
    if ($skipped > 0) {
        echo "<div class='info'>ℹ️ Skipped " . $skipped . " images (already in database)</div>";
    }
    
    // Show summary
    $result = $conn->query("SELECT room_type, COUNT(*) as count FROM room_images GROUP BY room_type");
    echo "<h2>Database Summary</h2>";
    echo "<table>";
    echo "<tr><th>Room Type</th><th>Image Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['room_type']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
    
    echo "<div class='success'><strong>✅ Done! Images are now in the database.</strong></div>";
    echo "<p>Next steps:</p>";
    echo "<ol>";
    echo "<li>Clear your browser cache (Ctrl+Shift+Delete)</li>";
    echo "<li>Go to booking page: <a href='booking.php'>booking.php</a></li>";
    echo "<li>Select a room and check if images display</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
