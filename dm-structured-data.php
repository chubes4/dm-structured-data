<?php
/**
 * Plugin Name: Data Machine Structured Data
 * Description: AI-powered semantic analysis for enhanced WordPress structured data via Data Machine pipelines
 * Version: 1.0.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Requires Plugins: data-machine
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DM_STRUCTURED_DATA_VERSION', '1.0.0');
define('DM_STRUCTURED_DATA_PATH', plugin_dir_path(__FILE__));
define('DM_STRUCTURED_DATA_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class for Data Machine Structured Data Extension
 * 
 * Registers handlers and AI tools with Data Machine pipeline system,
 * initializes Yoast SEO integration for enhanced schema markup, and
 * provides admin interface with Action Scheduler-based pipeline creation.
 */
class DM_StructuredData {
    
    /**
     * Initialize plugin hooks
     */
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    /**
     * Initialize plugin after all plugins are loaded
     */
    public function init() {
        $this->load_includes();
        
        add_filter('dm_handlers', [$this, 'register_handlers']);
        add_filter('ai_tools', [$this, 'register_ai_tools']);
        
        // Only load Yoast integration if Yoast SEO is active
        if ($this->is_yoast_active()) {
            new DM_StructuredData_YoastIntegration();
        }
        
        if (is_admin()) {
            global $dm_structured_data_admin_page;
            $dm_structured_data_admin_page = new DM_StructuredData_AdminPage();
        }
    }
    
    private function is_yoast_active() {
        return defined('WPSEO_VERSION') || class_exists('WPSEO_Options');
    }
    
    private function load_includes() {
        require_once DM_STRUCTURED_DATA_PATH . 'includes/StructuredDataHandler.php';
        
        // Only load Yoast integration if Yoast is active
        if ($this->is_yoast_active()) {
            require_once DM_STRUCTURED_DATA_PATH . 'includes/YoastIntegration.php';
        }
        
        if (is_admin()) {
            require_once DM_STRUCTURED_DATA_PATH . 'includes/admin/AdminPage.php';
        }
    }
    
    /**
     * Register structured data handler with Data Machine
     * 
     * @param array $handlers Existing handlers array
     * @return array Modified handlers array with structured_data handler
     */
    public function register_handlers($handlers) {
        $handlers['structured_data'] = [
            'type' => 'publish',
            'class' => 'DM_StructuredData_Handler',
            'label' => 'Structured Data',
            'description' => 'Save AI semantic analysis to WordPress post meta for enhanced schema markup'
        ];
        
        return $handlers;
    }
    
    /**
     * Register save_semantic_analysis AI tool with Data Machine
     * 
     * Defines AI tool parameters for semantic content analysis including
     * content classification, audience targeting, and complexity metrics.
     * 
     * @param array $tools Existing AI tools array
     * @return array Modified tools array with semantic analysis tool
     */
    public function register_ai_tools($tools) {
        $tools['save_semantic_analysis'] = [
            'class' => 'DM_StructuredData_Handler',
            'method' => 'handle_tool_call',
            'handler' => 'structured_data',
            'description' => 'Save semantic content analysis to WordPress post meta for AI-enhanced structured data. Analyze content and extract semantic metadata including content type, audience level, prerequisites, and characteristics that help AI crawlers better understand and categorize the content.',
            'parameters' => [
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Primary content classification: tutorial, guide, reference, opinion, review, how-to, announcement, case-study, comparison',
                    'required' => false
                ],
                'audience_level' => [
                    'type' => 'string', 
                    'description' => 'Target skill level: beginner, intermediate, advanced, expert',
                    'required' => false
                ],
                'skill_prerequisites' => [
                    'type' => 'array',
                    'description' => 'Required knowledge or skills to understand this content (e.g., "PHP basics", "WordPress hooks", "JavaScript")',
                    'required' => false
                ],
                'content_characteristics' => [
                    'type' => 'array',
                    'description' => 'Content traits: practical, theoretical, step-by-step, code-heavy, visual, reference, hands-on, conceptual',
                    'required' => false
                ],
                'primary_intent' => [
                    'type' => 'string',
                    'description' => 'Main purpose: educational, commercial, informational, entertainment, promotional, problem-solving',
                    'required' => false
                ],
                'actionability' => [
                    'type' => 'string',
                    'description' => 'Implementation level: theoretical, practical, step-by-step, reference-only, immediately-actionable',
                    'required' => false
                ],
                'complexity_score' => [
                    'type' => 'integer',
                    'description' => 'Content difficulty rating from 1-10 (1=very simple, 10=expert level)',
                    'required' => false
                ],
                'estimated_completion_time' => [
                    'type' => 'integer', 
                    'description' => 'Time to complete/implement content in minutes',
                    'required' => false
                ]
            ]
        ];
        
        return $tools;
    }
    
}

// Initialize plugin
new DM_StructuredData();

/**
 * Action Scheduler hook for asynchronous pipeline creation
 * 
 * Creates the complete structured data pipeline in background to avoid
 * wp_send_json interruption issues during complex Data Machine setup.
 * Uses name-based pipeline detection and stores component IDs in options.
 */
