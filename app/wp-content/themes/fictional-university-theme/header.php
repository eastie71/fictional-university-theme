<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<header class="site-header">
	    <div class="container">

	      <h1 class="school-logo-text float-left"><a href="<?php echo home_url(); ?>"><strong>Fictional</strong> University</a></h1>
	      <a href="<?php echo esc_url(home_url('/search')); ?>" class="js-search-trigger site-header__search-trigger"><i class="fa fa-search" aria-hidden="true"></i></a>
	      <i class="site-header__menu-trigger fa fa-bars" aria-hidden="true"></i>

	      <div class="site-header__menu group">
	        <nav class="main-navigation">
	          <ul>
	            <li 
	            	<?php
	            		// So here we are checking if the current page is the About Us page OR is a child of the About Us page
	            		// If so, add the class current-menu-item - and hence the menu will be highlighted 
	            		//if (is_page('about-us') or strpos(get_permalink(wp_get_post_parent_id(0)), 'about-us') !== false)
	            		if (is_page('about-us') or (wp_get_post_parent_id(0) == get_id_by_slug('about-us')))
	            			echo 'class="current-menu-item"';
	            	?>	            	
	            	><a href="<?php echo home_url('/about-us'); ?>">About Us</a></li>
	            <li
	            	<?php 
						if (get_post_type() == 'program')
							echo 'class="current-menu-item"';
					?>
	            	><a href="<?php echo get_post_type_archive_link('program'); ?>">Programs</a></li>
	            <li
					<?php 
						if (get_post_type() == 'event' or is_page('past-events'))
							echo 'class="current-menu-item"';
					?>
	            	><a href="<?php echo get_post_type_archive_link('event'); ?>">Events</a></li>
	            <li
	            	<?php 
						if (get_post_type() == 'campus')
							echo 'class="current-menu-item"';
					?>
	            	><a href="<?php echo get_post_type_archive_link('campus'); ?>">Campuses</a></li>
	            <li
					<?php 
						if (get_post_type() == 'post')
							echo 'class="current-menu-item"';
					?>
	            	><a href="<?php echo esc_url(home_url('/blog')); ?>">Blog</a></li>
	          </ul>
	        </nav>
	        <div class="site-header__util">
				<?php 
					if (is_user_logged_in()) {
				?>
						<a href="<?php echo esc_url(home_url('/my-notes')); ?>" class="btn btn--small  btn--dark-orange float-left push-right">My Notes</a>
						<a href="<?php echo wp_logout_url(); ?>" class="btn btn--small  btn--dark-orange float-left btn--with-photo">
							<span class="site-header__avatar"><?php echo get_avatar(get_current_user_id(), 60); ?></span>
							<span class="btn__text">Logout</span>
						</a>
				<?php
					} else {
				?>
						<a href="<?php echo wp_login_url(); ?>" class="btn btn--small btn--orange float-left push-right">Login</a>
	        			<a href="<?php echo wp_registration_url(); ?>" class="btn btn--small  btn--dark-orange float-left">Sign Up</a>
				<?php
					}
				?>
	        	<a href="<?php echo esc_url(home_url('/search')); ?>" class="search-trigger js-search-trigger"><i class="fa fa-search" aria-hidden="true"></i></a>
	        </div>
	      </div>
	    </div>
	 </header>
