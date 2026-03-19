<?php
/*

SA "$user_query" NIYO LAGAY YUNG MGA DATA, AT LEAST AS POSSIBLE PARA DI MAUBOS TOKENS
PWEDE NIYO LAGYAN NG MGA IF PARA SPECIFIC YUNG QUERIES SA DATABASE PARA RIN DI HUMABA YUNG PROMPT

example niyan if may word sa query na "announcement" then query yung announcement table para malaman niya yung annoucnements
meron yan sa php na keywords na code, "str_contains($user_query, 'announcement')". balik parin sa first message ko.
*/
require_once '../load_env.php';
try {
    $user_query = 'Answer in one sentence. Plain text only. Query: ' . $_POST['query'];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $_ENV['API_URL'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
        "messages": [
            {
                "role": "user",
                "content": "' . $user_query . '"
            }
        ],
        "model": "openai/gpt-oss-120b", 
        "temperature": 0.2,
        "max_completion_tokens": 200,
        "top_p": 1,
        "reasoning_effort": "medium",
        "stop": null,
        "tools": [],
        "seed": 1
    }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_ENV['API_BEARER_TOKEN'],
            'Cookie: __cf_bm=DbBFAfNz67CNr.IDFhxVYHVK5onMP.40pcDFUnQWwWE-1773877645.7889497-1.0.1.1-ESY_ivhV2UM6pwB5OYdbD7koNNZkDKQQkg6RZ9.ZGQdVWI51NkD6GtrcjT4ZZ74rCwVbZXPw.hZVvqoK4.J9bho5A7g9PuOMu2j_mm.iryVwK7pJbopTfYLDQ0tSwZiy'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);
    $ai_reponse = $data['choices'][0]['message']['content'];
    session_start();
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'message' => $_POST['query'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    if (isset($ai_reponse)) {
        $_SESSION['ai_response'] = $ai_reponse;
    } else {
        $_SESSION['ai_response'] = "Sorry, I couldn't process that. Please try again.";
    }
    $_SESSION['chat_history'][] = [
        'role' => 'ai',
        'message' => $_SESSION['ai_response'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    #debuggings
    echo "<h3> PROMPT: " . $user_query . "</h3>";
    echo "<hr>";
    echo "<h2> DEBUGGING SHI</h2>";
    echo "<hr>";
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . $response . "</pre>";

    echo "<h3>Decoded Data:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";

    echo "<h3>Extracted Fields:</h3>";
    echo "<pre>";
    echo "ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "Model: " . ($data['model'] ?? 'N/A') . "\n";
    echo "Created: " . ($data['created'] ?? 'N/A') . "\n";
    echo "AI Message: " . ($data['choices'][0]['message']['content'] ?? 'N/A') . "\n";
    echo "Reasoning: " . ($data['choices'][0]['message']['reasoning'] ?? 'N/A') . "\n";
    echo "Finish Reason: " . ($data['choices'][0]['finish_reason'] ?? 'N/A') . "\n";
    echo "Total Tokens: " . ($data['usage']['total_tokens'] ?? 'N/A') . "\n";
    echo "Prompt Tokens: " . ($data['usage']['prompt_tokens'] ?? 'N/A') . "\n";
    echo "Completion Tokens: " . ($data['usage']['completion_tokens'] ?? 'N/A') . "\n";
    echo "</pre>";

    echo "<hr>";
    echo "<a href='AI_ADVISOR.php' class='btn btn-primary'>Back to Chat</a>";
    #debuggings

    //header('Location: AI_ADVISOR.php');
    exit();
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>