<?php

/*
Plugin Name: Kontakt
Plugin URI: https://github.com/lutrov/kontakt
Description: Kontakt is a simple contact form that allows you to capture a name, email, telephone, company and message. No fancy form builder, no advanced conditional logic, just the basics. Allows you to block spambots without using annoying captchas and optionally stores messages as private custom post types in the database. Why this plugin name? Kontakt means "contact" in Polish.
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Version: 6.2
*/

defined('ABSPATH') || die();

//
// These are the rules when validating the form token:
//
// 1. If the remote address is not supplied, reject the submission as spam.
// 2. If the remote address doesn't match the remote address supplied when the form
//    was loaded, reject the submission as spam.
// 3. If the token timestamp is less than 5 seconds from when the form
//    was loaded, reject the submission as spam.
// 4. If the token timestamp is more than 5 minutes from when the form
//    was loaded, reject the submission as spam.
// 5. If the user agent string is not supplied, reject the submission as spam.
// 6. If the user agent string doesn't match the user agent string supplied
//    when the form was loaded, reject the submission as spam.
//

//
// Define constants used by this plugin.
//
define('KONTAKT_STORE_MESSAGES', false);

//
// Register kontakt custom post type.
// https://codex.wordpress.org/Function_Reference/register_post_type
//
add_action('wp_loaded', 'kontakt_message_register_post_type_action', 10, 0);
function kontakt_message_register_post_type_action() {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		$args = array(
			'labels' => array(
				'name' => __('Contact Messages', 'kontakt'),
				'search_items' => __('Search Messages', 'kontakt'),
				'not_found' => __('No messages found', 'kontakt'),
				'menu_name' => __('Contact Messages', 'kontakt')
			),
			'hierarchical' => false,
			'description' => 'Messages, not blog posts.',
			'supports' => array(),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => 'tools.php',
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array(),
			'capability_type' => 'post',
			'show_in_rest' => false
		);
		register_post_type('kontakt', $args);
	}
}

//
// Add extra columns to kontakt messages listing screen.
// https://smashingmagazine.com/2017/12/customizing-admin-columns-wordpress/
//
add_filter('manage_kontakt_posts_columns', 'kontakt_kontakt_manage_columns_filter', 8, 1);
function kontakt_kontakt_manage_columns_filter($columns) {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		$columns = array(
			'cb' => '<input type="checkbox">',
			'from' => __('From', 'kontakt'),
			'message' => __('Message', 'kontakt'),
			'permalink' => __('Permalink', 'kontakt'),
			'form' => __('Form', 'kontakt'),
			'created' => __('Date', 'kontakt'),
		);
	}
	return $columns;
}

//
// Populate extra columns in messages listing screen.
// https://smashingmagazine.com/2017/12/customizing-admin-columns-wordpress/
//
add_action('manage_kontakt_posts_custom_column', 'kontakt_kontakt_manage_custom_column_action', 8, 2);
function kontakt_kontakt_manage_custom_column_action($column, $message_id) {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		$post = get_post($message_id);
		$data = kontakt_make_message_fields($post->post_content);
		switch ($column) {
			case 'from':
				$from = array();
				if (empty($data['NAME']) == false) {
					array_push($from, $data['NAME']);
				}
				if (empty($data['EMAIL']) == false) {
					array_push($from, $data['EMAIL']);
				}
				if (empty($data['TELEPHONE']) == false) {
					array_push($from, $data['TELEPHONE']);
				}
				if (empty($data['COMPANY']) == false) {
					array_push($from, $data['COMPANY']);
				}
				echo implode('<br>', $from);
				break;
			case 'message':
				$string = $data['MESSAGE'];
				$length = 160;
				if (strlen($string) > $length) {
					$string = substr($string, 0, $length);
					$length = max(strrpos($string, '.'), strrpos($string, '?'), strrpos($string, '!')) + 1;
					if ($length > 1) {
						$string = substr($string, 0, $length);
						if (strlen($string) < strlen($data['MESSAGE'])) {
							$string = sprintf('%s [...]', substr($string, 0, strlen($string) - 1));
						}
					}
				}
				echo sprintf('%s', empty($string) == false ? $string : '--');
				break;
			case 'permalink':
				if (empty($data['PERMALINK']) == false) {
					$post_id = url_to_postid($data['PERMALINK']);
					echo sprintf(
						'<a href="%s" target="_blank" title="%s">%s</a>',
						esc_attr($data['PERMALINK']),
						$post_id > 0 ? esc_attr(get_post_field('post_title', $post_id)) : '????',
						str_replace(home_url(), null, $data['PERMALINK'])
					);
				} else {
					echo '--';
				}
				break;
			case 'form':
				echo sprintf('%s', empty($data['FORM']) == false ? $data['FORM'] : '--');
				break;
			case 'created':
				echo sprintf(
					'%s<br>%s',
					__('Created', 'kontakt'),
					date('Y-m-d H:i', strtotime(get_post_field('post_date', $message_id)))
				);
				break;
		}
	}
}

//
// Change sortable columns in kontakt messages listing screen.
//
add_filter('manage_edit-kontakt_sortable_columns', 'kontakt_manage_sortable_column_filter', 10, 1);
function kontakt_manage_sortable_column_filter($columns) {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
	}
	return $columns;
}