add_action('dm_structured_data_create_pipeline_async', 'dm_structured_data_create_pipeline_job');

function dm_structured_data_create_pipeline_job() {
    try {
        // Check Data Machine dependency availability in background context
        if (!has_filter('dm_handlers')) {
            update_option('dm_structured_data_creation_status', 'failed');
            do_action('dm_log', 'error', 'Data Machine plugin not available during async pipeline creation');
            return;
        }
        
        // Create pipeline using Data Machine action (reliable in Action Scheduler context)
        do_action('dm_create', 'pipeline', ['pipeline_name' => 'Structured Data Analysis Pipeline']);
        
        // Use name-based detection to get pipeline_id (more reliable than relying on action response)
        $pipelines = apply_filters('dm_get_pipelines', []);
        $pipeline_id = null;
        foreach ($pipelines as $pipeline) {
            if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
                $pipeline_id = $pipeline['pipeline_id'];
                break;
            }
        }
        
        if (!$pipeline_id) {
            update_option('dm_structured_data_creation_status', 'failed');
            do_action('dm_log', 'error', 'Failed to create or locate Structured Data Analysis Pipeline');
            return;
        }
        
        // Create pipeline steps (Data Machine actions work reliably in background jobs)
        do_action('dm_create', 'step', [
            'pipeline_id' => $pipeline_id,
            'step_type' => 'fetch'
        ]);
        
        do_action('dm_create', 'step', [
            'pipeline_id' => $pipeline_id,
            'step_type' => 'ai'
        ]);
        
        do_action('dm_create', 'step', [
            'pipeline_id' => $pipeline_id,
            'step_type' => 'publish'
        ]);
        
        // Data Machine automatically creates a Draft Flow when steps are added
        $flows = apply_filters('dm_get_pipeline_flows', [], $pipeline_id);
        if (empty($flows)) {
            update_option('dm_structured_data_creation_status', 'failed');
            do_action('dm_log', 'error', 'No flows found for Structured Data Analysis Pipeline');
            return;
        }
        
        $flow_id = $flows[0]['flow_id'];
        
        // Get flow configuration
        $flow_config = apply_filters('dm_get_flow_config', [], $flow_id);
        if (empty($flow_config)) {
            update_option('dm_structured_data_creation_status', 'failed');
            do_action('dm_log', 'error', 'Flow configuration not found for Structured Data Analysis Pipeline');
            return;
        }
        
        // Configure Data Machine handlers for each step type
        $fetch_step_id = null;
        foreach ($flow_config as $flow_step_id => $step_config) {
            $step_type = $step_config['step_type'] ?? '';
            
            switch ($step_type) {
                case 'fetch':
                    $fetch_step_id = $flow_step_id;
                    do_action('dm_update_flow_handler', $flow_step_id, 'wordpress', [
                        'post_type' => 'any',
                        'post_status' => 'any',
                        'post_id' => 0
                    ]);
                    break;
                    
                case 'ai':
                    do_action('dm_update_flow_handler', $flow_step_id, 'ai', [
                        'provider' => 'openai',
                        'model' => 'gpt-5-mini',
                        'system_prompt' => 'You are an AI assistant that analyzes WordPress content to extract semantic metadata for structured data enhancement.',
                        'tools' => ['save_semantic_analysis']
                    ]);
                    break;
                    
                case 'publish':
                    do_action('dm_update_flow_handler', $flow_step_id, 'structured_data', []);
                    break;
            }
        }
        
        // Store component IDs for admin interface access
        update_option('dm_structured_data_pipeline_id', $pipeline_id);
        update_option('dm_structured_data_flow_id', $flow_id);
        update_option('dm_structured_data_fetch_step_id', $fetch_step_id);
        
        // Update status for admin interface polling
        update_option('dm_structured_data_creation_status', 'completed');
        
        do_action('dm_log', 'info', 'Structured data pipeline created successfully via Action Scheduler', [
            'pipeline_id' => $pipeline_id,
            'flow_id' => $flow_id,
            'fetch_step_id' => $fetch_step_id
        ]);
        
    } catch (Exception $e) {
        update_option('dm_structured_data_creation_status', 'failed');
        do_action('dm_log', 'error', 'Failed to create pipeline via Action Scheduler: ' . $e->getMessage());
    }
}

/**
 * Plugin deactivation hook
 * 
 * Cleans up stored WordPress options but preserves pipeline entities
 * in Data Machine to avoid wp_send_json_success() interruption during
 * deactivation. Users can manually delete pipelines if needed.
 */
function dm_structured_data_deactivate() {
    // Clean up stored options only - don't attempt to delete Data Machine entities
    // during deactivation as dm_delete action causes wp_send_json_success() redirect
    // Users can manually delete pipelines from Data Machine admin if needed
    delete_option('dm_structured_data_pipeline_id');
    delete_option('dm_structured_data_flow_id');
    delete_option('dm_structured_data_fetch_step_id');
}
register_deactivation_hook(__FILE__, 'dm_structured_data_deactivate');