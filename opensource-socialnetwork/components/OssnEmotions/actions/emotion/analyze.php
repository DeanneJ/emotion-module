<?php
/**
 * OssnEmotions - Analyze text emotions action
 * POST handler for emotion analysis via OSSN action system
 */

$text = input('text');
$guid = input('guid');

if (empty($text)) {
    ossn_trigger_message(ossn_print('ossn:emotions:error:empty'), 'error');
    return;
}

$result = ossn_emotions_enhanced_analysis($text);

if ($result) {
    echo json_encode(array(
        'success' => true,
        'guid' => $guid,
        'data' => $result
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'error' => 'Emotion analysis service unavailable'
    ));
}
