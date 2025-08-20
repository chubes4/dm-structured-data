<?php
/**
 * Yoast SEO Integration for AI-Enhanced Schema Markup
 * 
 * Enhances Yoast-generated schema graphs with AI-enriched semantic
 * data by injecting aiEnrichment properties into supported schema types.
 * Supports Article, BlogPosting, WebPage, and CollectionPage schemas.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DM_StructuredData_YoastIntegration {
    
    /**
     * Initialize Yoast integration hooks
     */
    public function __construct() {
        add_filter('wpseo_schema_graph', [$this, 'enhance_schema_graph'], 10, 2);
    }
    
    /**
     * Enhance Yoast schema graph with AI enrichment data
     * 
     * Hooks into Yoast's schema generation to inject aiEnrichment
     * properties into supported schema types when semantic data exists.
     * 
     * @param array $graph Yoast schema graph
     * @param array $context Schema generation context
     * @return array Enhanced schema graph with AI enrichment
     */
    public function enhance_schema_graph($graph, $context) {
        if (!$this->is_yoast_active()) {
            return $graph;
        }
        
        $post_id = $this->get_post_id_from_context($context);
        
        if (!$post_id || !DM_StructuredData_Handler::has_structured_data($post_id)) {
            return $graph;
        }
        
        $structured_data = DM_StructuredData_Handler::get_structured_data($post_id);
        
        if (empty($structured_data)) {
            return $graph;
        }
        
        return $this->inject_ai_enrichment($graph, $structured_data);
    }
    
    private function is_yoast_active() {
        return class_exists('WPSEO_Options') || function_exists('wpseo_init');
    }
    
    /**
     * Extract post ID from schema context
     * 
     * Attempts multiple methods to determine the current post ID
     * from Yoast schema generation context or global state.
     * 
     * @param array $context Schema generation context
     * @return int|null Post ID or null if not found
     */
    private function get_post_id_from_context($context) {
        global $post;
        
        if (isset($context['post']) && is_object($context['post'])) {
            return $context['post']->ID;
        }
        
        if (isset($post) && is_object($post)) {
            return $post->ID;
        }
        
        if (is_singular()) {
            return get_the_ID();
        }
        
        return null;
    }
    
    /**
     * Inject AI enrichment into schema graph
     * 
     * Finds supported schema types (Article, BlogPosting, WebPage, 
     * CollectionPage) and adds aiEnrichment object with semantic metadata.
     * 
     * @param array $graph Schema graph pieces
     * @param array $structured_data Semantic metadata
     * @return array Modified schema graph
     */
    private function inject_ai_enrichment($graph, $structured_data) {
        if (!is_array($graph)) {
            return $graph;
        }
        
        $ai_enrichment = $this->build_ai_enrichment_object($structured_data);
        
        if (empty($ai_enrichment)) {
            return $graph;
        }
        
        foreach ($graph as &$graph_piece) {
            if (!is_array($graph_piece) || !isset($graph_piece['@type'])) {
                continue;
            }
            
            $type = $graph_piece['@type'];
            
            if (in_array($type, ['Article', 'BlogPosting', 'WebPage', 'CollectionPage'])) {
                $graph_piece['aiEnrichment'] = $ai_enrichment;
                break;
            }
        }
        
        return $graph;
    }
    
    /**
     * Build AI enrichment object for schema injection
     * 
     * Transforms internal semantic data structure to camelCase
     * properties suitable for schema markup output.
     * 
     * @param array $structured_data Internal semantic metadata
     * @return array AI enrichment object for schema
     */
    private function build_ai_enrichment_object($structured_data) {
        $enrichment = [];
        
        $field_mapping = [
            'content_type' => 'contentType',
            'audience_level' => 'audienceLevel',
            'skill_prerequisites' => 'skillPrerequisites', 
            'content_characteristics' => 'contentCharacteristics',
            'primary_intent' => 'primaryIntent',
            'actionability' => 'actionability',
            'complexity_score' => 'complexityScore',
            'estimated_completion_time' => 'estimatedCompletionTime'
        ];
        
        foreach ($field_mapping as $source_field => $target_field) {
            if (isset($structured_data[$source_field]) && !empty($structured_data[$source_field])) {
                $enrichment[$target_field] = $structured_data[$source_field];
            }
        }
        
        if (isset($structured_data['generated_at'])) {
            $enrichment['aiAnalysisDate'] = date('Y-m-d\TH:i:s\Z', $structured_data['generated_at']);
        }
        
        if (isset($structured_data['ai_model'])) {
            $enrichment['aiModel'] = $structured_data['ai_model'];
        }
        
        return $enrichment;
    }
    
    /**
     * Generate AI enrichment object for custom integrations
     * 
     * Provides access to AI enrichment data for developers
     * implementing custom schema or non-Yoast integrations.
     * 
     * @param int $post_id WordPress post ID
     * @return array|null AI enrichment object or null if no data
     */
    public static function get_enriched_schema_for_post($post_id) {
        if (!DM_StructuredData_Handler::has_structured_data($post_id)) {
            return null;
        }
        
        $structured_data = DM_StructuredData_Handler::get_structured_data($post_id);
        $integration = new self();
        
        return $integration->build_ai_enrichment_object($structured_data);
    }
}