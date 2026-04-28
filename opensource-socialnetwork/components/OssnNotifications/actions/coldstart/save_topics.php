<?php
header('Content-Type: application/json');

if (!ossn_isLoggedin()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_guid = (int) ossn_loggedin_user()->guid;
$topics    = input('topics');

if (empty($topics)) {
    echo json_encode(['status' => 'error', 'message' => 'No topics received']);
    exit;
}

$topics_array = array_filter(array_map('trim', explode(',', $topics)));

if (empty($topics_array)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid topics']);
    exit;
}

try {
    $db = new OssnDatabase();

    $topics_json = json_encode(array_values($topics_array));
    $time        = time();

    // Check if user already has topics saved
    $db->select(array(
        'from'   => 'ossn_cold_start_interests',
        'wheres' => array(array(
            'name'       => 'user_guid',
            'comparator' => '=',
            'value'      => $user_guid
        ))
    ));
    $existing = $db->fetch();

    if ($existing) {
        // Update existing record
        $db->update(array(
            'table' => 'ossn_cold_start_interests',
            'names' => array('topics', 'time_created'),
            'values' => array($topics_json, $time),
            'wheres' => array(array(
                'name'       => 'user_guid',
                'comparator' => '=',
                'value'      => $user_guid
            ))
        ));
    } else {
        // Insert new record
        $db->insert(array(
            'into'   => 'ossn_cold_start_interests',
            'names'  => array('user_guid', 'topics', 'time_created'),
            'values' => array($user_guid, $topics_json, $time)
        ));
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Topics saved successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save topics: ' . $e->getMessage()
    ]);
}
exit;