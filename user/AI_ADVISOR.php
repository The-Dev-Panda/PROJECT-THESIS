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
    'Build a safe workout I can do today based on my goal.',
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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>

<body style="background-color: black;">
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
      <!-- MAIN CONTENT -->
      <main class="main-content">
        <div class="container-fluid py-4">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <div>
              <h1 class="h3 mb-1">AI Advisor</h1>
              <p class="text-muted mb-0">Hi <?php echo htmlspecialchars($welcomeName, ENT_QUOTES, 'UTF-8'); ?>! This is your personal fitness assistant.</p>
            </div>
            <a href="AI_ADVISOR.php?clear=1" class="btn btn-outline-danger btn-sm mt-3 mt-md-0" onclick="return confirm('Clear all messages?')" title="Clear chat">
              <i class="bi bi-trash me-1"></i> Clear chat
            </a>
          </div>

          <div class="row gy-3">
            <div class="col-12 col-lg-8">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <?php if ($aiFlash !== ''): ?>
                    <div class="alert alert-primary d-flex align-items-start gap-2" role="alert">
                      <i class="bi bi-robot fs-4"></i>
                      <div><?php echo nl2br(htmlspecialchars($aiFlash, ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                  <?php endif; ?>

                  <h6 class="mb-3">Conversation History</h6>
                  <div class="list-group" style="max-height: 45vh; overflow-y: auto;">
                    <?php if (!empty($_SESSION['chat_history'])):
                        foreach ($_SESSION['chat_history'] as $entry):
                            $role = $entry['role'] === 'ai' ? 'AI' : 'You';
                            $label = $entry['role'] === 'ai' ? 'info' : 'secondary';
                    ?>
                        <div class="list-group-item list-group-item-<?php echo $label; ?> py-2">
                          <div class="d-flex justify-content-between align-items-start">
                            <strong><?php echo htmlspecialchars($role); ?></strong>
                            <small class="text-muted"><?php echo htmlspecialchars((string)$entry['timestamp'], ENT_QUOTES, 'UTF-8'); ?></small>
                          </div>
                          <div><?php echo $entry['role'] === 'ai' ? nl2br(htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8')) : htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="list-group-item text-muted">No conversation yet. Ask a question to begin.</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-4">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <form action="process_AI.php" method="POST" id="aiAdvisorForm">
                    <?php echo fitstop_csrf_input(); ?>
                    <div class="mb-3">
                      <label for="aiAdvisorQuery" class="form-label">Ask your AI advisor</label>
                      <input type="text" name="query" id="aiAdvisorQuery" placeholder="What do you want to know?" class="form-control" maxlength="500" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Submit</button>
                  </form>

                  <hr>
                  <h6 class="mb-2">Quick prompts</h6>
                  <div class="d-grid gap-2" id="aiQuickPromptList">
                    <?php foreach ($quickPrompts as $prompt): ?>
                      <button type="button" class="btn btn-outline-secondary btn-sm ai-quick-prompt text-start" data-prompt="<?php echo htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8'); ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <?php echo $custom_js; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-I6hXqZcZ30Qpi70YpDkU4hfOhxC1a4Ud/NOHUxDyEYvL48cZ8KXH/S9ZHAlGqBKx" crossorigin="anonymous"></script>
</body>
</html>
  
