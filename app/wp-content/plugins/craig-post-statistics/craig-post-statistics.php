<?php

/*
    Plugin Name: Craig Post Statistics
    Description: Post Statistics - Character and Word Count. Estimated read time. All configurable!
    Version: 1.0
    Author: Craig Eastwood
    Author URI: https://craigeastwood.com
    Text Domain: pspdomain
    Domain Path: /languages
*/

class PostStatsPlugin {
    function __construct() {
        add_action('admin_menu', array($this, 'adminSettings'));
        add_action('admin_init', array($this, 'settings'));
        add_filter('the_content', array($this, 'checkAdjustHTML'));
        add_action('init', array($this, 'languages'));
    }

    function adminSettings() {
        add_options_page('Post Stats Settings', esc_html__('Post Stats','pspdomain'), 'manage_options', 'craig-post-stats-settings', array($this, 'settingsHTML'));
    }

    function settings() {
        add_settings_section('psp_first_section', null, null, 'craig-post-stats-settings');

        add_settings_field('psp_location', 'Display Location', array($this, 'locationHTML'), 'craig-post-stats-settings', 'psp_first_section');
        register_setting('poststatsplugin', 'psp_location', array('sanitize_callback' => array($this, 'sanitizeLocation'), 'default' => '0'));
    
        add_settings_field('psp_headline', 'Headline Text', array($this, 'headlineHTML'), 'craig-post-stats-settings', 'psp_first_section');
        register_setting('poststatsplugin', 'psp_headline', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'Post Statistics'));

        add_settings_field('psp_wordcount', 'Word Count', array($this, 'checkboxHTML'), 'craig-post-stats-settings', 'psp_first_section', 
                            array('option' => 'psp_wordcount'));
        register_setting('poststatsplugin', 'psp_wordcount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));
        add_settings_field('psp_charcount', 'Character Count', array($this, 'checkboxHTML'), 'craig-post-stats-settings', 'psp_first_section', 
                            array('option' => 'psp_charcount'));
        register_setting('poststatsplugin', 'psp_charcount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));
        add_settings_field('psp_readtime', 'Reading Time', array($this, 'checkboxHTML'), 'craig-post-stats-settings', 'psp_first_section', 
                            array('option' => 'psp_readtime'));
        register_setting('poststatsplugin', 'psp_readtime', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));
    }

    function checkAdjustHTML($content) {
        if (is_main_query() AND is_single() AND 
            (get_option('psp_wordcount', '1') OR get_option('psp_charcount', '1') OR get_option('psp_readtime', '1'))) {
            
            return $this->adjustHTML($content);
        }
        return $content;
    }

    function languages() {
        load_plugin_textdomain('pspdomain', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    function adjustHTML($content) {
        $html = '<h4>' . esc_html(get_option('psp_headline', 'Post Statistics')) . '</h4><p>';

        if (get_option('psp_wordcount', '1') == '1' OR get_option('psp_read_time', '1') == '1') {
            $wordCount = str_word_count(strip_tags($content));
        }
        if (get_option('psp_wordcount', '1') == '1') {
            $html .= esc_html__('This post contains', 'pspdomain') . ' ' . $wordCount . ' ' . esc_html__('words.','pspdomain') . '<br>';
        }
        if (get_option('psp_charcount', '1') == '1') {
            $html .= 'This post contains ' . strlen(strip_tags($content)) . ' characters.<br>';
        }
        if (get_option('psp_readtime', '1') == '1') {
            $readTime = ceil($wordCount/225);
            $html .= 'Reading time is approx ' . $readTime . ($readTime > 1 ? ' minutes.' : ' minute.') . '<br>';
        }
        $html .= '</p>';
        if (get_option('psp_location', '0') == '1') {
            return $content . $html;     
        }
        return $html . $content; 
    }
    
    function settingsHTML() { ?>
        <div class="wrap">
            <h1>Post Statistics Settings</h1>
            <form action="options.php" method="POST">
            <?php
                settings_fields('poststatsplugin');
                do_settings_sections('craig-post-stats-settings');
                submit_button();
            ?>
            </form>
        </div>
    <?php }

    function locationHTML() { ?>
        <select name="psp_location">
            <option value="0" <?php selected(get_option('psp_location'), '0')?>>Beginning of post</option>
            <option value="1" <?php selected(get_option('psp_location'), '1')?>>End of post</option>
        </select>
    <?php }

    function sanitizeLocation($input) {
        if ($input != '0' AND $input != '1') {
            add_settings_error('psp_location', 'psp_location_error', 'Display location must be either Beginning or End of Post');
            return get_option('psp_location');
        }
        return $input;
    }

    function headlineHTML() { ?>
        <input type="text" name="psp_headline" value="<?php echo esc_attr(get_option('psp_headline'))?>">
    <?php }

    function checkboxHTML($args) { ?>
        <input type="checkbox" name="<?php echo $args['option']?>" value="1" <?php checked(get_option($args['option']), '1')?>>
    <?php }
}

$postStatPlugin = new PostStatsPlugin();


