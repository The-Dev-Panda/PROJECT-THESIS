<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once("../Login/connection.php");

// Get request data
$request = json_decode(file_get_contents('php://input'), true);

if ($request['action'] === 'create_backup') {
    try {
        // Define paths
        $db_path = '../Login/fitstop_database.db';
        $backup_dir = '../backups/';
        
        // Create backups directory if it doesn't exist
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Generate filename
        $timestamp = date('Y-m-d_His');
        $backup_filename = "FITSTOP_Backup_{$timestamp}.db";
        $zip_filename = "FITSTOP_Backup_{$timestamp}.zip";
        $backup_file = $backup_dir . $backup_filename;
        $zip_file = $backup_dir . $zip_filename;
        
        // Copy database file
        if (!copy($db_path, $backup_file)) {
            throw new Exception('Failed to create backup file');
        }
        
        // Create ZIP archive with password
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Failed to create ZIP archive');
        }
        
        // Add database to ZIP
        $zip->addFile($backup_file, $backup_filename);
        
        // Set encryption (password protection)
        $password = 'FITSTOP' . date('Y'); // Password format: FITSTOP2026
        $zip->setPassword($password);
        $zip->setEncryptionName($backup_filename, ZipArchive::EM_AES_256);
        
        $zip->close();
        
        // Get file size
        $file_size = filesize($zip_file);
        
        // Read ZIP file for email
        $zip_content = file_get_contents($zip_file);
        $zip_base64 = base64_encode($zip_content);
        
        // Send email
        $to = 'noreplayfitstop@gmail.com';
        $subject = 'FITSTOP Database Backup - ' . date('F d, Y');
        
        $boundary = md5(time());
        
        $headers = "From: FITSTOP Backup System noreplayfitstop@gmail.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        
        $message .= "<html><body style='font-family: Arial, sans-serif;'>";
        $message .= "<div style='background: #080808; padding: 30px; color: #fff;'>";
        $message .= "<h2 style='color: #FFCC00; margin: 0 0 10px 0;'>🔒 FITSTOP Database Backup</h2>";
        $message .= "<p style='color: #999; margin: 0 0 20px 0;'>Automated backup notification</p>";
        $message .= "<div style='background: #1a1a1a; border: 1px solid #333; padding: 20px; margin: 20px 0;'>";
        $message .= "<h3 style='color: #FFCC00; margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase;'>Backup Details</h3>";
        $message .= "<table style='width: 100%; color: #ccc; font-size: 13px;'>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>Backup Date:</strong></td><td>" . date('F d, Y g:i A') . "</td></tr>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>Filename:</strong></td><td>{$zip_filename}</td></tr>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>File Size:</strong></td><td>" . number_format($file_size / 1024, 2) . " KB</td></tr>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>Created By:</strong></td><td>" . $_SESSION['username'] . "</td></tr>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>Encryption:</strong></td><td>AES-256 (Password Protected)</td></tr>";
        $message .= "<tr><td style='padding: 8px 0;'><strong>Password:</strong></td><td style='color: #FFCC00; font-family: monospace; font-weight: bold;'>{$password}</td></tr>";
        $message .= "</table>";
        $message .= "</div>";
        $message .= "<div style='background: rgba(255, 204, 0, 0.1); border: 1px solid #FFCC00; padding: 15px; margin: 20px 0;'>";
        $message .= "<p style='margin: 0; font-size: 12px; color: #FFCC00;'><strong>⚠️ IMPORTANT:</strong> This backup is encrypted. Save the password above to extract the files.</p>";
        $message .= "</div>";
        $message .= "<p style='color: #666; font-size: 11px; margin: 30px 0 0 0;'>This is an automated message from FITSTOP Backup System.</p>";
        $message .= "</div>";
        $message .= "</body></html>\r\n\r\n";
        
        // Attachment
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/zip; name=\"{$zip_filename}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$zip_filename}\"\r\n\r\n";
        $message .= chunk_split($zip_base64) . "\r\n";
        $message .= "--{$boundary}--";
        
        // Send email
        $email_sent = mail($to, $subject, $message, $headers);
        
        if (!$email_sent) {
            throw new Exception('Failed to send email');
        }
        
        // Log to database
        $stmt = $pdo->prepare("INSERT INTO backup_history (backup_filename, backup_size, backup_type, status, sent_to_email, created_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $zip_filename,
            $file_size,
            'manual',
            'success',
            $to,
            $_SESSION['username'],
            "Backup created successfully. Password: {$password}"
        ]);
        
        // Clean up local files
        unlink($backup_file);
        unlink($zip_file);
        
        echo json_encode([
            'success' => true,
            'filename' => $zip_filename,
            'size' => $file_size
        ]);
        
    } catch (Exception $e) {
        // Log failed backup
        $stmt = $pdo->prepare("INSERT INTO backup_history (backup_filename, backup_type, status, created_by, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Failed_' . date('Y-m-d_His'),
            'manual',
            'failed',
            $_SESSION['username'] ?? 'system',
            'Error: ' . $e->getMessage()
        ]);
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>