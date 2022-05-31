<?php

/*
    Plugin Name: Craig Test Plugin
    Description: Craig First Plugin to Test - It adds a sentence to a Blog post!
    Version: 1.0
    Author: Craig Eastwood
    Author URI: https://craigeastwood.com
*/

add_filter('the_content', 'addSentenceToEndOfPost');

function addSentenceToEndOfPost($content) {
    if (is_single() && is_main_query()) {
        return $content . '<p>Extra sentence added by Craig Eastwood!</p>';
    }
    return $content;
}