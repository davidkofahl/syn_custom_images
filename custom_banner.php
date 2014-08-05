<?php
/*
Plugin Name: Custom Page Banners
Description: Adds Editable Banners with Titles and Captions to Pages 
Version: 0.2
Author: Pure Cobalt
Author URI: http://purecobalt.com
*/

if( !class_exists( 'PC_page_banner_images' ) ) {
    
    class PC_page_banner_images {
        private $id                 = '';
        private $post_type          = '';
        private $labels             = array();
        private $base_label         = '';
        private $slug               = '';
        private $metabox_id         = '';
        private $post_meta_key      = '';
        private $post_meta_flag     = '';
        private $calling_post       = 0;
        private $nonce              = '';
        private $total              = 0;

        private $default_args       = array(
          'base_label'    => 'Banner Image',
          'total'         => 5,
          'post_type'     => 'page',
          'width'         => 650,
          'meta_class'    => 'custom-banner',
          'height'        => 400,
          'fields'        => array(
            '_title'                => true,
            '_image'                => true,
            '_link'                 => true,
            '_subtitle'             => false,
            '_subtitle_link'        => false,
            '_caption'              => true,
            '_caption_link'         => false,
						'_additional'           => false,
						'_second_addition_text' => false,
						'_second_addution_link' => false
          )
        );
        
        /**
         * Initialize the plugin
         * 
         */

        public function __construct($args) {
          $args                 = wp_parse_args( $args, $this->default_args );
          extract( $args, EXTR_SKIP );

          $this->base_label     = str_replace (' ', '_', strtolower($base_label));

          $this->labels         = array(
            'name' => $base_label,
            'set' => 'Set Image',
            'remove' => 'Remove ' . $base_label,
            'use' => 'Use as ' . $base_label
          );

          $this->post_type      = 'pc_' . $this->base_label;
          $this->slug           = str_replace (' ', '-', strtolower($base_label));
          $this->id             = $this->base_label;
          $this->metabox_id     = $this->base_label;
          $this->post_meta_key  = 'pc_'; 
          $this->post_meta_flag = 'pc_' . $this->base_label;
          $this->total          = $total;
          $this->nonce          = 'pc_' . $this->id . $this->base_label;
          $this->fields         = $fields;
          $this->meta_class     = $meta_class;
          $this->post_type_edit = $post_type; // specify post_type that metaboxes are available for; defaults to page;
          
          if( !current_theme_supports( 'post-thumbnails' ) ) {
            add_theme_support( 'post-thumbnails' );
          }

          if ( function_exists( 'add_image_size' ) ) { 
            add_image_size( $this->base_label, $width, $height, true );  
          }
          
          $this->admin_init();

          add_action( 'wp_ajax_pc_insert_banner_' . $this->base_label, array( $this, 'ajax_insert_banner' ) );
          add_action( 'wp_ajax_pc_delete_banner_' . $this->base_label, array( $this, 'ajax_delete_banner' ) );
          add_action( 'wp_ajax_pc_update_banner_' . $this->base_label, array( $this, 'ajax_update_banner' ) );
//          add_action( 'delete_attachment', array( $this, 'delete_banner' ) );
          
        }

        /**
         * Add admin-Javascript and Custom Post-type for Banner
         * 
         * @return void 
         */
       public function admin_init() {
          wp_enqueue_script(
          'media-uploader',
            plugins_url( 'pc_custom_banners' ).'/js/media-uploader.js',
            'jquery'
          );

          wp_enqueue_style(
           'banner-styles',
           plugins_url( 'pc_custom_banners' ).'/css/style.css'
         );

        $this->banner_post_type();
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

      }

         /**
         * Add admin-Javascript
         * 
         * @return void 
         */

        public function banner_post_type() {
          $labels = array(
            'name'               => $this->base_label . 's',
            'singular_name'      => $this->base_label,
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New ' . $this->base_label,
            'edit_item'          => 'Edit ' . $this->base_label,
            'new_item'           => 'New ' . $this->base_label,
            'all_items'          => 'All ' . $this->base_label . 's',
            'view_item'          => 'View ' . $this->base_label,
            'search_items'       => 'Search ' . $this->base_label . 's',
            'not_found'          => 'No ' . $this->base_label . 's found',
            'not_found_in_trash' => 'No ' . $this->base_label . 's found in Trash',
            'parent_item_colon'  => '',
            'menu_name'          => $this->base_label . 's'
          );

          $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => $this->slug ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt')
          );

          register_post_type( 'pc_' . $this->post_type, $args );
        }

        /**
         * Add admin metabox for choosing additional featured images
         * 
         * @return void 
         */
        public function add_meta_boxes(){
          for ($counter = 1; $counter <= $this->total; $counter++){
            $metabox_id = $this->metabox_id . '-' . $counter;
            add_meta_box(
              $metabox_id,
              $this->labels['name'] . ' ' . $counter,
              array( $this, 'meta_box_content' ),
                $this->post_type_edit,
                'normal',
                'core',
                array( 'id' => $metabox_id )
            );

            add_filter('postbox_classes_' . $this->post_type_edit . '_' . $metabox_id, array($this, 'add_metabox_classes'));
          }
          
        }

        public function add_metabox_classes($classes) {
          array_push($classes, $this->meta_class);
          return $classes;
        }

        /**
         * Output the metabox content
         * 
         * @global object $post 
         * @return void
         */

        public function meta_box_content($post, $metabox) {
          
          $meta_key = $this->post_meta_key . $metabox['id'];
          $banner_id = get_post_meta(
            $post->ID,
            $meta_key,
            true
          );

          $args = array();
          $banner_post = get_post($banner_id);

          if( !empty( $banner_id ) ) {

            $post_meta  = get_post_custom( $banner_id );

            $subtitle      = esc_attr($banner_post->post_excerpt);
            $title         = esc_attr($banner_post->post_title);
            $caption       = esc_attr($banner_post->post_content);
            $add_copy      = esc_attr($post_meta[$this->post_meta_flag . '-add_link_copy'][0]);
            $image_id      = $post_meta[$this->post_meta_flag . '-image'][0];
            $add_link      = esc_attr($post_meta[$this->post_meta_flag . '-add_link'][0]);
            $sub_link      = $post_meta[$this->post_meta_flag . '-subtitle_link'][0];
            $cap_link      = $post_meta[$this->post_meta_flag . '-caption_link'][0];
						$img_link      = $post_meta[$this->post_meta_flag . '-img_link'][0];
						$sec_copy      = $post_meta[$this->post_meta_flag . '-sec_copy'][0];
						$sec_link      = $post_meta[$this->post_meta_flag . '-sec_link'][0];


            $args = array(
              'banner_id' => $banner_id,
              'title'     => $title,
              'subtitle'  => $subtitle,
              'caption'   => $caption,
              'image_id'  => $image_id,
              'add_copy'  => $add_copy,
              'add_link'  => $add_link,
              'img_link'  => $img_link,
              'sub_link'  => $sub_link,
							'cap_link'  => $cap_link,
							'sec_link'  => $sec_link,
							'sec_copy'  => $sec_copy,
            );

          }

          $args['metabox']    = $metabox;
          $args['parent_id']  = $post->ID;

          echo $this->meta_box_output($args);
        }
        
        /**
         * Utility to get pages as options list
         * 
         * @global int $post_ID
         * @param int button_id
         * @param string button_excerpt, button_id, button_href
         * @return string 
         */
        public function get_select_pages($post_ID, $image_content = NULL){
          
          $page = get_page_by_title('Home');
          $home_id = $page->ID;

          $args = array(
            'depth'        => 0,
            'title_li'     => __(''),
            'echo'         => 0,
            'exclude'      => $home_id,
            'walker'       => new PC_Walker_Pages(),
            'button_url'   => $image_content,
            'post_type'    => 'page',
            'post_status'  => 'publish'
          );

          #$args['child_of'] = $post_ID;
          $options = wp_list_pages($args);
          return $options;
        }

        public function create_extra_field($placement, $id, $link = NULL){

          $val = is_null($link) ? "http://" : $link;

          $output.= '<p>Enter a URL for the ' . $placement . ' <em>(optional)</em>:</p>';
          $output.= '<p>';
          $output.= '<input id="'. $id . '" type="text" class="widefat" width="30" value="' . $val . '" /><br>';
          $output.= '</p>';

          return $output;
        }

        public function check_if_empty($link){
          return is_null( $link ) || empty( $link ) ? 'http://' : $link;
        }

        /**
         * Generate the metabox content
         * 
         * @global int $post_ID
         * @param takes $args requires
         * metabox, banner_id , title, caption, link, image_id, parent_id, nonce_field, 
         * @return string 
         */
        public function meta_box_output( $args ) {
          global $post_ID;

          $default_args = array(
            'metabox'           => NULL, 
            'banner_id'         => NULL,
            'title'             => NULL, 
            'caption'           => NULL, 
						'add_link'          => NULL,
					 	'sec_copy'          => NULL,	
					 	'sec_link'          => NULL,	
            'img_link'          => NULL, 
            'sub_link'          => NULL,
            'cap_link'          => NULL,
            'subtitle'          => NULL,
            'add_copy'          => NULL, 
            'image_id'          => NULL, 
            'parent_id'         => NULL,
            'nonce_field'       => NULL
          );

          $args = wp_parse_args( $args, $default_args );

          extract( $args, EXTR_SKIP );
          extract( $this->fields, EXTR_SKIP );

          if ( is_null( $parent_id ) ){
            $parent_id = $post_ID;
          }
        
          if (is_array($metabox)){
            $metabox = $metabox['id'];
          }

          if( is_null($nonce_field)){
            $nonce_field = wp_create_nonce( $this->nonce );
          }

          $sub_link = $this->check_if_empty($sub_link);
          $img_link = $this->check_if_empty($img_link);
          $cap_link = $this->check_if_empty($cap_link);
					$add_link = $this->check_if_empty($add_link);
					$sec_link = $this->check_if_empty($sec_link);

          $current_href = NULL;

          $editor_settings = array( 
            'media_buttons' => false,
            'wpautop'       => false,
            'textarea_name' => 'caption_editor',
            'tweeny'        => true
          );

          $output = '';

          if( $_title ){
            $output .= '<h4>Add a Title</h4>';
            $output .= '<input id="title-'. $metabox . '" type="text" class="widefat" width="30" value="' . $title . '" />';
          }

          if( $_image ){         
            $output .= '<h4>Add an Image</h4>';
            if( $image_id && get_post( $image_id ) ) {
              $src      =  wp_get_attachment_image_src( $image_id, 'full' );
              $image    = '<img class="banner-thumb-admin" id="thumb-'. $metabox . '" src=' . $src[0] . ' data-id="' . $image_id . '" />';
              $image   .= sprintf(
                '<a title="%1$s" href="#" onclick="customBanners.media_uploader(\'%2$s\', \'%3$s\', \'%4$s\'); return false;">Choose another Image</a>',
                $this->labels['set'],
                $metabox,
                $nonce_field,
                $parent_id
              );
              $delete = sprintf(
                '<a href="#" class="submitdelete deletion" id="remove-%2$s" onclick="customBanners.deleteBanner( \'%2$s\', \'%4$s\', \'%1$s\', \'%3$s\', \'%5$s\' ); return false;">%6$s</a>',
                $metabox,
                $parent_id,
                $nonce_field,
                $banner_id,
                $this->base_label, 
                $this->labels['remove']
              );
            } else {
              $image = sprintf(
                '<a title="%1$s" href="#" id="add_%2$s" onclick="customBanners.media_uploader(\'%2$s\', \'%3$s\', \'%4$s\' ); return false;" class="button">%5$s</a>',
                $this->labels['set'],
                $metabox,
                $nonce_field,
                $parent_id,
                $this->labels['set']
              );
              $delete = '';
            
            }
            $output .= '<p id="thumb-wrap-'. $metabox .'" class="hide-if-no-js button-thumb">';
            $output .= $image;
            $output .= '</p>';
          }
          
          if( $_link ){
            $output .= $this->create_extra_field('image', 'thumb-url-' . $metabox, $img_link);
          }

          if( $_subtitle ){
            $output .= '<h4>Add a subtitle</h4>';
            $output .= '<input id="subtitle-'. $metabox . '" type="text" class="widefat" width="30" value="' . $subtitle . '" />';
          }

          if( $_subtitle_link ){
            $output .= $this->create_extra_field('subtitle', 'subtitle-url-' . $metabox, $sub_link);
          }

          if( $_caption ){

            $output .= '<h4>Add a Caption</h4>';
            $output .= '<textarea id="caption-' . $metabox .'" type="text" rows="6" cols="30">' . $caption . '</textarea>';
          }

          if( $_caption_link ){
            $output .= $this->create_extra_field('caption', 'caption-url-' . $metabox, $cap_link);
          }
          /*
          $output.= '<h4>Select Banner Page Link</h4>';
          if ( ! is_null($current_href) ){
            $output .= '<input id="currentHref-' . $metabox . '" type="text" class="widefat" readonly value="' . $current_href . '" />';
          }
          $output.= '<p>Enter a URL or select a page:</p>';
          $output.= '<p>';
          $output.= '<input id="url-'. $metabox . '" type="text" class="widefat" width="30" value="http://" /><br>';
          $output.= '</p>';
   
          $output.= '<select id="page-'. $metabox . '">';
          $output.= '<option value="blank">---</option>';
          $output.= $this->get_select_pages($post_id);
          $output.= '</select>';
          */

          if( $_additional ){ 
            $output .= '<h4>Add an Additional Link</h4>';
            $output .= '<input id="additional-link-'. $metabox . '" type="text" class="widefat" width="30" value="' . $add_copy . '" />';
            $output .= $this->create_extra_field('additional link', 'additional-url-' . $metabox, $add_link);
					}

          if( $_additional ){ 
            $output .= '<h4>Add an Additional Link</h4>';
            $output .= '<input id="second-additional-link-'. $metabox . '" type="text" class="widefat" width="30" value="' . $sec_copy . '" />';
            $output .= $this->create_extra_field('second additional link', 'second-additional-url-' . $metabox, $sec_link);
          }

          $output .= '<p class="hide-if-no-js">';
          $output .= '<div class="featured-image-buttons submitbox">';
          $output .= '<a href="#" class="button-primary" id="save-'.$metabox.'-image" data-postid="'.$parent_id.'" data-metaboxid="'.$metabox.'" data-nonce="'.$nonce_field.'" data-bannerid="'.$banner_id.'" data-bannerlabel="'.$this->base_label.'">Save</a>';
          $output .= $delete;
          $output .= '</div>';
          $output .= '</p>'; 
          return $output;        
        }
    
        /**
         * ajax action to insert button into DB
         * @return void
         */

        public function ajax_insert_banner(){

          $parent_id      = intval( $_POST['post_id'] ); 
          $metabox        = $_POST['metabox'];
          $imglink        = $_POST['imglink'];
          $sublink        = $_POST['sublink'];
          $caplink        = $_POST['caplink'];
					$addlink        = $_POST['addlink'];
					$seclink			  = $_POST['seclink'];
					$seccopy        = $_POST['seccopy'];
          $caption        = $_POST['caption'];
          $title          = $_POST['title'];
          $subtitle       = $_POST['subtitle'];
          $add_copy       = $_POST['add_copy'];
          $image_id       = intval( $_POST['image_id'] );

          if( !current_user_can( 'edit_post', $parent_id ) ) {
            die( '-1' );
          }

          check_ajax_referer( $this->nonce, '_ajax_nonce' );
            
          preg_match("/([0-9]+[\.,]?)+/", $metabox, $menu_order);

          $post = array(
            'menu_order'   => $menu_order[0],
            'post_content' => $caption,
            'post_excerpt' => $subtitle,
            'post_parent'  => $parent_id,
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_type'    => $this->post_type
          );

          $banner_id = wp_insert_post( $post, false );

          if( !empty( $banner_id ) ) {
            
            $meta_key = $this->post_meta_key . $metabox;

            update_post_meta( $parent_id, $meta_key, $banner_id );
            add_post_meta( $parent_id, $this->post_meta_flag, $banner_id);
            update_post_meta( $banner_id, $this->post_meta_flag . '-image', $image_id);
            update_post_meta( $banner_id, $this->post_meta_flag . '-add_link_copy', $add_copy);
						update_post_meta( $banner_id, $this->post_meta_flag . '-add_link', $addlink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-sec_copy', $seccopy);
						update_post_meta( $banner_id, $this->post_meta_flag . '-sec_link', $seclink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-img_link', $imglink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-subtitle_link', $sublink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-caption_link', $caplink);

            $args = array(
              'metabox'   => $metabox,
              'banner_id' => $banner_id,
              'title'     => $title,
              'subtitle'  => $subitle,
              'add_copy'  => $add_copy,
              'caption'   => $caption,
              'add_link'  => $addlink,
              'image_id'  => $image_id,
              'parent_id' => $parent_id,
              'img_link'  => $imglink,
              'sub_link'  => $sublink,
							'cap_link'  => $caplink,
						 	'sec_copy'  => $seccopy,	
						 	'sec_link'  => $seclink,	
            );

            die( "{$banner_id}" );             
          }
          die ('0');  
        }

        /**
         * ajax action to update button in DB
         * @return void
         */

        public function ajax_update_banner(){

          $parent_id      = intval( $_POST['post_id'] ); 
          $banner_id      = intval( $_POST['banner_id'] ); 
          $metabox        = $_POST['metabox'];
          $imglink        = $_POST['imglink'];
          $sublink        = $_POST['sublink'];
          $caplink        = $_POST['caplink'];
          $addlink        = $_POST['addlink'];
          $caption        = $_POST['caption'];
          $title          = $_POST['title'];
          $subtitle       = $_POST['subtitle'];
					$add_copy       = $_POST['add_copy'];
					$seclink			  = $_POST['seclink'];
					$seccopy        = $_POST['seccopy'];					
          $image_id       = intval( $_POST['image_id'] );

          if( !current_user_can( 'edit_post', $parent_id ) ) {
            die( '-1' );
          }

          check_ajax_referer( $this->nonce, '_ajax_nonce' );

          $post = array(
            'ID'           => $banner_id,
            'post_content' => $caption,
            'post_excerpt' => $subtitle,
            'post_parent'  => $parent_id,
            'post_title'   => $title,
          );

          if( !empty( $banner_id ) ) {
            wp_update_post( $post );
            update_post_meta( $banner_id, $this->post_meta_flag . '-image', $image_id);
            update_post_meta( $banner_id, $this->post_meta_flag . '-add_link_copy', $add_copy);
            update_post_meta( $banner_id, $this->post_meta_flag . '-add_link', $addlink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-img_link', $imglink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-subtitle_link', $sublink);
						update_post_meta( $banner_id, $this->post_meta_flag . '-caption_link', $caplink);
            update_post_meta( $banner_id, $this->post_meta_flag . '-sec_copy', $seccopy);
						update_post_meta( $banner_id, $this->post_meta_flag . '-sec_link', $seclink);

            $args = array(
              'metabox'   => $metabox,
              'banner_id' => $banner_id,
              'title'     => $title,
              'subtitle'  => $subtitle,
              'caption'   => $caption,
							'add_link'  => $addlink,
						 	'sec_copy'  => $seccopy,	
						 	'sec_link'  => $seclink,						
              'add_copy'  => $add_copy,
              'image_id'  => $image_id,
              'parent_id' => $parent_id,
              'img_link'  => $imglink,
              'sub_link'  => $sublink,
              'cap_link'  => $caplink
            );

            die( "{$banner_id}" );             

          }
          die ('0');  

        }

         /**
         * ajax action to remove button from DB
         * @return void
         */
        public function ajax_delete_banner(){

          global $wpdb;

          $parent_id  = intval( $_POST['post_id'] ); 
          $metabox    = $_POST['metabox'];
          $banner_id  = intval( $_POST['banner_id'] );
          $nonce      = $_POST['_ajax_nonce'];
          
          if( !current_user_can( 'edit_post', $parent_id ) ) {
            die( '-1' );
          }

          check_ajax_referer( $this->nonce, '_ajax_nonce' );
          
          if( !empty( $banner_id ) ) {
  
            $meta_key = $this->post_meta_key . $metabox;
  
            wp_delete_post( $banner_id, true );
            delete_post_meta( $parent_id, $meta_key );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-image' );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-add_link_copy' );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-add_link' );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-img_link' );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-subtitle_link' );
						delete_post_meta( $banner_id, $this->post_meta_flag . '-caption_link' );
            delete_post_meta( $banner_id, $this->post_meta_flag . '-sec_copy', $seccopy);
						delete_post_meta( $banner_id, $this->post_meta_flag . '-sec_link', $seclink);

            $wpdb->query( 
              $wpdb->prepare( 
                "DELETE FROM $wpdb->postmeta 
                WHERE meta_key = '%s' AND meta_value = %d
                LIMIT 1", 
                $this->post_meta_flag,
                $banner_id
              )
            );

            $args = array(
              'metabox'   => $metabox,
              'parent_id' => $parent_id
            );


            die( $this->meta_box_output( $args ) );
          }

          die ('0');

      } 
  
      public function get_banners( $post_id, $meta_key ){
        $meta_key = str_replace(' ', '_', strtolower($meta_key));
        $args = array(
          'post_parent' => $post_id,
          'orderby'     => 'menu_order',
          'order'       => 'ASC',
          'post_type'   => 'pc_' . $meta_key
        );

				$banners = get_children( $args );
				$banners_stripped = array(); //WPML pulls in metadata for translation, need to verify getting only the current posts banners

				foreach($banners as $banner){
					if ($banner->post_parent == $post_id){
						array_push($banners_stripped, $banner);
					}
				}
        return $banners_stripped;
      }

    }

    function get_banners( $post_id, $meta_key ){
      return PC_page_banner_images::get_banners( $post_id, $meta_key );
    }
}
