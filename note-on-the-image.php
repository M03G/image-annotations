<?php
/**
 * Note on the image
 *
 *
 * @wordpress-plugin
 * Plugin Name: Note on the image
 * Plugin URI:  http://m03g.guriny.ru/
 * Description: Добавление комментариев к изображениям, что есть в комментариях :)
 * Version:     0.1
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
add_action( 'wp_enqueue_scripts', 'add_scripts' );

	/**
	 * Adds the public JavaScript to the single post page.
	 */
	function add_scripts() {

		if( is_single() || is_page() ) {

			wp_register_script( 'note-on-the-image', plugins_url( '/note-on-the-image/js/plugin.min.js' ), array( 'jquery' ) );
			wp_enqueue_script( 'note-on-the-image' );

		} // end if

	} // end add_scripts