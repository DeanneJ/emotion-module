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

define('__OSSN_NOTIF__', ossn_route()->com . 'OssnNotifications/');
require_once __OSSN_NOTIF__ . 'classes/OssnNotifications.php';
/**
 * Initialize Notification Component
 *
 * @return void;
 * @access private
 */
function ossn_notifications() {
		//css
		ossn_extend_view('css/ossn.default', 'css/notifications');
		//js
		ossn_extend_view('js/ossn.site', 'js/OssnNotifications');

		if(ossn_isLoggedin()) {
				ossn_extend_view('ossn/site/head', 'js/notifications-settings');
				//pages
				ossn_register_page('notification', 'ossn_notification_page');
				ossn_register_page('notifications', 'ossn_notifications_page');
		}
		//callbacks
		ossn_register_callback('like', 'created', 'ossn_notification_like');
		ossn_register_callback('wall', 'post:created', 'ossn_notification_walltag');
		ossn_register_callback('annotations', 'created', 'ossn_notification_annotation');
		ossn_register_callback('user', 'delete', 'ossn_user_notifications_delete');

		//Orphan notification after posting/comment has been deleted #609
		ossn_register_callback('post', 'delete', 'ossn_post_notifications_delete');
		ossn_register_callback('like', 'deleted', 'ossn_like_notifications_delete');

		//hooks
		ossn_add_hook('notification:add', 'like:post', 'ossn_notificaiton_comments_post_hook');
		ossn_add_hook('notification:add', 'like:annotation', 'ossn_notificaiton_like_annotation_hook');
		ossn_add_hook('notification:add', 'like:entity', 'ossn_notificaiton_comment_entity_hook');
		ossn_add_hook('notification:add', 'like:object', 'ossn_notificaiton_comment_object_hook');
		ossn_add_hook('notification:view', 'friend_suggestion', 'research_friend_suggestions');
		ossn_add_hook('notification:add', 'comments:post', 'ossn_notificaiton_comments_post_hook');
		ossn_add_hook('notification:add', 'comments:entity', 'ossn_notificaiton_comment_entity_hook');
		ossn_add_hook('notification:add', 'comments:object', 'ossn_notificaiton_comment_object_hook');

		//friend recommendation system css
		ossn_extend_view('css/ossn.default', 'css/research_recs');
		

		//tag post with a friend, doesn't show in friend's notification #589
		ossn_add_hook('notification:add', 'wall:friends:tag', 'ossn_notificaiton_walltag_hook');

		if(ossn_isLoggedin()) {
				ossn_extend_view('ossn/js/head', 'notifications/js/autocheck');
				ossn_register_action('notification/mark/allread', __OSSN_NOTIF__ . 'actions/markread.php');
				if(ossn_isAdminLoggedin()) {
						ossn_register_action('notifications/admin/settings', __OSSN_NOTIF__ . 'actions/notifications/admin/settings.php');
						ossn_register_com_panel('OssnNotifications', 'settings');
				}
				ossn_register_action('notifications/delete/item', __OSSN_NOTIF__ . 'actions/delete/item.php');
				ossn_register_sections_menu('newsfeed', array(
						'name'   => 'notifications',
						'text'   => ossn_print('notifications'),
						'url'    => ossn_site_url('notifications/all'),
						'parent' => 'links',
				));
				ossn_register_action('coldstart/save_topics', __DIR__ . '/actions/coldstart/save_topics.php');
		}
}

/**
 * Render a single friend recommendation as an HTML notification string
 *
 * @param array $rec Recommendation array with keys: username, shared_interests, rec_guid
 * @return string HTML
 */
function ossn_render_friend_rec($rec) {
		$profile_url = ossn_site_url('u/' . $rec['username']);
		$username    = htmlspecialchars($rec['username']);
		$interests   = htmlspecialchars($rec['shared_interests']);
		$model       = htmlspecialchars($rec['model']);

		return '
		<div class="ossn-notification-item ossn-friend-rec-item" style="padding:10px; border-bottom:1px solid #eee; background:#f9f9ff;">
			<div style="display:flex; align-items:center; gap:10px;">
				<span style="font-size:22px;">🤝</span>
				<div>
					<div style="font-size:13px;">
						<strong>Friend Suggestion:</strong>
						<a href="' . $profile_url . '" style="color:#3b5998; font-weight:bold;">' . $username . '</a>
					</div>
					<div style="font-size:12px; color:#555; margin-top:2px;">
						Shared interest: <em>' . $interests . '</em>
						<span style="color:#aaa; font-size:11px; margin-left:6px;">via ' . $model . '</span>
					</div>
					<div style="margin-top:5px;">
						<a href="' . $profile_url . '"
						   style="font-size:11px; padding:3px 8px; background:#3b5998; color:#fff; border-radius:3px; text-decoration:none;">
							View Profile
						</a>
					</div>
				</div>
			</div>
		</div>';
}

