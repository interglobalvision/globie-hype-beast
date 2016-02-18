<?php
/**
 * Plugin Name: Globie Hype Beast
 * Plugin URI:
 * Description: Check for popular posts fetching data from facebook, twitter and page views
 * Version: 1.0.0
 * Author: Interglobal Vision
 * Author URI: http://interglobal.vision
 * License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

class Globie_Hype_Beast {

  public function __construct() {
    add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    add_action( 'wp_ajax_incr_page_views', array( $this, 'incr_page_views_callback' ));
    add_action( 'wp_ajax_nopriv_incr_page_views', array( $this, 'incr_page_views_callback' ));

  } 

  public function enqueue_scripts() {
    //echo admin_url('admin-ajax.php'); die;
    wp_enqueue_script( 'globie_hype_script', plugin_dir_url( __FILE__ ) . 'globie-hype-beast.js', array( 'jquery' ) );
    wp_localize_script( 'globie_hype_script', 'IGV_Hype', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  }

  public function incr_page_views_callback() {
    $permalink = $_POST['permalink'];
    $post_id = url_to_postid($permalink);
    $count = get_post_meta($post_id,'ghb_views_count', true);
    
    if (empty($count)) {
      $count = 0;
    }

    $count++;

    $update = update_post_meta($post_id,'ghb_views_count',$count);
  
    $this->update_hype($post_id);

    wp_die();
  }

  public function update_hype($post_id) {
    // Get views count
    $views = get_post_meta($post_id,'ghb_views_count', true);

    // Get fb likes from meta 
    $fb_hype = get_post_meta($post_id,'ghb_fb_likes', true);

    // Get tw likes from meta 
    $tw_hype = get_post_meta($post_id,'ghb_fb_likes', true);

    // Sum up
    $hype = $views + $fb_hype + $tw_hype;


    // Update hype
    update_post_meta($post_id,'ghb_hype',$hype);
    
  }

}

$gHPosts = new Globie_Hype_Beast();

?>
