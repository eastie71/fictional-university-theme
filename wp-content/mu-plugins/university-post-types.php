<?php
	function university_post_types() {
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
	}
	add_action('init', "university_post_types");
?>