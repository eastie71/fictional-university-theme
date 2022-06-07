<?php

/*
    Plugin Name: Craig Are You Paying Attention Quiz Plugin
    Description: Give your readers a multiple choice question to see if they are paying attention.
    Version: 1.0
    Author: Craig Eastwood
    Author URI: https://craigeastwood.com
*/

if ( ! defined( 'ABSPATH' )) exit; // Exit if accessed directly

class AreYouPayingAttention {
    function __construct() {
        add_action('init', array($this, 'adminAssets'));
    }

    function adminAssets() {
        wp_register_style('aypa-editcss', plugin_dir_url(__FILE__) . 'build/index.css');
        wp_register_script('aypa-blocktype', plugin_dir_url(__FILE__) . 'build/index.js', array('wp-blocks', 'wp-element', 'wp-editor'));
        register_block_type('craigplugin/are-you-paying-attention', array(
            'editor_script' => 'aypa-blocktype',
            'editor_style' => 'aypa-editcss',
            'render_callback' => array($this, 'renderHTML')
        ));
    }

    function renderHTML($attributes) {
        // return '<h3>Today the sky is completely ' . $attributes['skyColour'] . ' and the grass is very ' . $attributes['grassColour'] . '!!!</h3>';
        ob_start(); ?>
        <h3>Today the sky is <?php echo esc_html($attributes['skyColour']); ?> and the grass is <?php echo esc_html($attributes['grassColour']); ?>.</h3>
        <?php return ob_get_clean();
    }
}

$areYouPayingAttention = new AreYouPayingAttention();