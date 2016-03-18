<?php
/**
 * Plugin Name: Globie Hype Beast
 * Plugin URI:
 * Description: Check for popular posts fetching data from facebook and page views
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

    // *************************************

    // Add modifier section
    add_settings_section(
      'ghypebeast_modifier_section',
      __( 'Hype value modifiers', 'wordpress' ),
      array( $this, 'settings_modifier_section_callback' ),
      'ghypebeast_options_page'
    );

    // Register option: facebook hype modifier
    register_setting( 'ghypebeast_options_page', 'ghypebeast_settings_fb_modifier' );

    // facebook hype modifier field
    add_settings_field(
      'ghypebeast_fb_modifier',
      __( 'Facebook Like modifer value', 'wordpress' ),
      array( $this, 'ghypebeast_settings_fb_modifier_render' ),
      'ghypebeast_options_page',
      'ghypebeast_modifier_section'
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

  // Facebook modifier field render
  public function ghypebeast_settings_fb_modifier_render() {
    // Get options saved
    $facebook_modifer = get_option( 'ghypebeast_settings_fb_modifier' );
    // Render fields
    echo "<fieldset>";
    echo '<label for="ghypebeast_settings_fb_modifier" style="width: 100%;"><input type="text" style="width: 100%;" name="ghypebeast_settings_fb_modifier" id="ghypebeast_settings_fb_modifier" value="' . $facebook_modifer  . '"></label><br />';
    echo "</fieldset>";
  }

  public function settings_facebook_section_callback() {
    echo __( '', 'wordpress' );
  }

  public function settings_modifier_section_callback() {
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
    // Check is user is logged
    $isAdmin = '';
    if ( current_user_can('administrator') || current_user_can('editor') || current_user_can('author') || current_user_can('contributor') ) {
      $isAdmin = 1;
    }

    //echo admin_url('admin-ajax.php'); die;
    wp_enqueue_script( 'globie_hype_script', plugin_dir_url( __FILE__ ) . 'globie-hype-beast.js', array( 'jquery' ) );
    wp_localize_script( 'globie_hype_script', 'IGV_Hype_Vars', array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'isAdmin' => $isAdmin
    ) );
  }

  public function incr_page_views_callback() {
    $permalink = $_POST['permalink'];
    // needs error handling for bad url
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

  public function get_facebook_data($post_id) {
    $url = get_permalink($post_id);
    $query = "select total_count,like_count,comment_count,share_count,click_count from link_stat where url='{$url}'";
    $call = "https://api.facebook.com/method/fql.query?query=" . rawurlencode($query) . "&format=json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $call);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $output = json_decode($output);
    return $output[0];
  }

  // not currently used as needs access tokening
  public function get_facebook_data_via_API($post_id) {

    $app_id = get_option( 'ghypebeast_settings_fb_app_id' );
    $app_secret = get_option( 'ghypebeast_settings_fb_app_secret' );

    $facebookSession = new Facebook\Facebook([
      'app_id'  => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.5',
    ]);

    $response = $facebookSession->sendRequest(
      'GET',
      '/',
      array (
        'id' => get_permalink($post_id),
    ));

    error_log(print_r($response, TRUE));

    $graphObject = $response->getGraphObject();

    error_log(print_r($graphObject, TRUE));

    return($graphObject);

  }

  public function update_facebook_hype($post_id) {

    $data = $this->get_facebook_data($post_id);

    if (!empty($data->total_count)) {
      update_post_meta($post_id, 'ghb_fb_total_count', $data->total_count);
    }

  }

  public function update_hype($post_id) {
    // Get views count
    $views = get_post_meta($post_id,'ghb_views_count', true);

    $this->update_facebook_hype($post_id);

    // Get fb likes from meta
    $fb_hype = get_post_meta($post_id,'ghb_fb_total_count', true);

    // Get modifiers
    $facebook_modifer = get_option( 'ghypebeast_settings_fb_modifier' );
    if (empty($facebook_modifer)) {
      $facebook_modifer = 10;
    }
    // Sum up
    $hype = $views + ($fb_hype * $facebook_modifer);

    // Update hype
    update_post_meta($post_id,'ghb_hype',$hype);

  }

}

$gHPosts = new Globie_Hype_Beast();

function log_me($message) {
  if (WP_DEBUG === true) {
    if (is_array($message) || is_object($message)) {
      error_log(print_r($message, true));
    } else {
      error_log($message);
    }
  }
}

?>
