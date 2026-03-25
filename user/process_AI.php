<?php
require_once __DIR__ . '/../includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../load_env.php';
require_once __DIR__ . '/../Login/connection.php';

function redirectWithFlash(string $message): void
{
    $_SESSION['ai_flash'] = $message;
    header('Location: AI_ADVISOR.php');
    exit();
}

if (empty($_SESSION['id']) || strtolower((string)($_SESSION['user_type'] ?? '')) !== 'user') {
    redirectWithFlash('Unauthorized.');
}

$sessionToken = $_SESSION['csrf_token'] ?? '';
$requestToken = $_POST['csrf_token'] ?? null;
if (!is_string($sessionToken) || !is_string($requestToken) || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
    redirectWithFlash('Session expired or invalid request token. Please refresh the page and try again.');
}

$userId = (int)$_SESSION['id'];
$query = trim((string)($_POST['query'] ?? ''));

if ($query === '') {
    redirectWithFlash('Please enter a question first.');
}

if (mb_strlen($query) > 500) {
    redirectWithFlash('Your question is too long. Please keep it under 500 characters.');
}

$normalizedQuery = strtolower($query);

$hardBlockPatterns = [
    '/\bsteroid(s)?\b/i',
    '/\bcycle\b.*\bsteroid(s)?\b|\bsteroid(s)?\b.*\bcycle\b/i',
    '/\b(illegal\s+drugs?|meth|cocaine|heroin)\b/i',
    '/\b(self[-\s]?harm|suicide|kill myself|hurt myself|end my life)\b/i',
    '/\b(purge|purging|starv(e|ing)|vomit after eating|laxative abuse)\b/i',
    '/\blose\b.*\b(\d{1,3})\b.*\b(day|days|week|weeks)\b/i',
    '/\b(extreme|rapid)\s+weight\s+loss\b/i'
];

foreach ($hardBlockPatterns as $pattern) {
    if (preg_match($pattern, $normalizedQuery)) {
        redirectWithFlash("I can't help with unsafe or illegal requests. Please ask for a safe workout, meal, or recovery plan instead.");
    }
}

$softCautionPatterns = [
    '/\bchest pain\b/i',
    '/\bfaint(ing|ed)?\b/i',
    '/\bpregnan(t|cy)\b/i',
    '/\bserious\s+injur(y|ies)\b|\binjury\b/i',
    '/\bmedication(s)?\b|\bmedicine\b/i'
];

$needsCaution = false;
foreach ($softCautionPatterns as $pattern) {
    if (preg_match($pattern, $normalizedQuery)) {
        $needsCaution = true;
        break;
    }
}

$intent = 'general';
$progressKeywords = ['progress', 'attendance', 'streak', 'consistency', 'workout logs', 'pr', 'personal record', 'how am i doing'];
$mealKeywords = ['meal', 'diet', 'calories', 'protein', 'carbs', 'nutrition', 'food'];
$workoutKeywords = ['plan', 'routine', 'program', 'split', 'workout', 'training'];

foreach ($progressKeywords as $keyword) {
    if (strpos($normalizedQuery, $keyword) !== false) {
        $intent = 'progress';
        break;
    }
}

if ($intent === 'general') {
    foreach ($mealKeywords as $keyword) {
        if (strpos($normalizedQuery, $keyword) !== false) {
            $intent = 'meal';
            break;
        }
    }
}

if ($intent === 'general') {
    foreach ($workoutKeywords as $keyword) {
        if (strpos($normalizedQuery, $keyword) !== false) {
            $intent = 'workout';
            break;
        }
    }
}

