<?php
/**
 * Structured Data Handler for Data Machine Integration
 * 
 * Processes AI tool calls for semantic content analysis and manages
 * storage of semantic metadata in WordPress post meta. Handles content
 * hash validation for detecting when analysis updates are needed.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DM_StructuredData_Handler {
    
    /**
     * Process AI tool call for semantic analysis
     * 
     * Called by Data Machine publish step with AI tool parameters and tool definition.
     * Extracts post context from parameters and stores semantic metadata to WordPress post meta.
     * 
     * @param array $parameters AI tool parameters with semantic classifications
     * @param array $tool_def Tool definition from Data Machine (contains class, method, handler info)
     * @return array Result with success status, message, and data
     */
    public function handle_tool_call($parameters, $tool_def = []) {
        // Extract post ID using multiple strategies from parameters and tool definition
        $post_id = $this->extract_post_id_from_context($parameters, $tool_def);
        
        if (!$post_id) {
            do_action('dm_log', 'error', 'StructuredData Handler: No post ID found', [
                'parameters_keys' => array_keys($parameters),
                'tool_def' => $tool_def
            ]);
            return [
                'success' => false,
                'message' => 'No post ID found in AI tool parameters'
            ];
        }
        
        $post = get_post($post_id);
        if (!$post) {
            do_action('dm_log', 'error', 'StructuredData Handler: Post not found', [
                'post_id' => $post_id
            ]);
            return [
                'success' => false,
                'message' => "Post with ID {$post_id} not found"
            ];
        }
        
        $structured_data = $this->prepare_structured_data($parameters, $post);
        
        if (empty($structured_data)) {
            do_action('dm_log', 'error', 'StructuredData Handler: No valid semantic data to save', [
                'post_id' => $post_id,
                'parameters_keys' => array_keys($parameters)
            ]);
            return [
                'success' => false,
                'message' => 'No valid semantic data to save'
            ];
        }
        
        $result = update_post_meta($post_id, '_dm_structured_data', $structured_data);
        
        if ($result) {
            do_action('dm_log', 'info', 'StructuredData Handler: Analysis saved successfully', [
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'data_keys' => array_keys($structured_data)
            ]);
            return [
                'success' => true,
                'message' => "Semantic analysis saved to post {$post_id}",
                'data' => [
                    'post_id' => $post_id,
                    'post_url' => get_permalink($post_id),
                    'structured_data' => $structured_data
                ]
            ];
        } else {
            do_action('dm_log', 'error', 'StructuredData Handler: Failed to save analysis', [
                'post_id' => $post_id,
                'post_title' => $post->post_title
            ]);
            return [
                'success' => false,
                'message' => "Failed to save semantic analysis to post {$post_id}"
            ];
        }
    }
    
    /**
     * Extract post ID from tool parameters following established pattern
     * 
     * The Update step provides original_id from data packet metadata.
     * This follows the single, established pattern from Data Machine.
     * 
     * @param array $parameters Tool call parameters from Update step
     * @param array $tool_def Tool definition with handler config
     * @return int|null Post ID or null if not found
     */
    private function extract_post_id_from_context($parameters, $tool_def) {
        // Primary pattern: Update step extracts original_id from data packet metadata
        if (isset($parameters['original_id']) && is_numeric($parameters['original_id'])) {
            do_action('dm_log', 'debug', 'StructuredData Handler: Found post ID from Update step', [
                'post_id' => (int)$parameters['original_id']
            ]);
            return (int)$parameters['original_id'];
        }
        
        // Debugging - log what was actually provided
        do_action('dm_log', 'error', 'StructuredData Handler: original_id not found in parameters', [
            'parameters_keys' => array_keys($parameters),
            'expected_field' => 'original_id'
        ]);
        
        return null;
    }
    
    /**
     * Prepare and sanitize structured data for storage
     * 
     * Extracts only semantic analysis fields from AI parameters,
     * removing unnecessary metadata bloat for clean storage.
     * 
     * @param array $parameters AI tool parameters
     * @param WP_Post $post WordPress post object
     * @return array Clean structured data with only semantic fields
     */
    private function prepare_structured_data($parameters, $post) {
        $structured_data = [];
        
        $semantic_fields = [
            'content_type',
            'audience_level', 
            'skill_prerequisites',
            'content_characteristics',
            'primary_intent',
            'actionability',
            'complexity_score',
            'estimated_completion_time'
        ];
        
        foreach ($semantic_fields as $field) {
            if (isset($parameters[$field])) {
                $sanitized = $this->sanitize_field($field, $parameters[$field]);
                if ($sanitized !== null) {
                    $structured_data[$field] = $sanitized;
                }
            }
        }
        
        return $structured_data;
    }
    
    /**
     * Sanitize individual semantic field values
     * 
     * Applies appropriate sanitization based on field type:
     * - String fields: sanitize_text_field
     * - Array fields: sanitize each element
     * - Integer fields: validate and ensure positive values
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @return mixed Sanitized value
     */
    private function sanitize_field($field, $value) {
        // Only filter truly empty values
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            return null;
        }
        
        switch ($field) {
            case 'content_type':
            case 'audience_level':
            case 'primary_intent':
            case 'actionability':
                return sanitize_text_field($value);
                
            case 'skill_prerequisites':
            case 'content_characteristics':
                if (is_array($value)) {
                    $filtered = array_filter(array_map('sanitize_text_field', $value));
                    return !empty($filtered) ? array_values($filtered) : null;
                }
                return null;
                
            case 'complexity_score':
            case 'estimated_completion_time':
                return max(0, intval($value));
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Retrieve structured data for a post
     * 
     * @param int $post_id WordPress post ID
     * @return array|false Structured data array or false if none exists
     */
    public static function get_structured_data($post_id) {
        return get_post_meta($post_id, '_dm_structured_data', true);
    }
    
    /**
     * Check if post has valid structured data
     * 
     * @param int $post_id WordPress post ID
     * @return bool True if post has valid structured data
     */
    public static function has_structured_data($post_id) {
        $data = self::get_structured_data($post_id);
        return !empty($data) && is_array($data);
    }
    
    /**
     * Determine if structured data needs updating
     * 
     * Compares stored content hash with current post content
     * to detect if semantic analysis should be refreshed.
     * 
     * @param int $post_id WordPress post ID
     * @return bool True if data needs updating
     */
    public static function needs_update($post_id) {
        $data = self::get_structured_data($post_id);
        
        if (empty($data) || !isset($data['content_hash'])) {
            return true;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $current_hash = md5($post->post_content);
        return $data['content_hash'] !== $current_hash;
    }
}