//
// Hook the posts search if we're on the admin page for kontakt post types.
//
add_action('admin_init', 'kontakt_setup_search_filter_action', 10, 0);
function kontakt_setup_search_filter_action() {
	global $typenow;
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		if ($typenow === 'kontakt') {
			add_filter('posts_search', 'kontakt_search_filter', 10, 2);
		}
	}
}

//
// Change the standard query condition to allow searching on specific
// keywords. This enables you to filter by names, email addresses or forms
// by entering search queries using any one of these three formats:
//
// 1. "NAME Ivan Lutrov"
// 2. "EMAIL ivan@lutrov.com"
// 3. "FORM 1234"
//
// Note, the keywords "NAME", "EMAIL" and "FORM" must be entered in uppercase
// to have meaning otherwise the query strings will be treated as standard.
//
function kontakt_search_filter($search, $query) {
	global $wpdb;
	if ($query->is_main_query() == true && empty($query->query['s']) == false) {
		$s = preg_replace('#\s+#', ' ', $query->query['s']);
		switch (strtok($s, ' ')) {
			case 'NAME':
				$search = sprintf('AND (%s.post_content LIKE "%%NAME\r\n%s\r\n%%" OR %s.post_content LIKE "%%NAME\n%s\n%%")', $wpdb->posts, substr($s, 5), $wpdb->posts, substr($s, 5));
				break;
			case 'EMAIL':
				$search = sprintf('AND (%s.post_content LIKE "%%EMAIL\r\n%s\r\n%%" OR %s.post_content LIKE "%%EMAIL\n%s\n%%")', $wpdb->posts, substr($s, 6), $wpdb->posts, substr($s, 6));
				break;
			case 'FORM':
				$search = sprintf('AND (%s.post_content LIKE "%%FORM\r\n%s\r\n%%" OR %s.post_content LIKE "%%FORM\n%s\n%%")', $wpdb->posts, substr($s, 5), $wpdb->posts, substr($s, 5));
				break;
		}
	}
	return $search;
}

//
// Export button for kontakt messages screen.
//
add_action('manage_posts_extra_tablenav', 'kontakt_manage_posts_extra_tablenav_action', 20, 1);
function kontakt_manage_posts_extra_tablenav_action($which) {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		$screen = get_current_screen();
		if ($screen->post_type == 'kontakt' && $which == 'top') {
			echo sprintf('<div class="alignleft actions custom">');
			echo sprintf('<button type="submit" name="export" class="button" value="1">%s</button>', __('Export', 'kontakt'));
			echo sprintf('</div>');
		}
	}
}

//
// Export kontakt messages in CSV format.
// We can't use `get_current_screen()` here because we're changing output headers.
//
add_action('admin_init', 'kontakt_manage_posts_export_action', 10, 0);
function kontakt_manage_posts_export_action() {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		if (current_user_can('edit_posts') == true) {
			if (isset($_REQUEST['post_type']) == true && $_REQUEST['post_type'] == 'kontakt') {
				if (isset($_REQUEST['export']) == true && $_REQUEST['export'] == '1') {
					header(sprintf('Content-type: %s', 'text/csv'));
					header(sprintf('Content-Disposition: attachment; filename="kontakt-messages-%s.csv"', date('YmdHis')));
					$args = array(
						'post_type' => 'kontakt',
						'post_status' => 'private',
						'order' => 'ASC',
						'orderby' => 'ID',
						'posts_per_page' => -1
					);
					if (empty($_REQUEST['m']) == false) {
						$args = array_merge($args, array(
							'date_query' => array(array('m' => sanitize_text_field($_REQUEST['m'])))
						));
					}
					if (empty($_REQUEST['s']) == false) {
						$args = array_merge($args, array(
							's' => sanitize_text_field($_REQUEST['s'])
						));
					}
					$query = new wp_query($args);
					if (count($query->posts) > 0) {
						echo sprintf(
							"\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\r\n",
							__('NAME', 'kontakt'),
							__('EMAIL', 'kontakt'),
							__('TELEPHONE', 'kontakt'),
							__('COMPANY', 'kontakt'),
							__('MESSAGE', 'kontakt'),
							__('PERMALINK', 'kontakt'),
							__('FORM', 'kontakt'),
							__('DATE', 'kontakt'),
						);
						foreach ($query->posts as $post) {
							$data = str_replace(chr(34), chr(39), kontakt_make_message_fields($post->post_content));
							echo sprintf(
								"\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\r\n",
								$data['NAME'],
								$data['EMAIL'],
								$data['TELEPHONE'],
								$data['COMPANY'],
								$data['MESSAGE'],
								$data['PERMALINK'],
								$data['FORM'],
								date('Y-m-d H:i', strtotime($post->post_date))
							);
						}
						exit();
					}
				}
			}
		}
	}
}

