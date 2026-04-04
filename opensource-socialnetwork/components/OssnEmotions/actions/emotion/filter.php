<?php
/**
 * OssnEmotions - Content filtering action
 * Checks content for harmful material before posting
 */

$text = input('text');

if (empty($text)) {
    echo json_encode(array(
        'success' => true,
        'is_harmful' => false
    ));
    return;
}

$result = ossn_emotions_filter_content($text);

if ($result) {
    echo json_encode(array(
        'success' => true,
        'data' => $result
    ));
} else {
    // API unavailable, allow content through
    echo json_encode(array(
        'success' => true,
        'is_harmful' => false,
        'note' => 'Filter service unavailable'
    ));
}
