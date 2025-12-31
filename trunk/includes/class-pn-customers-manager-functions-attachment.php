<?php
/**
 * Define the attachments management functionality.
 *
 * Loads and defines the attachments management files for this plugin so that it is ready for attachment creation, edition or removal.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Functions_Attachment {
	/**
	 * Insert a new attachment into the library
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
	public function insert_attachment_from_url($url, $parent_post_id = null) {
    if(!class_exists('WP_Http')){
      require_once(ABSPATH . 'wp-includes/class-http.php'); 
    }

    $http = new WP_Http();
    $response = $http->request($url);
    $file_extension = pathinfo($url, PATHINFO_EXTENSION);

    if (is_wp_error($response)) {
      return false;
    }

    $upload = wp_upload_bits(basename($url . '.' . $file_extension), null, $response['body']);

    if(!empty($upload['error'])) {
      return false;
    }

    $file_path = $upload['file'];
    $file_name = basename($file_path);
    $file_type = wp_check_filetype($file_name, null);
    $attachment_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
    $wp_upload_dir = wp_upload_dir();

    $post_info = [
      'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
      'post_mime_type' => $file_type['type'],
      'post_title'     => $attachment_title,
      'post_content'   => '',
      'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($post_info, $file_path, $parent_post_id);
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    return $attach_id;
  }
}