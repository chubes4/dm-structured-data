<?php
/**
 * Pipeline Creation Service for Structured Data Plugin
 * 
 * Handles the creation of Data Machine pipelines, flows, and steps for structured data analysis.
 * Provides a clean service interface for pipeline creation operations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DM_StructuredData_CreatePipeline {
    
    /**
     * Create the structured data analysis pipeline using atomic database operations.
     * 
     * Creates a complete pipeline with fetch, AI, and publish steps configured
     * for WordPress content analysis and structured data enhancement.
     * 
     * @return array Success response with pipeline details or error information
     */
    public function create_pipeline(): array {
        try {
            // Check Data Machine dependency
            if (!has_filter('dm_handlers')) {
                return [
                    'success' => false,
                    'error' => 'Data Machine plugin is required for this plugin to work.'
                ];
            }
            
            // Get database services directly
            $all_databases = apply_filters('dm_db', []);
            $db_pipelines = $all_databases['pipelines'] ?? null;
            $db_flows = $all_databases['flows'] ?? null;
            
            if (!$db_pipelines || !$db_flows) {
                return [
                    'success' => false,
                    'error' => 'Data Machine database services not available.'
                ];
            }
            
            // Generate UUIDs for pipeline steps
            $fetch_step_id = wp_generate_uuid4();
            $ai_step_id = wp_generate_uuid4();
            $update_step_id = wp_generate_uuid4();
            
            // Create pipeline with complete configuration
            $pipeline_id = $db_pipelines->create_pipeline([
                'pipeline_name' => 'Structured Data Analysis Pipeline',
                'pipeline_config' => [
                    $fetch_step_id => [
                        'step_type' => 'fetch',
                        'execution_order' => 0,
                        'pipeline_step_id' => $fetch_step_id,
                        'label' => 'WordPress Fetch'
                    ],
                    $ai_step_id => [
                        'step_type' => 'ai',
                        'execution_order' => 1,
                        'pipeline_step_id' => $ai_step_id,
                        'provider' => 'openai',
                        'model' => 'gpt-5-mini',
                        'providers' => [
                            'openai' => ['model' => 'gpt-5-mini']
                        ],
                        'system_prompt' => 'You are an AI assistant that analyzes WordPress content to extract semantic metadata for structured data enhancement. Analyze the content and provide semantic classifications including content_type, audience_level, skill_prerequisites, content_characteristics, primary_intent, actionability, complexity_score, and estimated_completion_time.',
                        'label' => 'AI Analysis'
                    ],
                    $update_step_id => [
                        'step_type' => 'update',
                        'execution_order' => 2,
                        'pipeline_step_id' => $update_step_id,
                        'label' => 'Update Post Metadata'
                    ]
                ]
            ]);
            
            if (!$pipeline_id) {
                return ['success' => false, 'error' => 'Failed to create pipeline.'];
            }
            
            // Create flow - returns auto-incremented flow_id
            $flow_id = $db_flows->create_flow([
                'pipeline_id' => $pipeline_id,
                'flow_name' => 'Structured Data Analysis Flow',
                'flow_config' => [], // Empty initially 
                'scheduling_config' => ['interval' => 'manual']
            ]);
            
            if (!$flow_id) {
                return ['success' => false, 'error' => 'Failed to create flow.'];
            }
            
            // Build complete flow_config using returned flow_id
            $flow_config = [];
            $steps_data = [
                ['pipeline_step_id' => $fetch_step_id, 'step_type' => 'fetch', 'execution_order' => 0],
                ['pipeline_step_id' => $ai_step_id, 'step_type' => 'ai', 'execution_order' => 1], 
                ['pipeline_step_id' => $update_step_id, 'step_type' => 'update', 'execution_order' => 2]
            ];
            
            foreach ($steps_data as $step) {
                $flow_step_id = apply_filters('dm_generate_flow_step_id', '', $step['pipeline_step_id'], $flow_id);
                $flow_config[$flow_step_id] = [
                    'flow_step_id' => $flow_step_id,
                    'step_type' => $step['step_type'],
                    'pipeline_step_id' => $step['pipeline_step_id'],
                    'pipeline_id' => $pipeline_id,
                    'flow_id' => $flow_id,
                    'execution_order' => $step['execution_order'],
                    'handler' => null
                ];
            }
            
            // Update flow with complete config
            $success = $db_flows->update_flow($flow_id, [
                'flow_config' => $flow_config
            ]);
            
            if (!$success) {
                return ['success' => false, 'error' => 'Failed to update flow config.'];
            }
            
            // Configure handlers for each flow step
            foreach ($flow_config as $flow_step_id => $step_config) {
                switch ($step_config['step_type']) {
                    case 'fetch':
                        do_action('dm_update_flow_handler', $flow_step_id, 'wordpress_fetch', [
                            'post_type' => 'any',
                            'post_status' => 'any',
                            'post_id' => 0
                        ]);
                        break;
                    case 'update':
                        do_action('dm_update_flow_handler', $flow_step_id, 'structured_data', []);
                        break;
                }
            }
            
            // Store IDs
            update_option('dm_structured_data_pipeline_id', $pipeline_id);
            update_option('dm_structured_data_flow_id', $flow_id);
            
            return [
                'success' => true,
                'message' => 'Pipeline created successfully!',
                'pipeline_id' => $pipeline_id,
                'flow_id' => $flow_id
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create pipeline: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if the structured data pipeline already exists.
     * 
     * @return bool True if pipeline exists, false otherwise
     */
    public function pipeline_exists(): bool {
        $pipelines = apply_filters('dm_get_pipelines', []);
        foreach ($pipelines as $pipeline) {
            if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the flow step ID for a specific step type in the structured data flow.
     * 
     * @param string $step_type Step type to find (fetch, ai, publish)
     * @return string|null Flow step ID or null if not found
     */
    public function get_flow_step_id(string $step_type): ?string {
        $flow_id = get_option('dm_structured_data_flow_id');
        if (!$flow_id) {
            return null;
        }
        
        // Get database services
        $all_databases = apply_filters('dm_db', []);
        $db_flows = $all_databases['flows'] ?? null;
        
        if (!$db_flows) {
            return null;
        }
        
        $flow = $db_flows->get_flow($flow_id);
        if (!$flow || !isset($flow['flow_config'])) {
            return null;
        }
        
        // Find flow step with matching step_type
        foreach ($flow['flow_config'] as $flow_step_id => $step_config) {
            if (isset($step_config['step_type']) && $step_config['step_type'] === $step_type) {
                return $flow_step_id;
            }
        }
        
        return null;
    }
}