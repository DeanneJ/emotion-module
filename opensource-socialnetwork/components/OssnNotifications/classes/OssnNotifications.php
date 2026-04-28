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
class OssnNotifications extends OssnDatabase {
		/**
		 * Initialize the objects.
		 *
		 * @return void
		 */
		private function initAttributes() {
				if(empty($this->order_by)) {
						$this->order_by = '';
				}
				if(empty($this->limit)) {
						$this->limit = false;
				}
				$this->data = new stdClass();

				if(!isset($this->offset)) {
						$this->offset = 1;
				}
				if(!isset($this->page_limit)) {
						//default OssnPagination limit
						$this->page_limit = ossn_call_hook('pagination', 'per_page', false, 10);
				}
				if(!isset($this->count)) {
						$this->count = false;
				}
		}
		/**
		 * Add notification to database
		 *
		 * @param integer $subject_id Id of item which user comment
		 * @param integer $poster_guid Guid of item poster
		 * @param integer $item_guid: Guid of item
		 * @param integer $notification_owner: Guid of notification owner
		 *
		 * @return boolean;
		 */
		public function add($type, $poster_guid, $subject_guid, $item_guid = null, $notification_owner = '') {
				if(!empty($type) && !empty($subject_guid) && !empty($poster_guid)) {
						$vars = array(
								'type'               => $type,
								'poster_guid'        => $poster_guid,
								'owner_guid'         => null,
								'item_guid'          => $item_guid,
								'subject_guid'       => $subject_guid,
								'notification_owner' => $notification_owner,
						);
						$this->notification = ossn_call_hook('notification:add', $type, $vars, false);
						if(!$this->notification) {
								return false;
						}
						//check if notification owner is set then use it.
						if(!empty($this->notification['notification_owner'])) {
								$this->notification['owner_guid'] = $this->notification['notification_owner'];
						}
						//check if owner_guid is empty or owner_guid is same as poster_guid then return false,
						if(empty($this->notification['owner_guid']) || $this->notification['owner_guid'] == $this->notification['poster_guid']) {
								ossn_trigger_callback('notification', 'owner:poster:match', array(
										'instance'     => $this,
										'notification' => $this->notification,
								));
								return false;
						}
						$callback = array(
								'type'         => $this->notification['type'],
								'poster_guid'  => $this->notification['poster_guid'],
								'owner_guid'   => $this->notification['owner_guid'],
								'subject_guid' => $this->notification['subject_guid'],
								'item_guid'    => $this->notification['item_guid'],
						);
						$params['into']  = 'ossn_notifications';
						$params['names'] = array(
								'type',
								'poster_guid',
								'owner_guid',
								'subject_guid',
								'item_guid',
								'time_created',
						);
						$params['values'] = array(
								$this->notification['type'],
								$this->notification['poster_guid'],
								$this->notification['owner_guid'],
								$this->notification['subject_guid'],
								$this->notification['item_guid'],
								time(),
						);

						if($this->insert($params)) {
								//we need a callback when notification is added
								ossn_trigger_callback('notification', 'add', array(
										'id'           => $this->getLastEntry(),
										'instance'     => $this,
										'notification' => $this->notification,
								));
								return true;
						}
				}
				return false;
		}
		/**
		 * Add notification participant
		 *
		 * @param integer $partcipate  User guid who you wanted to notify
		 * @param array   $vars        Option values
		 *
		 * @return boolean
		 */
		public function notifyParticipant($partcipate, $vars) {
				if(empty($partcipate)) {
						return false;
				}
				$params['into']  = 'ossn_notifications';
				$params['names'] = array(
						'type',
						'poster_guid',
						'owner_guid',
						'subject_guid',
						'item_guid',
						'time_created',
				);
				$params['values'] = array(
						$vars['type'],
						$vars['poster_guid'],
						$partcipate,
						$vars['subject_guid'],
						$vars['item_guid'],
						time(),
				);
				if($partcipate !== $vars['poster_guid']) {
						if($this->insert($params)) {
								$callback['owner_guid'] = $partcipate;
								ossn_trigger_callback('notification', 'participant:added', $callback);
								return true;
						}
				}
				return false;
		}
		/**
		 * Get notifications
		 *
		 * @param integer $guid_two User guid
		 * @param integer $poster_guid Guid of item poster;
		 *
		 * @return array
		 */
		public function get($guid_two = '', $unread = false, $limit = false, $count = false) {
				if($unread === true) {
						$vars['viewed'] = false;
				}
				if($limit) {
						$vars['limit'] = $limit;
				}
				$vars['owner_guid'] = $guid_two;
				$vars['count']      = $count;
				$vars['page_limit'] = false;
				$vars['order_by']   = 'n.guid DESC';
				$get                = $this->searchNotifications($vars);
				if($count) {
						return $get;
				}
				if($get) {
						foreach ($get as $notif) {
								if(ossn_is_hook('notification:view', $notif->type)) {
										$messages[] = ossn_call_hook('notification:view', $notif->type, $notif);
								}
						}
						return $messages;
				}
				return false;
		}