/**
 * Notification Page
 *
 * @return mixed data;
 * @access public
 */
function ossn_notification_page($pages) {
		$page = $pages[0];
		if(empty($page)) {
				ossn_error_page();
		}
		header('Content-Type: application/json');
		switch ($page) {
		case 'notification':
				$get     = new OssnNotifications();
				$user_guid = ossn_loggedin_user()->guid;
				$unread  = ossn_call_hook('list', 'notification:unread', array(), true);

				// ── Get standard OSSN notifications ───────────────────────
				$raw_notifications = $get->get($user_guid, $unread);

				// ── Get friend recommendations separately ──────────────────
				$friend_recs = $get->getResearchFriendSuggestions($user_guid);

				// ── Build final notifications array ────────────────────────
				// Filter out any array items (old friend_suggestion arrays) from get()
				// and only keep proper HTML strings (standard notifications)
				$html_notifications = array();
				if(!empty($raw_notifications)) {
						foreach($raw_notifications as $item) {
								if(is_string($item)) {
										$html_notifications[] = $item;
								}
						}
				}

				// Render friend recs as HTML strings and append
				if(!empty($friend_recs)) {
						// Limit to 5 recommendations in the dropdown
						$recs_to_show = array_slice($friend_recs, 0, 5);
						foreach($recs_to_show as $rec) {
								$html_notifications[] = ossn_render_friend_rec($rec);
						}
				}

				if ($friend_recs === null) {
					$html_notifications[] = '
						<div class="ossn-notification-item ossn-friend-rec-item" style="padding:10px; background:#f9f9ff;">
							<strong>Friend Suggestions:</strong>
							<div style="margin-top:4px; font-size:12px;">
								<a href="' . ossn_site_url('notifications/all') . '" style="color:#3b5998;">
									Tell us your interests to get friend suggestions
								</a>
							</div>
						</div>';
				}

				$notifications['notifications'] = !empty($html_notifications) ? $html_notifications : false;
				$notifications['seeall']        = ossn_site_url('notifications/all');

				$clearall = ossn_plugin_view('output/url', array(
						'action' => true,
						'href'   => ossn_site_url('action/notification/mark/allread'),
						'class'  => 'ossn-notification-mark-read',
						'text'   => ossn_print('ossn:notifications:mark:as:read'),
				));

				if(!empty($notifications['notifications'])) {
						$data = ossn_plugin_view('notifications/pages/notification/notification', $notifications);
						echo json_encode(array(
								'type'  => 1,
								'data'  => $data,
								'extra' => $clearall,
						));
				} else {
						echo json_encode(array(
								'type' => 0,
								'data' => '<p>' . ossn_print('ossn:notification:no:notification') . '</p>',
						));
				}
				break;

		case 'friends':
				$friends['friends'] = ossn_loggedin_user()->getFriendRequests();
				if(!empty($friends['friends'])) {
						$data = ossn_plugin_view('notifications/pages/notification/friends', $friends);
						echo json_encode(array(
								'type' => 1,
								'data' => $data,
						));
				} else {
						echo json_encode(array(
								'type' => 0,
								'data' => '<p>' . ossn_print('ossn:notification:no:notification') . '</p>',
						));
				}
				break;

		case 'messages':
				$OssnMessages     = new OssnMessages();
				$params['recent'] = $OssnMessages->recentChat(ossn_loggedin_user()->guid);
				if(!empty($params['recent'])) {
						$data = ossn_plugin_view('messages/templates/message-with-notifi', $params);
						echo json_encode(array(
								'type' => 1,
								'data' => $data,
						));
				} else {
						echo json_encode(array(
								'type' => 0,
								'data' => '<p>' . ossn_print('ossn:notification:no:notification') . '</p>',
						));
				}
				break;

		case 'read':
				if(!empty($pages[1])) {
						$notification = new OssnNotifications();
						$notification = $notification->getbyGUID($pages[1]);
						if($notification->owner_guid == ossn_loggedin_user()->guid) {
								$notification->setViewed($pages[1]);
								$uri = $notification->getRedirectURI();
								//old mechanisim for other components
								$url = urldecode(input('notification'));
								if($uri) {
										$url = ossn_site_url($uri);
								}
								ob_flush();
								header("Location: {$url}");
								exit();
						}
				}
				redirect();
				break;

		case 'count':
				if(!ossn_isLoggedIn()) {
						ossn_error_page();
				}
				$notification = new OssnNotifications();
				$count_notif  = $notification->countNotification(ossn_loggedin_user()->guid);
				//Notifications crashing if OssnMessages module is disabled #646
				if(class_exists('OssnMessages')) {
						$messages       = new OssnMessages();
						$count_messages = $messages->countUNREAD(ossn_loggedin_user()->guid);
				} else {
						$count_messages = 0;
				}
				if(!$count_notif) {
						$count_notif = 0;
				}
				$friends   = ossn_loggedin_user()->getFriendRequests();
				$friends_c = 0;
				if($friends) {
						$friends_c = count($friends);
				}
				echo json_encode(array(
						'notifications' => $count_notif,
						'messages'      => $count_messages,
						'friends'       => $friends_c,
				));
				break;

		default:
				ossn_error_page();
				break;
		}
}

