<?php
	get_header(); 
	while (have_posts()) {
		the_post(); 
		pageBanner();
		?>
		<div class="container container--narrow page-section">
			<div class="metabox metabox--position-up metabox--with-home-link">
				<p><a class="metabox__blog-home-link" href="<?php echo get_post_type_archive_link('campus'); ?>"><i class="fa fa-home" aria-hidden="true"></i> All Campuses</a> <span class="metabox__main"><?php the_title(); ?></span></p>
		    </div>
			<div class="generic-content"><?php the_content(); ?></div>
			<div class="acf-map">
				<?php
					$mapLocation = get_field('map_location'); 
				?>
					<div class="marker" data-lat="<?php echo $mapLocation['lat']; ?>" data-lng="<?php echo $mapLocation['lng']; ?>">
						<h3><?php the_title(); ?></h3>
						<?php echo $mapLocation['address']; ?>
					</div>
			</div>
		<?php
 			$relatedPrograms = new WP_Query(array(
            'posts_per_page' => -1,
            'post_type' => 'program',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(
              // look at the custom field and perform a wildcard type search (LIKE) for the current campus ID (inside the array list - which is why it needs to be "quoted")
              array(
              	'key' => 'related_campus',
              	'compare' => 'LIKE',
              	'value' => '"'.get_the_ID().'"'
              		)
            	)
          	));

        	echo '<hr class="section_break">';
        	echo '<h2 class="headline headline--medium"> '.get_the_title().' Available Programs</h2>';
        	if ($relatedPrograms->have_posts()) {
          		echo '<ul class="min-list link-list">';
	        	while ($relatedPrograms->have_posts()) {
	        		$relatedPrograms->the_post();
	    ?>
	            	<li>
	            		<a href="<?php the_permalink(); ?>">
	            			<?php the_title(); ?>
	            		</a>
	            	</li>
	        <?php
	          } 
	          echo '</ul>';
	        } else {
	        	echo "None Found.";
	        }

	        // Reset the global post option back to the default URL query
	        wp_reset_postdata();

          $today = date('Ymd');
          // Perform a custom query - ie. last 2 events only.
          $homepageEvents = new WP_Query(array(
            'posts_per_page' => 2,
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
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
              ),
              // look at the custom field and perform a wildcard type search (LIKE) for the current program ID (inside the array list - which is why it needs to be "quoted")
              array(
              	'key' => 'related_programs',
              	'compare' => 'LIKE',
              	'value' => '"'.get_the_ID().'"'
              )
            )
          ));

          echo '<hr class="section_break">';
          echo '<h2 class="headline headline--medium">Upcoming '.get_the_title().' Events</h2>';
          if ($homepageEvents->have_posts()) {
	          while ($homepageEvents->have_posts()) {
	            $homepageEvents->the_post();
	        		get_template_part('template-parts/content-event');
	          } 
	        } else {
	        	echo "None Found.";
	        }
	        ?>
		</div>
<?php	}
	get_footer();
?>