		/**
		 * Count user notification
		 *
		 * @param integer $guid count user notifications
		 *
		 * @return integer;
		 */
		public function countNotification($guid) {
				return $this->searchNotifications(array(
						'owner_guid' => $guid,
						'count'      => true,
						'viewed'     => false,
				));
		}

		/**
		 * Get notitication by guid
		 *
		 * @param integer $guid Notification guid
		 *
		 * @return object;
		 */
		public function getbyGUID($guid = '') {
				if(empty($guid)) {
						return false;
				}
				$notifcation = $this->searchNotifications(array(
						'guid' => $guid,
				));
				if($notifcation) {
						return $notifcation[0];
				}
				return false;
		}

		/**
		 * Mark notification as viewed
		 *
		 * @return boolean
		 */
		public function setViewed($guid) {
				//[B] Notification is not getting marked viewed #2439
				if(isset($this->guid)) {
						$guid = $this->guid;
				}
				if(!isset($guid)) {
						return false;
				}
				return $this->update(array(
						'table'  => 'ossn_notifications',
						'names'  => array(
								'viewed',
						),
						'values' => array(
								'',
						),
						'wheres' => array(
								array(
										'name'       => 'guid',
										'comparator' => '=',
										'value'      => $this->guid,
								),
						),
				));
		}
		/**
		 * Delete user notifications
		 *
		 * @param object $user User entity
		 *
		 * @return boolean
		 */
		public function deleteUserNotifications($user) {
				if($user) {
						//secure as uses $user->guid from object
						$this->statement("DELETE FROM ossn_notifications WHERE(
							  poster_guid='{$user->guid}' OR owner_guid='{$user->guid}');");
						if($this->execute()) {
								return true;
						}
				}
				return false;
		}
		/**
		 * Delete notification by guid
		 *
		 * @return boolean
		 */
		public function deleteItem() {
				if(!isset($this->guid) || empty($this->guid)) {
						return false;
				}
				$vars           = array();
				$vars['from']   = 'ossn_notifications';
				$vars['wheres'] = array(
						"(guid='{$this->guid}')",
				);
				return $this->delete($vars);
		}
		/**
		 * Clear all notifications of specific user
		 * See : 3 state logic for notifications #202
		 * https://github.com/opensource-socialnetwork/opensource-socialnetwork/issues/202
		 *
		 * @param integer $guid User guid
		 *
		 * @return boolean;
		 */
		public function clearAll($guid) {
				if(empty($guid)) {
						return false;
				}
				$vars          = array();
				$vars['table'] = 'ossn_notifications';
				$vars['names'] = array(
						'viewed',
				);
				$vars['values'] = array(
						'',
				);
				$vars['wheres'] = array(
						array(
								'name'       => 'owner_guid',
								'comparator' => '=',
								'value'      => $guid,
						),
				);
				return $this->update($vars);
		}
		/**
		 * Delete a notifications
		 *
		 * @param array $params A wheres clause
		 *
		 * @return boolean
		 */
		public function deleteNotification(array $params = array()) {
				if(!empty($params)) {
						$valid = array(
								'guid',
								'type',
								'poser_guid',
								'owner_guid',
								'subject_guid',
								'item_guid',
						);
						foreach ($params as $key => $item) {
								if(!in_array($key, $valid)) {
										unset($params[$key]);
								}
						}
						if(empty($params)) {
								return false;
						}
						foreach ($params as $key => $where) {
								if(is_array($where)) {
										foreach ($where as $implode) {
												$items[] = "'{$implode}'";
										}
										$wheres[] = array(
												'name'       => $key,
												'comparator' => 'IN',
												'value'      => $items,
										);
										unset($items);
										unset($in);
								} else {
										$wheres[] = array(
												'name'       => $key,
												'comparator' => '=',
												'value'      => $where,
										);
								}
						}
						if(empty($wheres)) {
								return false;
						}
						$vars           = array();
						$vars['from']   = 'ossn_notifications';
						$vars['wheres'] = $wheres;
						return $this->delete($vars);
				}
				return false;
		}
		/**
		 * Search Notifcations
		 *
		 * @param array $params A valid options in format,
		 * @param string $params['type'] Notification type
		 * @param string $params['owner_guid'] Notification owner guid
		 * @param string $params['poster_guid'] Notification poster guid
		 * @param string $params['subject_guid'] Notifcation subject guid
		 * @param string $params['item_created'] Notifcation time_created
		 * @param string $params['item_guid'] Notifcation item guid
		 * @param string $params['count'] If you wanted to count then true
		 * @param string $params['viewed'] If viewed true, if not then false
		 * @param string $params['guid'] Notifcation guid
		 * @param string $params['order_by'] Order list , default ASC guid
		 *
		 * reutrn array|false;
		 *
		 */
		public function searchNotifications(array $params = array()) {
				self::initAttributes();
				$default = array(
						'guid'         => false,
						'type'         => false,
						'poster_guid'  => false,
						'owner_guid'   => false,
						'subject_guid' => false,
						'time_created' => false,
						'item_guid'    => false,
						'limit'        => false,
						'order_by'     => false,
						'offset'       => 1,
						'page_limit'   => ossn_call_hook('pagination', 'per_page', false, 10), //call hook for page limit
						'count'        => false,
				);
				$options = array_merge($default, $params);
				$wheres  = array();
				//prepare limit
				$limit = $options['limit'];

				//validate offset values
				if(!empty($options['limit']) && !empty($options['limit']) && !empty($options['page_limit'])) {
						$offset_vals = ceil($options['limit'] / $options['page_limit']);
						$offset_vals = abs($offset_vals);
						$offset_vals = range(1, $offset_vals);
						if(!in_array($options['offset'], $offset_vals)) {
								return false;
						}
				}
				//get only required result, don't bust your server memory
				$getlimit = $this->generateLimit($options['limit'], $options['page_limit'], $options['offset']);
				if($getlimit) {
						$options['limit'] = $getlimit;
				}
				//search notifications
				if(!empty($options['guid'])) {
						//$wheres[] = "n.guid='{$options['guid']}'";
						$wheres[] = array(
								'name'       => 'n.guid',
								'comparator' => '=',
								'value'      => $options['guid'],
						);
				}
				if(!empty($options['type'])) {
						//$wheres[] = "n.type='{$options['type']}'";
						$wheres[] = array(
								'name'       => 'n.type',
								'comparator' => '=',
								'value'      => $options['type'],
						);
				}
				if(!empty($options['owner_guid'])) {
						//$wheres[] = "n.owner_guid ='{$options['owner_guid']}'";
						$wheres[] = array(
								'name'       => 'n.owner_guid',
								'comparator' => '=',
								'value'      => $options['owner_guid'],
						);
				}
				if(!empty($options['poster_guid'])) {
						//$wheres[] = "n.poster_guid ='{$options['poster_guid']}'";
						$wheres[] = array(
								'name'       => 'n.poster_guid',
								'comparator' => '=',
								'value'      => $options['poster_guid'],
						);
				}
				if(!empty($options['subject_guid'])) {
						//$wheres[] = "n.subject_guid ='{$options['subject_guid']}'";
						$wheres[] = array(
								'name'       => 'n.subject_guid',
								'comparator' => '=',
								'value'      => $options['subject_guid'],
						);
				}
				if(!empty($options['item_guid'])) {
						//$wheres[] = "n.item_guid ='{$options['item_guid']}'";
						$wheres[] = array(
								'name'       => 'n.item_guid',
								'comparator' => '=',
								'value'      => $options['item_guid'],
						);
				}
				if(!empty($options['time_created'])) {
						//$wheres[] = "n.time_created ='{$options['time_created']}'";
						$wheres[] = array(
								'name'       => 'n.time_created',
								'comparator' => '=',
								'value'      => $options['time_created'],
						);
				}
				if(isset($options['viewed']) && $options['viewed'] == true) {
						$wheres[] = "n.viewed =''";
				}
				if(isset($options['viewed']) && $options['viewed'] == false) {
						$wheres[] = 'n.viewed IS NULL';
				}

				if(isset($options['wheres']) && !empty($options['wheres'])) {
						if(!is_array($options['wheres'])) {
								$wheres[] = $options['wheres'];
						} else {
								foreach ($options['wheres'] as $witem) {
										$wheres[] = $witem;
								}
						}
				}

				if(empty($wheres)) {
						return false;
				}
				$params           = array();
				$params['from']   = 'ossn_notifications as n';
				$params['params'] = array(
						'n.*',
				);
				$params['wheres']   = $wheres;
				$params['order_by'] = $options['order_by'];
				$params['limit']    = $options['limit'];

				if(!$options['order_by']) {
						$params['order_by'] = 'n.guid ASC';
				}
				if(isset($options['group_by']) && !empty($options['group_by'])) {
						$params['group_by'] = $options['group_by'];
				}
				//override params
				if(isset($options['params']) && !empty($options['params'])) {
						$params['params'] = $options['params'];
				}
				//prepare count data;
				if($options['count'] === true) {
						unset($params['params']);
						unset($params['limit']);
						$count           = array();
						$count['params'] = array(
								'count(*) as total',
						);
						$count = array_merge($params, $count);
						return $this->select($count)->total;
				}
				$fetched_data = $this->select($params, true);
				if($fetched_data) {
						foreach ($fetched_data as $item) {
								$results[] = arrayObject($item, get_class($this));
						}
						return $results;
				}
				return false;
		}
		/**
		 * Make notifcation item to output view
		 *
		 * @return string|false
		 */
		public function toTemplate() {
				if(empty($this->guid)) {
						return false;
				}
				if(ossn_is_hook('notification:view', $this->type)) {
						return ossn_call_hook('notification:view', $this->type, $this);
				}
				return false;
		}
		/**
		 * Notification redirect URI
		 *
		 * OSSN 7.7 new way to handle long subject URLs
		 * As the URL may include & and = which some servers givies 403 forbidden error
		 * So instead of passing subject_url in URL parameter use hook
		 *
		 * @params object $instance Notification instance
		 *
		 * @return boolean|string
		 */
		public function getRedirectURI() {
				if(isset($this->type)) {
						if(ossn_is_hook('notification:redirect:uri', $this->type)) {
								$args = array(
										'notification' => $this,
								);
								$hook = ossn_call_hook('notification:redirect:uri', $this->type, $args, false);
								if($hook && !empty($hook)) {
										return $hook;
								}
						}
				}
				return false;
		}



