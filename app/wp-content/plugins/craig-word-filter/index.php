<?php

/*
    Plugin Name: Craig Word Filter Plugin
    Description: Replaces words in posts based on a custom list.
    Version: 1.0
    Author: Craig Eastwood
    Author URI: https://craigeastwood.com
*/

if ( ! defined( 'ABSPATH' )) exit; // Exit if accessed directly

class WordFilterPlugin {
    function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'filterOptionSettings'));
        add_filter('the_content', array($this, 'filterContent'));
    }

    function menu() {
        // 1 - Document Title (on Tab), 2 - Admin Sidebar Text, 3 - Permissions/capability to see this option, 4 - Unique slug(short) name for option,
        // 5 - Function to render the HTML for the option page, 6 - Icon for sidebar option, 7 - A number for position it appears on sidebar
        // Used hard-coded SVG - converted to ASCII via chrome console -> btoa(SVG contents here) - Icon is 20 x 20 pixels
        $mainPageHook = add_menu_page('Words To Filter', 'Word Filter', 'manage_options', 'wordfilter', array($this, 'wordFilterPage'), 
             'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMCAyMEMxNS41MjI5IDIwIDIwIDE1LjUyMjkgMjAgMTBDMjAgNC40NzcxNCAxNS41MjI5IDAgMTAgMEM0LjQ3NzE0IDAgMCA0LjQ3NzE0IDAgMTBDMCAxNS41MjI5IDQuNDc3MTQgMjAgMTAgMjBaTTExLjk5IDcuNDQ2NjZMMTAuMDc4MSAxLjU2MjVMOC4xNjYyNiA3LjQ0NjY2SDEuOTc5MjhMNi45ODQ2NSAxMS4wODMzTDUuMDcyNzUgMTYuOTY3NEwxMC4wNzgxIDEzLjMzMDhMMTUuMDgzNSAxNi45Njc0TDEzLjE3MTYgMTEuMDgzM0wxOC4xNzcgNy40NDY2NkgxMS45OVoiIGZpbGw9IiNGRkRGOEQiLz4KPC9zdmc+Cg==', 
             100);
        // Direct File approach for SVG icon --> add_menu_page('Words To Filter', 'Word Filter', 'manage_options', 'wordfilter', array($this, 'wordFilterPage'), plugin_dir_url(__FILE__) . 'custom.svg', 100);
        // Needed this add_submenu_page call (which duplicates most of the above) so that we could change the sidebar name that appears to be 'Filter List'
        add_submenu_page('wordfilter', 'Words To Filter', 'Filter List', 'manage_options', 'wordfilter', array($this, 'wordFilterPage'));
        // 1 - Menu to add this subpage to, 2 - Document Title (on Tab), 3 - Admin Sidebar Text, 4 - Permissions/capability to see this option,
        // 5 - Unique slug(short) name for option, 6 - Function to render the HTML for the option page
        add_submenu_page('wordfilter', 'Word Filter Options', 'Options', 'manage_options', 'wordfilter-options', array($this, 'wordFilterOptionsPage'));

        // Use the main page hook - on load then load the CSS file
        add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
    }

    function filterOptionSettings() {
        add_settings_section('wfo_section', null, null, 'wordfilter-options');
        add_settings_field('wfo_replace_text', 'Replacement Text for Filtered Words', array($this, 'replaceTextHTML'), 'wordfilter-options', 'wfo_section');
        register_setting('wordfilterOptions', 'wfo_replace_text', array('sanitize_callback' => 'sanitize_text_field', 'default' => '$%!#'));
    }

    function replaceTextHTML() { ?>
        <input type="text" name="wfo_replace_text" value="<?php echo esc_attr(get_option('wfo_replace_text', '$%!#'))?>">
        <p class="description">Leave blank to simply remove the filtered words.</p>
    <?php }

    function filterContent($content) {
        $filterWords = explode(',', get_option('plugin_words_to_filter'));
        if (!$filterWords)
            return $content;
        $filterWordsTrimmed = array_map('trim', $filterWords);

        return str_ireplace($filterWordsTrimmed, esc_html(get_option('wfo_replace_text', '$%!#')), $content);
    }

    function wordFilterPage() { ?>
        <div class="wrap">
            <h1>Word Filter</h1>
            <!-- Because the POST does NOT have an action, the submit will call the same file (index.php) 
                 and therefore the "justsubmitted" does not get set until after the form is submitted -->
            <?php if (isset($_POST["justsubmitted"]) && $_POST['justsubmitted'] == "true") $this->handleWordsFormSubmit() ?>
            <form method="POST">
                <input type="hidden" name="justsubmitted" value="true">
                <?php wp_nonce_field('saveFilterWords', 'wfNonce'); ?>
                <label for="plugin_words_to_filter"><p>Enter a <strong>comma-separated</strong> list of words you want filtered from your post contents.</p></label>
                <div class="word-filter__flex-container">
                    <textarea name="plugin_words_to_filter" id="plugin_words_to_filter" placeholder="Eg. bad,mean,yuck,horrible"><?php echo esc_textarea(get_option('plugin_words_to_filter')) ?></textarea>
                </div>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </form>
        </div>
    <?php }

    function handleWordsFormSubmit() {
        if (isset($_POST["wfNonce"]) AND wp_verify_nonce($_POST['wfNonce'], 'saveFilterWords') AND current_user_can('manage_options')) {
            update_option('plugin_words_to_filter', sanitize_text_field($_POST['plugin_words_to_filter'])); ?>
            <div class="updated">
                <p>Your filtered words were saved.</p>
            </div>
        <?php } else { ?>
            <div class="error">
                <p>Sorry, you cannot perform that action.</p>
            </div>
        <?php }    
    }

    function wordFilterOptionsPage() { ?>
        <div class="wrap">
            <h1>Word Filter Options</h1>
            <form action="options.php" method="POST">
                <?php
                    settings_errors();
                    settings_fields('wordfilterOptions');
                    do_settings_sections('wordfilter-options');
                    submit_button();
                ?>
            </form>
        </div>
    <?php }

    function mainPageAssets() {
        // 1 - Unique slug(short) name, 2 - Actual file to load
        wp_enqueue_style('filterAdminCss', plugin_dir_url(__FILE__) . 'styles.css');
    }
}

$wordFilterPlugin = new WordFilterPlugin();