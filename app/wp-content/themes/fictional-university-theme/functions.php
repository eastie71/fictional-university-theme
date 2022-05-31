<?php
	require get_theme_file_path('/inc/search-route.php');
	require get_theme_file_path('/inc/like-route.php');

	function university_custom_rest() {
		register_rest_field('post', 'authorName', array(
			'get_callback' => function() { return get_the_author(); }
		));
		/*
		Lecture 84 - I dont think this is needed.
		register_rest_field('note', 'userNoteCount', array(
			'get_callback' => function() { return count_user_posts(get_current_user_id(), 'note'); }
		));
		*/
	}
	add_action('rest_api_init', 'university_custom_rest');

	function get_id_by_slug($page_slug) {
	    $page = get_page_by_path($page_slug);
	    if ($page) {
	        return $page->ID;
	    } else {
	        return null;
	    }
	}
	function pageBanner($args = NULL) {
		if (!isset($args['title'])) {
			$args['title'] = get_the_title();
		}
		if (!isset($args['subtitle'])) {
			$args['subtitle'] = get_field('page_banner_subtitle');
		}
		if (!isset($args['image'])) {
			$pageBannerImage = get_field('page_banner_background_image');
			if ($pageBannerImage) {
				$args['image'] = $pageBannerImage['sizes']['pageBanner']; 
			} else {
				$args['image'] = get_theme_file_uri('/images/ocean.jpg');
			}
		}

		// Drop out of php into html
		?>
		<div class="page-banner">
			<div class="page-banner__bg-image" 
				style=
					"background-image: url(
					<?php
						echo $args['image']; 					
					?>);
					">		
			</div>
			<div class="page-banner__content container container--narrow">
		 		<h1 class="page-banner__title"><?php echo $args['title']; ?></h1>
				<div class="page-banner__intro">
					<p><?php echo $args['subtitle']; ?></p>
				</div>
			</div>  
		</div>
		<!-- Drop back into PHP here -->
		<?php
	}  
	function university_files()
	{
		wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyCT-QAxH9JGB4DbEOGwHgiG9xb8yWhaYT0', NULL, '1.0', true);
		//wp_enqueue_script('main-university-js', get_theme_file_uri('js/scripts-bundled.js'), NULL, filemtime(get_theme_file_path().'/js/scripts-bundled.js'), true);
		wp_enqueue_style('custom-google-font', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
		wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		//wp_enqueue_style('university_main_styles', get_stylesheet_uri(), NULL, filemtime(get_stylesheet_directory().'/style.css'));

		if (strstr($_SERVER['SERVER_NAME'], 'fictional-university.test')) {
			// This is the Local by Flywheel reference
			wp_enqueue_script('main-university-js', 'http://localhost:3000/bundled.js', NULL, '1.0', true);
		} else {
			wp_enqueue_script('our-vendors-js', get_theme_file_uri('/bundled-assets/vendors~scripts.dc6122955d6bd376506a.js'), NULL, '1.0', true);
			wp_enqueue_script('main-university-js', get_theme_file_uri('/bundled-assets/scripts.88a30020f2cee88d21e0.js'), NULL, '1.0', true);
			wp_enqueue_style('our-main-styles', get_theme_file_uri('/bundled-assets/styles.88a30020f2cee88d21e0.css'));
		}
		// inside the main js file setup some global vars for quick access
		wp_localize_script('main-university-js', 'universityData', array(
			'root_url' => get_home_url(),
			// create a unique id for this session used for validation
			'nonce' => wp_create_nonce('wp_rest')
		));
	}
	add_action('wp_enqueue_scripts', 'university_files');

	function university_features() {
		add_theme_support('title-tag');
		// enable featured images for blog posts
		add_theme_support('post-thumbnails');
		add_image_size('professorLandscape', 400, 260, true);
		add_image_size('professorPortrait', 480, 650, true);
		add_image_size('pageBanner', 1500, 350, true);
	}

	add_action('after_setup_theme', 'university_features');

	function university_adjust_queries($query) {
		// We only want to manipulate the events archive page - not the admin pages, and not custom queries
		if (!is_admin() and is_post_type_archive('event') and $query->is_main_query()) {
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
		if (!is_admin() and is_post_type_archive('program') and $query->is_main_query()) {
			$query->set('orderby', 'title');
			$query->set('order', 'ASC');
			$query->set('posts_per_page', -1);
		}
		// Load ALL of the campus posts so that it will show ALL pins for locations on the Google Map
		if (!is_admin() and is_post_type_archive('campus') and $query->is_main_query()) {
			$query->set('posts_per_page', -1);
		}
	}
	add_action('pre_get_posts', 'university_adjust_queries');

	function university_map_key($api) {
		$api['key'] = 'AIzaSyCT-QAxH9JGB4DbEOGwHgiG9xb8yWhaYT0';
		return $api;
	}
	add_filter('acf/fields/google_map/api', 'university_map_key');

	// Redirect subscriber accounts out of admin dashboard and onto the homepage
	function redirectSubsToFrontend() {
		$theCurrentUser = wp_get_current_user();
		if (count($theCurrentUser->roles) == 1 AND $theCurrentUser->roles[0] == 'subscriber') {
			wp_redirect(home_url("/"));
			exit;
		}
	}
	add_action('admin_init', 'redirectSubsToFrontend');

	// Redirect admin bar for subscriber accounts ONLY
	function removeAdminBarForSubs() {
		$theCurrentUser = wp_get_current_user();
		if (count($theCurrentUser->roles) == 1 AND $theCurrentUser->roles[0] == 'subscriber') {
			show_admin_bar(false);
		}
	}
	add_action('wp_loaded', 'removeAdminBarForSubs');

	// Customize Login Screen
	function ourHeaderUrl() {
		// This is to overwrite the the Link so it doesnt point to Wordpress...
		return esc_url(home_url("/"));
	}
	add_filter('login_headerurl', 'ourHeaderUrl');

	function ourLoginCSS() {
		// Overwrite the default Wordpress Styles with our own for the Login Screen
		wp_enqueue_style('our-main-styles', get_theme_file_uri('/bundled-assets/styles.88a30020f2cee88d21e0.css'));
		wp_enqueue_style('custom-google-font', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
	}
	add_action('login_enqueue_scripts', 'ourLoginCSS');

	// Override the standard WP login info on "hover" with the actual site name.
	function ourLoginText() {
		return get_bloginfo('name');
	}
	add_filter('login_headertext', 'ourLoginText');

	// Force note posts to be private - this should be done on bankend PHP as JS could get hacked
	// Also remove any HTML from the content
	// Also limit number of Notes posted to $maxNotePosts
	function validateNotePosts($data, $postarr) {
		$maxNoPosts = 8;
		if ($data['post_type'] == 'note') {
			// The postarr ID will be empty on creating NEW post
			if (count_user_posts(get_current_user_id(), 'note') > $maxNoPosts && !$postarr['ID']) {
				die("Sorry, Note limit (".$maxNoPosts.") reached. Delete an existing note to add this note.");
			}
			// Strip out any HTML entered by the user
			$data['post_content'] = sanitize_textarea_field($data['post_content']);
			$data['post_title'] = sanitize_text_field($data['post_title']);
		}
		if ($data['post_type'] == 'note' && $data['post_status'] != 'trash') {
			$data['post_status'] = "private";
		}
		return $data;
	}
	// The '2' in argument tells WP to pass 2 arguments (the postarr)
	// The '10' represents the priority the function is run if you had multiple callbacks for the wp_insert_post_data call
	// So '10' is just an arbitory number in this case
	add_filter('wp_insert_post_data', 'validateNotePosts', 10, 2);
?>