//
// Hide the "add new" button, the quick edit & trash quicklinks on the
// kontakt messages listing screen as well as the "publish" button and the
// timestamp edit link on the kontakt message edit screen.
//
//
add_action('admin_head', 'kontakt_messages_admin_styles_action', 80, 0);
function kontakt_messages_admin_styles_action() {
?>
	<style type="text/css">
		.post-type-kontakt .wp-heading-inline + .page-title-action {
			display: none;
		}
		.post-type-kontakt .wp-list-table .row-actions {
			color: transparent;
		}
		.post-type-kontakt .wp-list-table .row-actions .inline,
		.post-type-kontakt .wp-list-table .row-actions .trash {
			display: none;
		}
		.post-php.post-type-kontakt a.edit-timestamp,
		.post-php.post-type-kontakt #major-publishing-actions {
			display: none;
		}
	</style>
<?php
}

//
// Contextual help screens.
//
add_action('admin_head', 'kontakt_contextual_help_action', 100, 0);
function kontakt_contextual_help_action() {
	$screen = get_current_screen();
	if ($screen->post_type == 'kontakt') {
		$screen->add_help_tab(array(
			'id' => 'overview',
			'title' => __('Overview'),
			'callback' => 'kontakt_contextual_help_callback'
		));
		$screen->add_help_tab(array(
			'id' => 'tips',
			'title' => __('Tips'),
			'callback' => 'kontakt_contextual_help_callback'
		));
	}
}

//
// Contextual help callback based on post type.
//
function kontakt_contextual_help_callback($screen, $tab) {
	switch ($tab['id']) {
		case 'overview':
			echo sprintf('<p>%s</p>', __('Contact messages are automatically generated when a contact form is submitted and should never be created, edited or deleted here.'));
			break;
		case 'tips':
			echo sprintf('<p>%s</p>', __('When searching contact messages, you can use specific keywords to restrict output based on the <em>name</em>, <em>email address</em> or <em>form</em>, like this example:'));
			echo sprintf('<ul>');
			echo sprintf('<li><kbd>NAME Jane Doe</kbd><br>%s</li>', __('Only show messages where the from name is <em>Jane Doe</em>'));
			echo sprintf('<li><kbd>EMAIL janedoe@example.com</kbd><br>%s</li>', __('Only show messages where the from email is <em>janedoe@example.com</em>'));
			echo sprintf('<li><kbd>FORM 201204</kbd><br>%s</li>', __('Only show messages where the form is <em>201204</em>'));
			echo sprintf('</ul>');
			echo sprintf('<p>%s</p>', __('Note, the keywords <kbd>NAME</kbd>, <kbd>EMAIL</kbd> and <kbd>FORM</kbd> must be entered in uppercase to have meaning otherwise the search query will be treated as normal.'));
			break;
	}
}

//
// Register additional custom dashboard metaboxes.
//
add_action('wp_dashboard_setup', 'kontakt_messages_add_dashboard_metaboxes_action', 20, 0);
function kontakt_messages_add_dashboard_metaboxes_action() {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		add_meta_box(
			'kontakt-recent-messages-metabox',
			__('Recent Contact Messages', 'kontakt'),
			'kontakt_recent_messages_dashboard_widget_callback',
			'dashboard',
			'normal', // normal, side, advanced
			'high' // default, high, low
		);
	}
}

//
// Dashboard recent messages widget callback function.
// http://wpengineer.com/2382/wordpress-constants-overview/
// TODO: Have to use the `wp_query` object since `get_posts()` doesn't work here.
//
function kontakt_recent_messages_dashboard_widget_callback($post, $args) {
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		$html = null;
		$args = array(
			'post_type' => 'kontakt',
			'post_status' => 'private',
			'order' => 'DESC',
			'orderby' => 'ID',
			'posts_per_page' => 10
		);
		$query = new wp_query($args);
		foreach ($query->posts as $post) {
			$data = kontakt_make_message_fields($post->post_content);
			$html = sprintf(
				'%s<dt><b>%s</b></dt><dd>%s &lt;%s&gt;</dd>',
				$html,
				date('Y-m-d H:i', strtotime($post->post_date)),
				$data['NAME'],
				$data['EMAIL']
			);
		}
		if (empty($html) == false) {
			echo sprintf('<dl>%s</dl><p><a href="edit.php?post_type=kontakt">%s</a></p>', $html, __('Show all', 'kontakt'));
		} else {
			echo sprintf('<p>%s</p>', __('There are no messages at this time.', 'kontakt'));
		}
	}
}

//
// Set classic editor to text mode on edit kontact message screen.
// https://developer.wordpress.org/reference/functions/wp_default_editor/
//
add_filter('wp_default_editor', 'kontakt_default_editor_filter', 10, 1);
function kontakt_default_editor_filter($editor) {
	global $typenow;
	if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
		if ($typenow == 'kontakt') {
			$editor = 'html';
		}
	}
	return $editor;
}

//
// Convert message text stream into a usable array.
//
function kontakt_make_message_fields($text) {
	$data = array(
		'NAME' => null,
		'EMAIL' => null,
		'TELEPHONE' => null,
		'COMPANY' => null,
		'MESSAGE' => null,
		'PERMALINK' => null,
		'FORM' => null
	);
	$text = explode("\n", $text);
	for ($i = 0; $i < count($text); $i++) {
		$key = trim($text[$i]);
		if (array_key_exists($key, $data) == true) {
			$data[$key] = trim($text[$i + 1]);
		}
	}
	return $data;
}

