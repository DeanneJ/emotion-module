<?php
/**
 * OssnEmotions CSS Styles
 */
?>
<style>
/* ===== Emotion Badge on Posts ===== */
.ossn-emotion-badge-container {
    padding: 0;
    margin: 0;
    transition: all 0.3s ease;
}

.ossn-emotion-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
    border: 1px solid #e8eaf6;
    border-radius: 10px;
    margin: 8px 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ossn-emotion-badge:hover {
    background: linear-gradient(135deg, #f0f2ff 0%, #e8eaf6 100%);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
}

.ossn-emotion-badge .emotion-emoji {
    font-size: 22px;
    line-height: 1;
}

.ossn-emotion-badge .emotion-label {
    font-size: 13px;
    font-weight: 600;
    color: #4338ca;
    text-transform: capitalize;
}

.ossn-emotion-badge .emotion-confidence {
    font-size: 11px;
    color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: auto;
}

.ossn-emotion-badge .emotion-sentiment {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.ossn-emotion-badge .emotion-sentiment.positive {
    color: #059669;
    background: rgba(5, 150, 105, 0.1);
}

.ossn-emotion-badge .emotion-sentiment.negative {
    color: #dc2626;
    background: rgba(220, 38, 38, 0.1);
}

.ossn-emotion-badge .emotion-sentiment.neutral,
.ossn-emotion-badge .emotion-sentiment.mixed {
    color: #6b7280;
    background: rgba(107, 114, 128, 0.1);
}

/* ===== Emotion Detail Panel ===== */
.ossn-emotion-detail-panel {
    display: none;
    margin: 0 15px 10px 15px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    animation: emotionSlideDown 0.3s ease-out;
}

@keyframes emotionSlideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        max-height: 600px;
        transform: translateY(0);
    }
}

.ossn-emotion-detail-panel.active {
    display: block;
}

.ossn-emotion-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}

.ossn-emotion-detail-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.ossn-emotion-detail-close {
    cursor: pointer;
    opacity: 0.8;
    font-size: 18px;
    background: none;
    border: none;
    color: white;
    padding: 0 4px;
}

.ossn-emotion-detail-close:hover {
    opacity: 1;
}

.ossn-emotion-detail-body {
    padding: 14px 16px;
}

/* Emotion bars */
.ossn-emotion-bar-container {
    margin-bottom: 8px;
}

.ossn-emotion-bar-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 3px;
}

.ossn-emotion-bar-label .emotion-name {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
    color: #374151;
    text-transform: capitalize;
}

.ossn-emotion-bar-label .emotion-pct {
    color: #6b7280;
    font-size: 11px;
}

.ossn-emotion-bar-track {
    width: 100%;
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
}

.ossn-emotion-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease-out;
}

/* ===== Sarcasm Alert ===== */
.ossn-emotion-sarcasm-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    margin: 8px 0;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    font-size: 12px;
    color: #92400e;
}

.ossn-emotion-sarcasm-alert .sarcasm-icon {
    font-size: 16px;
}

/* ===== Slang Alert ===== */
.ossn-emotion-slang-alert {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 12px;
    margin: 8px 0;
    background: #f3e8ff;
    border: 1px solid #c084fc;
    border-radius: 8px;
    font-size: 12px;
    color: #6b21a8;
}

/* ===== Emoji Suggestions Panel ===== */
.ossn-emotion-emoji-panel {
    padding: 10px 0;
    border-top: 1px solid #f3f4f6;
    margin-top: 8px;
}

.ossn-emotion-emoji-panel h5 {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 8px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ossn-emotion-emoji-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.ossn-emotion-emoji-item {
    font-size: 22px;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 8px;
    transition: all 0.15s ease;
    background: #f9fafb;
    border: 1px solid transparent;
}

.ossn-emotion-emoji-item:hover {
    background: #eff6ff;
    border-color: #bfdbfe;
    transform: scale(1.2);
}

.ossn-emotion-emoji-blocked {
    opacity: 0.3;
    text-decoration: line-through;
    cursor: not-allowed;
    position: relative;
}

.ossn-emotion-emoji-blocked:hover {
    transform: none;
    background: #fef2f2;
    border-color: #fecaca;
}

/* ===== Content Filter Warning ===== */
.ossn-emotion-filter-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    margin: 10px 0;
    background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
    border: 1px solid #fecaca;
    border-radius: 10px;
    font-size: 13px;
    color: #dc2626;
}

