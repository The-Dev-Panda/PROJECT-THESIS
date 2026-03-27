<?php
// This file should be called by a cron job monthly
// Cron: 0 2 1 * * /usr/bin/php /path/to/automated_backup_cron.php

require_once("../Login/connection.php");

try {
    // Define paths
    $db_path = '../Login/fitstop_database.db';
    $backup_dir = '../backups/';
    
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_His');
    $backup_filename = "FITSTOP_AutoBackup_{$timestamp}.db";
    $zip_filename = "FITSTOP_AutoBackup_{$timestamp}.zip";
    $backup_file = $backup_dir . $backup_filename;
    $zip_file = $backup_dir . $zip_filename;
    
    // Copy database
    copy($db_path, $backup_file);
    
    // Create ZIP
    $zip = new ZipArchive();
    $zip->open($zip_file, ZipArchive::CREATE);
    $zip->addFile($backup_file, $backup_filename);
    
    $password = 'FITSTOP' . date('Y');
    $zip->setPassword($password);
    $zip->setEncryptionName($backup_filename, ZipArchive::EM_AES_256);
    $zip->close();
    
    $file_size = filesize($zip_file);
    $zip_content = file_get_contents($zip_file);
    $zip_base64 = base64_encode($zip_content);
    
    // Send email
    $to = 'noreplayfitstop@gmail.com';
    $subject = 'FITSTOP Monthly Automated Backup - ' . date('F Y');
    
    $boundary = md5(time());
    
    $headers = "From: FITSTOP Backup System <noreplayfitstop@gmail.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    
    $message .= "<html><body style='font-family: Arial, sans-serif;'>";
    $message .= "<div style='background: #080808; padding: 30px; color: #fff;'>";
    $message .= "<h2 style='color: #FFCC00;'>📅 Monthly Automated Backup</h2>";
    $message .= "<p style='color: #999;'>Scheduled backup completed successfully</p>";
    $message .= "<div style='background: #1a1a1a; padding: 20px; margin: 20px 0;'>";
    $message .= "<p><strong>Date:</strong> " . date('F d, Y g:i A') . "</p>";
    $message .= "<p><strong>File:</strong> {$zip_filename}</p>";
    $message .= "<p><strong>Size:</strong> " . number_format($file_size / 1024, 2) . " KB</p>";
    $message .= "<p><strong>Password:</strong> <span style='color: #FFCC00; font-family: monospace;'>{$password}</span></p>";
    $message .= "</div>";
    $message .= "</div>";
    $message .= "</body></html>\r\n\r\n";
    
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: application/zip; name=\"{$zip_filename}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$zip_filename}\"\r\n\r\n";
    $message .= chunk_split($zip_base64) . "\r\n";
    $message .= "--{$boundary}--";
    
    mail($to, $subject, $message, $headers);
    
    // Log to database
    $stmt = $pdo->prepare("INSERT INTO backup_history (backup_filename, backup_size, backup_type, status, sent_to_email, created_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $zip_filename,
        $file_size,
        'automated',
        'success',
        $to,
        'system',
        "Monthly automated backup. Password: {$password}"
    ]);
    
    // Clean up
    unlink($backup_file);
    unlink($zip_file);
    
    echo "Backup completed successfully\n";
    
} catch (Exception $e) {
    // Log failed backup
    $stmt = $pdo->prepare("INSERT INTO backup_history (backup_filename, backup_type, status, created_by, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Failed_Auto_' . date('Y-m-d_His'),
        'automated',
        'failed',
        'system',
        'Error: ' . $e->getMessage()
    ]);
    
    echo "Backup failed: " . $e->getMessage() . "\n";
}
?>