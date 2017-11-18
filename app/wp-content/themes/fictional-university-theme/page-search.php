<?php 
	get_header();
	while (have_posts()) {
		the_post(); 
		pageBanner();
		?>
		  <div class="container container--narrow page-section">
		  	<?php
		  		$theParent = wp_get_post_parent_id(get_the_ID());
		  		if ($theParent > 0) { 
		  	?>
					<div class="metabox metabox--position-up metabox--with-home-link">
						<p><a class="metabox__blog-home-link" href="<?php echo get_permalink($theParent); ?>"><i class="fa fa-home" aria-hidden="true"></i> Back to <?php echo get_the_title($theParent); ?></a> <span class="metabox__main"><?php the_title(); ?></span></p>
		    		</div>
			<?php
		  		}
		  	?>

			<?php
			$pageHasChildren = get_pages(array(
				'child_of' => get_the_ID()
			));
			// Only display the sidebar for child pages or the parent page of child pages
			if ($theParent > 0 or $pageHasChildren) {
			?>
			    <div class="page-links">
					<!-- The get_the_title/permalink function will return the current page title/permalink if $theParent == 0 -->
			      <h2 class="page-links__title"><a href="<?php echo get_permalink($theParent); ?>"><?php echo get_the_title($theParent); ?></a></h2>
			      <ul class="min-list">
					<?php
						if ($theParent > 0) {
							// Current page is a child page - so need to use the Parent ID
							$parentPageId = $theParent;
						} else {
							// Current page is a parent page
							$parentPageId = get_the_ID();
						}
						wp_list_pages(array(
							'title_li' => NULL,
							'child_of' => $parentPageId,
							'sort_column' => 'menu_order'
						));
					?>
			      </ul>
			    </div>
			<?php
			}
			?>
		    <div class="generic-content">
		    	<?php get_search_form(); ?>
		    </div>

		 </div>			
<?php	
	}
	get_footer();
?>