.ossn-emotion-filter-warning .filter-icon {
    font-size: 20px;
}

.ossn-emotion-filter-warning .filter-text {
    font-weight: 500;
}

/* ===== Analyze Button in Post Menu ===== */
.ossn-emotion-analyze-btn {
    color: #6366f1 !important;
}

.ossn-emotion-analyze-btn:hover {
    color: #4338ca !important;
}

/* ===== Loading Indicator ===== */
.ossn-emotion-loading {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    margin: 8px 15px;
    color: #6366f1;
    font-size: 13px;
}

.ossn-emotion-loading .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #e0e7ff;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: emotionSpin 0.6s linear infinite;
}

@keyframes emotionSpin {
    to { transform: rotate(360deg); }
}

/* ===== Recommendation Cards ===== */
.ossn-emotion-recommendations {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f3f4f6;
}

.ossn-emotion-recommendation-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 6px 0;
    font-size: 12px;
    color: #374151;
}

.ossn-emotion-recommendation-item .rec-icon {
    font-size: 14px;
    flex-shrink: 0;
}

/* ===== Post Compose Emotion Indicator ===== */
.ossn-emotion-compose-indicator {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    margin-top: 6px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    font-size: 12px;
    color: #16a34a;
    transition: all 0.3s ease;
}

.ossn-emotion-compose-indicator.warning {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.ossn-emotion-compose-indicator.active {
    display: flex;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .ossn-emotion-badge {
        padding: 6px 10px;
        margin: 6px 10px;
        gap: 6px;
    }
    
    .ossn-emotion-badge .emotion-emoji {
        font-size: 18px;
    }
    
    .ossn-emotion-badge .emotion-label {
        font-size: 12px;
    }
    
    .ossn-emotion-detail-panel {
        margin: 0 10px 8px 10px;
    }
}

/* ===== Reaction Filtering ===== */
.emotion-reaction-blocked {
    opacity: 0.12 !important;
    filter: grayscale(100%) !important;
    pointer-events: none !important;
    cursor: not-allowed !important;
    position: relative;
    transform: scale(0.85) !important;
    transition: all 0.3s ease !important;
}

.emotion-reaction-blocked::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
}

.emotion-reaction-allowed {
    opacity: 1 !important;
    transition: transform 0.15s ease;
}

.emotion-reaction-allowed:hover {
    transform: scale(1.15);
}

/* Loading state while emotion API is being called */
.emotion-reaction-loading {
    position: relative;
}

.emotion-reaction-loading::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #6366f1, transparent);
    animation: reactionLoadingSlide 1.2s ease-in-out infinite;
    border-radius: 2px;
    z-index: 10;
}

