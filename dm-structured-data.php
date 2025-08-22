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
 * Pipeline creation is now handled synchronously via CreatePipeline service
 * following clean service architecture patterns for immediate feedback
 */

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
    delete_option('dm_structured_data_fetch_step_id');
}
register_deactivation_hook(__FILE__, 'dm_structured_data_deactivate');