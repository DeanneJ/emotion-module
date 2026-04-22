<?php

#changes for lifecycle-behavioral-bot-detection start 1 (LINE NO 2 TO 6)
require_once ossn_route()->www . "configurations/behavior_detection_config.php";
require_once BG_GUARD_PATH;

/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network
 * @author    Open Source Social Network Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 */
$OssnComment = new OssnComments;

//changes (LINE NO 17 TO 25)
$user = ossn_loggedin_user();

if(behaviourguard_is_restricted($user->guid,"limit_comments")){
    redirect(REF);
    return;
}

$image       = input('comment-attachment');
//comment image check if is attached or not
if(!empty($image)) {
		$OssnComment->comment_image = $image;
}
//post on which comment is going to be posted
$post = input('post');

//comment text
$comment = input('comment');
if($OssnComment->PostComment($post, ossn_loggedin_user()->guid, $comment)) {
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