/**
 * Notifications page
 *
 * @param (array) $pages Array containg pages
 *
 * @return false|null data;
 * @access public
 */
function ossn_notifications_page($pages) {
		$page = $pages[0];
		if(empty($page)) {
				return false;
		}
		switch ($page) {
		case 'all':
				$title    = 'Notifications';
				$contents = array(
						'content' => ossn_plugin_view('notifications/pages/all'),
				);
				$content = ossn_set_page_layout('media', $contents);
				echo ossn_view_page($title, $content);
				break;

		default:
				ossn_error_page();
				break;
		}
}

/**
 * Create a notification for annotation like
 *
 * @return void;
 * @access private
 */
function ossn_notification_annotation($callback, $type, $params) {
		$notification = new OssnNotifications();
		$notification->add($params['type'], $params['owner_guid'], $params['subject_guid'], $params['annotation_guid']);
}

/**
 * Create a notification for like created
 *
 * @return void;
 * @access private
 */
function ossn_notification_like($type, $event_type, $params) {
		$notification = new OssnNotifications();
		$notification->add($params['type'], $params['owner_guid'], $params['subject_guid'], $params['subject_guid']);
}

/**
 * Create a notification for wall tag
 *
 * @return void;
 * @access private
 */
function ossn_notification_walltag($type, $ctype, $params) {
		$notification = new OssnNotifications();
		if(isset($params['friends']) && is_array($params['friends'])) {
				foreach ($params['friends'] as $friend) {
						if(!empty($params['poster_guid']) && !empty($params['object_guid']) && !empty($friend)) {
								$notification->add('wall:friends:tag', $params['poster_guid'], $params['object_guid'], $params['object_guid'], $friend);
						}
				}
		}
}

/**
 * Wall post user tag notification hook
 */
function ossn_notificaiton_walltag_hook($hook, $type, $return, $params) {
		if(isset($params['notification_owner'])) {
				$params['owner_guid'] = $params['notification_owner'];
		}
		return $params;
}

/**
 * Delete user notifiactions when user deleted
 */
function ossn_user_notifications_delete($callback, $type, $params) {
		$delete = new OssnNotifications();
		$delete->deleteUserNotifications($params['entity']);
}

/**
 * Delete wall post notifiactions
 */
function ossn_post_notifications_delete($callback, $type, $guid) {
		$delete = new OssnNotifications();
		if(!empty($guid)) {
				$delete->deleteNotification(array(
						'subject_guid' => $guid,
						'type'         => array(
								'wall:friends:tag',
								'like:post',
						),
				));
		}
}

/**
 * Delete like notifiactions
 */
function ossn_like_notifications_delete($callback, $type, $vars) {
		$delete = new OssnNotifications();
		if(isset($vars['subject_id']) && !empty($vars['subject_id'])) {
				$delete->deleteNotification(array(
						'item_guid' => $vars['subject_id'],
						'type'      => array(
								'like:entity:file:profile:photo',
								'like:entity:file:profile:cover',
								'like:entity:file:ossn:aphoto',
								'like:post',
								'like:annotation',
						),
				));
		}
}

