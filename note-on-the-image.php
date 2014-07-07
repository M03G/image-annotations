<?php
/**
 * Note on the image
 *
 *
 * @wordpress-plugin
 * Plugin Name: Note on the image
 * Plugin URI:  http://m03g.guriny.ru/
 * Description: Добавление комментариев к изображениям, что есть в комментариях :)
 * Version:     0.12
 * Author:      M03G
 * Author URI:  http://m03g.guriny.ru/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// // If this file is called directly, abort.
// if ( ! defined( 'WPINC' ) ) {
// 	die;
// } // end if

// require_once( plugin_dir_path( __FILE__ ) . 'class-comment-image.php' );
// Comment_Image::get_instance();
error_log("!!!!");
add_action('wp_enqueue_scripts', 'add_scripts' );
add_action('wp_enqueue_scripts', 'add_style' );
add_filter('the_content', 'add_form');

	/**
	 * Adds the public JavaScript to the single post page.
	 */
function add_scripts() {
	if( is_single() || is_page() ) {
		wp_register_script( 'note-on-the-image', plugins_url( '/note-on-the-image/js/plugin.min.js' ), array( 'jquery' ) );		
		wp_enqueue_script( 'note-on-the-image' );
		wp_register_script( 'jqueryui', plugins_url( '/note-on-the-image/js/jqueryui/jquery-ui.min.js' ), array( 'jquery' ) );
		wp_enqueue_script( 'jqueryui' );
	}
}

function add_style() {
	if( is_single() || is_page() ) {
		wp_register_style( 'note-on-the-image-css', plugins_url( '/note-on-the-image/css/style.css' ));		
		wp_enqueue_style( 'note-on-the-image-css' );
		wp_register_style( 'jqueryuicss', plugins_url( '/note-on-the-image/js/jqueryui/jquery-ui.min.css' ));
		wp_enqueue_style( 'jqueryuicss' );
	}
}

function add_form($content) {

	$form = '<div class="anotText">';

	if (!is_user_logged_in()) {
		$form.= '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>';
	} else {
		$form.= '<textarea id="commImage" placeholder="Комментарий к изображению."></textarea><button class="cancelComm" type="cancel">cancel</button><button class="okComm">ok</button>';
	}

	$form.= '</div>';

	$content.= $form;
	return $content;
}