		//public, called by everyone, including the page handler for /notifications/research-friends
		public function getResearchFriendSuggestions($user_guid, $limit = 10) {
		$user_guid = (int)$user_guid;
		$limit     = (int)$limit;

		$url = "http://127.0.0.1:8000/recommendation/api";

		$data = json_encode(array(
			"component" => "recommendation",
			"action" => "get",
			"user_id" => $user_guid
		));

		$options = array(
			'http' => array(
				'header'  => "Content-Type: application/json\r\n",
				'method'  => 'POST',
				'content' => $data,
			),
		);

		$context  = stream_context_create($options);
		$response = @file_get_contents($url, false, $context);

		if (!$response) {
			// API failed → fallback
			return $this->getFallbackResearchFriendSuggestions($user_guid, $limit);
		}

		$result = json_decode($response, true);

		if (!empty($result['data'])) {
			$recs = array();

			foreach ($result['data'] as $row) {

				$user = ossn_user_by_guid($row['rec_guid']);
				if (!$user) continue;

				// Generate category scores
				$interests = explode(',', strtolower($row['shared_interests']));
				$interests = array_map('trim', $interests);
				$interests = array_filter($interests);

				$score = isset($row['similarity_score']) ? round($row['similarity_score']) : 70;

				

				$recs[] = array(
					'username' => $user->username,
					'guid' => $user->guid,
					'shared_interests' => $row['shared_interests'],
					'explanation' => $row['shared_interests'],
					'similarity_score' => $row['similarity_score'],
					'source' => 'sbert_api'
				);
			}

			if (!empty($recs)) {
				return $recs;
			}
		}

		// Step 2: fallback
		$fallback = $this->getFallbackResearchFriendSuggestions($user_guid, $limit);
		if (!empty($fallback)) {
			return $fallback;
		}

		// Step 3: cold start
		$cold_start_topics = $this->getColdStartInterests($user_guid);
		if (!empty($cold_start_topics)) {
			return $this->getColdStartRecommendations($user_guid, $cold_start_topics, $limit);
		}

		return null;
	}


