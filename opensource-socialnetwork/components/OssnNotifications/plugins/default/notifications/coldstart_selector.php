<?php
/**
 * Cold start topic selector popup
 * Shown when user has no SBERT data, no posts, and hasn't picked topics yet
 */

// Check if user already has topics saved
$notifications = new OssnNotifications();
$user = ossn_loggedin_user();
$existing_topics = $notifications->getColdStartInterests($user->guid);

// Don't show selector if topics already exist
if (!empty($existing_topics)) {
    return;
}

$save_url = ossn_site_url('action/coldstart/save_topics');
$token    = ossn_generate_action_token($save_url);
?>

<div id="coldstart-overlay" style="
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
">
    <div id="coldstart-box" style="
        background: #fff;
        border-radius: 10px;
        padding: 30px;
        max-width: 520px;
        width: 90%;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        font-family: Arial, sans-serif;
    ">
        <h2 style="margin: 0 0 8px 0; font-size: 20px; color: #1a1a2e;">
            👋 Welcome! What are you interested in?
        </h2>
        <p style="color: #555; font-size: 14px; margin-bottom: 20px;">
            Pick at least 3 topics so we can suggest friends with similar interests.
        </p>

        <div id="coldstart-topics" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <?php
            $topics = array(
                'Memes', 'Food', 'Finance', 'Travel', 'Health', 'Books', 'Fitness', 'Fashion', 
                'Music', 'Education', 'Technology', 'Art', 'Pets', 'Gaming', 'Photography', 'Politics'
            );
            foreach ($topics as $topic) {
                echo "<span class='coldstart-topic' data-topic='" . htmlspecialchars($topic) . "' style='
                    padding: 8px 16px;
                    border: 2px solid #ddd;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 13px;
                    color: #333;
                    background: #f5f5f5;
                    transition: all 0.2s;
                    user-select: none;
                '>" . htmlspecialchars($topic) . "</span>";
            }
            ?>
        </div>

        <div id="coldstart-msg" style="font-size:13px; color:#e74c3c; margin-bottom:10px; display:none;">
            Please select at least 3 topics.
        </div>

        <button id="coldstart-save" style="
            background: #3b5998;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
        ">
            Find Friends →
        </button>
    </div>
</div>

<script>
(function() {
    var selected = [];
    var saveUrl  = '<?php echo $save_url; ?>';
    var token    = '<?php echo $token; ?>';

    document.querySelectorAll('.coldstart-topic').forEach(function(el) {
        el.addEventListener('click', function() {
            var topic = this.getAttribute('data-topic');
            var idx   = selected.indexOf(topic);

            if (idx === -1) {
                selected.push(topic);
                this.style.background  = '#3b5998';
                this.style.color       = '#fff';
                this.style.borderColor = '#3b5998';
            } else {
                selected.splice(idx, 1);
                this.style.background  = '#f5f5f5';
                this.style.color       = '#333';
                this.style.borderColor = '#ddd';
            }
        });
    });

    document.getElementById('coldstart-save').addEventListener('click', function() {
        if (selected.length < 3) {
            document.getElementById('coldstart-msg').style.display = 'block';
            return;
        }

        document.getElementById('coldstart-msg').style.display = 'none';

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Saving...';

        Ossn.PostRequest({
            url: saveUrl,
            params: 'topics=' + encodeURIComponent(selected.join(',')),
            callback: function(res) {
                console.log("Response:", res);

                if (res && res.status === 'success') {
                    document.getElementById('coldstart-overlay').style.display = 'none';
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Find Friends →';
                    alert(res && res.message ? res.message : 'Something went wrong.');
                }
            }
        });
    });
})();
</script>