try {
    $apiUrl = trim((string)($_ENV['API_URL'] ?? ''));
    $apiToken = trim((string)($_ENV['API_BEARER_TOKEN'] ?? ''));
    if ($apiUrl === '' || $apiToken === '') {
        redirectWithFlash('AI advisor configuration is incomplete. Please contact admin.');
    }

    $profileStmt = $pdo->prepare('SELECT age, height_cm, weight_kg, fitness_level, goal FROM member_profiles WHERE user_id = :user_id LIMIT 1');
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $profileSummary = sprintf(
        'Member profile: age=%s, height_cm=%s, weight_kg=%s, fitness_level=%s, goal=%s',
        ($profile['age'] ?? 'unknown'),
        ($profile['height_cm'] ?? 'unknown'),
        ($profile['weight_kg'] ?? 'unknown'),
        ($profile['fitness_level'] ?? 'unknown'),
        ($profile['goal'] ?? 'unknown')
    );

    $progressSummary = '';
    $availableExercisesSummary = '';
    if ($intent === 'progress') {
        $threshold7d = date('Y-m-d H:i:s', strtotime('-6 days'));
        $threshold30d = date('Y-m-d H:i:s', strtotime('-29 days'));

        $attendance7Stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = :user_id AND datetime >= :threshold");
        $attendance7Stmt->execute([':user_id' => $userId, ':threshold' => $threshold7d]);
        $attendance7d = (int)$attendance7Stmt->fetchColumn();

        $attendance30Stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = :user_id AND datetime >= :threshold");
        $attendance30Stmt->execute([':user_id' => $userId, ':threshold' => $threshold30d]);
        $attendance30d = (int)$attendance30Stmt->fetchColumn();

        $workout7Stmt = $pdo->prepare("SELECT COUNT(*) FROM workout_logs WHERE user_id = :user_id AND logged_at >= :threshold");
        $workout7Stmt->execute([':user_id' => $userId, ':threshold' => $threshold7d]);
        $workoutLogs7d = (int)$workout7Stmt->fetchColumn();

        $workout30Stmt = $pdo->prepare("SELECT COUNT(*) FROM workout_logs WHERE user_id = :user_id AND logged_at >= :threshold");
        $workout30Stmt->execute([':user_id' => $userId, ':threshold' => $threshold30d]);
        $workoutLogs30d = (int)$workout30Stmt->fetchColumn();

        $trainingDaysStmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(logged_at)) FROM workout_logs WHERE user_id = :user_id AND logged_at >= :threshold");
        $trainingDaysStmt->execute([':user_id' => $userId, ':threshold' => $threshold30d]);
        $trainingDays30d = (int)$trainingDaysStmt->fetchColumn();

        $topExercisesStmt = $pdo->prepare("SELECT e.name AS exercise_name, COUNT(*) AS sets_logged, MAX(wl.weight) AS max_weight
            FROM workout_logs wl
            LEFT JOIN exercises e ON e.exercise_id = wl.exercise_id
            WHERE wl.user_id = :user_id
              AND wl.logged_at >= :threshold
            GROUP BY wl.exercise_id, e.name
            ORDER BY sets_logged DESC, max_weight DESC
            LIMIT 5");
        $topExercisesStmt->execute([':user_id' => $userId, ':threshold' => $threshold30d]);
        $topExercises = $topExercisesStmt->fetchAll(PDO::FETCH_ASSOC);

        $topExerciseChunks = [];
        foreach ($topExercises as $row) {
            $name = trim((string)($row['exercise_name'] ?? 'Unknown exercise'));
            $sets = (int)($row['sets_logged'] ?? 0);
            $maxWeight = isset($row['max_weight']) ? (float)$row['max_weight'] : 0.0;
            $topExerciseChunks[] = $name . ' (' . $sets . ' sets, max ' . $maxWeight . 'kg)';
        }

        $progressSummary = 'Progress summary: attendance_7d=' . $attendance7d
            . ', attendance_30d=' . $attendance30d
            . ', workout_logs_7d=' . $workoutLogs7d
            . ', workout_logs_30d=' . $workoutLogs30d
            . ', training_days_30d=' . $trainingDays30d
            . ', top_exercises_30d=' . (empty($topExerciseChunks) ? 'none' : implode('; ', $topExerciseChunks));
    }

    if ($intent === 'workout' || $intent === 'progress' || strpos($normalizedQuery, 'exercise') !== false) {
        $exerciseStmt = $pdo->query('SELECT name, target_muscle, movement_type FROM exercises ORDER BY name ASC LIMIT 120');
        $exerciseRows = $exerciseStmt ? ($exerciseStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        if (!empty($exerciseRows)) {
            $movementBuckets = [];
            $exerciseNameList = [];

            foreach ($exerciseRows as $row) {
                $name = trim((string)($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $movementType = trim((string)($row['movement_type'] ?? 'General'));
                if ($movementType === '') {
                    $movementType = 'General';
                }

                $targetMuscle = trim((string)($row['target_muscle'] ?? ''));
                $label = $targetMuscle !== '' ? ($name . ' [' . $targetMuscle . ']') : $name;

                if (!isset($movementBuckets[$movementType])) {
                    $movementBuckets[$movementType] = [];
                }
                $movementBuckets[$movementType][] = $label;
                $exerciseNameList[] = $name;
            }

            $movementChunks = [];
            foreach ($movementBuckets as $type => $items) {
                $movementChunks[] = $type . ': ' . implode(', ', array_slice($items, 0, 8));
            }

            $availableExercisesSummary = 'Available exercises in system (' . count($exerciseNameList) . '): '
                . implode(' | ', $movementChunks)
                . '. Full exercise name list: '
                . implode(', ', array_slice($exerciseNameList, 0, 120));
        } else {
            $availableExercisesSummary = 'Available exercises in system: none found in exercises table.';
        }
    }

    $contextBlocks = [$profileSummary, 'Intent: ' . $intent];
    if ($needsCaution) {
        $contextBlocks[] = 'Caution flag: The user may have a medical risk signal. Add one caution line advising consultation with a licensed professional.';
    }
    if ($progressSummary !== '') {
        $contextBlocks[] = $progressSummary;
    }
    if ($availableExercisesSummary !== '') {
        $contextBlocks[] = $availableExercisesSummary;
    }

    $systemPrompt = "You are a gym AI advisor for members. You SUPPLEMENT personal trainers, never replace them. "
        . "Do not provide medical diagnosis. Refuse unsafe/illegal requests (steroids, illegal drugs, self-harm, eating-disorder behaviors, extreme rapid weight loss). "
        . "When suggesting workouts, strongly prefer exercises from the provided Available exercises in system list. "
        . "If an exercise is not in that list, clearly label it as an optional substitute and provide listed alternatives. "
        . "Avoid extreme dieting advice. Keep answers concise and practical. "
        . "Output plain text in this exact structure:\n"
        . "1) Quick answer (1-2 sentences)\n"
        . "2) Suggestions (3-6 bullets)\n"
        . "3) Trainer check (1 bullet)\n"
        . "4) One follow-up question";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    if (!empty($_SESSION['chat_history']) && is_array($_SESSION['chat_history'])) {
        $historySlice = array_slice($_SESSION['chat_history'], -4);
        foreach ($historySlice as $entry) {
            if (!is_array($entry) || empty($entry['role']) || !isset($entry['message'])) {
                continue;
            }
            $role = $entry['role'] === 'ai' ? 'assistant' : ($entry['role'] === 'user' ? 'user' : '');
            if ($role === '') {
                continue;
            }
            $messages[] = [
                'role' => $role,
                'content' => (string)$entry['message']
            ];
        }
    }

    $messages[] = [
        'role' => 'user',
        'content' => "Member context:\n" . implode("\n", $contextBlocks) . "\n\nUser question:\n" . $query
    ];

    $payload = [
        'messages' => $messages,
        'model' => 'openai/gpt-oss-120b',
        'temperature' => 0.2,
        'max_completion_tokens' => 350,
        'tools' => []
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiToken
        ],
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($rawResponse === false || $curlError !== '') {
        redirectWithFlash('AI advisor is temporarily unavailable. Please try again in a moment.');
    }

    if ($httpCode >= 400) {
        $apiErrorData = json_decode((string)$rawResponse, true);
        $apiErrorMessage = trim((string)($apiErrorData['error']['message'] ?? ''));

        if ($httpCode === 401) {
            redirectWithFlash('AI advisor configuration error: API key is invalid or expired. Please contact admin.');
        }

        if ($httpCode === 429) {
            redirectWithFlash('AI advisor is currently busy (rate limit reached). Please try again in a minute.');
        }

        if ($apiErrorMessage !== '') {
            redirectWithFlash('AI advisor error: ' . $apiErrorMessage);
        }

        redirectWithFlash('AI advisor is temporarily unavailable. Please try again in a moment.');
    }

    $data = json_decode((string)$rawResponse, true);
    $aiResponse = trim((string)($data['choices'][0]['message']['content'] ?? ''));

    if ($aiResponse === '') {
        redirectWithFlash('AI advisor could not generate a response right now. Please try again.');
    }

    if ($needsCaution) {
        $cautionLine = 'Caution: Symptoms or special conditions should be reviewed by a licensed doctor and your trainer before changing your routine.';
        if (stripos($aiResponse, 'caution:') === false) {
            $aiResponse = $cautionLine . "\n\n" . $aiResponse;
        }
    }

    $_SESSION['ai_flash'] = $aiResponse;

    if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'message' => $query,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $_SESSION['chat_history'][] = [
        'role' => 'ai',
        'message' => $aiResponse,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);

    header('Location: AI_ADVISOR.php');
    exit();
} catch (Throwable $e) {
    redirectWithFlash('Something went wrong while processing your request. Please try again.');
}
?>