@keyframes reactionLoadingSlide {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Emotion tag indicator on reaction panel */
.reaction-emotion-tag {
    position: absolute;
    right: -8px;
    top: -12px;
    font-size: 14px;
    background: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    z-index: 10;
    animation: tagPop 0.3s ease;
}

@keyframes tagPop {
    0% { transform: scale(0); }
    80% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* Ensure reaction panel has relative positioning for the emotion tag */
.ossn-like-reactions-panel {
    position: relative;
}

/* ===== Image Emotion Badge ===== */
.ossn-image-emotion-badge-container {
    padding: 0;
    margin: 0;
}

.ossn-image-emotion-badge {
    background: linear-gradient(135deg, #fef9f0 0%, #fdf2e9 100%) !important;
    border-color: #f5deb3 !important;
}

.ossn-image-emotion-badge:hover {
    background: linear-gradient(135deg, #fdf2e9 0%, #fce4c7 100%) !important;
    box-shadow: 0 2px 8px rgba(234, 179, 8, 0.2) !important;
}

.ossn-image-emotion-badge .emotion-source-icon {
    font-size: 14px;
    line-height: 1;
    opacity: 0.7;
}

.ossn-image-emotion-badge .emotion-label {
    color: #b45309 !important;
}

.ossn-image-emotion-badge .emotion-confidence {
    color: #d97706 !important;
    background: rgba(217, 119, 6, 0.1) !important;
}

.ossn-image-emotion-header {
    background: linear-gradient(135deg, #d97706 0%, #e8890c 100%) !important;
}

/* ============================================================
   MoodBuddy Chat Widget Styles
   ============================================================ */

.moodbuddy-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Trigger button */
.moodbuddy-trigger {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}
.moodbuddy-trigger:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}
.moodbuddy-trigger-active {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
.moodbuddy-trigger-icon {
    font-size: 28px;
    line-height: 1;
}
.moodbuddy-trigger-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid rgba(102, 126, 234, 0.6);
    animation: moodbuddy-pulse 2s infinite;
    pointer-events: none;
}
@keyframes moodbuddy-pulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* Chat window */
.moodbuddy-chat {
    position: absolute;
    bottom: 75px;
    right: 0;
    width: 380px;
    max-height: 520px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

/* Header */
.moodbuddy-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.moodbuddy-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.moodbuddy-avatar {
    font-size: 28px;
    line-height: 1;
}
.moodbuddy-header-info {
    display: flex;
    flex-direction: column;
}
.moodbuddy-name {
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.3px;
}
.moodbuddy-status {
    font-size: 11px;
    opacity: 0.85;
    margin-top: 1px;
}
.moodbuddy-close {
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
    opacity: 0.8;
    padding: 0 4px;
    line-height: 1;
    transition: opacity 0.15s;
}
.moodbuddy-close:hover {
    opacity: 1;
}

/* Messages area */
.moodbuddy-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    min-height: 280px;
    max-height: 350px;
    background: #f9fafb;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Individual message */
.moodbuddy-message {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    max-width: 85%;
    animation: moodbuddy-fadein 0.25s ease;
}
@keyframes moodbuddy-fadein {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.moodbuddy-bot {
    align-self: flex-start;
}
.moodbuddy-user {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.moodbuddy-message-avatar {
    font-size: 20px;
    flex-shrink: 0;
    line-height: 1;
}
.moodbuddy-message-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.45;
    word-wrap: break-word;
}
.moodbuddy-bot .moodbuddy-message-bubble {
    background: #ffffff;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
}
.moodbuddy-user .moodbuddy-message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    border-bottom-right-radius: 4px;
}

/* Typing indicator */
.moodbuddy-dots {
    display: flex;
    gap: 4px;
    padding: 4px 0;
}
.moodbuddy-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #9ca3af;
    animation: moodbuddy-bounce 1.4s infinite ease-in-out;
}
.moodbuddy-dots span:nth-child(2) { animation-delay: 0.16s; }
.moodbuddy-dots span:nth-child(3) { animation-delay: 0.32s; }
@keyframes moodbuddy-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

/* Emotion bar */
.moodbuddy-emotion-bar {
    padding: 6px 14px;
    background: #fef3c7;
    border-top: 1px solid #fde68a;
    flex-shrink: 0;
}
.moodbuddy-emotion-tag {
    font-size: 12px;
    color: #92400e;
    font-weight: 500;
}

/* Input area */
.moodbuddy-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
    flex-shrink: 0;
}
.moodbuddy-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 8px 14px;
    font-size: 13.5px;
    line-height: 1.4;
    resize: none;
    outline: none;
    max-height: 100px;
    font-family: inherit;
    background: #f9fafb;
    transition: border-color 0.15s;
}
.moodbuddy-input:focus {
    border-color: #667eea;
    background: #fff;
}
.moodbuddy-send {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s, opacity 0.15s;
}
.moodbuddy-send:hover:not(:disabled) {
    transform: scale(1.05);
}
.moodbuddy-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Mobile responsive */
@media (max-width: 480px) {
    .moodbuddy-chat {
        width: calc(100vw - 30px);
        right: -5px;
        bottom: 70px;
        max-height: 70vh;
    }
    .moodbuddy-trigger {
        width: 54px;
        height: 54px;
    }
    .moodbuddy-trigger-icon {
        font-size: 24px;
    }
}

/* Scrollbar styling */
.moodbuddy-messages::-webkit-scrollbar {
    width: 5px;
}
.moodbuddy-messages::-webkit-scrollbar-track {
    background: transparent;
}
.moodbuddy-messages::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}
.moodbuddy-messages::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
