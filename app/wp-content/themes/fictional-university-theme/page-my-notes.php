<?php
	// Can only access my notes if you are logged in.
	if (!is_user_logged_in()) {
		wp_redirect(esc_url(home_url('/')));
		// exit from server so doesnt use any more resources
		exit;
	} 
	get_header();
	while (have_posts()) {
		the_post(); 
		pageBanner();
?>
			<div class="container container--narrow page-section">
				<div class="create-note">
					<h2 class="headline headline--medium">Create New Note</h2>
					<input class="new-note-title" type="text" maxlength="50" placeholder="Title (max 50 characters)">
					<textarea class="new-note-body" maxlength="500" placeholder="Your notes here (max 500 characters)..."></textarea>
					<span class="submit-note">Create</span>
					<span class="note-limit-message"></span>
				</div>
				<ul class="min-list link-list" id="my-notes">
					<?php 
						$userNotes = new WP_Query(array(
							'post_type' => 'note',
							'posts_per_page' => -1,
							'author' => get_current_user_id()
						));
						while ($userNotes->have_posts()) {
							$userNotes->the_post();
					?>
							<li data-id="<?php the_ID(); ?>">
								<!-- Private posts are prepended with "Private: ", so we want to remove that. -->
								<input readonly class="note-title-field" type="text" maxlength="50" value="<?php echo str_replace('Private: ', '', esc_attr(get_the_title())); ?>">
								<span class="edit-note"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</span>
								<span class="delete-note"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</span>
								<textarea readonly class="note-body-field" maxlength="500"><?php echo esc_textarea(get_the_content()); ?></textarea>
								<span class="update-note btn btn--blue btn--small"><i class="fa fa-arrow-right" aria-hidden="true"></i> Save</span>
							</li>
					<?php
						}
					?>
				</ul>

			</div>			
<?php	
	}
	get_footer();
?>