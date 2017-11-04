<?php
	function get_id_by_slug($page_slug) {
	    $page = get_page_by_path($page_slug);
	    if ($page) {
	        return $page->ID;
	    } else {
	        return null;
	    }
	}  
	function university_files()
	{
		wp_enqueue_script('main-university-js', get_theme_file_uri('js/scripts-bundled.js'), NULL, '1.0', true);
		wp_enqueue_style('custom-google-font', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
		wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		wp_enqueue_style('university_main_styles', get_stylesheet_uri());
	}
	add_action('wp_enqueue_scripts', 'university_files');

	function university_features() {
		add_theme_support('title-tag');
	}

	add_action('after_setup_theme', 'university_features');

	function university_adjust_queries($query) {
		// We only want to manipulate the events archive page - not the admin pages, and not custom queries
		if (!is_admin() and is_post_type_archive('event') and is_main_query()) {
			$today = date('Ymd');

			// sort by event date in ascending order           
			$query->set('meta_key', 'event_date');
			$query->set('orderby', 'meta_value_num');
			$query->set('order', 'ASC');
			
			// filter to event date greater than or equal to todays date
            // custom event date field is stored as YYYYMMDD
            $query->set('meta_query', array(
              array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
              )
            ));
		}
		
	}
	add_action('pre_get_posts', 'university_adjust_queries')
?>