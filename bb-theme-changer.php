<?php
/*
 * Plugin Name: BB Theme Changer
 * Plugin URI: brownbox.net.au
 * Description: Adds the ability to select a theme per post
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * Version: 0.1
 */

require_once('meta_.php');

if (is_admin()) {
    $current_theme = wp_get_theme();
    $theme_meta = array(
            array(
                    'title' => 'Page Theme',
                    'description' => 'Select the theme to be used for this post',
                    'field_name' => 'post_theme',
                    'type' => 'select',
                    'options' => bb_theme_changer_theme_options(),
                    'default' => $current_theme->stylesheet,
            ),
    );
    new bb_theme_changer\metaClass('Theme Changer', get_post_types(), $theme_meta);
}

function bb_theme_changer_theme_options() {
    $options = array();
    $current_theme = wp_get_theme();
    $themes = wp_get_themes();
    foreach ($themes as $theme) {
        $name = $theme->name;
        if ($theme->stylesheet == $current_theme->stylesheet) {
            $name .= ' (Default)';
        }
        $options[] = array(
                'label' => $name,
                'value' => $theme->stylesheet,
        );
    }
    return $options;
}

/* Hook on setup_theme so we can modify things */
add_action('setup_theme', 'bb_theme_changer_theme_init');

// globals
$bb_theme_changer_theme = '';
$bb_theme_changer_css = '';

function bb_theme_changer_theme_init() {
    global $bb_theme_changer_theme, $bb_theme_changer_css;

    $bb_theme_changer_theme = $_GET['t'];
    $bb_theme_changer_css = $_GET['preview_css'];

    /* Don't allow directory traversal */
    if (validate_file($bb_theme_changer_theme) !== 0) {
        return;
    }

    if (validate_file($bb_theme_changer_css) !== 0) {
        return;
    }

    if (!$bb_theme_changer_css) {
        $bb_theme_changer_css = $bb_theme_changer_theme;
    }

    if ($bb_theme_changer_theme && file_exists(get_theme_root().'/'.$bb_theme_changer_theme)) {
        add_filter('template', 'use_preview_theme');
    }

    if ($bb_theme_changer_css && file_exists(get_theme_root().'/'.$bb_theme_changer_css)) {
        add_filter('stylesheet', 'use_preview_css');
    }
}

function use_preview_theme($themename) {
    global $bb_theme_changer_theme;
    return $bb_theme_changer_theme;
}

function use_preview_css($cssname) {
    global $bb_theme_changer_css;
    return $bb_theme_changer_css;
}

if (!is_admin()) {
    add_action('wp', 'bb_theme_changer_redirect');
}

function bb_theme_changer_redirect() {
    $qs = $_SERVER["QUERY_STRING"];
    parse_str($qs, $atts);
    global $post;
    $post_theme = get_post_meta($post->ID, 'post_theme', true);
    $current_theme = wp_get_theme();
    if (empty($atts['t']) && !empty($post_theme) && $post_theme != $current_theme->stylesheet) {
        $atts['t'] = $post_theme;
        $qs = http_build_query($atts);
        wp_redirect('?'.$qs);
        exit;
    }
}
