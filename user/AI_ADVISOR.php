<?php
require_once __DIR__ . '/auth_user.php';
$welcomeName = 'Member';


try {
  require __DIR__ . '/../Login/connection.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT first_name, username FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($user['first_name'])) {
      $welcomeName = (string)$user['first_name'];
    } elseif (!empty($user['username'])) {
      $welcomeName = (string)$user['username'];
    }
  }
} catch (Throwable $e) {
  // Show empty-state card if loading from DB fails.
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['chat_history']);
}

$aiFlash = '';
if (isset($_SESSION['ai_flash']) && is_string($_SESSION['ai_flash'])) {
    $aiFlash = $_SESSION['ai_flash'];
    unset($_SESSION['ai_flash']);
}

$quickPrompts = [
    'Build a safe 45-minute workout I can do today based on my goal.',
    'Suggest a post-workout meal and include simple portion sizes.',
    'Give me a recovery plan for tonight with sleep and hydration targets.',
    'Review my progress this week and share 3 practical improvements.'
];

$custom_js = <<<'JS'
<script>
(function() {
    const form = document.getElementById('aiAdvisorForm');
    const input = document.getElementById('aiAdvisorQuery');
    if (!form || !input) {
        return;
    }

    document.querySelectorAll('.ai-quick-prompt').forEach((button) => {
        button.addEventListener('click', function() {
            const prompt = this.getAttribute('data-prompt') || '';
            if (prompt.trim() === '') {
                return;
            }

            input.value = prompt;
            form.submit();
        });
    });
})();
</script>
JS;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exercise History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-OERcA2zY1OHt4q4Fv8B+U7MeM3NnN3KK2eEbV5t8JSaI1zlzW3URy9Bv1WTRi7v8Q" crossorigin="anonymous">
    <link rel="stylesheet" href="user.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
  </head>

<body>
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
          <div class="welcome">
            <h1>History</h1>
            <p>
              Hi <?php echo htmlspecialchars($welcomeName, ENT_QUOTES, 'UTF-8'); ?>!
              This is AI Advisor, your personal fitness assistant. 
            </p>
          </div>
        </header>
    <?php include('../includes/header.php') ?>
    
        <?php
        if ($aiFlash !== '') {
            echo '<div class="alert alert-primary">';
            echo '<i class="bi bi-robot me-2"></i>';
            echo nl2br(htmlspecialchars($aiFlash, ENT_QUOTES, 'UTF-8'));
            echo '</div>';
        }

        // Optional session chat history (not persisted in database)
        if (!empty($_SESSION['chat_history'])) {
            foreach ($_SESSION['chat_history'] as $entry) {
                if ($entry['role'] == 'user') {
                    echo '
                <div class="alert alert-secondary">
                    <i class="bi bi-person-fill me-2"></i>';
                    echo htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8');
                    echo '<div class="text-muted">' . htmlspecialchars((string)$entry['timestamp'], ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '</div>';
                } elseif ($entry['role'] == 'ai') {
                    echo '
                <div class="alert alert-info">
                    <i class="bi bi-robot me-2"></i>';
                    echo nl2br(htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8'));
                    echo '<div class="text-muted">' . htmlspecialchars((string)$entry['timestamp'], ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '</div>';
                }
            }
        }
        ?>
        <div class="container d-flex gap-2 align-items-center">
            <form action="process_AI.php" method="POST" class="d-flex gap-2 flex-grow-1" id="aiAdvisorForm">
                <?php echo fitstop_csrf_input(); ?>
                <input type="text" name="query" id="aiAdvisorQuery" placeholder="Enter your query" class="form-control" maxlength="500" required>
                <button type="submit" class="btn btn-success">Submit</button>
            </form>
            <a href="AI_ADVISOR.php?clear=1" class="btn btn-sm btn-danger"
                onclick="return confirm('Clear all messages?')" title="Clear chat">
                <i class="bi bi-trash"></i>
            </a>
        </div>
        <div class="container mt-3">
            <div class="small text-muted mb-2">Quick prompts</div>
            <div class="d-flex flex-wrap gap-2" id="aiQuickPromptList">
                <?php foreach ($quickPrompts as $prompt): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm ai-quick-prompt" data-prompt="<?php echo htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
  
