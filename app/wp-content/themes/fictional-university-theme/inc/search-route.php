<?php

add_action('rest_api_init', 'universityRegisterSearch');

function universityRegisterSearch() {
	register_rest_route('university/v1', 'search', array(
		// equivalent to 'GET' on any browser
		'methods' => WP_REST_SERVER::READABLE,

		'callback' => 'universitySearchResults',
		'permission_callback' => '__return_true'
	));
}

function universitySearchResults($searchdata) {
	$mainQuery = new WP_Query(array(
		'post_type' => array('post', 'page', 'professor', 'program', 'campus', 'event'),
		// 's' is for searching. need to sanitize the input for any malicious injections
		's' => sanitize_text_field($searchdata['data'])
	));

	$results = array(
		'generalInfo' => array(),
		'professors' => array(),
		'programs' => array(),
		'events' => array(),
		'campuses' => array()
	);

	while ($mainQuery->have_posts()) {
		$mainQuery->the_post();
		if (get_post_type() == 'post' OR get_post_type() == 'page') {
			array_push($results['generalInfo'], array(
				'type' => get_post_type(),
				'authorName' => get_the_author(),
				'title' => get_the_title(),
				'permalink' => get_the_permalink()
			));
		} else if (get_post_type() == 'professor') {
			array_push($results['professors'], array(
				'title' => get_the_title(),
				'permalink' => get_the_permalink(),
				// 0 represents the CURRENT post here
				'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
			));
		} else if (get_post_type() == 'campus') {
			array_push($results['campuses'], array(
				'title' => get_the_title(),
				'permalink' => get_the_permalink()
			));
		} else if (get_post_type() == 'program') {
			$relatedCampuses = get_field('related_campus');
			if ($relatedCampuses) {
				foreach ($relatedCampuses as $campus) {
					array_push($results['campuses'], array(
						'title' => get_the_title($campus),
						'permalink' => get_the_permalink($campus)
					));
				}
			}
			array_push($results['programs'], array(
				'title' => get_the_title(),
				'permalink' => get_the_permalink(),
				'id' => get_the_id()
			));
		} else if (get_post_type() == 'event') {
			$eventDate = new DateTime(get_field('event_date'));
			if (has_excerpt()) { 
				$excerpt = get_the_excerpt();
			} else {
				$excerpt = wp_trim_words(get_the_content(), 18); 
			}
			array_push($results['events'], array(
				'title' => get_the_title(),
				'permalink' => get_the_permalink(),
				'month' => $eventDate->format('M'),
				'day' => $eventDate->format('d'),
				'excerpt' => $excerpt
			));
		}
		
	}

	if ($results['programs']) {
		$programsMetaQuery = array('relation' => 'OR');
		foreach ($results['programs'] as $related_program) {
			array_push($programsMetaQuery, array(
				'key' => 'related_programs',
				'compare' => 'LIKE',
				'value' => '"'.$related_program['id'].'"'
			));
		}
		$programRelationshipQuery = new WP_Query(array(
			'post_type' => array('professor', 'event'),
			'meta_query' => $programsMetaQuery
		));

		while ($programRelationshipQuery->have_posts()) {
			$programRelationshipQuery->the_post();
			if (get_post_type() == 'professor') {
				array_push($results['professors'], array(
					'title' => get_the_title(),
					'permalink' => get_the_permalink(),
					// 0 represents the CURRENT post here
					'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
				));
			} else if (get_post_type() == 'event') {
				$eventDate = new DateTime(get_field('event_date'));
				if (has_excerpt()) { 
					$excerpt = get_the_excerpt();
				} else {
					$excerpt = wp_trim_words(get_the_content(), 18); 
				}
				array_push($results['events'], array(
					'title' => get_the_title(),
					'permalink' => get_the_permalink(),
					'month' => $eventDate->format('M'),
					'day' => $eventDate->format('d'),
					'excerpt' => $excerpt
				));
			}
		}
	}
	// Remove any duplicates from the results array and make sure the array numbers are OK (array_values)
	$results['professors'] = array_values(array_unique($results['professors'], SORT_REGULAR));
	$results['events'] = array_values(array_unique($results['events'], SORT_REGULAR));

	return $results;
}