<?php
/**
 * Admin Management Page for Structured Data Plugin
 * 
 * Provides comprehensive admin interface with Action Scheduler-based pipeline creation,
 * AJAX-powered post analysis, bulk operations, and real-time status monitoring for
 * managing semantic data and pipeline operations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DM_StructuredData_AdminPage {
    
    private $page_slug = 'dm-structured-data';
    private $flow_id = null;
    private $fetch_step_id = null;
    
    public function __construct() {
        add_filter('dm_admin_pages', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_dm_structured_data_analyze', [$this, 'ajax_analyze_post']);
        add_action('wp_ajax_dm_structured_data_search_posts', [$this, 'ajax_search_posts']);
        add_action('wp_ajax_dm_structured_data_update_field', [$this, 'ajax_update_field']);
        add_action('wp_ajax_dm_structured_data_delete', [$this, 'ajax_delete_data']);
        add_action('wp_ajax_dm_structured_data_bulk_action', [$this, 'ajax_bulk_action']);
        add_action('wp_ajax_dm_structured_data_create_pipeline', [$this, 'ajax_create_pipeline']);
        add_action('wp_ajax_dm_structured_data_check_status', [$this, 'ajax_check_pipeline_status']);
        
        // Get stored IDs
        $this->flow_id = get_option('dm_structured_data_flow_id');
        $this->fetch_step_id = get_option('dm_structured_data_fetch_step_id');
    }
    
    /**
     * Check if pipeline exists by name-based detection
     * 
     * Uses Data Machine's pipeline query to locate the structured data
     * pipeline by name rather than relying on stored option IDs.
     */
    public function pipeline_exists() {
        $pipelines = apply_filters('dm_get_pipelines', []);
        foreach ($pipelines as $pipeline) {
            if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Register admin page with Data Machine
     * 
     * @param array $pages Existing admin pages
     * @return array Modified pages array
     */
    public function register_admin_page($pages) {
        $pages[$this->page_slug] = [
            'page_title' => 'Structured Data Management',
            'menu_title' => 'Structured Data',
            'capability' => 'manage_options',
            'position' => 30,
            'templates' => DM_STRUCTURED_DATA_PATH . 'includes/admin/templates/',
        ];
        
        return $pages;
    }
    
    /**
     * Enqueue admin assets for structured data page
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only enqueue on our specific page
        if (strpos($hook_suffix, $this->page_slug) === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'dm-structured-data-admin',
            DM_STRUCTURED_DATA_URL . 'includes/admin/admin-page.css',
            [],
            DM_STRUCTURED_DATA_VERSION,
            'all'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'dm-structured-data-admin',
            DM_STRUCTURED_DATA_URL . 'includes/admin/admin-page.js',
            ['jquery'],
            DM_STRUCTURED_DATA_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dm-structured-data-admin', 'dmStructuredData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dm_structured_data_admin'),
            'strings' => [
                'analyzing' => 'Analyzing post...',
                'completed' => 'Analysis completed',
                'failed' => 'Analysis failed',
                'no_results' => 'No structured data found',
                'confirm_delete' => 'Are you sure you want to delete this structured data?',
                'confirm_bulk_delete' => 'Are you sure you want to delete structured data for the selected posts?'
            ]
        ]);
    }
    
    
    /**
     * Get posts that have structured data
     */
    public function get_posts_with_structured_data() {
        $posts_query = new WP_Query([
            'meta_key' => '_dm_structured_data',
            'meta_compare' => 'EXISTS',
            'posts_per_page' => 50,
            'post_status' => 'any',
            'post_type' => ['post', 'page']
        ]);

        $structured_data_posts = [];
        
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $post_id = get_the_ID();
                $structured_data = get_post_meta($post_id, '_dm_structured_data', true);
                
                if ($structured_data && is_array($structured_data)) {
                    $structured_data_posts[] = [
                        'post_id' => $post_id,
                        'post_title' => get_the_title(),
                        'content_type' => $structured_data['content_type'] ?? 'N/A',
                        'audience_level' => $structured_data['audience_level'] ?? 'N/A',
                        'complexity_score' => $structured_data['complexity_score'] ?? null,
                        'generated_at' => $structured_data['generated_at'] ?? time()
                    ];
                }
            }
            wp_reset_postdata();
        }
        
        return $structured_data_posts;
    }
    
    
    
    /**
     * AJAX handler for analyzing a post
     */
    public function ajax_analyze_post() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Verify pipeline exists using name-based detection
        if (!$this->pipeline_exists()) {
            wp_send_json_error('Structured data pipeline not found. Please create the pipeline first.');
        }
        
        // Configure WordPress fetch handler to target specific post
        try {
            do_action('dm_update_flow_handler', $this->fetch_step_id, 'wordpress', [
                'post_id' => $post_id
            ]);
            
            // Execute pipeline flow for immediate analysis
            do_action('dm_run_flow_now', $this->flow_id);
            
            wp_send_json_success([
                'message' => 'Structured data processing started',
                'post_id' => $post_id,
                'flow_id' => $this->flow_id
            ]);
        } catch (Exception $e) {
            do_action('dm_log', 'error', 'Failed to run structured data flow: ' . $e->getMessage(), [
                'post_id' => $post_id,
                'flow_id' => $this->flow_id,
                'fetch_step_id' => $this->fetch_step_id,
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error('Failed to start structured data processing: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for searching posts
     */
    public function ajax_search_posts() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search_term) < 2) {
            wp_send_json_success(['posts' => []]);
        }
        
        $posts = get_posts([
            's' => $search_term,
            'numberposts' => 10,
            'post_status' => 'publish',
            'post_type' => ['post', 'page']
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'date' => date('M j, Y', strtotime($post->post_date))
            ];
        }
        
        wp_send_json_success(['posts' => $results]);
    }
    
    /**
     * AJAX handler for updating a field inline
     */
    public function ajax_update_field() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $field = sanitize_text_field($_POST['field'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');
        
        if (!$post_id || !$field) {
            wp_send_json_error('Invalid parameters');
        }
        
        $structured_data = DM_StructuredData_Handler::get_structured_data($post_id);
        if (!$structured_data) {
            wp_send_json_error('No structured data found');
        }
        
        // Update the specific field
        $structured_data[$field] = $value;
        update_post_meta($post_id, '_dm_structured_data', $structured_data);
        
        wp_send_json_success([
            'message' => 'Field updated',
            'field' => $field,
            'value' => $value
        ]);
    }
    
    /**
     * AJAX handler for deleting structured data
     */
    public function ajax_delete_data() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        delete_post_meta($post_id, '_dm_structured_data');
        
        wp_send_json_success([
            'message' => 'Structured data deleted',
            'post_id' => $post_id
        ]);
    }
    
    /**
     * AJAX handler for bulk actions
     */
    public function ajax_bulk_action() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $post_ids = array_map('intval', $_POST['post_ids'] ?? []);
        
        if (!$action || empty($post_ids)) {
            wp_send_json_error('Invalid parameters');
        }
        
        $results = [];
        
        switch ($action) {
            case 'delete':
                foreach ($post_ids as $post_id) {
                    delete_post_meta($post_id, '_dm_structured_data');
                    $results[] = $post_id;
                }
                wp_send_json_success([
                    'message' => 'Structured data deleted for ' . count($results) . ' posts',
                    'processed' => $results
                ]);
                break;
                
            case 'reanalyze':
                // Verify pipeline exists for bulk operations
                if (!$this->pipeline_exists()) {
                    wp_send_json_error('Structured data pipeline not found. Please create the pipeline first.');
                    break;
                }
                
                // Execute re-analysis for multiple posts using configured pipeline
                foreach ($post_ids as $post_id) {
                    // Configure WordPress fetch handler for each post
                    do_action('dm_update_flow_handler', $this->fetch_step_id, 'wordpress', [
                        'post_id' => $post_id
                    ]);
                    
                    // Execute flow for this post
                    do_action('dm_run_flow_now', $this->flow_id);
                    $results[] = $post_id;
                }
                wp_send_json_success([
                    'message' => 'Re-analysis queued for ' . count($results) . ' posts',
                    'processed' => $results
                ]);
                break;
                
            default:
                wp_send_json_error('Unknown action');
        }
    }
    
    /**
     * AJAX handler for creating the structured data pipeline asynchronously
     * 
     * Triggers Action Scheduler background job for reliable pipeline creation
     * outside of admin request context to avoid wp_send_json interruptions.
     */
    public function ajax_create_pipeline() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Check Data Machine dependency
        if (!has_filter('dm_handlers')) {
            wp_send_json_error('Data Machine plugin is required for this plugin to work.');
            return;
        }
        
        // Check if Action Scheduler is available
        if (!function_exists('as_schedule_single_action')) {
            wp_send_json_error('Action Scheduler not available. Unable to create pipeline asynchronously.');
            return;
        }
        
        // Set creation status for admin interface polling
        update_option('dm_structured_data_creation_status', 'in_progress');
        
        // Schedule Action Scheduler background job
        $scheduled = as_schedule_single_action(time(), 'dm_structured_data_create_pipeline_async');
        
        if ($scheduled) {
            wp_send_json_success([
                'message' => 'Pipeline creation started in background. Please wait...',
                'status' => 'in_progress'
            ]);
        } else {
            update_option('dm_structured_data_creation_status', 'failed');
            wp_send_json_error('Failed to schedule pipeline creation job.');
        }
    }
    
    /**
     * AJAX handler for checking pipeline creation status
     * 
     * Polls WordPress option status and verifies pipeline existence
     * for real-time admin interface updates during background creation.
     */
    public function ajax_check_pipeline_status() {
        check_ajax_referer('dm_structured_data_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $status = get_option('dm_structured_data_creation_status', 'not_started');
        
        wp_send_json_success([
            'status' => $status,
            'pipeline_exists' => $this->pipeline_exists()
        ]);
    }
}