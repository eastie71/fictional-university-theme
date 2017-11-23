<?php

/*
Plugin Name: Craig Eastwood's Really Cool Plugin 
Description: Will perform some basic swear word filtering and add IP clause for content and display. Also supplied a couple of shortcodes to count the number of programs [programCount] and campuses [campusCount]
*/

function swearFilter($text) {
	$text = str_replace("fuck", "****", $text);
	$text = str_replace(" cunt ", " **** ", $text);
	return $text;
}

function coolContentEdits($content) {
	$content = $content . '<p>All content remains the property of Fictional University</p>';
	$content = swearFilter($content);	
	return $content;
}
add_filter('the_content', 'coolContentEdits');

function coolTitleEdits($title) {
	return swearFilter($title);
}
add_filter('the_title', 'coolTitleEdits');

function numberOfPrograms() {
	$count_posts = wp_count_posts('program');
	return $count_posts->publish;
}
add_shortcode('programCount', 'numberOfPrograms');

function numberOfCampuses() {
	$count_posts = wp_count_posts('campus');
	return $count_posts->publish;
}
add_shortcode('campusCount', 'numberOfCampuses');