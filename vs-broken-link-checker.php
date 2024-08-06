<?php
/*
Plugin Name: Broken Link Checker VS
Plugin URI: https://www.vsplash.com/
Description: A plugin to check for broken links in posts.
Version: 1.0
Author: vsplash
Author URI: https://www.vsplash.com/
License: GPL2
*/


function vsblc_enqueue_scripts()
{
    wp_enqueue_script('vsblc-ajax', plugin_dir_url(__FILE__) . 'assets/js/vsblc-ajax.js', [], null, true);
    wp_localize_script('vsblc-ajax', 'vsblcAjax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('admin_enqueue_scripts', 'vsblc_enqueue_scripts');


// Hook for adding admin menus
add_action('admin_menu', 'vsblc_add_pages');
// Action function for the above hook
function vsblc_add_pages()
{
    add_menu_page(
        'Broken Link Checker',
        'Broken Link Checker',
        'manage_options',
        'vs-broken-link-checker',
        'vsblc_options_page',
        'dashicons-editor-unlink',
        100
    );
}

function vsblc_options_page() {
?>
    <div class="wrap">
        <h1>Broken Link Checker</h1>
        <button id="vsblc-check-links-button" class="button-primary">Check for Broken Links</button>
        <div id="vsblc-results"></div>
    </div>
<?php
}

function vsblc_check_links_ajax()
{
    ob_start();
    vsblc_check_links();
    $output = ob_get_clean();
    echo $output;
    wp_die();
}
add_action('wp_ajax_vsblc_check_links', 'vsblc_check_links_ajax');

function vsblc_check_links()
{
    global $wpdb;
    $posts = $wpdb->get_results("SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_status = 'publish'");
    $broken_links = [];

    foreach ($posts as $post) {
        if (empty($post->post_content)) {
            continue;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($post->post_content);
        $links = $doc->getElementsByTagName('a');

        foreach ($links as $link) {
            $url = $link->getAttribute('href');
            if (empty($url)) {
                continue;
            }
            
            $link_text = $link->textContent;
            $headers = @get_headers($url);

            if ($headers) {
                // Extract the HTTP status code from the headers
                $status_code = intval(substr($headers[0], 9, 3));

                // Check if the status code is in the 4xx range
                if ($status_code >= 400 && $status_code < 500) {
                    $broken_links[] = [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'post_link' => get_permalink($post->ID),
                        'url' => $url,
                        'link_text' => $link_text
                    ];
                }
            }
        }
    }

    if (empty($broken_links)) {
        echo '<p>No broken links found.</p>';
    } else {
        echo '<h2>Broken Links</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Post ID</th><th>Post Title</th><th>Post Link</th><th>Broken URL</th><th>Link Text</th></tr></thead><tbody>';
        foreach ($broken_links as $link) {
            echo '<tr>';
            echo '<td>' . $link['post_id'] . '</td>';
            echo '<td>' . esc_html($link['post_title']) . '</td>';
            echo '<td><a href="' . esc_url($link['post_link']) . '" target="_blank">' . esc_url($link['post_link']) . '</a></td>';
            echo '<td><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_url($link['url']) . '</a></td>';
            echo '<td>' . esc_html($link['link_text']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
?>
