    //ADMIN NOTIFICATION
    $sql = "INSERT INTO notification_history (name, description, datetime, remarks, is_read, category) VALUES (:name, :description, :datetime, :remarks, :is_read, :category)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':name' => 'New Member',
        ':description' => 'username: ' . $username, //change nalang, kung ano dapat malaman ng admin
        ':datetime' => date('Y-m-d H:i:s'),
        ':remarks' => 'Successfully added by' . $_SESSION['username'],
        ':is_read' => 0,
        ':category' => 'membership'
    ]);

    dagdag mo nalang to saan mo sa tingin dapat magnotify sa admin