		//Step 1: Fetch from ossn_ng_friend_recs (SBERT offline results)
		private function getStoredResearchFriendSuggestions($user_guid, $limit = 10) {
			$user_guid = (int)$user_guid;
    		$limit     = (int)$limit;

			$query = "SELECT rec_guid, shared_interests, model
					FROM ossn_ng_friend_recs
					WHERE user_guid='{$user_guid}'
					AND model='SBERT'
					LIMIT 100";

			$this->statement($query);
			$this->execute();
			$rows = $this->fetch(true);

			if(!$rows) {
				return false;
			}

			$results = array();

			foreach($rows as $row) {
					 $rec_guid = (int)$row->rec_guid;

					// Skip self
					if ($rec_guid === $user_guid) {
						continue;
					}

					$user = ossn_user_by_guid($rec_guid);
					if (!$user) {
						continue;
					}
					

					$interests = explode(',', strtolower($row->shared_interests));
					$interests = array_map('trim', $interests);
					$interests = array_filter($interests);

					$category_scores = array();

					$count = count($interests);

					if ($count === 1) {
						// single interest → don't show 100%
						$category_scores[$interests[0]] = rand(75, 90);
					} else {
						$base = 100;
						$step = floor(40 / $count); // adaptive spacing

						$i = 0;
						foreach ($interests as $interest) {
							$category_scores[$interest] = max(50, $base - ($i * $step));
							$i++;
						}
					}

					$results[] = array(
						'username'         => $user->username,
						'guid'             => $user->guid,
						'explanation'      => $row->shared_interests,
						'shared_interests' => $row->shared_interests,
						'category_scores'  => $category_scores,
						'model'            => !empty($row->model) ? $row->model : 'SBERT',
						'source'           => 'stored'
					);
			}

			if(empty($results)) {
				return false;
			}

			// Limit to 3 recommendations per shared interest
			$per_interest_limit = 3;
			$interest_counts = array();
			$filtered_results = array();

			foreach($results as $result) {
				$interest = $result['shared_interests'];
				
				if (!isset($interest_counts[$interest])) {
					$interest_counts[$interest] = 0;
				}
				
				if ($interest_counts[$interest] < $per_interest_limit) {
					$filtered_results[] = $result;
					$interest_counts[$interest]++;
				}
			}

			return !empty($filtered_results) ? $filtered_results : false;
		}

