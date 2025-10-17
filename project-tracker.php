<?php
/**
 * Plugin Name: Project Tracker
 * Plugin URI: https://github.com/Reetika-k/project-tracker.git
 * Description: A custom WordPress plugin to manage projects and tasks via dashboard and REST API.
 * Version: 1.0.0
 * Author: Reetika Kukreja
 * Author URI: https://github.com/Reetika-k/
 * License: GPL-2.0-or-later
 * Text Domain: project-tracker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Project_Tracker
 *
 * Main class for the Project Tracker plugin.
 */
class Project_Tracker {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Plugin slug.
     *
     * @var string
     */
    const SLUG = 'project-tracker';

    /**
     * REST API namespace.
     *
     * @var string
     */
    const REST_NAMESPACE = self::SLUG . '/v1';

    /**
     * Transient key for caching projects.
     *
     * @var string
     */
    const TRANSIENT_KEY = 'project_tracker_projects';

    /**
     * Constructor.
     */
    public function __construct() {
        // Register custom post type and taxonomy.
        add_action( 'init', array( $this, 'register_post_type_and_taxonomy' ) );

        // Add meta boxes.
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

        // Save meta box data.
        add_action( 'save_post_project', array( $this, 'save_meta_boxes' ) );

        // Register REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Clear cache on project changes.
        add_action( 'save_post_project', array( $this, 'clear_cache' ) );
        add_action( 'delete_post', array( $this, 'clear_cache_on_delete' ) );
    }

