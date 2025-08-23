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
 * provides admin interface with synchronous pipeline creation service.
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
        add_filter('ai_tools', [$this, 'register_ai_tools'], 10, 3);
        
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
        require_once DM_STRUCTURED_DATA_PATH . 'includes/CreatePipeline.php';
        
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
            'type' => 'update',
            'class' => 'DM_StructuredData_Handler',
            'label' => 'Structured Data',
            'description' => 'Update existing WordPress posts with AI semantic analysis for enhanced schema markup'
        ];
        
        return $handlers;
    }
    
    /**
     * Register save_semantic_analysis AI tool with Data Machine
     * 
     * Follows Data Machine's established conditional tool registration pattern.
     * Only registers tool when structured_data handler is the target handler.
     * 
     * @param array $tools Existing AI tools array
     * @param string $handler_slug Handler slug for conditional registration
     * @param array $handler_config Handler configuration
     * @return array Modified tools array with semantic analysis tool
     */
    public function register_ai_tools($tools, $handler_slug = null, $handler_config = []) {
        // Only generate structured_data tool when it's the target handler
        if ($handler_slug === 'structured_data') {
            $tools['save_semantic_analysis'] = [
                'class' => 'DM_StructuredData_Handler',
                'method' => 'handle_tool_call',
                'handler' => 'structured_data',
                'description' => 'Extract semantic metadata from content for AI crawler optimization',
                'handler_config' => $handler_config,
            'parameters' => [
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Content classification: tutorial, guide, reference, opinion, review, how-to, announcement, case-study, comparison'
                ],
                'audience_level' => [
                    'type' => 'string', 
                    'description' => 'Skill level: beginner, intermediate, advanced, expert'
                ],
                'skill_prerequisites' => [
                    'type' => 'array',
                    'description' => 'Required knowledge/skills'
                ],
                'content_characteristics' => [
                    'type' => 'array',
                    'description' => 'Content traits: practical, theoretical, step-by-step, code-heavy, visual, reference, hands-on, conceptual'
                ],
                'primary_intent' => [
                    'type' => 'string',
                    'description' => 'Main purpose: educational, commercial, informational, entertainment, promotional, problem-solving'
                ],
                'actionability' => [
                    'type' => 'string',
                    'description' => 'Implementation level: theoretical, practical, step-by-step, reference-only, immediately-actionable'
                ],
                'complexity_score' => [
                    'type' => 'integer',
                    'description' => 'Difficulty rating 1-10'
                ],
                'estimated_completion_time' => [
                    'type' => 'integer', 
                    'description' => 'Implementation time in minutes'
                ]
            ]
        ];
        }
        
        return $tools;
    }
    
}

// Initialize plugin
new DM_StructuredData();


/**
 * Plugin deactivation hook
 * 
 * Cleans up stored WordPress options but preserves pipeline entities
 * in Data Machine for potential reactivation. Users can manually delete
 * pipelines via Data Machine interface if needed.
 */
function dm_structured_data_deactivate() {
    // Clean up stored options only - preserve Data Machine entities for reactivation
    delete_option('dm_structured_data_pipeline_id');
    delete_option('dm_structured_data_flow_id');
}
register_deactivation_hook(__FILE__, 'dm_structured_data_deactivate');