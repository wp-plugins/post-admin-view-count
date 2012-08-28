<?php
/*
  Plugin Name: Post Admin View Count
  Plugin URI: http://www.jonbishop.com/downloads/wordpress-plugins/post-admin-view-count/
  Description: Adds a sortable column to the admin's post manager, displaying the view count for each post.
  Version: 1.0
  Author: Jon Bishop
  Author URI: http://www.jonbishop.com
  License: GPL2
 */

class PostAdminViewCount {
    function init() {
        if (is_admin()) {
            add_filter('manage_edit-post_sortable_columns', array(&$this, 'pvc_column_register_sortable'));
            add_filter('posts_orderby', array(&$this, 'pvc_column_orderby'), 10, 2);
            add_filter("manage_posts_columns", array(&$this, "pvc_columns"));
            add_action("manage_posts_custom_column", array(&$this, "pvc_column"));
            add_action("admin_footer-edit.php", array(&$this, "pvc_update_date"));
            add_action('admin_head-edit.php', array(&$this, "pvc_update_views"));
        }
    }

    // Get views for post ID
    function pvc_update_views() {
        $last_checked = get_option('pvc_last_checked');
        if(gmdate( 'Y-m-d' ) > $last_checked){
            $views = stats_get_csv( 'postviews', "days=30&limit=-1" );
            foreach($views as $post_views){
                if(empty($post_views['views'])){
                    update_post_meta($post_views['post_id'], '_post_view_count', $post_views['views']);
                } else {
                     update_post_meta($post_views['post_id'], '_post_view_count', 0);
                }
            }
        }
    }

    // Add new columns to action post type
    function pvc_columns($columns) {
        $columns["post_view_count"] = "View Count";
        return $columns;
    }

    // Add data to new columns of action post type
    function pvc_column($column) {
        global $post, $pvc_last;
        if ("post_view_count" == $column) {
            // Grab a fresh view count
            $view_count = get_post_meta($post->ID, '_post_view_count', true);
            if(empty($view_count) && $view_count !== 0){
                $views = stats_get_csv( 'postviews', "days=30&limit=-1&post_id=" . $post->ID );
                $views = $views[0]['views'];
                if(empty($post_views['views'])){
                    update_post_meta($post->ID, '_post_view_count', $views);
                } else {
                    update_post_meta($post->ID, '_post_view_count', 0);
                }
            }
            echo $view_count;
        }
    }

    // Queries to run when sorting
    // new columns of action post type
    function pvc_column_orderby($orderby, $wp_query) {
        global $wpdb;
        if ('post_view_count' == @$wp_query->query['orderby'])
            $orderby = "(SELECT CAST(meta_value as decimal) FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = '_post_view_count') " . $wp_query->get('order');

        return $orderby;
    }

    // Make new columns to action post type sortable
    function pvc_column_register_sortable($columns) {
        $columns['post_view_count'] = 'post_view_count';
        return $columns;
    }

    function pvc_update_date() {
        // Save the last time this page was generated
        $current_date = gmdate( 'Y-m-d' );
        update_option('pvc_last_checked', $current_date);
    }

}

$postAdminViewCount = new PostAdminViewCount();
$postAdminViewCount->init();
?>