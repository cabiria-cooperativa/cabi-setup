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

    function __construct() {

        add_action('wp_enqueue_scripts', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_settings_page'));

        add_action('wp_head', function() {
            ?>
            <link rel='dns-prefetch' href='//www.googletagmanager.com'>
            <link rel='preconnect' href='https://www.googletagmanager.com'>
            <link rel='preconnect' href='https://fonts.googleapis.com'>
            <?php
        }, 1);

        /* rimuovo i pingback interni tra articoli */
        add_action('pre_ping', array($this,'disable_self_pingbacks'));
		
		/* nascondo gli errori al login */
		add_filter('login_errors', function(){
            return "Il silenzio è d'oro.";
        });
		add_filter('login_messages', function(){
            return "Il silenzio è d'oro.";
        });
		
		/* rimuovo la versione di WordPress */
        remove_action('wp_head', 'wp_generator');
        
        /* ATTIVARE SOLO IN PRODUZIONE - rimuovo il parametro di versione da script e stili */
        //add_action('init', array($this,'remove_query_strings'));

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
            add_image_size('hero_image', 1920, 600, array('center', 'top')); /* crop */
            add_image_size('hero_image_full', 1920, 9999); /* resize */
        }
        add_filter( 'image_size_names_choose', 'add_custom_sizes_to_images' );
        function add_custom_sizes_to_images($sizes) {
            return array_merge( $sizes, array(
                'hero_image' => __('Hero image 1920 x 600'),
            ) );
        }
        add_filter('wp_get_attachment_image_attributes', 'set_responsive_sizes', 10 , 3);
        function set_responsive_sizes ($attr, $attachment, $size) {
            if ($size === 'hero_image') {
                $image = wp_get_attachment_image_src($attachment->ID, 'hero_image');
                $attr['srcset'] = wp_get_attachment_image_srcset($attachment->ID) . ', ' . $image[0] . ' 1920w';
                $attr['sizes'] = '(min-width: 1200px) 1920px, (min-width: 1024px) 1024px, (min-width: 768px) 768px, (min-width: 400px) 768px, 300px';
            } elseif ($size === 'full') {
                $image = wp_get_attachment_image_src($attachment->ID, 'hero_image_full');
                $attr['srcset'] = wp_get_attachment_image_srcset($attachment->ID) . ', ' . $image[0] . ' 1920w';
                $attr['sizes'] = '(min-width: 1200px) 1920px, (min-width: 1024px) 1024px, (min-width: 768px) 768px, (min-width: 400px) 768px, 300px';
            } else {
                $attr['srcset'] = wp_get_attachment_image_srcset($attachment->ID);
                $attr['sizes'] = '(min-width: 1200px) 100vw, (min-width: 1024px) 1024px, (min-width: 768px) 768px, (min-width: 400px) 768px, 300px';
            }
            return $attr;
        }
        add_filter('max_srcset_image_width', 'max_srcset_dimension');
        function max_srcset_dimension($max_srcset_dimension) {
            return 1920;
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

    private function get_base_path() {
        require_once (ABSPATH . 'wp-admin/includes/file.php');
        return get_home_path();
    }

    private function get_htaccess_ruleset() {
        $htaccess_ruleset = plugin_dir_url( __FILE__ ) . 'assets/htaccess/htaccess.txt';
        return @file_get_contents($htaccess_ruleset);
    }

    private function get_htaccess_contents() {
        $fp = fopen($this->get_base_path() . '/.htaccess', 'r');
        if (!$fp) return false;
        $contents = fread($fp, filesize($this->get_base_path() . '/.htaccess'));
        fclose($fp);
        return $contents;
    }

    private function set_htaccess_contents($contents, $ruleset = '') {
        $fp = fopen($this->get_base_path() . '/.htaccess', 'w');
        if (!$fp) return false;
        if ($ruleset) fwrite($fp, $ruleset . "\n\n");
        fwrite($fp, $contents);
        fclose($fp);
    }

    function activation(){
        $this->add_settings();
        
        $path = $this->get_base_path();
        /* eseguo una copia di sicurezza dell'htaccess */
        $result = copy($path . '/.htaccess', $path . '/.htaccess' . '.backup_cabi_' . date('Ymd_Hi'));
        if ($result === true) {
            /* recupero le regole da aggiungere all'htaccess */
            $ruleset = $this->get_htaccess_ruleset();
            if ($ruleset === false) return;
            /* apro il file .htaccess in lettura */
            $contents = $this->get_htaccess_contents();
            /* aggiungo le regole in cima al file */
            if ($contents === false) return;
            $this->set_htaccess_contents($contents, $ruleset);
        }
    }

    function deactivation(){
        $this->remove_settings();

        /* recupero le regole da rimuovere dall'htaccess */
        $ruleset = $this->get_htaccess_ruleset();
        if ($ruleset === false) return;
        /* apro il file .htaccess in lettura */
        $contents = $this->get_htaccess_contents();
        if ($contents === false) return;
        /* rimuovo le regole */
        $contents = str_replace($ruleset, '', $contents);
        /* aggiorno l'htaccess */
        $this->set_htaccess_contents($contents);
	  }

    function init() {
        wp_enqueue_style( 'cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' , array(), mt_rand());
        wp_enqueue_script('cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/js/cabi-setup.js', array('jquery'), mt_rand(), true);

        /* table saw - gestione tabelle */
        if (get_option('cs-addon-tablesaw')) {
            wp_enqueue_style('tablesaw', plugin_dir_url( __FILE__ ) . 'assets/vendor/tablesaw/tablesaw.css',array(), '3.1.2');
            wp_enqueue_script('tablesaw', plugin_dir_url( __FILE__ ) . 'assets/vendor/tablesaw/tablesaw.jquery.js', array('jquery'), '3.1.2', true);
            wp_enqueue_script('tablesaw-init', plugin_dir_url( __FILE__ ) . 'assets/vendor/tablesaw/tablesaw-init.js', array('jquery', 'tablesaw'), '3.1.2', true);
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
        add_option('cs-addon-tablesaw', 0);
    }

    function remove_settings() {
        delete_option('cs-addon-tablesaw');
    }

    function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die('Non possiedi i permessi per accedere a questa pagina');
        ?>
        <div class="wrap">
            <h2>Cabi Setup</h2>
            <?php
            if (isset($_POST['submit']) && wp_verify_nonce($_POST['modify_settings_nonce'], 'modify_settings')) {
                update_option('cs-addon-tablesaw', $_POST['cs-addon-tablesaw']);
            }
            ?>
            <h3>Tablesaw | Addon</h3>
            <p>Attivo la libreria per la gestione responsive delle tabelle?</p>
            <form method="post">
                <?php wp_nonce_field('modify_settings', 'modify_settings_nonce') ?>
                <select name="cs-addon-tablesaw">
                    <option <?php if (get_option('cs-addon-tablesaw') == 0) echo 'selected="selected"' ?> value="0">No</option>
                    <option <?php if (get_option('cs-addon-tablesaw') == 1) echo 'selected="selected"' ?> value="1">Si</option>
                </select>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /* rimuovo i pingback interni tra articoli */
    function disable_self_pingbacks(&$links) {
        foreach ($links as $l => $link) {
            if (0 === strpos($link, get_option('home'))) unset($links[$l]);
        }
    }

    function remove_query_strings() {
        if (!is_admin()) {
            add_filter('script_loader_src', array($this, 'remove_query_strings_split'), 15);
            add_filter('style_loader_src', array($this, 'remove_query_strings_split'), 15);
        }
    }

    function remove_query_strings_split($src) {
        $output = preg_split("/(&ver|\?ver)/", $src);
        return $output[0];
    
    }

}

new CabiSetup();