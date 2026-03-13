<?php
/**
 * Fix Image Paths Script
 * This script corrects all image paths in the database to ensure images display properly
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Image Paths</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🖼️ Fix Image Paths</h1>
    <div class='info'><strong>ℹ️ This script will correct image paths in the database</strong></div>
";

try {
    $conn = getDBConnection();
    
    // Get all images from room_images table
    echo "<h2>Room Images</h2>";
    $result = $conn->query("SELECT id, filename, file_path, room_type, room_number FROM room_images ORDER BY id");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Filename</th><th>Current Path</th><th>Status</th></tr>";
        
        $updated = 0;
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $filename = $row['filename'];
            $currentPath = $row['file_path'];
            $roomType = $row['room_type'];
            $roomNumber = $row['room_number'];
            
            // Correct path should be: uploads/rooms/individual/filename
            $correctPath = 'uploads/rooms/individual/' . $filename;
            
            // Check if path needs fixing
            $needsFix = ($currentPath !== $correctPath);
            
            if ($needsFix) {
                // Update the path
                $stmt = $conn->prepare("UPDATE room_images SET file_path = ? WHERE id = ?");
                $stmt->bind_param("si", $correctPath, $id);
                if ($stmt->execute()) {
                    echo "<tr>";
                    echo "<td>" . $id . "</td>";
                    echo "<td>" . htmlspecialchars($filename) . "</td>";
                    echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                    echo "<td><span style='color: green;'>✅ Fixed</span></td>";
                    echo "</tr>";
                    $updated++;
                } else {
                    echo "<tr>";
                    echo "<td>" . $id . "</td>";
                    echo "<td>" . htmlspecialchars($filename) . "</td>";
                    echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                    echo "<td><span style='color: red;'>❌ Error</span></td>";
                    echo "</tr>";
                }
                $stmt->close();
            } else {
                echo "<tr>";
                echo "<td>" . $id . "</td>";
                echo "<td>" . htmlspecialchars($filename) . "</td>";
                echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                echo "<td><span style='color: blue;'>✓ OK</span></td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        echo "<div class='success'>✅ Updated " . $updated . " image paths</div>";
    } else {
        echo "<div class='info'>ℹ️ No room images found in database</div>";
    }
    
    // Get all images from website_photos table
    echo "<h2>Website Photos</h2>";
    $result = $conn->query("SELECT id, filename, file_path, section FROM website_photos ORDER BY id");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Filename</th><th>Section</th><th>Current Path</th><th>Status</th></tr>";
        
        $updated = 0;
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $filename = $row['filename'];
            $currentPath = $row['file_path'];
            $section = $row['section'];
            
            // Determine correct path based on section
            $sectionMap = [
                'carousel' => 'uploads/carousel/',
                'restaurant' => 'uploads/restaurant/',
                'spa' => 'uploads/spa/',
                'pool' => 'uploads/pool/',
                'pavilion' => 'uploads/pavilion/',
                'bar' => 'uploads/bar/'
            ];
            
            $correctPath = ($sectionMap[$section] ?? 'uploads/') . $filename;
            
            // Check if path needs fixing
            $needsFix = ($currentPath !== $correctPath);
            
            if ($needsFix) {
                // Update the path
                $stmt = $conn->prepare("UPDATE website_photos SET file_path = ? WHERE id = ?");
                $stmt->bind_param("si", $correctPath, $id);
                if ($stmt->execute()) {
                    echo "<tr>";
                    echo "<td>" . $id . "</td>";
                    echo "<td>" . htmlspecialchars($filename) . "</td>";
                    echo "<td>" . htmlspecialchars($section) . "</td>";
                    echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                    echo "<td><span style='color: green;'>✅ Fixed</span></td>";
                    echo "</tr>";
                    $updated++;
                } else {
                    echo "<tr>";
                    echo "<td>" . $id . "</td>";
                    echo "<td>" . htmlspecialchars($filename) . "</td>";
                    echo "<td>" . htmlspecialchars($section) . "</td>";
                    echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                    echo "<td><span style='color: red;'>❌ Error</span></td>";
                    echo "</tr>";
                }
                $stmt->close();
            } else {
                echo "<tr>";
                echo "<td>" . $id . "</td>";
                echo "<td>" . htmlspecialchars($filename) . "</td>";
                echo "<td>" . htmlspecialchars($section) . "</td>";
                echo "<td><code>" . htmlspecialchars($correctPath) . "</code></td>";
                echo "<td><span style='color: blue;'>✓ OK</span></td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        echo "<div class='success'>✅ Updated " . $updated . " website photo paths</div>";
    } else {
        echo "<div class='info'>ℹ️ No website photos found in database</div>";
    }
    
    $conn->close();
    
    echo "<div class='success'><strong>✅ Image path fix completed!</strong></div>";
    echo "<p>Your images should now display correctly. If you still see missing images:</p>";
    echo "<ol>";
    echo "<li>Make sure image files exist in the uploads folder</li>";
    echo "<li>Check file permissions (should be readable)</li>";
    echo "<li>Clear your browser cache (Ctrl+Shift+Delete)</li>";
    echo "<li>Try uploading new images through the admin panel</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
