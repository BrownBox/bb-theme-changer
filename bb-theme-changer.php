<?php
/*
 * Plugin Name: BB Theme Changer
 * Plugin URI: brownbox.net.au
 * Description: Adds the ability to select a theme per post
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * Version: 1.0
 */
require_once('meta_.php');

function bb_theme_changer_meta() {
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
            array(
                    'title' => 'Page Template',
                    'description' => 'Select the template to be used for this post',
                    'field_name' => 'post_template',
                    'type' => 'select',
                    'options' => bb_theme_changer_template_options(),
                    'default' => '',
            ),
    );
    new bb_theme_changer\metaClass('Theme Changer', get_post_types(), $theme_meta);
}
add_action('admin_init', 'bb_theme_changer_meta');

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

function bb_theme_changer_template_options() {
    if (!empty($_GET['post'])) {
        $post = get_post($_GET['post']); // Global post variable isn't available yet when we need to set this up
    }
    $options = array(
            array(
                    'label' => 'Default Template',
                    'value' => '',
            ),
    );
    $page_theme = wp_get_theme(get_post_meta($post->ID, 'post_theme', true));
    $templates = $page_theme->get_page_templates();
    foreach ($templates as $template_filename => $template_name) {
        $options[] = array(
                'label' => $template_name,
                'value' => $template_filename,
        );
    }
    return $options;
}

/* Hook on setup_theme so we can modify things */
add_action('setup_theme', 'bb_theme_changer_theme_init');

// globals
$bb_theme_changer_theme = $bb_theme_changer_css = '';

function bb_theme_changer_theme_init() {
    global $bb_theme_changer_theme, $bb_theme_changer_css, $bb_theme_changer_template;

    $bb_theme_changer_theme = @$_GET['t'];
    $bb_theme_changer_css = @$_GET['preview_css'];

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

add_filter('template_include', 'bb_theme_changer_template_init', 999);
function bb_theme_changer_template_init($template) {
    global $post, $bb_theme_changer_theme;
    $bb_theme_changer_template = get_post_meta($post->ID, 'post_template', true);
    if (!empty($bb_theme_changer_template) && file_exists(get_theme_root().'/'.$bb_theme_changer_theme.'/'.$bb_theme_changer_template)) {
        return get_theme_root().'/'.$bb_theme_changer_theme.'/'.$bb_theme_changer_template;
    }
    return $template;
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
