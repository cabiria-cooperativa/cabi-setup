<?php
/**
 * Plugin Name: Cabiria theme setup
 * Plugin URI: https://www.cabiria.net
 * Description: Impostazioni per il tema Cabi
 * Version: 1.0.0
 * Author: Simone Alati
 * Author URI: https://www.cabiria.net
 * Text Domain: cabi
 */

 // termino l'esecuzione se il plugin è richiamato direttamente
 if (!defined('WPINC')) die;

class CabiSetup {

    //const SLUG = 'cabi-setup';

    function __construct() {

        //$this->cpt_name = self::SLUG;
        //$this->cpt_slug = self::SLUG;

		//add_action('init', array($this, 'add_cpt'), 0);             	/* aggiungo un custom post type         */
        add_action('wp_enqueue_scripts', array($this, 'init'));     	/* accodo js e css                      */
        add_action('admin_menu', array($this, 'add_settings_page'));    /* creo una pagina di impostazioni      */
		
		/* nascondo gli errori al login */
		add_filter('login_errors', '__return_false');
		add_filter('login_messages', '__return_false');
		
		/* rimuovo la versione di WordPress */
		remove_action('wp_head', 'wp_generator');

        /* azioni ajax */
        add_action('wp_ajax_nopriv_hello_world_ajax', array($this, 'hello_world_ajax'));
        add_action('wp_ajax_hello_world_ajax', array($this, 'hello_world_ajax'));

        /* attivazione e disattivazione plugin */
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook( __FILE__, array($this, 'deactivation'));

        /* setup tema */
        add_action( 'init', function(){
            global $wp_rewrite;
            /* rimuovo i paragrafi automatici */
            /* remove_filter( 'the_content', 'wpautop' ); */
            
            /* aggiungo i tag alle pagine */
            register_taxonomy_for_object_type('post_tag', 'page');
            
            /* aggiungo le categorie alle pagine */
            register_taxonomy_for_object_type('category', 'page');
            
            /* cambio lo slug di default per la pagina autore */
            $wp_rewrite->author_base = 'profilo';
        });

        /**
         * Aggiungo un formato immagine personalizzato per la featured image / hero image / slider
         */

        add_action('after_setup_theme', 'set_custom_sizes_to_images');
        function set_custom_sizes_to_images() {
            add_image_size('hero_image', 1920, 600, array('center', 'center'));
        }
        add_filter( 'image_size_names_choose', 'add_custom_sizes_to_images' );
        function add_custom_sizes_to_images($sizes) {
            return array_merge( $sizes, array(
                'hero_image' => __('Hero image'),
            ) );
        }

        /**
        * Modifico lo slug di default per le categorie
        * il valore impostato si vede anche nella pagina dei permalink del backend
        * https://wpdreamer.com/2014/01/how-to-change-your-wordpress-category-tag-or-post-format-permalink-structure
        */
        add_filter( 'pre_option_category_base', 'cabi_change_category_slug' );
        function cabi_change_category_slug( $value ) {
            return 'categoria';
        }

        /**
        * Modifico l'url dell'archivio post di un utente sostituendo lo username con il nickname
        * N.B. Ricordarsi di modificare il nickname dell'admin di WP (che di default è uguale allo username)
        * https://wordpress.stackexchange.com/questions/5742/change-the-author-slug-from-username-to-nickname#answer-6527
        */
        add_filter( 'request', 'wpse5742_request' );
        //intercetta la richiesta di un autore
        function wpse5742_request( $query_vars )
        {
            if ( array_key_exists( 'author_name', $query_vars ) ) {
                global $wpdb;
                $author_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='nickname' AND meta_value = %s", $query_vars['author_name'] ) );
                if ( $author_id ) {
                    $query_vars['author'] = $author_id;
                    unset( $query_vars['author_name'] );    
                }
            }
            return $query_vars;
        }
        add_filter( 'author_link', 'wpse5742_author_link', 10, 3 );
        //modifica l'url autore da /author/username in /author/nickname
        function wpse5742_author_link( $link, $author_id, $author_nicename )
        {
            $author_nickname = get_user_meta( $author_id, 'nickname', true );
            if ($author_nickname) $link = str_replace( $author_nicename, $author_nickname, $link );
            return $link;
        }

    }

    function activation(){
        //$this->add_settings();
    }

    function deactivation(){
		//$this->remove_cpt();
        //$this->remove_settings();
	  }

    function init() {
        wp_enqueue_style( 'cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' , array(), mt_rand());
        wp_enqueue_script('cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/js/cabi-setup.js', array('jquery'), mt_rand(), true);
    }

