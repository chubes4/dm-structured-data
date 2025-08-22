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
     * Create the structured data analysis pipeline using new dm_create_ actions.
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
            
            // Create pipeline using new dm_create_pipeline filter
            $pipeline_id = apply_filters('dm_create_pipeline', false, [
                'pipeline_name' => 'Structured Data Analysis Pipeline'
            ]);
            
            if (!$pipeline_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to create pipeline.'
                ];
            }
            
            // Create steps using new dm_create_step filters
            $fetch_step_id = apply_filters('dm_create_step', false, [
                'pipeline_id' => $pipeline_id,
                'step_type' => 'fetch'
            ]);
            
            $ai_step_id = apply_filters('dm_create_step', false, [
                'pipeline_id' => $pipeline_id,
                'step_type' => 'ai'
            ]);
            
            $publish_step_id = apply_filters('dm_create_step', false, [
                'pipeline_id' => $pipeline_id,
                'step_type' => 'publish'
            ]);
            
            if (!$fetch_step_id || !$ai_step_id || !$publish_step_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to create pipeline steps.'
                ];
            }
            
            // Get the auto-created flow ID (dm_create_pipeline creates a default flow)
            $flows = apply_filters('dm_get_pipeline_flows', [], $pipeline_id);
            $flow_id = !empty($flows) ? $flows[0]['flow_id'] : null;
            
            if (!$flow_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to get flow ID.'
                ];
            }
            
            // Configure AI step with structured data tools
            $this->configure_ai_step($pipeline_id, $ai_step_id);
            
            // Configure handlers for each step
            $this->configure_step_handlers($flow_id, $pipeline_id);
            
            // Store component IDs for later use
            $this->store_pipeline_ids($pipeline_id, $flow_id);
            
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
     * Configure AI step with structured data tools and system prompt.
     * 
     * @param int $pipeline_id The pipeline ID
     * @param string $ai_step_id The AI step ID
     */
    private function configure_ai_step(int $pipeline_id, string $ai_step_id): void {
        // Get current pipeline steps
        $pipeline_steps = apply_filters('dm_get_pipeline_steps', [], $pipeline_id);
        
        // Update AI step with structured data configuration
        if (isset($pipeline_steps[$ai_step_id])) {
            $pipeline_steps[$ai_step_id] = array_merge($pipeline_steps[$ai_step_id], [
                'provider' => 'openai',
                'model' => 'gpt-5-mini',
                'providers' => [
                    'openai' => [
                        'model' => 'gpt-5-mini'
                    ]
                ],
                'system_prompt' => 'You are an AI assistant that analyzes WordPress content to extract semantic metadata for structured data enhancement.',
                'ai_tools' => [
                    'save_semantic_analysis' => [
                        'description' => 'Save semantic analysis data for structured data enhancement'
                    ]
                ]
            ]);
            
            // Update pipeline configuration
            $all_databases = apply_filters('dm_db', []);
            $db_pipelines = $all_databases['pipelines'] ?? null;
            if ($db_pipelines) {
                $db_pipelines->update_pipeline($pipeline_id, [
                    'pipeline_config' => json_encode($pipeline_steps)
                ]);
            }
        }
    }
    
    /**
     * Configure handlers for each step in the flow.
     * 
     * @param int $flow_id The flow ID
     * @param int $pipeline_id The pipeline ID
     */
    private function configure_step_handlers(int $flow_id, int $pipeline_id): void {
        $flow_config = apply_filters('dm_get_flow_config', [], $flow_id);
        $fetch_step_id = null;
        
        foreach ($flow_config as $flow_step_id => $step_config) {
            $step_type = $step_config['step_type'] ?? '';
            
            switch ($step_type) {
                case 'fetch':
                    $fetch_step_id = $flow_step_id;
                    do_action('dm_update_flow_handler', $flow_step_id, 'wordpress_fetch', [
                        'wordpress_fetch' => [
                            'post_type' => 'any',
                            'post_status' => 'any', 
                            'post_id' => 0
                        ]
                    ]);
                    break;
                    
                case 'ai':
                    // AI steps don't use handlers - configuration is stored in pipeline step definition
                    break;
                    
                case 'publish':
                    do_action('dm_update_flow_handler', $flow_step_id, 'structured_data', [
                        'structured_data' => []
                    ]);
                    break;
            }
        }
        
        // Store the fetch step ID for later use
        if ($fetch_step_id) {
            update_option('dm_structured_data_fetch_step_id', $fetch_step_id);
        }
    }
    
    /**
     * Store pipeline component IDs in WordPress options.
     * 
     * @param int $pipeline_id The pipeline ID
     * @param int $flow_id The flow ID
     */
    private function store_pipeline_ids(int $pipeline_id, int $flow_id): void {
        update_option('dm_structured_data_pipeline_id', $pipeline_id);
        update_option('dm_structured_data_flow_id', $flow_id);
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
}