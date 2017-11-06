<?php
	get_header(); 
	while (have_posts()) {
		the_post(); 
		pageBanner();
		?>
		<div class="container container--narrow page-section">
			<div class="metabox metabox--position-up metabox--with-home-link">
				<p><a class="metabox__blog-home-link" href="<?php echo get_post_type_archive_link('program'); ?>"><i class="fa fa-home" aria-hidden="true"></i> All Programs</a> <span class="metabox__main"><?php the_title(); ?></span></p>
		    </div>
			<div class="generic-content"><?php the_content(); ?></div>
					<?php
 					$relatedProfessors = new WP_Query(array(
            'posts_per_page' => -1,
            'post_type' => 'professor',
            'orderby' => 'title',
            'order' => 'ASC',
            // filter to event date greater than or equal to todays date
            // custom event date field is stored as YYYYMMDD
            'meta_query' => array(
              // look at the custom field and perform a wildcard type search (LIKE) for the current program ID (inside the array list - which is why it needs to be "quoted")
              array(
              	'key' => 'related_programs',
              	'compare' => 'LIKE',
              	'value' => '"'.get_the_ID().'"'
              )
            )
          ));

          echo '<hr class="section_break">';
          echo '<h2 class="headline headline--medium"> '.get_the_title().' Professors</h2>';
          if ($relatedProfessors->have_posts()) {
          	echo '<ul class="professor-cards">';
	          while ($relatedProfessors->have_posts()) {
	            $relatedProfessors->the_post();
	        ?>
	            <li class="professor-card__list-item">
	            	<a class="professor-card" href="<?php the_permalink(); ?>">
	            		<img class="professor-card__image" src="<?php the_post_thumbnail_url('professorLandscape'); ?>">
	            		<span class="professor-card__name"><?php the_title(); ?></span>
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
	        ?>
	            <div class="event-summary">
	              <a class="event-summary__date t-center" href="<?php the_permalink(); ?>">
	                <span class="event-summary__month">
	                  <?php
	                    $eventDate = new DateTime(get_field('event_date')); 
	                    echo $eventDate->format('M');
	                  ?>
	                </span>
	                <span class="event-summary__day">
	                  <?php 
	                    echo $eventDate->format('d');
	                  ?>                  
	                </span>  
	              </a>
	              <div class="event-summary__content">
	                <h5 class="event-summary__title headline headline--tiny"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
	                <p><?php
	                      if (has_excerpt()) { 
	                        echo get_the_excerpt();
	                      } else {
	                        echo wp_trim_words(get_the_content(), 18); 
	                      }
	                   ?>
	                   <a href="<?php the_permalink(); ?>" class="nu gray"> Learn more</a>
	                 </p>
	              </div>
	            </div>
	        <?php
	          } 
	        } else {
	        	echo "None Found.";
	        }
	        ?>
		</div>
<?php	}
	get_footer();
?>