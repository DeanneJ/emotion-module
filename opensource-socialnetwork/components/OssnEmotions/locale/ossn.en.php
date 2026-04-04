<?php
/**
 * OssnEmotions - English Language Strings
 */

$en = array(
    'ossn:emotions:analyze' => 'Analyze Emotion',
    'ossn:emotions:analyzing' => 'Analyzing emotions...',
    'ossn:emotions:unavailable' => 'Emotion analysis unavailable',
    'ossn:emotions:error:empty' => 'No text to analyze',
    'ossn:emotions:sarcasm:detected' => 'Sarcasm detected',
    'ossn:emotions:slang:detected' => 'Slang detected',
    'ossn:emotions:filter:warning' => 'This content may contain harmful material',
    'ossn:emotions:filter:blocked' => 'This content has been flagged as potentially harmful',
    'ossn:emotions:suggested:reactions' => 'Suggested Reactions',
    'ossn:emotions:blocked:reactions' => 'Inappropriate Reactions',
    'ossn:emotions:panel:title' => 'Emotion Analysis',
    'ossn:emotions:settings' => 'Emotion Detection Settings',
    'ossn:emotions:settings:api_url' => 'Emotion API URL',
    'ossn:emotions:settings:auto_analyze' => 'Auto-analyze posts on load',
    'ossn:emotions:settings:filter_posts' => 'Filter harmful content before posting',
    'ossn:emotions:settings:max_auto' => 'Max posts to auto-analyze',
);
ossn_register_languages('en', $en);