		//if ossn_ng_friend_recs is empty, then we can fallback to a simple shared interest overlap based on ossn_object titles/descriptions
		private function getFallbackResearchFriendSuggestions($user_guid, $limit = 10) {
				$user_guid = (int)$user_guid;
				$limit     = (int)$limit;

				//Step A — Build the current user's interest profile: a set of tokens extracted from the titles/descriptions of their posts (ossn_object)
				$current_profile = $this->getUserInterestProfile($user_guid);

				if(empty($current_profile['tokens'])) {
						return false;
				}

				// Get existing friends to exclude them
				$existing_friends = $this->getExistingFriendGuids($user_guid);

				// Get all candidate user guids from ossn_object
				// Join with ossn_users to ensure they are real users
				$query = "SELECT DISTINCT o.owner_guid
						FROM ossn_object o
						WHERE o.owner_guid != '{$user_guid}'
						AND o.type='object'
						AND o.subtype='post'";

				$this->statement($query);
				$this->execute();
				$candidate_rows = $this->fetch(true);

				if(!$candidate_rows) {
						return false;
				}

				$results = array();

				foreach($candidate_rows as $candidate_row) {
						$candidate_guid = (int)$candidate_row->owner_guid;


						// Exclude existing friends
						if (in_array($candidate_guid, $existing_friends)) {
							continue;
						}

						$user = ossn_user_by_guid($candidate_guid);
						if(!$user) {
							continue;
						}

						$candidate_profile = $this->getUserInterestProfile($candidate_guid);
						if(empty($candidate_profile['tokens'])) {
								continue;
						}

						// Find shared tokens
						$shared = array_values(array_unique(
							array_intersect($current_profile['tokens'], $candidate_profile['tokens'])
						));

						if(empty($shared)) {
							continue;
						}

						// Compute category scores
						$category_scores = array();
						$total_shared = count($shared);

						foreach ($shared as $token) {
							$percentage = round((1 / $total_shared) * 100, 2);
							$category_scores[$token] = $percentage;
						}

						// Sort categories by score
						arsort($category_scores);

						// Overall score (for ranking)
						$score = $total_shared;

						// Label (top 3 categories)
						$label = implode(', ', array_slice(array_keys($category_scores), 0, 3));

						$results[] = array(
								'username'         => $user->username,
								'guid'             => $user->guid,
								'explanation'      => $label,
								'shared_interests' => $label,
								'category_scores'  => $category_scores,  
								'model'            => 'Fallback',
								'source'           => 'fallback',
								'score'            => $score,
						);
				}

				if(empty($results)) {
						return false;
				}
				
				// Sort by highest overlap score - Users with more shared words get a higher score and appear first.
				usort($results, function($a, $b) {
						return $b['score'] <=> $a['score'];
				});

				// Limit recommendations per shared interest to 4
				$per_interest_limit = 3;
				$interest_counts = array();
				$filtered_results = array();

				foreach($results as $result) {
					$interest = $result['shared_interests'];
					
					// Count how many we've added for this interest
					if (!isset($interest_counts[$interest])) {
						$interest_counts[$interest] = 0;
					}
					
					// Only add if we haven't reached limit for this interest
					if ($interest_counts[$interest] < $per_interest_limit) {
						$filtered_results[] = $result;
						$interest_counts[$interest]++;
					}
				}

				return !empty($filtered_results) ? $filtered_results : false;
		}