//
// Shortcode with name, email, message other fields.
// [kontakt form="1234" fields="name|email|telephone|company|message|agreement" required="name|email|telephone|company|message|agreement" subject="Contact form" cc="hello@example.com" bcc="bye@example.com" agreement="privacy-policy" redirect="/stage/test/thank-you" anchor="content"]
//
add_shortcode('kontakt', 'kontakt_shortcode');
add_shortcode('contact', 'kontakt_shortcode');
function kontakt_shortcode($atts) {
	global $post;
	static $sent = false;
	$html = null;
	extract(shortcode_atts(
		array(
			'form' => null,
			'fields' => 'name|email|telephone|company|message|agreement',
			'required' => 'name|email|telephone|company|message|agreement',
			'subject' => null,
			'to' => null,
			'cc' => null,
			'bcc' => null,
			'agreement' => null,
			'quiz' => null,
			'redirect' => null,
			'anchor' => null
		),
		$atts
	));
	if (empty($form) == true) {
		$form = hash('adler32', sprintf('%s%s%s%s%s', __FUNCTION__, $fields, $required, $subject, $to));
	}
	$id = sanitize_title($form);
	$fields = explode('|', sanitize_text_field($fields));
	$required = explode('|', sanitize_text_field($required));
	$subject = sanitize_text_field($subject);
	$to = sanitize_text_field($to);
	$cc = sanitize_text_field($cc);
	$bcc = sanitize_text_field($bcc);
	$agreement = sanitize_text_field($agreement);
	$quiz = sanitize_text_field($quiz);
	if (empty($quiz) == false) {
		array_push($fields, 'quiz');
		array_push($required, 'quiz');
	}
	$redirect = sanitize_text_field($redirect);
	$anchor = sanitize_text_field($anchor);
	$form = array(
		'markup' => array(),
		'data' => array('name' => null, 'email' => null, 'telephone' => null, 'company' => null, 'message' => null, 'quiz' => null, 'agreement' => null),
		'errors' => array()
	);
	array_push(
		$form['markup'],
		sprintf('<div class="wp-block-kontakt-form">')
	);
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 		$_POST = array_map('stripslashes_deep', $_POST);
		if (empty($_POST[sprintf('submit-%s', $id)]) == false) {
			if (in_array('name', $fields) == true) {
				$form['data']['name'] = sanitize_text_field($_POST[sprintf('name-%s', $id)]);
				if (in_array('name', $required) == true) {
					if (empty($form['data']['name']) == true) {
						$form['errors']['name'] = apply_filters(
							'kontakt_shortcode_name_empty',
							__('Name is required.', 'kontakt'),
							$id
						);
					} elseif (preg_match("#^[A-Za-z .'-]+$#", $form['data']['name']) == 0) {
						$form['errors']['name'] = apply_filters(
							'kontakt_shortcode_name_invalid',
							__('Invalid name.', 'kontakt'),
							$id
						);
					}
				} else {
					if (empty($form['data']['name']) == false) {
						if (preg_match("#^[A-Za-z .'-]+$#", $form['data']['name']) == 0) {
							$form['errors']['name'] = apply_filters(
								'kontakt_shortcode_name_invalid',
								__('Invalid name.', 'kontakt'),
								$id
							);
						}
					}
				}
			}
			if (in_array('email', $fields) == true) {
				$form['data']['email'] = sanitize_text_field($_POST[sprintf('email-%s', $id)]);
				if (in_array('email', $required) == true) {
					if (empty($form['data']['email']) == true) {
						$form['errors']['email'] = apply_filters(
							'kontakt_shortcode_email_empty',
							__('Email is required.', 'kontakt'),
							$id
						);
					} elseif (preg_match("#^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$#", $form['data']['email']) == 0) {
						$form['errors']['email'] = apply_filters(
							'kontakt_shortcode_email_invalid',
							__('Invalid email.', 'kontakt'),
							$id
						);
					}
				} else {
					if (empty($form['data']['email']) == false) {
						if (preg_match("#^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$#", $form['data']['email']) == 0) {
							$form['errors']['email'] = apply_filters(
								'kontakt_shortcode_email_invalid',
								__('Invalid email.', 'kontakt'),
								$id
							);
						}
					}
				}
			}
			if (in_array('telephone', $fields) == true) {
				$form['data']['telephone'] = sanitize_text_field($_POST[sprintf('telephone-%s', $id)]);
				if (in_array('telephone', $required) == true) {
					if (empty($form['data']['telephone']) == true) {
						$form['errors']['telephone'] = apply_filters(
							'kontakt_shortcode_telephone_empty',
							__('Telephone is required.', 'kontakt'),
							$id
						);
					} elseif (preg_match("#^[0-9 ()-]+$#", $form['data']['telephone']) == 0) {
						$form['errors']['telephone'] = apply_filters(
							'kontakt_shortcode_telephone_invalid',
							__('Invalid telephone.', 'kontakt'),
							$id
						);
					}
				} else {
					if (empty($form['data']['telephone']) == false) {
						if (preg_match("#^[0-9 ()-]+$#", $form['data']['telephone']) == 0) {
							$form['errors']['telephone'] = apply_filters(
								'kontakt_shortcode_telephone_invalid',
								__('Invalid telephone.', 'kontakt'),
								$id
							);
						}
					}
				}
			}
			if (in_array('company', $fields) == true) {
				$form['data']['company'] = sanitize_text_field($_POST[sprintf('company-%s', $id)]);
				if (in_array('company', $required) == true) {
					if (empty($form['data']['company']) == true) {
						$form['errors']['company'] = apply_filters(
							'kontakt_shortcode_company_empty',
							__('Company is required.', 'kontakt'),
							$id
						);
					}
				}
			}
			if (in_array('message', $fields) == true) {
				$form['data']['message'] = sanitize_textarea_field($_POST[sprintf('message-%s', $id)]);
				if (in_array('message', $required) == true) {
					if (empty($form['data']['message']) == true) {
						$form['errors']['message'] = apply_filters(
							'kontakt_shortcode_message_empty',
							__('Message is required.', 'kontakt'),
							$id
						);
					}
				}
				if (empty($form['data']['message']) == false) {
					$form['data']['message'] = trim(preg_replace('#\s+#', ' ', $form['data']['message']));
				}
			}
			if (in_array('quiz', $fields) == true) {
				$form['data']['quiz'] = sanitize_text_field($_POST[sprintf('quiz-%s', $id)]);
				$question = sprintf('%s?', trim(strtok($quiz, '?')));
				$answer = trim(strtok('?'));
				if (in_array('quiz', $required) == true) {
					if (empty($form['data']['quiz']) == true) {
						$form['errors']['quiz'] = apply_filters(
							'kontakt_shortcode_quiz_empty',
							__('Quiz answer is required.', 'kontakt'),
							$id
						);
					} elseif (strtolower($form['data']['quiz']) <> strtolower($answer)) {
						$form['errors']['quiz'] = apply_filters(
							'kontakt_shortcode_quiz_invalid',
							__('Invalid quiz answer.', 'kontakt'),
							$id
						);
					}
				} else {
					if (empty($form['data']['quiz']) == false) {
						if (strtolower($form['data']['quiz']) <> strtolower($answer)) {
							$form['errors']['quiz'] = apply_filters(
								'kontakt_shortcode_quiz_invalid',
								__('Invalid quiz answer.', 'kontakt'),
								$id
							);
						}
					}
				}
			}
			if (in_array('agreement', $fields) == true) {
				$form['data']['agreement'] = isset($_POST[sprintf('agreement-%s', $id)]) == true ? absint($_POST[sprintf('agreement-%s', $id)]) : null;
				if (in_array('agreement', $required) == true) {
					if (empty($form['data']['agreement']) == true) {
						$form['errors']['agreement'] = apply_filters(
							'kontakt_shortcode_agreement_empty',
							__('Agreement is required.', 'kontakt'),
							$id
						);
					}
				}
			}
			// Validate spam token
			$spam = true;
			if (isset($_POST[sprintf('token-%s', $id)]) == true) {
				$ip = strtok($_POST[sprintf('token-%s', $id)], '-');
				// By using `rand()` for empty remote addresses, we ensure they must be supplied
				if (hash_hmac('ripemd128', empty($_SERVER['REMOTE_ADDR']) == false ? $_SERVER['REMOTE_ADDR'] : rand(), NONCE_SALT) == $ip) {
					// Divide timestamp by 4 to get the actual value
					$ts = ((int) strtok('-')) / 4;
					// Must be more than 5 seconds since form loading time
					if (($ts + 5) < time()) {
						// Must be less than 5 minutes since form loading time
						if (($ts + 300) > time()) {
							$ua = strtok('-');
							// By using `rand()` for empty user agent strings, we ensure they must be supplied
							if (hash_hmac('ripemd128', empty($_SERVER['HTTP_USER_AGENT']) == false ? $_SERVER['HTTP_USER_AGENT'] : rand(), NONCE_SALT) == $ua) {
								$spam = false;
							}
						}
					}
				}
			}
			if ($spam == true) {
				$form['errors'] = array('spam' => true);
			}
			if ($sent == false) {
				if (empty($form['errors']) == true) {
					if (empty($to) == true) {
						$to = get_option('admin_email');
					}
					$body = null;
					if (in_array('name', $fields) == true) {
						$body = sprintf(
							"%s%s\n%s\n\n",
							$body,
							__('NAME', 'kontakt'),
							empty($form['data']['name']) == false ? $form['data']['name'] : '--'
						);
					}
					if (in_array('email', $fields) == true) {
						$body = sprintf(
							"%s%s\n%s\n\n",
							$body,
							__('EMAIL', 'kontakt'),
							empty($form['data']['email']) == false ? $form['data']['email'] : '--'
						);
					}
					if (in_array('telephone', $fields) == true) {
						$body = sprintf(
							"%s%s\n%s\n\n",
							$body,
							__('TELEPHONE', 'kontakt'),
							empty($form['data']['telephone']) == false ? $form['data']['telephone'] : '--'
						);
					}
					if (in_array('company', $fields) == true) {
						$body = sprintf(
							"%s%s\n%s\n\n",
							$body,
							__('COMPANY', 'kontakt'),
							empty($form['data']['company']) == false ? $form['data']['company'] : '--'
						);
					}
					if (in_array('message', $fields) == true) {
						$body = sprintf(
							"%s%s\n%s\n\n",
							$body,
							__('MESSAGE', 'kontakt'),
							empty($form['data']['message']) == false ? $form['data']['message'] : '--'
							);
					}
					$body = sprintf(
						"%s%s\n%s\n\n",
						$body,
						__('PERMALINK', 'kontakt'),
						get_permalink()
					);
					$body = sprintf(
						"%s%s\n%s\n\n",
						$body,
						__('FORM', 'kontakt'),
						$id
					);
					$headers = array();
					if (empty($cc) == false) {
						array_push($headers, sprintf('Cc: %s', $cc));
					}
					if (empty($bcc) == false) {
						array_push($headers, sprintf('Bcc: %s', $bcc));
					}
					if (empty($form['data']['email']) == false) {
						if (empty($form['data']['name']) == false) {
							array_push($headers, sprintf('Reply-To: %s <%s>', $form['data']['name'], $form['data']['email']));
						} else {
							array_push($headers, sprintf('Reply-To: %s', $form['data']['email']));
						}
					}
					$sent = wp_mail(
						$to,
						empty($subject) == false ? $subject : sprintf('%s %s', __('Contact form', 'kontakt'), $id),
						$body,
						$headers
					);
					if ($sent == true) {
						array_push(
							$form['markup'],
							sprintf(
								'<p class="message">%s</p>',
								apply_filters(
									'kontakt_shortcode_message_success',
									__('Your message has been sent.', 'kontakt'),
									$id
								)
							)
						);
						if (apply_filters('kontakt_store_messages', KONTAKT_STORE_MESSAGES) == true) {
							$message_id = wp_insert_post(array(
								'post_author' => 0,
								'post_content' => $body,
								'post_title' => sprintf('M%s', date('YmdHis')),
								'post_status' => 'private',
								'post_type' => 'kontakt',
								'comment_status' => 'closed',
								'ping_status' => 'closed',
							));
						}
						if (empty($redirect) == false) {
							$page = get_page_by_path($redirect);
							if (empty($page) == false) {
								$location = get_permalink($page);
							} else {
								$location = get_permalink();
							}
							wp_safe_redirect($redirect);
							exit();
						}
					} else {
						array_push(
							$form['markup'],
							sprintf(
								'<p class="message error">%s</p>',
								apply_filters(
									'kontakt_shortcode_message_tech_error',
									__('There was an error while sending your message, please try again later.', 'kontakt'),
									$id
								)
							)
						);
					}			
				} else {
					if (array_key_exists('spam', $form['errors']) == true) {
						$html = sprintf(
							'<p class="message error">%s</p>',
							apply_filters(
								'kontakt_shortcode_message_spam_error',
								__('There was an error while sending your message, please try again later.', 'kontakt'),
								$id
							)
						);
					} else {
						$html = sprintf(
							'<p class="message error">%s</p>',
							apply_filters(
								'kontakt_shortcode_message_submit_error',
								__('One or more fields have an error, please check and try again.', 'kontakt'),
								$id
							)
						);
					}
					array_push($form['markup'], $html);
				}
			}
		}
	}
	if ($sent == false) {
		array_push(
			$form['markup'],
			sprintf(
				'<form name="form-%s" id="form-%s" method="post" action="%s%s">',
				$id,
				$id,
				$_SERVER['REQUEST_URI'],
				empty($anchor) == false ? sprintf('#%s', $anchor) : null
			)
		);
		array_push(
			$form['markup'],
			sprintf('<p class="message">%s <span class="required">*</span> %s</p>',
				__('Fields marked with', 'kontakt'),
				__('are required.', 'kontakt')
			)
		);
		if (in_array('name', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__name">'
			);
			if (in_array('name', $required) == true) {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="name-%s">%s <span class="required">*</span></label>',
						$id,
						apply_filters('kontakt_shortcode_name_label', __('Name', 'kontakt'), $id)
					)
				);
			} else {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="name-%s">%s</label>',
						$id,
						apply_filters('kontakt_shortcode_name_label', __('Name', 'kontakt'), $id)
					)
				);
			}
			array_push(
				$form['markup'],
				sprintf(
					'<input type="text" name="name-%s" id="name-%s" class="%s" value="%s">',
					$id,
					$id,
					isset($form['errors']['name']) == true ? 'error' : null,
					$form['data']['name']
				)
			);
			array_push(
				$form['markup'],
				sprintf(
					'<p class="error">%s</p>',
					isset($form['errors']['name']) == true ? $form['errors']['name'] : null
				)
			);
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		if (in_array('email', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__email">'
			);
			if (in_array('email', $required) == true) {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="email-%s">%s <span class="required">*</span></label>',
						$id,
						apply_filters('kontakt_shortcode_email_label', __('Email', 'kontakt'), $id)
					)
				);
			} else {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="email-%s">%s</label>',
						$id,
						apply_filters('kontakt_shortcode_email_label', __('Email', 'kontakt'), $id)
					)
				);
			}
			array_push(
				$form['markup'],
				sprintf(
					'<input type="email" name="email-%s" id="email-%s" class="%s" value="%s">',
					$id,
					$id,
					isset($form['errors']['email']) == true ? 'error' : null,
					$form['data']['email']
				)
			);
			array_push(
				$form['markup'],
				sprintf(
					'<p class="error">%s</p>',
					isset($form['errors']['email']) == true ? $form['errors']['email'] : null
				)
			);
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		if (in_array('telephone', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__telephone">'
			);
			if (in_array('telephone', $required) == true) {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="telephone-%s">%s <span class="required">*</span></label>',
						$id,
						apply_filters('kontakt_shortcode_telephone_label', __('Telephone', 'kontakt'), $id)
					)
				);
			} else {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="telephone-%s">%s</label>',
						$id,
						apply_filters('kontakt_shortcode_telephone_label', __('Telephone', 'kontakt'), $id)
					)
				);
			}
			array_push(
				$form['markup'],
				sprintf(
					'<input type="tel" name="telephone-%s" id="telephone-%s" class="%s" value="%s">',
					$id,
					$id,
					isset($form['errors']['telephone']) == true ? 'error' : null,
					$form['data']['telephone']
				)
			);
			array_push(
				$form['markup'],
				sprintf(
					'<p class="error">%s</p>',
					isset($form['errors']['telephone']) == true ? $form['errors']['telephone'] : null
				)
			);
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		if (in_array('company', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__company">'
			);
			if (in_array('company', $required) == true) {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="company-%s">%s <span class="required">*</span></label>',
						$id,
						apply_filters('kontakt_shortcode_company_label', __('Company', 'kontakt'), $id)
					)
				);
			} else {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="company-%s">%s</label>',
						$id,
						apply_filters('kontakt_shortcode_company_label', __('Company', 'kontakt'), $id)
					)
				);
			}
			array_push(
				$form['markup'],
				sprintf(
					'<input type="text" name="company-%s" id="company-%s" class="%s" value="%s">',
					$id,
					$id,
					isset($form['errors']['company']) == true ? 'error' : null,
					$form['data']['company']
				)
			);
			array_push(
				$form['markup'],
				sprintf(
					'<p class="error">%s</p>',
					isset($form['errors']['company']) == true ? $form['errors']['company'] : null
				)
			);
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		if (in_array('message', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__message">'
			);
			if (in_array('message', $required) == true) {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="message-%s">%s <span class="required">*</span></label>',
						$id,
						apply_filters('kontakt_shortcode_message_label', __('Message', 'kontakt'), $id)
					)
				);
			} else {
				array_push(
					$form['markup'],
					sprintf(
						'<label for="message-%s">%s</label>',
						$id,
						apply_filters('kontakt_shortcode_message_label', __('Message', 'kontakt'), $id)
					)
				);
			}
			array_push(
				$form['markup'],
				sprintf(
					'<textarea name="message-%s" id="message-%s" class="%s" rows="4">%s</textarea>',
					$id,
					$id,
					isset($form['errors']['message']) == true ? 'error' : null,
					$form['data']['message']
				)
			);
			array_push(
				$form['markup'],
				sprintf(
					'<p class="error">%s</p>',
					isset($form['errors']['message']) == true ? $form['errors']['message'] : null
				)
			);
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		if (in_array('quiz', $fields) == true) {
			if (empty($quiz) == false) {
				$question = sprintf('%s?', trim(strtok($quiz, '?')));
				array_push(
					$form['markup'],
					'<div class="wp-block-kontakt-form__quiz">'
				);
				if (in_array('quiz', $required) == true) {
					array_push(
						$form['markup'],
						sprintf(
							'<label for="quiz-%s">%s <span class="required">*</span></label>',
							$id,
							$question
						)
					);
				} else {
					array_push(
						$form['markup'],
						sprintf(
							'<label for="quiz-%s">%s</label>',
							$id,
							$question
						)
					);
				}
				array_push(
					$form['markup'],
					sprintf(
						'<input type="text" name="quiz-%s" id="quiz-%s" class="%s" value="%s">',
						$id,
						$id,
						isset($form['errors']['quiz']) == true ? 'error' : null,
						$form['data']['quiz']
					)
				);
				array_push(
					$form['markup'],
					sprintf(
						'<p class="error">%s</p>',
						isset($form['errors']['quiz']) == true ? $form['errors']['quiz'] : null
					)
				);
				array_push(
					$form['markup'], sprintf('</div>')
				);
			}
		}
		if (in_array('agreement', $fields) == true) {
			array_push(
				$form['markup'],
				'<div class="wp-block-kontakt-form__agreement">'
			);
			if (empty($agreement) == false) {
				$page = get_page_by_path($agreement);
				if (empty($page) == false) {
					$agreement = sprintf(
						__('I agree to the terms set out in the %s page', 'kontakt'),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							get_permalink($page),
							strtolower($page->post_title)
						)
					);
					if (in_array('agreement', $required) == true) {
						array_push(
							$form['markup'],
							sprintf(
								'<label for="agreement-%s"><input type="checkbox" name="agreement-%s" id="agreement-%s" class="%s" value="1"%s> %s <span class="required">*</span></label>',
								$id,
								$id,
								$id,
								isset($form['errors']['agreement']) == true ? 'error' : null,
								$form['data']['agreement'] == 1 ? ' checked' : null,
								apply_filters('kontakt_shortcode_agreement_label', $agreement, $id)
							)
						);
					} else {
						array_push(
							$form['markup'],
							sprintf(
								'<label for="agreement-%s"><input type="checkbox" name="agreement-%s" id="agreement-%s" class="%s" value="1"%s> %s</label>',
								$id,
								$id,
								$id,
								isset($form['errors']['agreement']) == true ? 'error' : null,
								$form['data']['agreement'] == 1 ? ' checked' : null,
								apply_filters('kontakt_shortcode_agreement_label', $agreement, $id)
							)
						);
					}
					array_push(
						$form['markup'],
						sprintf(
							'<p class="error">%s</p>',
							isset($form['errors']['agreement']) == true ? $form['errors']['agreement'] : null
						)
					);
				}
			}
			array_push(
				$form['markup'], sprintf('</div>')
			);
		}
		array_push(
			$form['markup'],
			sprintf(
				'<input type="hidden" name="token-%s" value="%s-%s-%s">',
				$id,
				hash_hmac('ripemd128', empty($_SERVER['REMOTE_ADDR']) == false ? $_SERVER['REMOTE_ADDR'] : rand(), NONCE_SALT),
				time() * 4,
				hash_hmac('ripemd128', empty($_SERVER['HTTP_USER_AGENT']) == false ? $_SERVER['HTTP_USER_AGENT'] : rand(), NONCE_SALT)
			)
		);
		array_push(
			$form['markup'],
			sprintf('<div class="wp-block-kontakt-form__submit">')
		);
		array_push(
			$form['markup'],
			sprintf(
				'<input type="submit" name="submit-%s" class="wp-element-button" value="%s">',
				$id,
				apply_filters('kontakt_shortcode_submit_label', __('Submit', 'kontakt'), $id)
			)
		);
		array_push(
			$form['markup'], sprintf('</div>')
		);
		array_push(
			$form['markup'], sprintf('</form>')
		);
	} else {
		array_push(
			$form['markup'], sprintf('<dl>')
		);
		if (in_array('name', $fields) == true) {
			array_push(
				$form['markup'],
				sprintf('<dt>%s</dt>', apply_filters('kontakt_shortcode_name_label', __('Name', 'kontakt'), $id))
			);
			array_push(
				$form['markup'],
				sprintf('<dd>%s</dd>', empty($form['data']['name']) == false ? $form['data']['name'] : '--')
			);
		}
		if (in_array('email', $fields) == true) {
			array_push(
				$form['markup'],
				sprintf('<dt>%s</dt>', apply_filters('kontakt_shortcode_email_label', __('Email', 'kontakt'), $id))
			);
			array_push(
				$form['markup'],
				sprintf('<dd>%s</dd>', empty($form['data']['email']) == false ? $form['data']['email'] : '--')
			);
		}
		if (in_array('telephone', $fields) == true) {
			array_push(
				$form['markup'],
				sprintf('<dt>%s</dt>', apply_filters('kontakt_shortcode_telephone_label', __('Telephone', 'kontakt'), $id))
			);
			array_push(
				$form['markup'],
				sprintf('<dd>%s</dd>', empty($form['data']['telephone']) == false ? $form['data']['telephone'] : '--')
			);
		}
		if (in_array('company', $fields) == true) {
			array_push(
				$form['markup'],
				sprintf('<dt>%s</dt>', apply_filters('kontakt_shortcode_company_label', __('Company', 'kontakt'), $id))
			);
			array_push(
				$form['markup'],
				sprintf('<dd>%s</dd>', empty($form['data']['company']) == false ? $form['data']['company'] : '--')
			);
		}
		if (in_array('message', $fields) == true) {
			array_push(
				$form['markup'],
				sprintf('<dt>%s</dt>', apply_filters('kontakt_shortcode_message_label', __('Message', 'kontakt'), $id))
			);
			array_push(
				$form['markup'],
				sprintf('<dd>%s</dd>', empty($form['data']['message']) == false ? $form['data']['message'] : '--')
			);
		}
		array_push(
			$form['markup'], sprintf('</dl>')
		);
	}
	array_push(
		$form['markup'], sprintf('</div>')
	);
	if (empty($form['markup']) == false) {
		$html = implode("\n", $form['markup']);
	}
	return $html;
}

//
// Load the plugin language files.
//
add_action('init', 'kontakt_load_textdomain_action', 10, 0);
function kontakt_load_textdomain_action() {
	$domain = 'kontakt';
	load_textdomain(
		$domain,
		sprintf(
			'%s/%s/%s-%s.mo',
			WP_LANG_DIR,
			$domain,
			$domain,
			apply_filters('plugin_locale', get_locale(), $domain)
		)
	);
	load_plugin_textdomain(
		$domain,
		false,
		sprintf('%s/lang/', dirname(plugin_basename(__FILE__)))
	);
}

?>
