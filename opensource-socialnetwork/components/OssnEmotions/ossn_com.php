<?php
/**
 * OssnEmotions - AI Emotion Detection Component
 *
 * Integrates the Emotion-Aware AI backend with OSSN social network.
 * Features:
 *   - Real-time text emotion detection on posts/comments
 *   - Image emotion detection on uploaded photos
 *   - Context-aware emoji reaction suggestions
 *   - Harmful content filtering
 *   - Sarcasm and slang detection
 *   - Emotion badges on wall posts
 *
 * @package   OssnEmotions
 * @author    D S Jayawardena
 * @version   1.0
 */

define('__OSSN_EMOTIONS__', ossn_route()->com . 'OssnEmotions/');

/**
 * Initialize the OssnEmotions component
 */
function ossn_emotions() {
    // ---- CSS & JS ----
    ossn_extend_view('css/ossn.default', 'css/emotions');
    ossn_extend_view('js/ossn.site', 'js/emotions/main');

    // ---- Actions (require login) ----
    if (ossn_isLoggedIn()) {
        ossn_register_action('emotion/analyze', __OSSN_EMOTIONS__ . 'actions/emotion/analyze.php');
        ossn_register_action('emotion/filter', __OSSN_EMOTIONS__ . 'actions/emotion/filter.php');
        ossn_register_action('emotion/suggest_emoji', __OSSN_EMOTIONS__ . 'actions/emotion/suggest_emoji.php');
        ossn_register_action('emotion/analyze_image', __OSSN_EMOTIONS__ . 'actions/emotion/analyze_image.php');
        ossn_register_action('emotion/chat', __OSSN_EMOTIONS__ . 'actions/emotion/chat.php');

        // ---- MoodBuddy Chat Widget (floating bubble) ----
        ossn_extend_view('ossn/page/footer', 'emotions/chat_widget');
    }

    // ---- Hooks: inject emotion UI into wall posts ----
    // After wall item loads, add emotion analysis badge
    ossn_register_callback('wall', 'load:item', 'ossn_emotions_wall_menu', 50);

    // Hook into post creation to auto-analyze
    ossn_register_callback('wall', 'post:created', 'ossn_emotions_on_post_created');

    // Hook into comment creation to auto-analyze  
    ossn_register_callback('comment', 'created', 'ossn_emotions_on_comment_created');

    // Hook into wall template to inject emotion badge HTML
    ossn_add_hook('wall', 'templates:item', 'ossn_emotions_wall_template_hook');

    // Hook into comment view to show emotion badge
    ossn_add_hook('comment:view', 'template:params', 'ossn_emotions_comment_hook');

    // ---- Page handler for emotion analysis page ----
    ossn_register_page('emotions', 'ossn_emotions_page_handler');

    // ---- Admin settings ----
    if (ossn_isAdminLoggedin()) {
        ossn_register_com_panel('OssnEmotions', 'settings');
    }
}

/**
 * Get the Emotion API base URL from component settings or default
 */
function ossn_emotions_api_url() {
    $url = ossn_call_hook('emotions', 'api:url', false);
    if ($url) {
        return rtrim($url, '/');
    }
    // Default: emo-app backend running locally
    return 'http://localhost:8000/api/v1';
}

/**
 * Get the AffectNet (image emotion) API base URL
 *
 * @return string API URL (default: http://localhost:8001)
 */
function ossn_emotions_image_api_url() {
    $url = ossn_call_hook('emotions', 'image_api:url', false);
    if ($url) {
        return rtrim($url, '/');
    }
    return 'http://localhost:8000/image-api';
}

/**
 * Call the AffectNet image API with a local image file (multipart upload)
 *
 * @param string $filepath Absolute path to the image file on disk
 * @return array|false     Decoded response or false on failure
 */
