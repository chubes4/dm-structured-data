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
     * Validates context, sanitizes parameters, and stores semantic
     * metadata to WordPress post meta for schema enhancement.
     * 
     * @param array $parameters AI tool parameters with semantic classifications
     * @param array $context Pipeline context including source_item_id and content
     * @return array Result with success status, message, and data
     */
    public function handle_tool_call($parameters, $context = []) {
        if (!isset($context['source_item_id'])) {
            return [
                'success' => false,
                'message' => 'No source post ID found in context'
            ];
        }
        
        $post_id = $context['source_item_id'];
        
        if (!get_post($post_id)) {
            return [
                'success' => false,
                'message' => "Post with ID {$post_id} not found"
            ];
        }
        
        $structured_data = $this->prepare_structured_data($parameters, $context);
        
        $result = update_post_meta($post_id, '_dm_structured_data', $structured_data);
        
        if ($result) {
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
            return [
                'success' => false,
                'message' => "Failed to save semantic analysis to post {$post_id}"
            ];
        }
    }
    
    /**
     * Prepare and sanitize structured data for storage
     * 
     * Combines AI parameters with metadata including generation timestamp,
     * AI model, content hash, and plugin version for tracking.
     * 
     * @param array $parameters AI tool parameters
     * @param array $context Pipeline context
     * @return array Structured data ready for storage
     */
    private function prepare_structured_data($parameters, $context) {
        $post_content = isset($context['content']) ? $context['content'] : '';
        $content_hash = md5($post_content);
        
        $structured_data = [
            'generated_at' => current_time('timestamp'),
            'ai_model' => $context['ai_model'] ?? 'unknown',
            'content_hash' => $content_hash,
            'plugin_version' => DM_STRUCTURED_DATA_VERSION
        ];
        
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
            if (isset($parameters[$field]) && !empty($parameters[$field])) {
                $structured_data[$field] = $this->sanitize_field($field, $parameters[$field]);
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
        switch ($field) {
            case 'content_type':
            case 'audience_level':
            case 'primary_intent':
            case 'actionability':
                return sanitize_text_field($value);
                
            case 'skill_prerequisites':
            case 'content_characteristics':
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return [];
                
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