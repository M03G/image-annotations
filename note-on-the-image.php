<?php
/**
 * Note on the image
 *
 *
 * @wordpress-plugin
 * Plugin Name: Note on the image
 * Plugin URI:  http://m03g.guriny.ru/
 * Description: Добавление комментариев к изображениям, что есть в комментариях :)
 * Version:     0.78
 * Author:      M03G
 * Author URI:  http://m03g.guriny.ru/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action('wp_enqueue_scripts', 'add_scripts' );
add_action('wp_enqueue_scripts', 'add_style' );
add_filter('the_content', 'add_form');
add_action('wp_ajax_add_comm_on_i', 'my_action_callback');
add_action('wp_ajax_del_ai_text', 'delete_text');

add_filter( 'comments_array', 'display_comment_on_image');


function display_comment_on_image($comments) {
		if( count( $comments ) > 0 ) {
			global $current_user;
			foreach( $comments as $comment ) {
				$new_ul = $list_div = '';
				if( true == get_comment_meta( $comment->comment_ID, 'comm_on_im' ) ) {
					global $wpdb;
					$annotations = $wpdb->get_results("
                        SELECT * FROM $wpdb->commentmeta WHERE comment_id = " . $comment->comment_ID . "
                        AND meta_key = 'comm_on_im'
                        ORDER by meta_id 
                    ");
					$new_ul .= '<ul>';
					foreach ($annotations as $annot) {
						$unsercomm = unserialize(unserialize($annot->meta_value));
						$del = '';
						if ($current_user->user_login == $unsercomm['user']['name']) {
							$del = '<div class="ia-del-comm" nonce="' . wp_create_nonce("noncedel") . '">x</div>';
						}
						$new_ul .= '<li coi-id="' . $annot->meta_id . '" class="coi coi-text"><span class="date" title="Время пользователя: '. $unsercomm['comm']['usertime'] . '">'. date("d.m.Y H:i", $unsercomm['comm']['time']) . '</span><span class="author">' . $unsercomm['user']['dname'] . ':</span><span class="text">' . $unsercomm['comm']['text'] . '</span>' . $del . '</li>';
						$list_div .= '<div class="coi coi-area" coi-id="' . $annot->meta_id . '" style="top:' . $unsercomm['comm']['top'] . 'px;left:' . $unsercomm['comm']['left'] . 'px;width:' . $unsercomm['comm']['sidew'] . 'px;height:' . $unsercomm['comm']['sideh'] . 'px;"></div>';
					}
					$new_ul .= '</ul>';
					$array_cont = explode('<p class="comment-image">', $comment->comment_content);
					$comment->comment_content = $array_cont[0] . '<div class="kostyl"><p class="comment-image">' . $array_cont[1] . '<div class="area-vis-switch hide" vis="on"></div>' . $list_div . '<div class="comm-vis-switch hide" vis="on"></div></div>';

					$comment->comment_content .= '<div class="comm_on_in">';
					$comment->comment_content .= $new_ul;
					$comment->comment_content .= '</div>';
				}
			}
		}
	return $comments;
}

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
		wp_enqueue_style( 'note-on-the-image-css');
		wp_register_style( 'jqueryuicss', plugins_url( '/note-on-the-image/js/jqueryui/jquery-ui.min.css' ));
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

	$newcomm['comm']['text'] = $_POST['text'];
	$newcomm['comm']['top'] = $_POST['top'];
	$newcomm['comm']['left'] = $_POST['left'];
	$newcomm['comm']['sidew'] = $_POST['sidew'];
	$newcomm['comm']['sideh'] = $_POST['sideh'];
	$newcomm['comm']['img'] = substr($_POST['img'], 8) + 0;
	$newcomm['comm']['time'] = time();
	$newcomm['comm']['usertime'] = $_POST['usertime'];

	$comm = serialize($newcomm);
	add_comment_meta($newcomm['comm']['img'], 'comm_on_im', $comm);	
}

function delete_text() {
	if (!wp_verify_nonce($_POST['nonce'], "noncedel")) {
		exit(":(");
	} 
	global $current_user;
	global $wpdb;
	$idcommimg = substr($_POST['commimg'], 8) + 0;
	$idcommia = $_POST['delid'];
	$annotations = $wpdb->get_row("SELECT * FROM $wpdb->commentmeta WHERE meta_id = " . $idcommia . " AND comment_id = " . $idcommimg . " AND meta_key = 'comm_on_im' LIMIT 1");
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
			$form.= '<textarea id="commImage" placeholder="Комментарий к изображению."></textarea><button class="cancelComm" type="cancel">cancel</button><button nonce=' . wp_create_nonce("nonceok") . ' class="okComm">ok</button>';
		}

		$form.= '</div>';

		$content.= $form;
	}
	return $content;
}
