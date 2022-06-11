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
        // The following 2 files are now loaded via "block.json" file.
        //wp_register_style('aypa-editcss', plugin_dir_url(__FILE__) . 'build/index.css');
        //wp_register_script('aypa-blocktype', plugin_dir_url(__FILE__) . 'build/index.js', array('wp-blocks', 'wp-element', 'wp-editor'));
        
        register_block_type(__DIR__, array(
            'render_callback' => array($this, 'renderHTML')
        ));
    }

    function renderHTML($attributes) {
        if (!is_admin()) {
            // We only want to load these files on the frontend - NOT in the wp admin screen
            // wp-element dependency includes wordpress version of React and ReactDOM
            wp_enqueue_script('attentionFrondend', plugin_dir_url(__FILE__) . 'build/frontend.js', array('wp-element'));
            //This CSS is now loaded via the "block.json" file
            //wp_enqueue_style('attentionFrontendStyles', plugin_dir_url(__FILE__) . 'build/frontend.css');
        }

        // return '<h3>Today the sky is completely ' . $attributes['skyColour'] . ' and the grass is very ' . $attributes['grassColour'] . '!!!</h3>';
        
        // Below we setup a "hook" div with a particular class name (paying-attention-update-me), that is then used in the frontend JS to select
        // this particular div and populate it with a react component. We encode the $attributes as JSON and make the display hidden - but it will
        // still exist in the DOM for the JS to access
        ob_start(); ?>
        <div class="paying-attention-update-me"><pre style="display: none"><?php echo wp_json_encode($attributes) ?></pre></div>
        <?php return ob_get_clean();
    }
}

$areYouPayingAttention = new AreYouPayingAttention();