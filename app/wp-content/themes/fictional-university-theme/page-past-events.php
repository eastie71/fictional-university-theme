<?php
	get_header();
	pageBanner(array(
		'title' => 'Past Events',
		'subtitle' => 'A Recap of the events we have held in the past.'
	));
?>
	<div class="container container--narrow page-section">
		<?php
			$today = date('Ymd');
			$pastEvents = new WP_Query(array(
				// pagination configured to be based on current page, if no page exists fall back to page 1
				'paged' => get_query_var('paged', 1),
				'post_type' => 'event',
				// sort by event date in ascending order
	            'meta_key' => 'event_date',
	            'orderby' => 'meta_value_num',
	            'order' => 'ASC',
	            // filter to event date greater than or equal to todays date
	            // custom event date field is stored as YYYYMMDD
	            'meta_query' => array(
	              array(
	                'key' => 'event_date',
	                'compare' => '<',
	                'value' => $today,
	                'type' => 'numeric'
	            	)
				))
			);
			while ($pastEvents->have_posts()) {
				$pastEvents->the_post();
				get_template_part('template-parts/content-event');
			}
			// pagination needs to be based on the past events max number of pages
			echo paginate_links(array(
				'total' => $pastEvents->max_num_pages
			));
		?>

	</div>

<?php
	get_footer();
?>