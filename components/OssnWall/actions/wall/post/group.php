<?php

#changes for lifecycle-behavioral-bot-detection start 1 (LINE NO 2 TO 6)
require_once ossn_route()->www . "configurations/behavior_detection_config.php";
require_once BG_GUARD_PATH;

/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network (OSSN)
 * @author    OSSN Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 */

$OssnWall = new OssnWall();

//changes (LINE NO 18 TO 26)
$user = ossn_loggedin_user();

if(behaviourguard_is_restricted($user->guid,"limit_posting")){
    ossn_trigger_message("Posting temporarily restricted due to suspicious behaviour.", 'error');
    redirect(REF);
    return;
}

$OssnWall->poster_guid = ossn_loggedin_user()->guid;

$owner = input('wallowner');
if(isset($owner) && !empty($owner)) {
		$OssnWall->owner_guid = $owner;
}

$group = ossn_get_group_by_guid($owner);

$OssnWall->type = 'group';

$post     = input('post');
$location = input('location');
$friends  = input('friends');

if($friends) {
		$friend_guids = explode(',', $friends);
		$friend_guids = array_unique($friend_guids);
		$friends      = array();
		foreach ($friend_guids as $guid) {
				if($group->isMember($group->guid, $guid)) {
						$friends[] = $guid;
				}
		}
		$friends = implode(',', $friends);
}
if($group && $OssnWall->Post($post, $friends, $location, OSSN_PRIVATE)) {
		if(ossn_is_xhr()) {
				$guid = $OssnWall->getObjectId();
				$get  = $OssnWall->GetPost($guid);
				if($get) {
						$get = ossn_wallpost_to_item($get);
						ossn_set_ajax_data(array(
								'post' => ossn_wall_view_template($get),
						));
				}
		}
		//no need to show message on success.
		//3.x why not? $arsalanshah
		ossn_trigger_message(ossn_print('post:created'));
		if(isset($OssnWall->OssnFile) && isset($OssnWall->OssnFile->error)) {
				ossn_trigger_message($OssnWall->OssnFile->getFileUploadError($OssnWall->OssnFile->error), 'error');
		}
		redirect(REF);
} else {
		if(isset($OssnWall->OssnFile) && isset($OssnWall->OssnFile->error)) {
				ossn_trigger_message($OssnWall->OssnFile->getFileUploadError($OssnWall->OssnFile->error), 'error');
		} else {
				ossn_trigger_message(ossn_print('post:create:error'), 'error');
		}
		redirect(REF);
}