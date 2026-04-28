<?php
// bootstrap OSSN environment for CLI access
define('OSSN_ALLOW_SYSTEM_START', TRUE);
require_once __DIR__ . '/system/start.php';

$db = new OssnDatabase();
try {
    $db->statement("SELECT tone,starter_text FROM ossn_ng_starters LIMIT 1");
    $db->execute();
    $rows = $db->fetch(true);
    echo "ok\n";
    var_dump($rows);
} catch (Exception $e) {
    echo "error: " . $e->getMessage() . "\n";
}

// try rendering the research_tone_selector view for testing
$user = ossn_loggedin_user();
if (!$user) {
    // fetch any user from db
    $db->statement("SELECT guid FROM ossn_users LIMIT 1");
    $db->execute();
    $u = $db->fetch();
    if ($u) {
        $user = ossn_user_by_guid($u->guid);
    }
}
$params = array('user' => $user);
$html = ossn_plugin_view('messages/research_tone_selector', $params);
echo "\n--- view output ---\n";
echo $html;