function ossn_emotions_image_api_call($filepath) {
    $url = ossn_emotions_image_api_url() . '/predict';

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($filepath);
    if (!$mime || strpos($mime, 'image/') !== 0) {
        error_log("OssnEmotions: Invalid image MIME type: {$mime}");
        return false;
    }

    $cfile = new CURLFile($filepath, $mime, basename($filepath));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => $cfile));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400) {
        error_log("OssnEmotions Image API error: httpCode={$httpCode} error={$error}");
        return false;
    }

    return json_decode($response, true);
}

/**
 * Call the Emotion API backend
 *
 * @param string $endpoint API endpoint (e.g., '/emotions/text')
 * @param array  $data     POST data (will be JSON-encoded)
 * @param string $method   HTTP method
 * @return array|false      Decoded response or false on failure
 */
function ossn_emotions_api_call($endpoint, $data = array(), $method = 'POST') {
    $url = ossn_emotions_api_url() . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400) {
        error_log("OssnEmotions API error: endpoint={$endpoint} httpCode={$httpCode} error={$error}");
        return false;
    }

    return json_decode($response, true);
}

/**
 * Analyze text emotions via the API
 *
 * @param string $text      Text to analyze
 * @param float  $threshold Emotion threshold
 * @return array|false
 */
function ossn_emotions_analyze_text($text, $threshold = 0.3) {
    if (empty(trim($text))) {
        return false;
    }
    return ossn_emotions_api_call('/text/enhanced', array(
        'text' => $text,
        'threshold' => $threshold
    ));
}

/**
 * Get emoji suggestions for text
 *
 * @param string $text Text to analyze
 * @return array|false
 */
function ossn_emotions_suggest_emojis($text) {
    if (empty(trim($text))) {
        return false;
    }
    return ossn_emotions_api_call('/emojis/suggest', array(
        'text' => $text,
        'threshold' => 0.3
    ));
}

/**
 * Filter content for harmful material
 *
 * @param string $text Content to check
 * @return array|false
 */
function ossn_emotions_filter_content($text) {
    if (empty(trim($text))) {
        return false;
    }
    return ossn_emotions_api_call('/filter/content', array(
        'text' => $text
    ));
}

/**
 * Enhanced text analysis (emotions + sarcasm + slang)
 *
 * @param string $text Text to analyze
 * @return array|false
 */
function ossn_emotions_enhanced_analysis($text) {
    if (empty(trim($text))) {
        return false;
    }
    return ossn_emotions_api_call('/text/enhanced', array(
        'text' => $text,
        'include_emotions' => true,
        'include_sarcasm' => true,
        'include_slang' => true,
        'threshold' => 0.3
    ));
}

/**
 * Add "Analyze Emotion" menu item to wall posts
 */
function ossn_emotions_wall_menu($callback, $type, $params) {
    if (!ossn_isLoggedIn()) {
        return;
    }
    $guid = $params['post']->guid;

    // Remove any previously registered emotion_analyze menu item to prevent duplicates
    ossn_unregister_menu('emotion_analyze', 'postextra');

    ossn_register_menu_item('postextra', array(
        'name'    => 'emotion_analyze',
        'href'    => 'javascript:void(0);',
        'onclick' => "OssnEmotions.analyzePost({$guid});",
        'text'    => '<i class="fa fa-brain"></i> ' . ossn_print('ossn:emotions:analyze'),
        'class'   => 'ossn-emotion-analyze-btn',
    ));
}

/**
 * Hook into wall template to inject emotion badge container
 * Note: wall:templates:item hook receives $params array as $return, not a string
 */
function ossn_emotions_wall_template_hook($hook, $type, $return, $params) {
    if (!is_array($return) || !isset($return['post']->guid)) {
        return $return;
    }
    $guid = $return['post']->guid;
    $badge_html = '<div class="ossn-emotion-badge-container" id="emotion-badge-' . $guid . '" data-guid="' . $guid . '"></div>';
    
    // Add emotion badge HTML to the template params
    if (!isset($return['emotion_badge'])) {
        $return['emotion_badge'] = $badge_html;
    }
    return $return;
}

/**
 * Hook into comment view to add emotion data
 */
