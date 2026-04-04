<?php
/**
 * MoodBuddy Chat Widget – rendered into ossn/page/footer for logged-in users.
 */
if (!ossn_isLoggedIn()) return;
$user = ossn_loggedin_user();
$firstName = htmlspecialchars($user->first_name ?? 'there', ENT_QUOTES, 'UTF-8');
?>
<div id="moodbuddy-widget" class="moodbuddy-widget">
    <!-- Floating trigger button -->
    <button id="moodbuddy-trigger" class="moodbuddy-trigger" title="Chat with MoodBuddy" aria-label="Open emotional assistant">
        <span class="moodbuddy-trigger-icon">🧠</span>
        <span class="moodbuddy-trigger-pulse"></span>
    </button>

    <!-- Chat window -->
    <div id="moodbuddy-chat" class="moodbuddy-chat" style="display:none;">
        <div class="moodbuddy-header">
            <div class="moodbuddy-header-left">
                <span class="moodbuddy-avatar">🧠</span>
                <div class="moodbuddy-header-info">
                    <span class="moodbuddy-name">MoodBuddy</span>
                    <span class="moodbuddy-status">Emotional Wellness Assistant</span>
                </div>
            </div>
            <button id="moodbuddy-close" class="moodbuddy-close" title="Close" aria-label="Close chat">&times;</button>
        </div>
        <div id="moodbuddy-messages" class="moodbuddy-messages">
            <div class="moodbuddy-message moodbuddy-bot">
                <div class="moodbuddy-message-avatar">🧠</div>
                <div class="moodbuddy-message-bubble">
                    Hi <?php echo $firstName; ?>! 👋 I'm MoodBuddy, your emotional wellness companion. How are you feeling today?
                </div>
            </div>
        </div>
        <div id="moodbuddy-emotion-bar" class="moodbuddy-emotion-bar" style="display:none;">
            <span class="moodbuddy-emotion-tag" id="moodbuddy-emotion-tag"></span>
        </div>
        <div class="moodbuddy-input-area">
            <textarea id="moodbuddy-input" class="moodbuddy-input" placeholder="Type your message..." rows="1" maxlength="2000"></textarea>
            <button id="moodbuddy-send" class="moodbuddy-send" title="Send" aria-label="Send message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
</div>
