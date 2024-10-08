<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 */

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/StarterSite.php';

// Initialize Timber.
Timber\Timber::init();

// Sets the directories (inside your theme) to find .twig files, the files must be in /templates and/or /views and/or pages.
Timber::$dirname = [ 'templates', 'views', 'pages' ];

// Instantiate the StarterSite class.
new StarterSite();


// Function to create the custom post type (CPT)
function prefix_create_custom_post_type() {
    
    // The $labels describes how the post type appears.
    $labels = array(
        'name'          => 'Recipes',
        'singular_name' => 'Recipe', 
    );

     // The $supports parameter describes what type of content the post type supports.
    $supports = array(
        'title',        // Post title
        'editor',       // Post content
        'excerpt',      // Allows short description
        'author',       // Allows showing and choosing author
        'thumbnail',    // Allows feature images
        //'comments',     // Enables comments
        //'trackbacks',   // Supports trackbacks
        //'revisions',    // Shows autosaved version of the posts
        'custom-fields' // Supports by custom fields
    ); 

    // The $args parameter holds important parameters for the custom post type
    $args = array(
        'labels'              => $labels,
        'description'         => 'Post type post recipe', // Description
        'supports'            => $supports,
        'taxonomies'          => array( 'category', 'post_tag' ), // Allowed taxonomies
        'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page
        'public'              => true,  // Makes the post type public
        'show_ui'             => true,  // Displays an interface for this post type
        'show_in_menu'        => true,  // Displays in the Admin Menu (the left panel)
        'show_in_nav_menus'   => true,  // Displays in Appearance -> Menus
        'show_in_admin_bar'   => true,  // Displays in the black admin bar
        'menu_position'       => 5,     // The position number in the left menu
        'menu_icon'           => true,  // The URL for the icon used for this post type
        'can_export'          => true,  // Allows content export using Tools -> Export
        'has_archive'         => true,  // Enables post type archive (by month, date, or year)
        'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false
        'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true
        'capability_type'     => 'post' // Allows read, edit, delete like “Post”
    ); 

    register_post_type('recipe', $args); //Create a post type with the slug is ‘recipe’ and arguments in $args.
}

// We call the function using the hook init
add_action('init', 'prefix_create_custom_post_type');

// Function to add the style.css by using wp_enqueue_style (this is meant to load css in wp)
function mytheme_enqueue_styles() {
    wp_enqueue_style('tailwindcss', get_template_directory_uri() . '/assets/css/style.css', array(), '1.0', 'all');
}

add_action('wp_enqueue_scripts', 'mytheme_enqueue_styles');


// ----------------------------Last 6 recipes---------------------------------
// We add a filter to print the last 6 recipes of the CPT 
add_filter('timber_context', 'add_recipes_to_context');
function add_recipes_to_context($context) {
    // Context = associative array, latest_recipes = key, Timber::get_posts() object´s array of posts
    $context['latest_recipes'] = Timber::get_posts(array(
        'post_type' => 'recipe',
        'posts_per_page' => 6, // 6 recipes to show
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    return $context;
}

// -------------------------All recipes----------------------------------
// We add a filter to print all recipes of the CPT 
add_filter('timber_context', 'add_all_recipes_to_context');
function add_all_recipes_to_context($context) {
    // Context = associative array, all_recipes = key, Timber::get_posts() object´s array of posts
    $context['all_recipes'] = Timber::get_posts(array(
        'post_type' => 'recipe',
        'posts_per_page' => -1, // Retrieve all recipes
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    return $context;
}

// --------------------Suggested recipes----------------------
// Function to get the suggested recipes for a single recipe post 
add_filter('timber_context', 'add_suggested_recipes_to_context');
function add_suggested_recipes_to_context($context) {
    // Get the current post
    $post = Timber::get_post();

    // Get the related recipes using ACF with the correct field name
    $suggested_recipes = get_field('suggested_recipes', $post->ID);

    // Give the correct recipes to the context
    if ($suggested_recipes) {
        $context['suggested_recipes'] = Timber::get_posts($suggested_recipes);
    }

    return $context;
}

// ----------------Get post image---------------
add_filter('timber_context', 'add_custom_image_to_context');
function add_custom_image_to_context($context) {
    $post = Timber::get_post();
    
    // Obtener el campo de imagen personalizado de ACF
    $custom_image = get_field('custom_image', $post->ID);

    // Pasar la imagen al contexto de Timber
    if ($custom_image) {
        $context['custom_image'] = $custom_image;
    }

    return $context;
}


// -------------------------------AJAX--------------------------------
function enqueue_custom_scripts() {
    wp_enqueue_script('custom-js', get_template_directory_uri() . '/assets/js/custom.js', array(), null, true); 

    // Transfer data from PHP to JS. Locate script to pass the AJAX´s URL
    wp_localize_script('custom-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// ----------------------------Form function--------------------------------
function handle_contact_form_submission() {

    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['description'])) {
        global $wpdb;

        $name = sanitize_text_field($_POST['name']);
        $email    = sanitize_email($_POST['email']);
        $description = sanitize_text_field($_POST['description']);

        $table_name = $wpdb->prefix . 'contacts'; // WP table prefix
        
        $data = array(
            'name'      => $name,
            'email'      => $email,
            'text'   => $description,
        );

        $format = array('%s', '%s', '%s');  

        $success = $wpdb->insert($table_name, $data, $format);

        if ($success) {
            $to = $email; // Send confirmation email to the user
            $subject = 'Confirmación de contacto';
            $message = 'Hola ' . $name . ",\n\nGracias por contactarnos. Hemos recibido tu mensaje y nos pondremos en contacto contigo pronto.\n\nTu mensaje:\n" . $description;
            $headers = array('Content-Type: text/plain; charset=UTF-8'); // Header to have plain text

            wp_mail($to, $subject, $message, $headers);

            wp_send_json_success(array('redirect_url' => home_url('/form-success')));
        } else {
            wp_send_json_error($wpdb->show_errors());
        }
    } else {
        wp_send_json_error('Datos incompletos');
    }

    wp_die();
}

add_action('wp_ajax_handle_contact_form', 'handle_contact_form_submission'); // For authenticated users
add_action('wp_ajax_nopriv_handle_contact_form', 'handle_contact_form_submission'); // For non authenticated users



