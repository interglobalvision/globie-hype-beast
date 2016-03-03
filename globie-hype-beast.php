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

// Requires
require_once __DIR__ . '/lib/Facebook/autoload.php';

class Globie_Hype_Beast {

  public function __construct() {
    add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    add_action( 'wp_ajax_incr_page_views', array( $this, 'incr_page_views_callback' ));
    add_action( 'wp_ajax_nopriv_incr_page_views', array( $this, 'incr_page_views_callback' ));

    add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    add_action( 'admin_init', array( $this, 'settings_init' ) );

  } 

  public function add_admin_menu() {
    add_options_page(
      'Globie Hype Beast',
      'Globie Hype Beast',
      'manage_options',
      'globie_hype_beast',
      array( $this, 'options_page' )
    );
  }

  /*
   *
   * Register settings, sections and fields
   *
   */
  public function settings_init() {
    // Add facebook section
    add_settings_section(
      'ghypebeast_facebook_section',
      __( 'Facebook Keys', 'wordpress' ),
      array( $this, 'settings_facebook_section_callback' ),
      'ghypebeast_options_page'
    );

    // Register option: facebook app id
    register_setting( 'ghypebeast_options_page', 'ghypebeast_settings_fb_app_id' );

    // facebook app id field
    add_settings_field(
      'ghypebeast_fb_app_id_field',
      __( 'App id', 'wordpress' ),
      array( $this, 'settings_facebook_app_id_field_render' ),
      'ghypebeast_options_page',
      'ghypebeast_facebook_section'
    );

    // Register option: facebook app secret
    register_setting( 'ghypebeast_options_page', 'ghypebeast_settings_fb_app_secret' );

    // facebook app id field
    add_settings_field(
      'ghypebeast_fb_app_secret_field',
      __( 'App Secret', 'wordpress' ),
      array( $this, 'settings_facebook_app_secret_field_render' ),
      'ghypebeast_options_page',
      'ghypebeast_facebook_section'
    );

  }

  // App ID field render
  public function settings_facebook_app_id_field_render() {
    // Get options saved
    $facebook_app_id = get_option( 'ghypebeast_settings_fb_app_id' );
    // Render fields
    echo "<fieldset>";
    echo '<label for="ghypebeast_input_facebook" style="width: 100%;"><input type="text" style="width: 100%;" name="ghypebeast_settings_fb_app_id" id="ghypebeast_input_facebook" value="' . $facebook_app_id  . '"></label><br />';
    echo "</fieldset>";
  }

  // App Secret field render
  public function settings_facebook_app_secret_field_render() {
    // Get options saved
    $facebook_app_secret = get_option( 'ghypebeast_settings_fb_app_secret' );
    // Render fields
    echo "<fieldset>";
    echo '<label for="ghypebeast_input_facebook" style="width: 100%;"><input type="text" style="width: 100%;" name="ghypebeast_settings_fb_app_secret" id="ghypebeast_input_facebook" value="' . $facebook_app_secret  . '"></label><br />';
    echo "</fieldset>";
  }

  public function settings_facebook_section_callback() {
    echo __( '', 'wordpress' );
  }

  public function options_page() {
    echo '<form action="options.php" method="post">';
    echo '<h2>Globie Hype Beast Options</h2>';
    settings_fields( 'ghypebeast_options_page' );
    do_settings_sections( 'ghypebeast_options_page' );
    submit_button();
    echo '</form>';
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
  
    $this->request_facebook_likes($post_id);
    $this->update_hype($post_id);

    wp_die();
  }

  public function request_facebook_likes($post_id) {
    $app_id = get_option( 'ghypebeast_settings_fb_app_id' );
    $app_secret = get_option( 'ghypebeast_settings_fb_app_secret' );

    $fb = new Facebook\Facebook([
      'app_id'  => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.5',
    ]);

    // Get User ID
    $user = $facebook->getUser();

    if ($user) {
      try {
        // Proceed knowing you have a logged in user who's authenticated.
        $user_profile = $facebook->api('/me');
      } catch (FacebookApiException $e) {
        error_log($e);
        $user = null;
      }
    }

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
