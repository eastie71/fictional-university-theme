<?php

add_action('rest_api_init', 'universityRegisterLike');

function universityRegisterLike() {
	register_rest_route('university/v1', 'manageLike', array(
		'methods' => 'POST',
		'callback' => 'createLike',
		'permission_callback' => '__return_true'
	));

	register_rest_route('university/v1', 'manageLike', array(
		'methods' => "DELETE",
		'callback' => 'removeLike',
		'permission_callback' => '__return_true'
	));
}

function createLike($data) {
	if (is_user_logged_in()) {
		$prof_id = sanitize_text_field($data['professor_id']);

		$currentUserLikes = new WP_Query(array(
			'author' => get_current_user_id(),
			'post_type' => 'like',
			'meta_query' => array(
				array(
					'key' => 'liked_professor_id',
					'compare' => '=',
					'value' => $prof_id
				)
			)
		));
		// Only add a like if the current user has not liked the professor already AND
		// make sure the ID of the professor actually exists
		if ($currentUserLikes->found_posts == 0 && get_post_type($prof_id) == 'professor') {
			// create new like post
			$result['success'] = true;
			$result['id'] = wp_insert_post(array(
				'post_type' => 'like',
				'post_status' => 'publish',
				'post_title' => "Professor ID: ".$prof_id,
				'meta_input' => array(
					'liked_professor_id' => $prof_id
				)
			));
			return $result;
		} else {
			die("Invalid Professor ID");
		}
		

	} else {
		die("Only logged in users can create a like");
	}
	
}

function removeLike($data) {
	$likeId = sanitize_text_field($data['like']);
	if (get_current_user_id() == get_post_field('post_author', $likeId) && get_post_type($likeId) == 'like') {
		wp_delete_post($likeId, true);
		$result['success'] = true;
		return $result;
	} else {
		die("You do not have permission to remove this like");
	}
}