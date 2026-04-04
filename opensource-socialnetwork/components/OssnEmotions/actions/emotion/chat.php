<?php
/**
 * OssnEmotions – Chat with MoodBuddy (Emotional Assistant)
 *
 * Proxies the chat request to the emo-app /api/v1/chat endpoint.
 * Uses header + echo + exit to return raw JSON, bypassing OSSN's
 * default XHR wrapper which would produce invalid double-JSON.
 */
header('Content-Type: application/json; charset=utf-8');

// Must be logged in
$user = ossn_loggedin_user();
if (!$user) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

// Get input
$message = input('message');
$history_raw = input('conversation_history');
$emotion_context = input('emotion_context');

if (empty(trim($message))) {
    echo json_encode(array('success' => false, 'error' => 'Empty message'));
    exit;
}

// Build payload for emo-app
$payload = array(
    'message' => $message,
    'conversation_history' => array(),
    'emotion_context' => $emotion_context ? $emotion_context : null
);

// Parse conversation history JSON if provided
if (!empty($history_raw)) {
    $history = json_decode($history_raw, true);
    if (is_array($history)) {
        $payload['conversation_history'] = $history;
    }
}

// Call emo-app chat API (timeout extended for LLM response)
$url = ossn_emotions_api_url() . '/chat';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json'
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode >= 400) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Chat service unavailable',
        'response' => "Sorry, I'm having trouble connecting right now. Please try again in a moment! \xF0\x9F\x92\x99"
    ));
    exit;
}

$result = json_decode($response, true);
if (!$result) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Invalid response from chat service',
        'response' => "Something went wrong on my end. Please try again! \xF0\x9F\x92\x99"
    ));
    exit;
}

echo json_encode(array(
    'success' => true,
    'response' => isset($result['response']) ? $result['response'] : '',
    'status' => isset($result['status']) ? $result['status'] : 'unknown',
    'detected_emotion' => isset($result['detected_emotion']) ? $result['detected_emotion'] : null
));
exit;
