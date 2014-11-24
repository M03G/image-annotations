<?php
/**
 * Image Annotations
 *
 *
 * @wordpress-plugin
 * Plugin Name: Image Annotations
 * Plugin URI:  http://m03g.guriny.ru/image-annotations/
 * Description: Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments. Important: for now the plugin works only with Comment Images plugin (by Tom McFarlin).
 * Version:     1.00
 * Author:      M03G
 * Author URI:  http://m03g.guriny.ru/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action('wp_enqueue_scripts', 'add_scripts' );
add_action('wp_enqueue_scripts', 'add_style' );
add_filter('the_content', 'add_form');
add_action('wp_ajax_add_annotation', 'my_action_callback');
add_action('wp_ajax_del_annotation', 'delete_text');

add_filter( 'comments_array', 'display_annotation');


function display_annotation($comments) {
		if( count( $comments ) > 0 ) {
			global $current_user;
			foreach( $comments as $comment ) {
				$new_ul = $list_div = '';
				if( true == get_comment_meta( $comment->comment_ID, 'annotation_to_image' ) ) {
					global $wpdb;
					$annotations = $wpdb->get_results("SELECT * FROM $wpdb->commentmeta WHERE comment_id = " . $comment->comment_ID . " AND meta_key = 'annotation_to_image' ORDER by meta_id");
					$new_ul .= '<ul>';
					foreach ($annotations as $annot) {
						$unsercomm = unserialize(unserialize($annot->meta_value));
						$del = '';
						if ($current_user->user_login == $unsercomm['user']['name']) {
							$del = '<div title="Удалить комментарий" class="ia-del" nonce="' . wp_create_nonce("noncedel") . '"></div>';
						}
						$new_ul .= '<li ia-id="' . $annot->meta_id . '" class="ia ia-annotation"><span class="ia-date" title="Время пользователя: '. $unsercomm['annotation']['usertime'] . '">'. date("d.m.Y H:i", $unsercomm['annotation']['time']) . '</span><span class="ia-author">' . $unsercomm['user']['dname'] . ':</span><span class="ia-text">' . $unsercomm['annotation']['text'] . '</span>' . $del . '</li>';
						$list_div .= '<div class="ia ia-area" title="' . $unsercomm['annotation']['text'] . '" ia-id="' . $annot->meta_id . '" style="top:' . $unsercomm['annotation']['top'] . 'px;left:' . $unsercomm['annotation']['left'] . 'px;width:' . $unsercomm['annotation']['sidew'] . 'px;height:' . $unsercomm['annotation']['sideh'] . 'px;"></div>';
					}
					$new_ul .= '</ul>';
					$array_cont = explode('<p class="comment-image">', $comment->comment_content);
					$comment->comment_content = $array_cont[0] . '<div class="ia-main"><p class="comment-image">' . $array_cont[1] . '<div class="ia-area-vis-switch hide" vis="on"></div>' . $list_div . '<div class="ia-annotations-vis-switch hide" vis="on"></div></div>';
					$comment->comment_content .= '<div class="ia-annotations">';
					$comment->comment_content .= $new_ul;
					$comment->comment_content .= '</div>';
				}
			}
		}
	return $comments;
}

function add_scripts() {
	if( is_single() || is_page() ) {
		wp_register_script( 'image-annotation', plugins_url( '/image-annotations/js/plugin.min.js' ), array( 'jquery' ) );		
		wp_enqueue_script( 'image-annotation' );
		wp_register_script( 'jqueryui', plugins_url( '/image-annotations/js/jqueryui/jquery-ui.min.js' ), array( 'jquery' ) );
		wp_enqueue_script( 'jqueryui' );
	}
}

function add_style() {
	if( is_single() || is_page() ) {
		wp_register_style( 'image-annotation-css', plugins_url( '/image-annotations/css/style.css' ));		
		wp_enqueue_style( 'image-annotation-css');
		wp_register_style( 'jqueryuicss', plugins_url( '/image-annotations/js/jqueryui/jquery-ui.min.css' ));
		wp_enqueue_style( 'jqueryuicss' );
	}
}

function my_action_callback() {
	if (!wp_verify_nonce($_POST['nonce'], "nonceok")) {
		exit(":(");
	} 
	global $current_user;
	$newcomm = array();

	$newcomm['user']['id'] = $current_user->ID;
	$newcomm['user']['name'] = $current_user->user_login;
	$newcomm['user']['dname'] = $current_user->display_name;

	$newcomm['annotation']['text'] = $_POST['text'];
	$newcomm['annotation']['top'] = $_POST['top'];
	$newcomm['annotation']['left'] = $_POST['left'];
	$newcomm['annotation']['sidew'] = $_POST['sidew'];
	$newcomm['annotation']['sideh'] = $_POST['sideh'];
	$newcomm['annotation']['img'] = substr($_POST['img'], 8) + 0;
	$newcomm['annotation']['time'] = time();
	$newcomm['annotation']['usertime'] = $_POST['usertime'];

	$annotation = serialize($newcomm);
	add_comment_meta($newcomm['annotation']['img'], 'annotation_to_image', $annotation);	
}

function delete_text() {
	if (!wp_verify_nonce($_POST['nonce'], "noncedel")) {
		exit(":(");
	} 
	global $current_user;
	global $wpdb;
	$idcommimg = substr($_POST['commimg'], 8) + 0;
	$idcommia = $_POST['delid'];
	$annotations = $wpdb->get_row("SELECT * FROM $wpdb->commentmeta WHERE meta_id = " . $idcommia . " AND comment_id = " . $idcommimg . " AND meta_key = 'annotation_to_image' LIMIT 1");
	$unsercomm = unserialize(unserialize($annotations->meta_value));
	if ($current_user->user_login == $unsercomm['user']['name']) {
		$wpdb->delete( 'wp_commentmeta', array( 'meta_id' => $idcommia ), array( '%d' ) );
	}
}

function add_form($content) {
	if( is_single() || is_page() ) {

		$form = '<div class="anotText">';

		if (!is_user_logged_in()) {
			$form.= '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>';
		} else {
			$form.= '<textarea id="ia-textarea" placeholder="Комментарий к изображению."></textarea><button class="ia-cancel" type="cancel">cancel</button><button nonce=' . wp_create_nonce("nonceok") . ' class="ia-ok">ok</button>';
		}

		$form.= '</div>';

		$content.= $form;
	}
	return $content;
}
