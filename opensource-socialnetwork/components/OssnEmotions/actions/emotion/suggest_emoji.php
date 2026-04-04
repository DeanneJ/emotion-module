<?php
/**
 * OssnEmotions - Emoji suggestion action
 * Returns context-aware emoji suggestions for text
 */

$text = input('text');

if (empty($text)) {
    echo json_encode(array(
        'success' => false,
        'error' => 'No text provided'
    ));
    return;
}

$result = ossn_emotions_suggest_emojis($text);

if ($result) {
    echo json_encode(array(
        'success' => true,
        'data' => $result
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'error' => 'Emoji suggestion service unavailable'
    ));
}
