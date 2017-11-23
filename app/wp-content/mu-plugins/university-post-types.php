<?php
	function university_post_types() {
		// Custom Event Post Type
		register_post_type('event', array(
			// default for capability type is "post" - which means an editor will have access to this type
			// unless you set a specific capability type (such as'event' here)
			'capability_type' => 'event',
			// without setting the map-meta-cap to true we would need to create our own custom logic for when the new capability is required. 
			'map_meta_cap' => true,
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
			// by default post types get title and editor - for program we do NOT want editor as we are
			// using a custom field for the main body content
			'supports' => array('title'),
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

		// Custom Professor Post Type
		register_post_type('professor', array(
			// by default post types get title and editor - needed to add thumbnail for Professor image
			'supports' => array('title', 'editor', 'thumbnail'),
			'public' => true,
			'labels' => array(
				'name' => 'Professors',
				'add_new_item' => 'Add New Professor',
				'edit_item' => 'Edit Professor',
				'all_items' => 'All Professors',
				'singular_name' => 'Professor'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-welcome-learn-more'		));

		// Custom Campus Post Type
		register_post_type('campus', array(
			'capability_type' => 'campus',
			'map_meta_cap' => true,
			// by default post types get title and editor - add excerpt as well
			'supports' => array('title', 'editor', 'excerpt'),
			// by default slug would be 'program' so override to 'programs' instead
			'rewrite' => array('slug' => 'campuses'),
			'public' => true,
			'has_archive' => true,
			'labels' => array(
				'name' => 'Campuses',
				'add_new_item' => 'Add New Campus',
				'edit_item' => 'Edit Campus',
				'all_items' => 'All Campuses',
				'singular_name' => 'Campus'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-location-alt'		));

		// Custom Note Post Type
		register_post_type('note', array(
			'capability_type' => 'note',
			'map_meta_cap' => true,
			'show_in_rest' => true,
			// by default post types get title and editor
			'supports' => array('title', 'editor'),
			'public' => false,
			// Show admin dashboard UI?
			'show_ui' => true,
			'labels' => array(
				'name' => 'Notes',
				'add_new_item' => 'Add New Note',
				'edit_item' => 'Edit Note',
				'all_items' => 'All Notes',
				'singular_name' => 'Note'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-welcome-write-blog'		));
	
		// Custom LIKE Post Type
		register_post_type('like', array(
			// by default post types get title and editor - just need title for like
			'supports' => array('title'),
			'public' => false,
			// Show admin dashboard UI?
			'show_ui' => true,
			'labels' => array(
				'name' => 'Likes',
				'add_new_item' => 'Add New Like',
				'edit_item' => 'Edit Like',
				'all_items' => 'All Likes',
				'singular_name' => 'Like'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-heart'		));

		// Custom Home Page Slide Post Type
		register_post_type('homeslide', array(
			// by default slug would be 'event' so override to 'events' instead
			'rewrite' => array('slug' => 'homeslide'),
			// by default post types get title and editor - just need title ONLY
			'supports' => array('title'),
			'public' => false,
			'show_ui' => true,
			'labels' => array(
				'name' => 'Home Page Slides',
				'add_new_item' => 'Add New Home Page Slide',
				'edit_item' => 'Edit Home Page Slide',
				'all_items' => 'All Home Page Slides',
				'singular_name' => 'Home Page Slide'
			),
			// Icon for the WP dashboard
			'menu_icon' => 'dashicons-slides'		));
	}
	add_action('init', "university_post_types");
?>