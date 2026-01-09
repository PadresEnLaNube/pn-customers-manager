<?php
/**
 * Define the posts management functionality.
 *
 * Loads and defines the posts management files for this plugin so that it is ready for post creation, edition or removal.
 *  
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Functions_Post {
	/**
	 * Insert a new post into the database
	 * 
	 * @param string $title
	 * @param string $content
	 * @param string $excerpt
	 * @param string $name
	 * @param string $type
	 * @param string $status
	 * @param int $author
	 * @param int $parent
	 * @param array $cats
	 * @param array $tags
	 * @param array $postmeta
	 * @param bool $overwrite_id Overwrites the post if it already exists checking existing post by post name
	 * 
	 * @since    1.0.0
	 */
	public function PN_CUSTOMERS_MANAGER_insert_post($title, $content, $excerpt, $name, $type, $status, $author = 1, $parent = 0, $cats = [], $tags = [], $postmeta = [], $overwrite_id = true) {
    $post_values = [
      'post_title' => trim($title),
      'post_content' => $content,
      'post_excerpt' => $excerpt,
      'post_name' => $name,
      'post_type' => $type,
      'post_status' => $status,
      'post_author' => $author,
      'post_parent' => $parent,
      'comment_status' => 'closed',
      'ping_status' => 'closed',
    ];

    // Always require post.php since post_exists() is needed
    require_once(ABSPATH . 'wp-admin/includes/post.php');

    if (!post_exists($title, '', '', $type) || !$overwrite_id) {
      $post_id = wp_insert_post($post_values);
    } else {
      $posts = get_posts(['fields' => 'ids', 'post_type' => $type, 'title' => $title, 'post_status' => 'any', ]);
      $post_id = !empty($posts) ? $posts[0] : 0;

      if (!empty($post_id)) {
        wp_update_post(['ID' => $post_id, 'post_title' => $title, 'post_content' => $content, 'post_excerpt' => $excerpt, 'post_name' => $name, 'post_type' => $type, 'post_status' => $status, ]);
      } else {
        return false;
      }
    }

    if (!empty($cats)) {
      wp_set_post_categories($post_id, $cats);
      if ($type == 'product') {
        wp_set_post_terms($post_id, $cats, 'product_cat', true);
      }
    }

    if (!empty($tags)) {
      wp_set_post_tags($post_id, $tags);
      if ($type == 'product') {
        wp_set_post_terms($post_id, $tags, 'product_tag', true);
      }
    }
 
    if (!empty($postmeta)) {
      foreach ($postmeta as $meta_key => $meta_value) {
        if ((is_array($meta_value) && count($meta_value)) || (!is_array($meta_value) && (!empty($meta_value) || (string)($meta_value) == '0'))) {
          update_post_meta($post_id, $meta_key, $meta_value);
        }
      }
    }

    return $post_id;
  }

  /**
   * Duplicates a post and all its associated data
   * 
   * @param int $post_id The ID of the post to duplicate
   * @param string $post_status The status for the new post (default: 'draft')
   * @param string $suffix Optional suffix to add to the title (default: ' (copy)')
   * @return int|false The new post ID on success, false on failure
   */
  public function PN_CUSTOMERS_MANAGER_duplicate_post($post_id, $post_status = 'draft', $suffix = ' (copy)') {
    // Get the original post
    $post = get_post($post_id);
    if (!$post) {
      return false;
    }

    // Prepare the new post data
    $new_post = array(
      'post_title'     => $post->post_title . $suffix,
      'post_name'      => wp_unique_post_slug(sanitize_title($post->post_title . $suffix), 0, $post_status, $post->post_type, 0),
      'post_content'   => $post->post_content,
      'post_excerpt'   => $post->post_excerpt,
      'post_status'    => $post_status,
      'post_type'      => $post->post_type,
      'post_parent'    => $post->post_parent,
      'post_password'  => $post->post_password,
      'menu_order'     => $post->menu_order,
      'to_ping'        => $post->to_ping,
      'pinged'         => $post->pinged,
      'comment_status' => $post->comment_status,
      'ping_status'    => $post->ping_status,
      'post_author'    => get_current_user_id(),
    );

    // Insert the new post
    $new_post_id = wp_insert_post($new_post, true);

    // Check for errors
    if (is_wp_error($new_post_id)) {
      return false;
    }

    // Copy all post meta
    $meta_keys = get_post_custom_keys($post_id);
    if (!empty($meta_keys)) {
      foreach ($meta_keys as $meta_key) {
        // Skip internal WordPress meta keys
        if (strpos((string)$meta_key, '_') === 0) {
          continue;
        }
        
        $meta_values = get_post_custom_values($meta_key, $post_id);
        foreach ($meta_values as $meta_value) {
          $meta_value = maybe_unserialize($meta_value);
          update_post_meta($new_post_id, $meta_key, $meta_value);
        }
      }
    }

    // Copy all taxonomies
    $taxonomies = get_object_taxonomies($post->post_type);
    if (!empty($taxonomies)) {
      foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
        if (!is_wp_error($terms)) {
          wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }
      }
    }

    // Copy featured image if exists
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
      set_post_thumbnail($new_post_id, $thumbnail_id);
    }

    // Copy post format if exists
    $post_format = get_post_format($post_id);
    if ($post_format) {
      set_post_format($new_post_id, $post_format);
    }

    // Allow other plugins to hook into the duplication process
    do_action('PN_CUSTOMERS_MANAGER_after_post_duplication', $new_post_id, $post_id);

    return $new_post_id;
  }
}