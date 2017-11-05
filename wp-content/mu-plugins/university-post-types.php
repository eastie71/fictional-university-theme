<?php
	function university_post_types() {
		// Custom Event Post Type
		register_post_type('event', array(
			'has_archive' => true,
			// by default slug would be 'event' so override to 'events' instead
			'rewrite' => array('slug' => 'events'),
			// by default post types get title and editor, but NOT excerpt
			'supports' => array('title', 'editor', 'excerpt'),
			'public' => true,
			'labels' => array(
				'name' => 'Events',
				'add_new_item' => 'Add New Event',
				'edit_item' => 'Edit Event',
				'all_items' => 'All Events',
				'singular_name' => 'Event'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-calendar'		));

		// Custom Program Post Type
		register_post_type('program', array(
			'has_archive' => true,
			// by default slug would be 'program' so override to 'programs' instead
			'rewrite' => array('slug' => 'programs'),
			// by default post types get title and editor, excerpt not required.
			'supports' => array('title', 'editor',),
			'public' => true,
			'labels' => array(
				'name' => 'Programs',
				'add_new_item' => 'Add New Program',
				'edit_item' => 'Edit Program',
				'all_items' => 'All Programs',
				'singular_name' => 'Program'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-awards'		));
	}
	add_action('init', "university_post_types");
?>