<?php
/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network
 * @author    Open Source Social Network Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 * 
 * 
 * 
 * 
 */



require_once ossn_route()->www . 'moderation_api.php';

$OssnComment = new OssnComments;
$image       = input('comment-attachment');

if(!empty($image)) {
    $OssnComment->comment_image = $image;
}

$post    = input('post');
$comment = input('comment');

// ==============================
// AI COMMENT MODERATION
// ==============================

$result = moderate_comment_api($comment);

$action   = "ALLOW";
$reason   = "";
$keywords = "";

if(isset($result['ok']) && $result['ok']){

    $action = $result['recommended_action'] ?? "ALLOW";
    $reason = $result['reason'] ?? "";

    if(isset($result['evidence']['keywords_detected']) &&
       count($result['evidence']['keywords_detected']) > 0){
        $keywords = implode(", ", $result['evidence']['keywords_detected']);
    }
}

// ==============================
// DELETE CASE
// ==============================

if ($action === 'DELETE') {

    $keyword_block = "";
    if(!empty($keywords)){
        $keyword_block = "<br><b>Detected abusive terms:</b> <span style='color:#c0392b;font-weight:bold'>{$keywords}</span>";
    }

    $message = "
    <div style='
        background:#fff9db;
        border:1px solid #f2c94c;
        border-left:6px solid #f39c12;
        padding:18px;
        border-radius:8px;
        font-family:Arial,Helvetica,sans-serif;
        box-shadow:0 3px 8px rgba(0,0,0,0.08);
        max-width:520px;
        margin:auto;
        text-align:center;
        line-height:1.6;
    '>
        <div style='font-size:20px;font-weight:600;color:#c0392b'>
        🚫 Comment Removed
        </div>

        <div style='margin-top:10px;font-size:14px;color:#333'>
        This comment violated community guidelines and has been removed.
        </div>

        {$keyword_block}
    </div>
    ";

    ossn_trigger_message($message, 'warning');

    if (ossn_is_xhr()) {
        header('Content-Type: application/json');
        echo json_encode(array(
			'process' => 1,
			'comment' => ''
		));
        exit;
    }

    redirect(REF);
    exit;
}

// ==============================
// SAVE COMMENT
// ==============================

if($OssnComment->PostComment($post, ossn_loggedin_user()->guid, $comment)) {

    // ==========================
    // WARN CASE
    // ==========================
    if ($action === 'WARN') {

        $keyword_block = "";
        if(!empty($keywords)){
            $keyword_block = "
            <div style='margin-top:10px'>
                <b>Detected terms:</b>
                <span style='color:#c0392b;font-weight:bold'>{$keywords}</span>
            </div>";
        }

        $message = "
        <div style='
            background:#fff9db;
            border:1px solid #f2c94c;
            border-left:6px solid #f39c12;
            padding:18px;
            border-radius:8px;
            font-family:Arial,Helvetica,sans-serif;
            box-shadow:0 3px 8px rgba(0,0,0,0.08);
            max-width:520px;
            margin:auto;
            text-align:center;
            line-height:1.6;
        '>
            <div style='font-size:20px;font-weight:600;color:#d68910'>
            ⚠ Comment Warning
            </div>

            <div style='margin-top:10px;font-size:14px;color:#333'>
            This comment may contain language that violates community guidelines.
            </div>

            {$keyword_block}
        </div>
        ";

        ossn_trigger_message($message, 'warning');
    }

    $vars            = array();
    $vars['comment'] = (array)ossn_get_comment($OssnComment->getCommentId());
    $data            = ossn_comment_view($vars);

    if(!ossn_is_xhr()) {
        redirect(REF);
    } else {
        header('Content-Type: application/json');
        echo json_encode(array(
            'comment' => $data,
            'process' => 1
        ));
        exit;
    }

} else {
    if(!ossn_is_xhr()) {
        redirect(REF);
    } else {
        header('Content-Type: application/json');
        echo json_encode(array(
            'process' => 0
        ));
        exit;
    }
}
