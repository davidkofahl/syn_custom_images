<?php
/*
Plugin Name: Custom 
Description: Adds Editable Banners with Titles and Captions to Pages 
Version: 0.2
Author: Pure Cobalt
Author URI: http://purecobalt.com
*/

if( !class_exists( 'Syn_wp_images' ) ) {
    
    class Syn_wp_images {

        private $id                 = null;
        private $post_type          = null;
        private $labels             = array();
        private $base_label         = null;
        private $slug               = null;
        private $metabox_id         = null;
        private $post_meta_key      = null;
        private $post_meta_flag     = null;
        private $calling_post       = null;
        private $nonce              = null;

        public $plugin_dir          = 'syn_wp_custom_banners'; //dirname(__FILE__)

        private $default_args       = array(
            'base_label'    => 'Custom Banner',
            'post_type'     => 'page',
            'width'         => 650,
            'height'        => 400,
            'meta_class'    => 'custom-banner',
            'fields'        => array(
                '_title'                => true,
                '_title_link'           => false,
                '_image'                => true,
                '_image_link'           => false,
                '_subtitle'             => false,
                '_subtitle_link'        => false,
                '_caption'              => true,
                '_caption_link'         => false,
            )
        );
        
        /**
         * Initialize the plugin
         * 
         */

        public function __construct($args) {

            $args = wp_parse_args( $args, $this->default_args );
            extract( $args, EXTR_SKIP );

            $this->base_label = str_replace (' ', '_', strtolower($base_label));

            $this->labels = array(
                'name' => $base_label,
                'set' => 'Set Image',
                'remove' => 'Remove ' . $base_label,
                'use' => 'Use as ' . $base_label
            );

            $this->post_meta_key  = 'syn_'; 
            $this->post_type      = $this->post_meta_key . $this->base_label;
            $this->slug           = $this->base_label;
            $this->id             = $this->post_meta_key . $this->base_label;
            $this->post_meta_flag = $this->post_meta_key . $this->base_label;
            $this->nonce          = $this->post_meta_key . $this->id . $this->base_label;
            $this->fields         = $fields;
            $this->meta_class     = $meta_class;
            $this->post_type_edit = $post_type; // specify post_type that metaboxes are available for; defaults to page;
          
            if( !current_theme_supports( 'post-thumbnails' ) ) {
                add_theme_support( 'post-thumbnails' );
            }

            if ( function_exists( 'add_image_size' ) ) { 
                add_image_size( $this->base_label, $width, $height, true );  
            }
            
            add_action( 'wp_ajax_syn_insert_banner', array( $this, 'ajax_insert_banner' ) );
            add_action( 'wp_ajax_syn_delete_banner', array( $this, 'ajax_delete_banner' ) );
            add_action( 'wp_ajax_syn_update_banner', array( $this, 'ajax_update_banner' ) );
       
            $this->admin_init();

        }

        /**
         * Add admin-Javascript and Custom Post-type for Banner
         * 
         * @return void 
         */
        public function admin_init() {

            wp_enqueue_script('WP_images',  plugins_url('', __FILE__) . '/js/uploader.js');
            wp_enqueue_script('zeroclipboard',  plugins_url('', __FILE__) . '/js/lib/zeroclipboard/dist/ZeroClipboard.min.js');
            wp_enqueue_script('handlebars',  plugins_url('', __FILE__) . '/js/lib/handlebars/handlebars.min.js');
            wp_enqueue_style('banner-styles', plugins_url($this->plugin_dir).'/css/style.css');

            $this->create_banner_post_type();
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

        }

         /**
         * Add admin-Javascript
         * 
         * @return void 
         */
        public function create_banner_post_type() {
            $labels = array(
                'name'               => $this->base_label . 's',
                'singular_name'      => $this->base_label,
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

            register_post_type($this->post_type, $args);
        }

        /**
         * Add admin metabox for choosing additional featured images
         * 
         * @return void 
         */
        public function add_meta_boxes(){
            
            add_meta_box(
                $this->id,
                $this->labels['name'],
                array( $this, 'meta_box_content' ),
                $this->post_type_edit,
                'normal',
                'core',
                array( 'id' => $this->id )
            );

            add_filter('postbox_classes_' . $this->post_type_edit . '_' . $metabox_id, array($this, 'add_metabox_classes'));
          
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
          $args = array();
          echo $this->meta_box_output($args);
        }

        /**
         * Generate the metabox content
         * 
         * @global int $post_ID
         * @param takes $args requires
         * @return string 
         */
        public function meta_box_output( $args ) {

            global $post_ID;
            $banners = $this->get_all_banners();

            $filename =  plugin_dir_path(__FILE__) . '/js/templates/uploaderFormTemplate.html';
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            $contents = preg_replace(array('/\r/', '/\n/'), '', $contents);
            fclose($handle);

            echo '<ul data-post-id="' . $post_ID . '" id="custom-banner-form-wrap"></ul>';
            echo '<button id="add-new-banner" class="button-primary">Add New Banner</button>';
            echo "<script>window.uploaderData = {banners: '" . json_encode($banners) . "', template: '" . $contents . "'}</script>";
        }

        /**
         * ajax action to insert button into DB
         * @return void
         */

        public function ajax_insert_banner(){
            
            extract($_POST, EXTR_SKIP);

            if( !current_user_can( 'edit_post', $parent_id ) ) {
                die( '-1' );
            }

            //check_ajax_referer( $this->nonce, '_ajax_nonce' );

            $post = array(
                'menu_order'   => $menu_order,
                'post_content' => $caption,
                'post_parent'  => $parent_id,
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_type'    => $this->post_type
            );

            $banner_id = wp_insert_post( $post, false );

            if (!empty($banner_id)){

                //add_post_meta( $parent_id, $this->post_meta_flag, $banner_id );
                update_post_meta( $banner_id, $this->post_meta_flag . '_image', $image_src );

                $shortcode = '[quote id="' . $banner_id . '" type="side"]';

                $data = array(
                    'post_content'   => $caption,
                    'post_title'     => $title,
                    'menu_order'     => $menu_order,
                    'image_src'      => $image_src,
                    'ID'             => $banner_id,
                    'shortcode'      => $shortcode
                );

                die(json_encode($data));

            } else {
                die('0');
            }
        }

         /**
         * ajax action to insert button into DB
         * @return void
         */

        public function ajax_update_banner(){
            extract($_POST, EXTR_SKIP);

            if( !current_user_can( 'edit_post', $parent_id ) ) {
                die( '-1' );
            }

            if (!empty($banner_id)){
                $post = array(
                    'ID'           => $banner_id,
                    'post_content' => $caption,
                    'post_parent'  => $parent_id,
                    'post_title'   => $title,
                );

                wp_update_post($post);
                //update_post_meta( $parent_id, $this->post_meta_flag, $banner_id );
                update_post_meta( $banner_id, $this->post_meta_flag . '_image', $image_src );
                
                $shortcode = '[quote id="' . $banner_id . '" type="side"]';

                $data = array(
                    'post_content'   => $caption,
                    'post_title'     => $title,
                    'menu_order'     => $menu_order,
                    'image_src'      => $image_src,
                    'ID'             => $banner_id,
                    'shortcode'      => $shortcode
                );

                die(json_encode($data));

            } else {
                die('0');
            }
        }
        
        /**
         * ajax action to delete button into DB
         * @return void
         */
        public function ajax_delete_banner(){

             
            extract($_POST, EXTR_SKIP);
            
            if( !current_user_can( 'edit_post', $parent_id ) ) {
                die( '-1' );
            }

            //check_ajax_referer( $this->nonce, '_ajax_nonce' );
            
            if( !empty( $banner_id ) ) {
    
                wp_delete_post( $banner_id, true );
               // delete_post_meta( $parent_id, $this->post_meta_flag, $banner_id);
                delete_post_meta( $banner_id, $this->post_meta_flag . '_image' );

                die('deleted');
            }

            die ('failed');
        }

        public function get_all_banners($post_id = null) {
            global $post_ID;
            $post_id = (is_null($post_id) ? $post_ID : $post_id);

            $args = array(
                'post_parent'       => $post_id,
                'post_type'         => $this->post_type,
                'orderby'           => 'menu_order',
            	'order'             => 'ASC', 
	            'posts_per_page'    => -1,
            	'post_status'       => 'publish'
            );

            $banners_raw = get_children($args, ARRAY_A);
            $banners = array();

            foreach($banners_raw as $key => $banner) {
                $image_src = get_post_meta($banner['ID']);
                $shortcode = '[quote id=\"' . $banner['ID'] . '\" type=\"side\"]';

                $banner['image_src'] = array_shift($image_src);
                $banner['shortcode'] = $shortcode;
                array_push($banners, $banner);
            }
           
            return $banners;
        }

        public function get_single_banner($banner_id = null) {
            $image_src = get_post_meta($banner_id);
            $banner = get_post($banner_id, ARRAY_A); 
            $banner['image_src'] = array_shift($image_src)[0];
            return $banner;
        }

    }

    function syn_get_banners($post_id = null) {
        global $post_ID;
        $post_id = (is_null($post_id) ? $post_ID : $post_id);
        return Syn_wp_images::get_all_banners($post_id);
    }

    function syn_get_single_banner($banner_id = null) {
        return Syn_wp_images::get_single_banner($banner_id);
    }

    function build_banner($attr, $content = "") {
        $id = $attr['id'];
        $type = $attr['type'];
        $banner = syn_get_single_banner($id);

        $content  = '<div class="quote quote-' .$type .'">';
        $content .= '<h1 class="quote-title">' . $banner['post_title'] . '</h1>';
        $content .= '<p class="quote-content">' . $banner['post_content'] . '</p>';
        $content .= '<img src="' . $banner['image_src'] . '" class="quote-image" />';
        $content .= '</div>';

        return $content;
    }
    
    add_shortcode('quote', 'build_banner');
    
}