/**
 * Wall post comments/likes notification hook
 */
function ossn_notificaiton_comments_post_hook($hook, $type, $return, $params) {
		$object              = new OssnObject();
		$object->object_guid = $params['subject_guid'];
		$object              = $object->getObjectById();
		if($object) {
				$params['owner_guid'] = $object->owner_guid;
				if($object->type !== 'user') {
						$params['type'] = "{$params['type']}:{$object->type}:{$object->subtype}";
						return ossn_call_hook('notification:add', $params['type'], $params, false);
				}
				return $params;
		}
		return false;
}

/**
 * Annotations likes notification hook
 */
function ossn_notificaiton_like_annotation_hook($hook, $type, $return, $params) {
		$annotation                = new OssnAnnotation();
		$annotation->annotation_id = $params['subject_guid'];
		$annotation                = $annotation->getAnnotationById();
		if($annotation) {
				$params['type']         = "like:annotation:{$annotation->type}";
				$params['owner_guid']   = $annotation->owner_guid;
				$params['subject_guid'] = $annotation->subject_guid;
				return $params;
		}
		return false;
}

/**
 * Entity comments/likes notification hook
 */
function ossn_notificaiton_comment_entity_hook($hook, $type, $return, $params) {
		$entity              = new OssnEntities();
		$entity->entity_guid = $params['subject_guid'];
		$entity              = $entity->get_entity();
		$params['type']      = "{$params['type']}:{$entity->subtype}";
		if($entity) {
				if($entity->type == 'user') {
						$params['owner_guid'] = $entity->owner_guid;
				}
				if($entity->type == 'object') {
						$object              = new OssnObject();
						$object->object_guid = $entity->owner_guid;
						$object              = $object->getObjectById();
						if($object) {
								$params['owner_guid'] = $object->owner_guid;
						}
				}
				return $params;
		}
		return false;
}

/**
 * Object comments/likes notification hook
 */
function ossn_notificaiton_comment_object_hook($hook, $type, $return, $params) {
		$object = ossn_get_object($params['subject_guid']);
		if($object) {
				$params['type'] = "{$params['type']}:{$object->subtype}";
				if($object->type == 'user') {
						$params['owner_guid'] = $object->owner_guid;
				}
				return $params;
		}
		return false;
}

//initialize notification component
ossn_register_callback('ossn', 'init', 'ossn_notifications');




//It generates full recommendation cards with Add Friend and Message buttons and appends them to whatever was already being displayed.
function research_friend_suggestions($hook, $type, $return, $params) {
		$user = ossn_loggedin_user();

		if(!$user) {
			return $return;
		}

		$notifications = new OssnNotifications();
		$recs = $notifications->getResearchFriendSuggestions($user->guid);

		// Cold start signal - show topic selector inline
		if ($recs === null) {
			$output  = "<div class='research-recommendations'>";
			$output .= "<p style='padding:10px;color:#555;font-size:13px;'>👋 ";
			$output .= "<a href='" . ossn_site_url('notifications/all') . "' style='color:#3b5998;'>";
			$output .= "Tell us your interests to get friend suggestions!</a></p>";
			$output .= "</div>";
			return $return . $output;
		}

		if (empty($recs)) {
			return $return;
		}

		$output = "<div class='research-recommendations'>";
		$output .= "<h3 class='rec-title'>Suggested Friends</h3>";

		foreach($recs as $rec) {
				$profile_url = ossn_site_url('u/' . $rec['username']);
				$message_url = ossn_site_url("messages/message/" . $rec['username'] . "?interest=" . urlencode($rec['shared_interests']));
				$username    = htmlspecialchars($rec['username']);
				$interests   = htmlspecialchars($rec['shared_interests']);

				$output .= "<div class='rec-card'>";

				$output .= "<div class='rec-user'>";
				$output .= "<b><a href='{$profile_url}' style='color:#3b5998;text-decoration:none;'>{$username}</a></b>";
				$output .= "</div>";

				$output .= "<div class='rec-explanation'>";
				$output .= "Suggested because you both engage with: <b>{$interests}</b>";
				$output .= "</div>";

				$output .= "<div class='rec-actions'>";
				$output .= "<a href='{$profile_url}' class='btn-addfriend'>Add Friend</a>";
				$output .= "<a href='{$message_url}' class='btn-message'>Message</a>";
				$output .= "</div>";

				$output .= "</div>";
		}

		$output .= "</div>";

		return $return . $output;
}