    // Set up the project post type and client taxonomy
    public function register_post_type_and_taxonomy() {
        // Register 'project' custom post type.
        $labels = array(
            'name'                  => __( 'Projects', 'project-tracker' ),
            'singular_name'         => __( 'Project', 'project-tracker' ),
            'menu_name'             => __( 'Projects', 'project-tracker' ),
            'name_admin_bar'        => __( 'Project', 'project-tracker' ),
            'add_new'               => __( 'Add New', 'project-tracker' ),
            'add_new_item'          => __( 'Add New Project', 'project-tracker' ),
            'new_item'              => __( 'New Project', 'project-tracker' ),
            'edit_item'             => __( 'Edit Project', 'project-tracker' ),
            'view_item'             => __( 'View Project', 'project-tracker' ),
            'all_items'             => __( 'All Projects', 'project-tracker' ),
            'search_items'          => __( 'Search Projects', 'project-tracker' ),
            'parent_item_colon'     => __( 'Parent Projects:', 'project-tracker' ),
            'not_found'             => __( 'No projects found.', 'project-tracker' ),
            'not_found_in_trash'    => __( 'No projects found in Trash.', 'project-tracker' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'project' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'project', $args );

        // Register 'client' taxonomy.
        $labels = array(
            'name'              => __( 'Clients', 'project-tracker' ),
            'singular_name'     => __( 'Client', 'project-tracker' ),
            'search_items'      => __( 'Search Clients', 'project-tracker' ),
            'all_items'         => __( 'All Clients', 'project-tracker' ),
            'parent_item'       => __( 'Parent Client', 'project-tracker' ),
            'parent_item_colon' => __( 'Parent Client:', 'project-tracker' ),
            'edit_item'         => __( 'Edit Client', 'project-tracker' ),
            'update_item'       => __( 'Update Client', 'project-tracker' ),
            'add_new_item'      => __( 'Add New Client', 'project-tracker' ),
            'new_item_name'     => __( 'New Client Name', 'project-tracker' ),
            'menu_name'         => __( 'Clients', 'project-tracker' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'client' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'client', array( 'project' ), $args );
    }

    /**
     * Add meta boxes to the project edit screen.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'project_details',
            __( 'Project Details', 'project-tracker' ),
            array( $this, 'render_meta_box' ),
            'project',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'project_tracker_nonce', 'project_tracker_nonce' );

        $start_date = get_post_meta( $post->ID, '_start_date', true );
        $end_date   = get_post_meta( $post->ID, '_end_date', true );
        $status     = get_post_meta( $post->ID, '_status', true );
        $budget     = get_post_meta( $post->ID, '_budget', true );
        $project_manager = get_post_meta( $post->ID, '_project_manager', true );

        // Status options.
        $statuses = array( 'active', 'on-hold', 'completed' );

        ?>
        <p>
            <label for="start_date"><?php esc_html_e( 'Start Date:', 'project-tracker' ); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
        </p>
        <p>
            <label for="end_date"><?php esc_html_e( 'End Date:', 'project-tracker' ); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
        </p>
        <p>
            <label for="status"><?php esc_html_e( 'Status:', 'project-tracker' ); ?></label>
            <select id="status" name="status">
                <?php foreach ( $statuses as $s ) : ?>
                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="budget"><?php esc_html_e( 'Budget:', 'project-tracker' ); ?></label>
            <input type="number" id="budget" name="budget" value="<?php echo esc_attr( $budget ); ?>" step="0.01">
        </p>
        <p>
            <label for="project_manager"><?php esc_html_e( 'Project Manager:', 'project-tracker' ); ?></label>
            <select id="project_manager" name="project_manager">
                <option value=""><?php esc_html_e( 'Select User', 'project-tracker' ); ?></option>
                <?php
                $users = get_users( array( 'role__in' => array( 'administrator', 'editor' ) ) );
                foreach ( $users as $user ) {
                    printf(
                        '<option value="%d" %s>%s</option>',
                        esc_attr( $user->ID ),
                        selected( $project_manager, $user->ID, false ),
                        esc_html( $user->display_name )
                    );
                }
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $project_id The post ID.
     */
    public function save_meta_boxes( $project_id ) {
        if ( ! isset( $_POST['project_tracker_nonce'] ) || ! wp_verify_nonce( $_POST['project_tracker_nonce'], 'project_tracker_nonce' ) ) {
            return;
        }

        // Quick check to avoid autosaves messing things up
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $project_id ) ) {
            return;
        }

        // Save start date.
        if ( isset( $_POST['start_date'] ) ) {
            update_post_meta( $project_id, '_start_date', sanitize_text_field( $_POST['start_date'] ) );
        }

        // Save end date.
        if ( isset( $_POST['end_date'] ) ) {
            update_post_meta( $project_id, '_end_date', sanitize_text_field( $_POST['end_date'] ) );
        }

        // Save status.
        if ( isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'active', 'on-hold', 'completed' ), true ) ) {
            update_post_meta( $project_id, '_status', sanitize_text_field( $_POST['status'] ) );
        }

        // Save budget.
        if ( isset( $_POST['budget'] ) ) {
            update_post_meta( $project_id, '_budget', floatval( $_POST['budget'] ) );
        }

        // Save project manager.
        if ( isset( $_POST['project_manager'] ) ) {
            update_post_meta( $project_id, '_project_manager', intval( $_POST['project_manager'] ) );
        }
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        // GET /projects
        register_rest_route( self::REST_NAMESPACE, '/projects', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_projects' ),
            'permission_callback' => '__return_true',
        ) );

        // POST /projects
        register_rest_route( self::REST_NAMESPACE, '/projects', array(
            'methods' => 'POST',
            'callback' => array( $this, 'create_project' ),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        // GET /projects/{id}
        register_rest_route( self::REST_NAMESPACE, '/projects/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_project' ),
            'permission_callback' => '__return_true',
        ) );

        // PUT /projects/{id}
        register_rest_route( self::REST_NAMESPACE, '/projects/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array( $this, 'update_project' ),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        // DELETE /projects/{id}
        register_rest_route( self::REST_NAMESPACE, '/projects/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array( $this, 'delete_project' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    /**
     * Get all projects with caching and enhanced filtering.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_projects( $request ) {
        $cache = get_transient( self::TRANSIENT_KEY );
        if ( $cache ) {
            return rest_ensure_response( $cache );
        }

        $args = array(
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        //$this->log_debug( 'Fetching projects with args: ' . print_r( $args, true ) );
        // Valid status values.
        $valid_statuses = array( 'active', 'on-hold', 'completed' );

        // Filter by status.
        if ( $status = $request->get_param( 'status' ) ) {
            $status = sanitize_text_field( $status );
            if ( ! in_array( $status, $valid_statuses, true ) ) {
                return new WP_Error(
                    'invalid_status',
                    __( 'Invalid status. Must be one of: active, on-hold, completed.', 'project-tracker' ),
                    array( 'status' => 400 )
                );
            }
            $args['meta_query'][] = array(
                'key'     => '_status',
                'value'   => $status,
                'compare' => '=',
            );
        }

        // Filter by client.
        if ( $client = $request->get_param( 'client' ) ) {
            $client = sanitize_text_field( $client );
            // Verify client term exists.
            if ( ! term_exists( $client, 'client' ) ) {
                return new WP_Error(
                    'invalid_client',
                    __( 'Invalid client slug.', 'project-tracker' ),
                    array( 'status' => 400 )
                );
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'client',
                'field'    => 'slug',
                'terms'    => $client,
            );
        }

        // $projects = new WP_Query( [ 'post_type' => 'project' ] );
        $projects = get_posts( $args );
        $data = array();

        if ( empty( $projects ) ) {
            // Cache empty result to prevent repeated queries.
            set_transient( self::TRANSIENT_KEY, $data, 60 );
            return rest_ensure_response( $data );
        }

        foreach ( $projects as $project ) {
            $data[] = $this->prepare_project_data( $project );
        }

        // Cache for 60 seconds.
        set_transient( self::TRANSIENT_KEY, $data, 60 );

        return rest_ensure_response( $data );
    }

    /**
     * Create a new project.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_project( $request ) {
        $params = $request->get_params();

        if ( empty( $params['title'] ) ) {
            return new WP_Error( 'no_title', 'Hey, you forgot to add a project title!', [ 'status' => 400 ] );        
        }

        if ( ! $post || $post->post_type !== 'project' ) {
            return new WP_Error( 'project_missing', 'Oops, that project doesn’t exist.', [ 'status' => 404 ] );
        }
            
        $project_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $params['title'] ),
            'post_content' => wp_kses_post( $params['description'] ?? '' ),
            'post_type'    => 'project',
            'post_status'  => 'publish',
        ) );

        if ( is_wp_error( $project_id ) ) {
            return $project_id;
        }

        $this->update_project_meta( $project_id, $params );

        if ( isset( $params['client'] ) ) {
            wp_set_object_terms( $project_id, sanitize_text_field( $params['client'] ), 'client' );
        }

        $this->clear_cache();

        return rest_ensure_response( $this->prepare_project_data( get_post( $project_id ) ) );
    }

    /**
     * Get a single project.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_project( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || $post->post_type !== 'project' ) {
            return new WP_Error( 'not_found', __( 'Project not found.', 'project-tracker' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->prepare_project_data( $post ) );
    }

    /**
     * Update a project.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_project( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || $post->post_type !== 'project' ) {
            return new WP_Error( 'not_found', __( 'Project not found.', 'project-tracker' ), array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return new WP_Error( 'forbidden', __( 'You are not allowed to edit this project.', 'project-tracker' ), array( 'status' => 403 ) );
        }

        $params = $request->get_params();

        $update = array(
            'ID'           => $post->ID,
            'post_title'   => isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : $post->post_title,
            'post_content' => isset( $params['description'] ) ? wp_kses_post( $params['description'] ) : $post->post_content,
        );

        wp_update_post( $update );

        $this->update_project_meta( $post->ID, $params );

        if ( isset( $params['client'] ) ) {
            wp_set_object_terms( $post->ID, sanitize_text_field( $params['client'] ), 'client' );
        }

        $this->clear_cache();

        return rest_ensure_response( $this->prepare_project_data( get_post( $post->ID ) ) );
    }

    /**
     * Delete a project.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_project( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || $post->post_type !== 'project' ) {
            return new WP_Error( 'not_found', __( 'Project not found.', 'project-tracker' ), array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'delete_post', $post->ID ) ) {
            return new WP_Error( 'forbidden', __( 'You are not allowed to delete this project.', 'project-tracker' ), array( 'status' => 403 ) );
        }

        wp_delete_post( $post->ID, true );

        $this->clear_cache();

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Prepare project data for response.
     *
     * @param WP_Post $post The post object.
     * @return array
     */
    private function prepare_project_data( $post ) {
        $clients = wp_get_object_terms( $post->ID, 'client', array( 'fields' => 'names' ) );

        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'description'     => $post->post_content,
            'start_date'      => get_post_meta( $post->ID, '_start_date', true ),
            'end_date'        => get_post_meta( $post->ID, '_end_date', true ),
            'status'          => get_post_meta( $post->ID, '_status', true ),
            'budget'          => get_post_meta( $post->ID, '_budget', true ),
            'project_manager' => get_post_meta( $post->ID, '_project_manager', true ),
            'client'          => $clients ? $clients[0] : '',
        );
    }

    /**
     * Update project meta fields.
     *
     * @param int   $project_id The post ID.
     * @param array $params  The parameters.
     */
    private function update_project_meta( $project_id, $params ) {
    // Handle all meta updates in one go
        $fields = [
            'start_date' => 'sanitize_text_field',
            'end_date' => 'sanitize_text_field',
            'status' => ['sanitize_text_field', ['active', 'on-hold', 'completed']],
            'budget' => 'floatval',
            'project_manager' => 'intval'
        ];
        foreach ( $fields as $key => $sanitizer ) {
            if ( isset( $params[$key] ) ) {
                if ( is_array( $sanitizer ) && ! in_array( $params[$key], $sanitizer[1], true ) ) {
                    continue; // Skip invalid status
                }
                update_post_meta( $project_id, "_{$key}", is_array( $sanitizer ) ? $sanitizer[0]( $params[$key] ) : $sanitizer( $params[$key] ) );
            }
        }
    }

    
    // Clear the projects cache.
    public function clear_cache() {
        delete_transient( self::TRANSIENT_KEY );
    }

    /**
     * Clear cache on delete if it's a project.
     *
     * @param int $project_id The post ID.
     */
    public function clear_cache_on_delete( $project_id ) {
        if ( get_post_type( $project_id ) === 'project' ) {
            $this->clear_cache();
        }
    }

    private function log_debug( $message ) {
    // debug log
        if ( WP_DEBUG ) {
            error_log( '[Project Tracker] ' . $message );
        }
    }
}

// Instantiate the class.

new Project_Tracker();