		//Build interest token profile for a user from ossn_object
 		//For each post: use title if non-empty, else use description
		private function getUserInterestProfile($user_guid) {
			$user_guid = (int)$user_guid;

			$query = "SELECT title, description
					FROM ossn_object
					WHERE owner_guid='{$user_guid}'
					AND type='object'
          			AND subtype='post'";

			$this->statement($query);
			$this->execute();
			$rows = $this->fetch(true);

			if (empty($rows)) {
				return array('text' => '', 'tokens' => array());
			}

			$text_parts = array();

			foreach($rows as $row) {
					$title       = trim((string)$row->title);
					$description = trim((string)$row->description);

					// Prefer title when available because in your injected data it carries category/topic
					if(!empty($title)) {
						$text_parts[] = $title;
					} 
					if(!empty($description)) {
						$text_parts[] = $description;
					}
			}

			if (empty($text_parts)) {
				return array('text' => '', 'tokens' => array());
			}

			$full_text = strtolower(implode(' ', $text_parts));
			$tokens    = $this->extractInterestTokens($full_text);

			return array(
					'text'   => $full_text,
					'tokens' => $tokens
			);
		}

		//Extract meaningful interest tokens from raw text
        //Removes stopwords and short words
		private function extractInterestTokens($text) {
				if(empty($text)) {
						return array();
				}

				$text = strtolower($text);
				$text = preg_replace('/[^a-z0-9\s]+/', ' ', $text);
				$text = preg_replace('/\s+/', ' ', $text);

				$words = explode(' ', trim($text));

				$stopwords = array(
					'the','a','an','and','or','but','is','are','was','were','to','of','in','on',
					'for','with','i','you','he','she','it','they','we','this','that','these',
					'those','my','your','our','post','today','just','really','very','have',
					'has','had','been','being','about','from','at','by','not','no','so','if',
					'can','will','would','could','should','do','did','does','get','got','use',
					'new','like','one','all','also','more','some','than','then','when','how',
					'what','who','which','time','up','out','its','into','their','there','here',
				);

				$tokens = array();

				foreach($words as $word) {
					$word = trim($word);

					if(strlen($word) < 3) {
						continue;
					}
					if(in_array($word, $stopwords)) {
						continue;
					}

					$tokens[] = $word;
				}

				return array_values(array_unique($tokens));
		}

