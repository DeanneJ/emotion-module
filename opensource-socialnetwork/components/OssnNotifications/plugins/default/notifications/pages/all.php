<?php
/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network (OSSN)
 * @author    OSSN Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 */


$get = new  OssnNotifications;
$notifications = $get->searchNotifications(array(
					'owner_guid' => ossn_loggedin_user()->guid,	
					'offset' => input('offset', '', 1),
					'order_by' => 'n.guid DESC',
));
$count = $get->searchNotifications(array(
					'owner_guid' => ossn_loggedin_user()->guid,
					'count' => true,
));
$list = '<div class="ossn-notifications-all ossn-notification-page">';
if($notifications){
	foreach($notifications as $item){
			$list .= $item->toTemplate();	
	}
}
$list .= "</div>";
$pagination = ossn_view_pagination($count);
$extra = '';

$notifications_obj = new OssnNotifications();
$user = ossn_loggedin_user();
$recs = $notifications_obj->getResearchFriendSuggestions($user->guid);

if ($recs === null) {
    $extra .= ossn_plugin_view('notifications/coldstart_selector');
} elseif (!empty($recs)) {
    $extra .= ossn_plugin_view('notifications/research_recs');
}

echo ossn_plugin_view('widget/view', array(
				'title' => ossn_print('notifications'),
				'contents' => $list . $pagination . $extra,
));






?>