<!-- Force the method to be GET and the url to be the BASE url for WP -->
<form class="search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="headline headline--medium" for="s">Perform a New Search</label>
	<div class="search-form-row">
		<input id="s" class="s" type="search" name="s" placeholder="Enter your search...">
		<input class="search-submit" type="submit" value="Search">
	</div>
</form>