	function add_cpt() {
        $labels = array(
            'name'                  => _x( 'Post Types', 'Post Type General Name', 'cabi' ),
            'singular_name'         => _x( 'Post Type', 'Post Type Singular Name', 'cabi' ),
            'menu_name'             => __( 'Post Types', 'cabi' ),
            'name_admin_bar'        => __( 'Post Types', 'cabi' ),
            'archives'              => __( 'Item Archives', 'cabi' ),
            'parent_item_colon'     => __( 'Parent Item:', 'cabi' ),
            'all_items'             => __( 'All Items', 'cabi' ),
            'add_new_item'          => __( 'Add New Item', 'cabi' ),
            'add_new'               => __( 'Add New', 'cabi' ),
            'new_item'              => __( 'New Item', 'cabi' ),
            'edit_item'             => __( 'Edit Item', 'cabi' ),
            'update_item'           => __( 'Update Item', 'cabi' ),
            'view_item'             => __( 'View Item', 'cabi' ),
            'search_items'          => __( 'Search Item', 'cabi' ),
            'not_found'             => __( 'Not found', 'cabi' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'cabi' ),
            'featured_image'        => __( 'Featured Image', 'cabi' ),
            'set_featured_image'    => __( 'Set featured image', 'cabi' ),
            'remove_featured_image' => __( 'Remove featured image', 'cabi' ),
            'use_featured_image'    => __( 'Use as featured image', 'cabi' ),
            'insert_into_item'      => __( 'Insert into item', 'cabi' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'cabi' ),
            'items_list'            => __( 'Items list', 'cabi' ),
            'items_list_navigation' => __( 'Items list navigation', 'cabi' ),
            'filter_items_list'     => __( 'Filter items list', 'cabi' ),
        );
        $rewrite = array(
            'slug'                  => $this->cpt_slug,
            'with_front'            => false,
            'pages'                 => true,
            'feeds'                 => true,
        );
        $args = array(
            'label'                 => __( 'Post Type', 'cabi' ),
            'description'           => __( 'Post Type Description', 'cabi' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats', ),
            'taxonomies'            => array( 'category', 'post_tag' ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5.2,
            'menu_icon'             => 'dashicons-admin-post',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'custom-post-type',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'rewrite'               => $rewrite,
            'capability_type'       => 'page',
        );
        register_post_type( $this->cpt_name, $args );
    }

    function remove_cpt() {
        global $wpdb;
        global $wp_post_types;

        $prefix = $wpdb->prefix;
        if (post_type_exists($this->cpt_name)) {

            // deregistro il cpt
            unset($wp_post_types[$this->cpt_name]);

            // rimuovo la pagina di menu
            remove_menu_page($this->cpt_slug);

            // recupero le revisioni del custom post
            $rows = $wpdb->get_results ("SELECT ID FROM {$prefix}posts WHERE post_type = '{$this->cpt_slug}'");
            $ids = '';
            for ($i = 0; $i < count($rows); $i++) {
                $ids .= $rows[$i]->ID . ',';
            }
            $ids = substr($ids, 0, -1);

            //rimuovo le revisioni
            $query = "DELETE FROM {$prefix}posts WHERE post_type = 'revision' and post_parent IN ($ids)";
            $result = $wpdb->query($wpdb->prepare($query));

            // rimuovo i custom post e i relativi meta
            $query = "DELETE a,b,c FROM {$prefix}posts a LEFT JOIN {$prefix}term_relationships b ON (a.ID = b.object_id) LEFT JOIN {$prefix}postmeta c ON (a.ID = c.post_id) WHERE a.post_type = %s";
            $result = $wpdb->query($wpdb->prepare($query, $this->cpt_slug));

        }
    }

    function hello_world_ajax() {
        echo json_encode(array('Hello', 'world'));
		wp_die(); /* previene che WordPress accodi '0' al risultato */
    }

    function add_settings_page() {
        add_options_page(
            'Cabi setup',
            'Cabi setup',
            'manage_options',
            'cabi-settings-page',
            array($this,'render_settings_page')
        );
    }

    function add_settings() {
        //add_option('key', 'value');
    }

    function remove_settings() {
        //delete_option('key');
    }

    function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die('Non possiedi i permessi per accedere a questa pagina');
        ?>
        <div class="wrap">
            <h2>Cabi Setup</h2>
            <?php
            if (isset($_POST['submit']) && wp_verify_nonce($_POST['modify_settings_nonce'], 'modify_settings')) {
                /* opzione da salvare */
                //update_option('key', 'value');
            }
            ?>
            <form method="post">
                <?php wp_nonce_field('modify_settings', 'modify_settings_nonce') ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

}

new CabiSetup();
