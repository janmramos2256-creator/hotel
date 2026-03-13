<?php
/**
 * Image Diagnostic Script
 * Checks database records and file system to diagnose image issues
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Image Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; }
        .status-ok { color: green; }
        .status-error { color: red; }
        .status-warning { color: orange; }
        h2 { margin-top: 2rem; border-bottom: 2px solid #C9A961; padding-bottom: 0.5rem; }
    </style>
</head>
<body>
    <h1>🔍 Image Diagnostic Report</h1>
    <div class='info'><strong>ℹ️ This script checks your image database records and files</strong></div>
";

try {
    $conn = getDBConnection();
    
    // Check 1: Room Images in Database
    echo "<h2>1. Room Images in Database</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM room_images");
    $row = $result->fetch_assoc();
    $roomImageCount = $row['count'];
    
    if ($roomImageCount > 0) {
        echo "<div class='success'>✅ Found " . $roomImageCount . " room image records in database</div>";
    } else {
        echo "<div class='error'>❌ No room image records found in database</div>";
    }
    
    // Check 2: Sample Room Images
    echo "<h2>2. Sample Room Image Records</h2>";
    $result = $conn->query("SELECT id, filename, file_path, room_type, room_number, pax_group FROM room_images LIMIT 10");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Filename</th><th>File Path</th><th>Room Type</th><th>Room #</th><th>Pax</th><th>File Exists</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $filePath = $row['file_path'];
            $filename = $row['filename'];
            
            // Check if file exists
            $fileExists = file_exists($filePath);
            $status = $fileExists ? '<span class=\"status-ok\">✅ Yes</span>' : '<span class=\"status-error\">❌ No</span>';
            
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td><code>" . htmlspecialchars($filename) . "</code></td>";
            echo "<td><code>" . htmlspecialchars($filePath) . "</code></td>";
            echo "<td>" . htmlspecialchars($row['room_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
            echo "<td>" . $row['pax_group'] . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check 3: File System Check
    echo "<h2>3. File System Check</h2>";
    $uploadsDir = 'uploads/rooms/individual/';
    
    if (is_dir($uploadsDir)) {
        echo "<div class='success'>✅ Uploads directory exists: <code>" . $uploadsDir . "</code></div>";
        
        $files = scandir($uploadsDir);
        $imageFiles = array_filter($files, function($f) {
            return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
        });
        
        echo "<div class='info'>Found " . count($imageFiles) . " image files in uploads folder</div>";
    } else {
        echo "<div class='error'>❌ Uploads directory not found: <code>" . $uploadsDir . "</code></div>";
    }
    
    // Check 4: Database vs File System Mismatch
    echo "<h2>4. Database vs File System Comparison</h2>";
    
    $result = $conn->query("SELECT filename, file_path FROM room_images GROUP BY filename");
    $dbFiles = [];
    while ($row = $result->fetch_assoc()) {
        $dbFiles[$row['filename']] = $row['file_path'];
    }
    
    $filesystemFiles = array_filter(scandir($uploadsDir), function($f) {
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
    });
    
    $missingInDB = array_diff($filesystemFiles, array_keys($dbFiles));
    $missingInFS = array_diff(array_keys($dbFiles), $filesystemFiles);
    
    if (empty($missingInDB) && empty($missingInFS)) {
        echo "<div class='success'>✅ All files match between database and file system</div>";
    } else {
        if (!empty($missingInDB)) {
            echo "<div class='warning'>⚠️ " . count($missingInDB) . " files exist in file system but not in database:</div>";
            echo "<ul>";
            foreach (array_slice($missingInDB, 0, 10) as $file) {
                echo "<li><code>" . htmlspecialchars($file) . "</code></li>";
            }
            if (count($missingInDB) > 10) {
                echo "<li>... and " . (count($missingInDB) - 10) . " more</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($missingInFS)) {
            echo "<div class='error'>❌ " . count($missingInFS) . " files in database but missing from file system:</div>";
            echo "<table>";
            echo "<tr><th>Filename</th><th>Database Path</th></tr>";
            foreach (array_slice($missingInFS, 0, 10) as $file) {
                echo "<tr>";
                echo "<td><code>" . htmlspecialchars($file) . "</code></td>";
                echo "<td><code>" . htmlspecialchars($dbFiles[$file]) . "</code></td>";
                echo "</tr>";
            }
            echo "</table>";
            if (count($missingInFS) > 10) {
                echo "<p>... and " . (count($missingInFS) - 10) . " more</p>";
            }
        }
    }
    
    // Check 5: API Test
    echo "<h2>5. API Test</h2>";
    echo "<p>Testing the image API endpoint...</p>";
    
    // Get a sample room
    $result = $conn->query("SELECT DISTINCT room_number, room_type, pax_group FROM room_images LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $roomNumber = $row['room_number'];
        $roomType = $row['room_type'];
        $paxGroup = $row['pax_group'];
        
        echo "<p>Testing with: Room " . htmlspecialchars($roomNumber) . " (" . htmlspecialchars($roomType) . ", " . $paxGroup . " pax)</p>";
        echo "<p>API URL: <code>api/get_room_image.php?room_number=" . urlencode($roomNumber) . "&room_type=" . urlencode($roomType) . "&pax_group=" . $paxGroup . "</code></p>";
    }
    
    // Check 6: Website Photos
    echo "<h2>6. Website Photos</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM website_photos");
    $row = $result->fetch_assoc();
    $photoCount = $row['count'];
    
    if ($photoCount > 0) {
        echo "<div class='success'>✅ Found " . $photoCount . " website photo records</div>";
        
        $result = $conn->query("SELECT section, COUNT(*) as count FROM website_photos GROUP BY section");
        echo "<table>";
        echo "<tr><th>Section</th><th>Count</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['section']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No website photo records found</div>";
    }
    
    // Check 7: Recommendations
    echo "<h2>7. Recommendations</h2>";
    
    $issues = [];
    
    if ($roomImageCount === 0) {
        $issues[] = "No room images in database - upload images through admin panel";
    }
    
    if (!empty($missingInFS)) {
        $issues[] = "Some database records point to missing files - delete orphaned records or upload missing files";
    }
    
    if (empty($issues)) {
        echo "<div class='success'>✅ No issues detected! Images should display correctly.</div>";
        echo "<p>If images still don't display:</p>";
        echo "<ol>";
        echo "<li>Clear browser cache (Ctrl+Shift+Delete)</li>";
        echo "<li>Check browser console (F12) for errors</li>";
        echo "<li>Try a different browser</li>";
        echo "<li>Check server error logs</li>";
        echo "</ol>";
    } else {
        echo "<div class='error'>Issues found:</div>";
        echo "<ol>";
        foreach ($issues as $issue) {
            echo "<li>" . htmlspecialchars($issue) . "</li>";
        }
        echo "</ol>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
