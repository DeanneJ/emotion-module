<?php
/**
 * OssnEmotions - Analyze image emotion via AffectNet API
 *
 * Accepts a wall post GUID, retrieves the attached photo,
 * and sends it to the AffectNet facial emotion recognition API.
 *
 * @package OssnEmotions
 */

// Require login
$user = ossn_loggedin_user();
if (!$user) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    return;
}

$guid = input('guid');
if (empty($guid) || !is_numeric($guid)) {
    echo json_encode(array('success' => false, 'error' => 'Invalid post GUID'));
    return;
}

// Load the wall post and get its photo
$post = new OssnWall;
$wall_post = $post->GetPost($guid);

if (!$wall_post || !isset($wall_post->{'file:wallphoto'})) {
    echo json_encode(array('success' => false, 'error' => 'No photo found for this post'));
    return;
}

// Use OssnWall's getPhotoFile method to find the image
$post->guid = $guid;
$post->{'file:wallphoto'} = $wall_post->{'file:wallphoto'};
$photoFile = $post->getPhotoFile();

if (!$photoFile) {
    echo json_encode(array('success' => false, 'error' => 'Photo file not found'));
    return;
}

$filepath = ossn_get_userdata("object/{$guid}/ossnwall/images/{$photoFile->value}");

if (!file_exists($filepath)) {
    echo json_encode(array('success' => false, 'error' => 'Photo file missing from disk'));
    return;
}

// Send to AffectNet API
$result = ossn_emotions_image_api_call($filepath);

if ($result === false) {
    echo json_encode(array('success' => false, 'error' => 'AffectNet API unavailable'));
    return;
}

echo json_encode(array(
    'success' => true,
    'guid'    => (int) $guid,
    'data'    => $result,
));
