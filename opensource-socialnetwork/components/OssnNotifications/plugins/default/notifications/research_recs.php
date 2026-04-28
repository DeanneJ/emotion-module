<?php

$notifications = new OssnNotifications();
$user = ossn_loggedin_user();

$recs = $notifications->getResearchFriendSuggestions($user->guid);

// null = cold start signal, false/empty = truly nothing
if ($recs === null) {
    echo ossn_plugin_view('notifications/coldstart_selector');
    return;
}

if (empty($recs)) {
    return;
}

echo "<div class='research-recommendations'>";
echo "<h3 class='rec-title'>Recommended Friends</h3>";

foreach($recs as $rec){
    $profile_url = ossn_site_url("u/".$rec['username']);
    $message_url = ossn_site_url("messages/message/".$rec['username']."?interest=" . urlencode($rec['explanation']));

    echo "<div class='rec-card'>";

    // USER NAME
    echo "<div class='rec-user'><b>".htmlspecialchars($rec['username'])."</b></div>";

    // INTERESTS
    echo "<div class='rec-explanation'>";
    echo "Shared interests: <b>" . htmlspecialchars($rec['shared_interests']) . "</b>";
    echo "</div>";

    // SCORE
    if (!empty($rec['similarity_score'])) {
        echo "<div class='rec-source-text'>";
        echo "Match score: <b>" . $rec['similarity_score'] . "%</b>";
        echo "</div>";
    }

    // SOURCE TEXT
    if (!empty($rec['source']) && $rec['source'] === 'coldstart') {
        echo "<div class='rec-source-text'>Based on your selected interests</div>";
    } elseif (!empty($rec['source']) && $rec['source'] === 'fallback') {
        echo "<div class='rec-source-text'>Based on your posts</div>";
    }

    // ACTION BUTTONS (INSIDE CARD)
    echo "<div class='rec-actions'>";
    echo "<a href='".$profile_url."' class='btn-addfriend'>Add Friend</a>";
    echo "<a href='".$message_url."' class='btn-message'>Message</a>";
    echo "</div>";

    echo "</div>"; 
}
echo "</div>";
?>