function ossn_emotions_comment_hook($hook, $type, $return, $params) {
    // Comments get analyzed on-demand via JS
    return $return;
}

/**
 * Callback when a wall post is created - auto-analyze emotions
 */
function ossn_emotions_on_post_created($callback, $type, $params) {
    // Auto-analyze is done asynchronously via JavaScript
    // This hook can be used for server-side processing if needed
}

/**
 * Callback when a comment is created
 */
function ossn_emotions_on_comment_created($callback, $type, $params) {
    // Comments are analyzed on-demand via JS
}

/**
 * Page handler for emotion-related pages
 */
function ossn_emotions_page_handler($pages) {
    $page = $pages[0] ?? '';
    switch ($page) {
        case 'analyze':
            echo ossn_plugin_view('emotions/pages/analyze');
            break;
        default:
            echo ossn_plugin_view('emotions/pages/dashboard');
            break;
    }
}

/**
 * Map emotion to color for UI display
 *
 * @param string $emotion Emotion name
 * @return string Hex color code
 */
function ossn_emotions_get_color($emotion) {
    $colors = array(
        'joy'            => '#FFD93D',
        'happy'          => '#FFD93D',
        'love'           => '#FF6B6B',
        'admiration'     => '#FF8FA3',
        'amusement'      => '#FFC93C',
        'excitement'     => '#FF6F3C',
        'gratitude'      => '#95E1D3',
        'optimism'       => '#7BE495',
        'pride'          => '#DDA0DD',
        'relief'         => '#87CEEB',
        'caring'         => '#FFB5B5',
        'approval'       => '#98D8AA',
        'desire'         => '#FF69B4',
        'sadness'        => '#5B8FB9',
        'sad'            => '#5B8FB9',
        'grief'          => '#4A6FA5',
        'disappointment' => '#7B8FA1',
        'embarrassment'  => '#C9A0DC',
        'remorse'        => '#8B7E74',
        'anger'          => '#FF4757',
        'angry'          => '#FF4757',
        'annoyance'      => '#FF6348',
        'disapproval'    => '#E17055',
        'fear'           => '#8854D0',
        'nervousness'    => '#A29BFE',
        'surprise'       => '#F9CA24',
        'realization'    => '#F0932B',
        'confusion'      => '#FDCB6E',
        'curiosity'      => '#6C5CE7',
        'disgust'        => '#2ED573',
        'neutral'        => '#95A5A6',
        'other'          => '#BDC3C7',
    );
    return $colors[strtolower($emotion)] ?? '#95A5A6';
}

/**
 * Map emotion to emoji icon
 *
 * @param string $emotion Emotion name
 * @return string Emoji character
 */
function ossn_emotions_get_emoji($emotion) {
    $emojis = array(
        'joy'            => '😄',
        'happy'          => '😊',
        'love'           => '❤️',
        'admiration'     => '🤩',
        'amusement'      => '😂',
        'excitement'     => '🎉',
        'gratitude'      => '🙏',
        'optimism'       => '🌟',
        'pride'          => '💪',
        'relief'         => '😌',
        'caring'         => '🤗',
        'approval'       => '👍',
        'desire'         => '😍',
        'sadness'        => '😢',
        'sad'            => '😢',
        'grief'          => '😭',
        'disappointment' => '😞',
        'embarrassment'  => '😳',
        'remorse'        => '😔',
        'anger'          => '😡',
        'angry'          => '😡',
        'annoyance'      => '😤',
        'disapproval'    => '👎',
        'fear'           => '😨',
        'nervousness'    => '😰',
        'surprise'       => '😲',
        'realization'    => '💡',
        'confusion'      => '😕',
        'curiosity'      => '🤔',
        'disgust'        => '🤢',
        'neutral'        => '😐',
        'other'          => '🔵',
    );
    return $emojis[strtolower($emotion)] ?? '🔵';
}

ossn_register_callback('ossn', 'init', 'ossn_emotions');