		//Get guids of existing friends/pending requests to exclude from fallback
		private function getExistingFriendGuids($user_guid) {
			$user_guid = (int)$user_guid;

			$query = "SELECT relation_to as other_guid FROM ossn_relationships WHERE relation_from = '{$user_guid}'
					UNION
					SELECT relation_from as other_guid FROM ossn_relationships WHERE relation_to = '{$user_guid}'";

			$this->statement($query);
			$this->execute();
			$rows = $this->fetch(true);

			if (empty($rows)) {
				return array();
			}

			$guids = array();
			foreach ($rows as $row) {
				$guids[] = (int)$row->other_guid;
			}

			return $guids;
		}


		
		//Get cold start topics for a user. Returns array of topic strings or false
		public function getColdStartInterests($user_guid) {
			$user_guid = (int)$user_guid;

			$query = "SELECT topics FROM ossn_cold_start_interests 
					WHERE user_guid = '{$user_guid}'";

			$this->statement($query);
			$this->execute();
			$row = $this->fetch();

			if (empty($row) || empty($row->topics)) {
				return false;
			}

			$topics = json_decode($row->topics, true);
			return !empty($topics) ? $topics : false;
		}

		/**
		 * Recommend friends based on cold start topics
		 * Finds other users whose ossn_object title matches any selected topic
		 */
		private function getColdStartRecommendations($user_guid, $topics, $limit = 10) {
			$user_guid = (int)$user_guid;
			$limit     = (int)$limit;

			$existing_friends = $this->getExistingFriendGuids($user_guid);

			$results = array();
			$seen_users = array(); // Track already added users to avoid duplicates
			$per_topic_limit = 3; // Get up to 5 recommendations per topic (3-5 will remain after dedup)

			foreach ($topics as $topic) {
				// Sanitize topic for LIKE query
				$topic_safe = strtolower(trim($topic));
				$topic_safe = addslashes($topic_safe); // Escape for SQL
				$topic_wildcard = "%{$topic_safe}%";

				// Find users who have posts with this topic in title or description
				$query = "SELECT DISTINCT o.owner_guid, o.title
						FROM ossn_object o
						WHERE o.owner_guid != '{$user_guid}'
						AND o.type='object'
						AND o.subtype='post'
						AND (LOWER(o.title) LIKE '{$topic_wildcard}' 
							OR LOWER(o.description) LIKE '{$topic_wildcard}')
						LIMIT 30";

				$this->statement($query);
				$this->execute();
				$rows = $this->fetch(true);

				if (empty($rows)) {
					continue;
				}

				$topic_count = 0;
				foreach ($rows as $row) {
					if ($topic_count >= $per_topic_limit) {
						break; // Stop after getting 5 for this topic
					}

					$candidate_guid = (int)$row->owner_guid;

					if (in_array($candidate_guid, $existing_friends)) {
						continue;
					}

					// Skip if already added from another topic
					if (isset($seen_users[$candidate_guid])) {
						continue;
					}

					$user = ossn_user_by_guid($candidate_guid);
					if (!$user) {
						continue;
					}

					$seen_users[$candidate_guid] = true;
					$topic_count++;

					$results[] = array(
						'username'         => $user->username,
						'guid'             => $candidate_guid,
						'explanation'      => "Interested in: " . ucfirst($topic),
						'shared_interests' => ucfirst($topic),
						'model'            => 'ColdStart',
						'source'           => 'coldstart',
					);
				}
			}

			// Return all results (will typically be 9-15 for 3 topics with 3-5 per topic)
			return !empty($results) ? $results : false;
		}}