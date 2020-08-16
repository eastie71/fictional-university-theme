<?php
	get_header();
	pageBanner(array(
		'title' => 'All Current Events',
		'subtitle' => 'Checkout the exciting things happening!'
	));
?>
	<div class="container container--narrow page-section">
		<?php
			while (have_posts()) {
				the_post();
				get_template_part('template-parts/content-event');
			}
			echo paginate_links();
		?>
		<hr class="section-break">
		<p>Looking for events we have held in the past? <a href="<?php echo home_url('/past-events'); ?>">Check out our past events archive.</a></p>
	</div>

<?php
	get_footer();
?>