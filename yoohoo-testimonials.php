<?php
/**
 * Plugin Name: YooHoo Testimonails
 * Description: Create custom testimonials - UPDATE THIS
 * Plugin URI: https://arctek.co.za
 * Author: N/A
 * Author URI: https://arctek.co.za
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yoohoo-testimonials
 *
 *
 * YooHoo Testimonials is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * YooHoo Testimonials is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with YooHoo Testimonials. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) or exit;

class Yoohoo_Testimonials{

	private static $instance = null;

	public function __construct(){

		define('YOOHOO_TESTIMONIALS_PLUGIN_DIR', plugins_url().'/yoohoo-testimonials');

		/**
		* General Hooks
		**/

		add_action( 'init', array( $this, 'yoohoo_setup_post_types' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'yoohoo_testimonials_enqueues') );


		/**
		* Admin hooks only
		**/

		add_action( 'admin_menu', array( $this, 'yoohoo_testimonials_settings_add_to_menu' ) );
		add_action( 'add_meta_boxes', array( $this, 'yoohoo_testimonials_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'yoohoo_testimonials_save_meta_boxes' ), 1, 2 );

		add_action( 'wp_ajax_change_yoohoo_testimonial', array( $this, 'yoohoo_shortcode' ) );
		add_action( 'wp_ajax_nopriv_change_yoohoo_testimonial', array( $this, 'yoohoo_shortcode' ) );


		/**
		* Other hooks/shortcodes
		**/

		add_shortcode( 'yoohoo-testimonials', array( $this, 'yoohoo_shortcode' ) );

	}

	public static function get_instance() {
		if ( null == self::$instance ) {
		    self::$instance = new self;
		}
	return self::$instance;
	}

	/*
	* Create Custom Post Type
	*/
	function yoohoo_setup_post_types() {

		$labels = array(
			'name'                => _x( 'Testimonials', 'Post Type General Name', 'yoohoo-testimonials' ),
			'singular_name'       => _x( 'Testimonial', 'Post Type Singular Name', 'yoohoo-testimonials' ),
			'menu_name'           => __( 'Testimonials', 'yoohoo-testimonials' ),
			'add_new_item'        => __( 'Add New Testimonial', 'yoohoo-testimonials' ),
			'add_new'             => __( 'Add New Testimonial', 'yoohoo-testimonials' ),
			'all_items'           => __( 'All Testimonials', 'yoohoo-testimonials' ),
			'view_item'           => __( 'View Testimonial', 'yoohoo-testimonials' ),
			'edit_item'           => __( 'Edit Testimonial', 'yoohoo-testimonials' ),
			'update_item'         => __( 'Update Testimonial', 'yoohoo-testimonials' ),
			'search_items'        => __( 'Search Testimonials', 'yoohoo-testimonials' ),
			'not_found'           => __( 'Not Found', 'yoohoo-testimonials' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'yoohoo-testimonials' ),
		);

		$args = array(
			'label'               => __( 'yoohoo-testimonials', 'yoohoo-testimonials' ),
			'description'         => __( 'Testimonials for anatomy', 'yoohoo-testimonials' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'revisions' ),
			'taxonomies'          => array( 'custom-taxonomy' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
	    	'rewrite'             => array( 'slug' => 'testimonials' ),
	    	'menu_icon'			  => 'dashicons-format-quote',
		 );

		register_post_type( 'yoohoo-testimonials', $args );

	}

	function yoohoo_testimonials_add_meta_boxes(){
		add_meta_box( 'yoohoo-testimonials-name', __( 'Name', 'yoohoo-testimonials' ), array( $this, 'yoohoo_testimonials_meta_boxes' ), 'yoohoo-testimonials', 'side', 'low');
	}

	function yoohoo_testimonials_meta_boxes(){
		global $post;

		echo '<input type="hidden" name="yoohoo_testimonials_nonce_name" id="yoohoo_testimonials_nonce_name" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		$name = get_post_meta( $post->ID, '_name', true );

		echo '<input type="text" name="_name" value="' . $name . '" class="widefat" />';

	}

	function yoohoo_testimonials_save_meta_boxes( $post_id, $post ){
		
		if ( isset($_POST['yoohoo_testimonials_nonce_name']) && !wp_verify_nonce( $_POST['yoohoo_testimonials_nonce_name'], plugin_basename(__FILE__) ) ) {
			return $post->ID;
		}

		if ( !current_user_can( 'edit_post', $post->ID ) ){
			return $post->ID;	
		}

		//create an array for the post meta - this allows for it to be easily extendable
		if( isset( $_POST['_name'] ) ){

			$yoohoo_testimonials_meta['_name'] = esc_attr( $_POST['_name'] );

			foreach ( $yoohoo_testimonials_meta as $key => $value ) {

				if( $post->post_type == 'revision' ){
					return;	
				}

				$value = implode( ',', ( array ) $value );

				if( get_post_meta( $post->ID, $key, FALSE ) ) {
					update_post_meta( $post->ID, $key, $value );
				} else { 
					add_post_meta( $post->ID, $key, $value );
				}

				if ( !$value ){
					delete_post_meta( $post->ID, $key );
				} 
			}
		}	
		
	}


	function yoohoo_testimonials_enqueues(){
		wp_register_style( 'yoohoo-testimonials-main', YOOHOO_TESTIMONIALS_PLUGIN_DIR . '/css/yoohoo-testimonials-main.css' );
		wp_enqueue_style( 'yoohoo-testimonials-main' );

		wp_enqueue_script( 'yoohoo-testimonials-frontend', YOOHOO_TESTIMONIALS_PLUGIN_DIR . '/js/yoohoo-testimonials-frontend.js', array( 'jquery' ));
		wp_localize_script( 'yoohoo-testimonials-frontend', 'yoohoo_testimonials_ajaxurl', admin_url('admin-ajax.php') );
	}

	function yoohoo_testimonials_settings_add_to_menu(){
		add_submenu_page( 'edit.php?post_type=yoohoo-testimonials', __( 'Testimonial Settings', 'yoohoo-testimonials' ), __( 'Settings', 'yoohoo-testimonials' ), 'manage_options', 'yoohoo_testimonial_setttings', array( $this, 'yoohoo_testimonials_settings_page' ) );
	}

	function yoohoo_testimonials_settings_page(){
		include ( YOOHOO_TESTIMONIALS_PLUGIN_DIR . '/adminpages/yoohoo-testimonials-settings-page.php' );
	}


	//move to a class?
	function yoohoo_shortcode( $atts ) {
		
		$paged = 1;
		$per_page = 1;
		
		if(isset($_POST['action'])){
			if($_POST['action'] == 'change_yoohoo_testimonial'){

				if(isset($_POST['page_num'])){
					$paged = $_POST['page_num'];

				}

			}
		}
		

		$yoohoo_atts = shortcode_atts(
			array(
				'id' => '',
				'per_page' => $per_page,
				), $atts
			);

		$args = array( 
			'post_type' => 'yoohoo-testimonials',
			'posts_per_page' => $yoohoo_atts['per_page'],
			'p' => $yoohoo_atts['id'],
			'paged' =>$paged
			);


		$the_query = new WP_Query( $args );
		
		
		
		if ( $the_query->have_posts() ) {

			if( !isset($_POST['action']) ){
			//if changing with Ajax do not load the parent wrapper because it keeps on nesting every Ajax call. - Look into this.
			
		?> 


		
			<div class="yoohoo-testimonials-wrapper"> 
		<?php
	}

			while ( $the_query->have_posts() ) {

				$the_query->the_post();

				$testimonial_persons_name = get_post_meta( get_the_ID(), '_name', true );
				$testimonial_thumbnail = get_the_post_thumbnail( null, 'thumbnail', array( "class" => "yoohoo-testimonials-thumbnail" ) );
				$testimonial_thumbnail_placeholder = YOOHOO_TESTIMONIALS_PLUGIN_DIR . '/assets/img/blank_avatar.jpg';

				echo '<center>';
				if( !empty( $testimonial_thumbnail ) ){
				?>
					<div class="yoohoo-testimonials-profile">
				<?php
				echo $testimonial_thumbnail; 
				?>
				</div>
				<?php

				}else{
				echo '<div class="yoohoo-testimonials-profile"> <img class="yoohoo-testimonials-thumbnail wp-post-image" width="150" height="150" src="' .$testimonial_thumbnail_placeholder. '"></div>';
				}

				?>
				<div class="yoohoo-testimonials-content">
				<?php

				echo '<span class="yoohoo-testimonials-title"><h2>' . get_the_title() . '</h2></span>';
				echo '<span class="yoohoo-testimonials-description">"' . get_the_content() . '"</span><br>';
				if( !empty( $testimonial_persons_name ) ){
					echo '<span class="yoohoo-testimonials-author">' . $testimonial_persons_name . '</span>';
				}
				echo '</center>';
				

				if(isset($_POST['action'])){
					if($_POST['action'] == 'change_yoohoo_testimonial'){
						wp_die();						
					}

				}
			}
			?>
				</div>
			<ul class="yoohoo-pagination">
			<?php

				/**
				* Code for custom Pagination
				*/
				$paging ='';
				for( $page_count = 1; $page_count <= $the_query->max_num_pages; $page_count++ ){
					$paging .= '<li><a href="javascript:void(0);" class="yoohoo_testimonial_paginate" id="testimonial_page_' . $page_count . '" title="' . __( 'Page', 'yoohoo-testimonials' ) . ' ' .$page_count .'" pgnum="' . $page_count . '" ppage="'. $per_page .'">' . $page_count . '</a></li>';
				}
				return $paging;
				?></ul><?php 
		} else {
			// no posts found
			_e( 'No testimonials found', 'yoohoo-testimonials' );
		}

		?>
			
		</div>
		<?php
	}

} //class end

$yoohoo_testimonials = new Yoohoo_Testimonials();

