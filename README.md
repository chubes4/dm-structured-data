# Data Machine - Structured Data Extension

**⚠️ EXPERIMENTAL PLUGIN** - This is an experimental extension for testing AI-powered structured data enhancement concepts.

AI-powered semantic analysis for enhanced WordPress structured data via [Data Machine](https://github.com/chubes4/data-machine) pipelines. This plugin automatically analyzes WordPress content using AI and injects semantic metadata into Yoast SEO schema markup to improve AI crawler understanding and search engine optimization.

## Features

- **Automated Semantic Analysis**: AI-powered content classification and metadata extraction
- **Yoast SEO Integration**: Seamless enhancement of existing schema markup
- **Content Intelligence**: Extracts audience level, complexity, prerequisites, and actionability
- **AI Crawler Optimization**: Structured data designed for modern AI search systems
- **WordPress Native**: Built on WordPress standards with proper sanitization and security

## Requirements

- WordPress 5.0 or higher
- PHP 8.0 or higher
- **Data Machine plugin** (required - plugin will not activate without it)
- Yoast SEO plugin (optional - for automatic schema enhancement)

## Installation

1. **Install Data Machine Plugin** (required dependency)
   ```bash
   # Install from: https://github.com/chubes4/data-machine
   # Ensure Data Machine is installed and activated first
   ```

2. **Install DM Structured Data Extension**
   ```bash
   # Upload plugin files to wp-content/plugins/dm-structured-data/
   # Or install via WordPress admin
   ```

3. **Activate Plugin**
   - Navigate to WordPress Admin → Plugins
   - Activate "Data Machine - Structured Data Extension"
   - **Note**: Plugin will not activate if Data Machine is not installed/active

4. **Optional: Install Yoast SEO** (for schema enhancement)
   - Install and activate Yoast SEO plugin
   - Structured data will automatically enhance Yoast's schema output

## Configuration

### Data Machine Pipeline Setup

The plugin provides an admin interface for managing the structured data analysis pipeline. Pipeline creation uses a synchronous service for immediate feedback and reliable setup.

**Admin Interface Access**:
- Navigate to Data Machine → Structured Data in WordPress admin
- Create pipeline through the admin interface (immediate setup)
- Monitor pipeline status and manage semantic data

**Synchronous Pipeline Creation**:
- Pipeline Name: "Structured Data Analysis Pipeline"
- Handler: `structured_data` (publish type)
- AI Tool: `save_semantic_analysis`
- Processing: Immediate synchronous creation via CreatePipeline service

### WordPress Integration

**Automated Setup** (Recommended)
1. Navigate to Data Machine → Structured Data in WordPress admin
2. Click "Create Pipeline" for immediate setup
3. Receive instant success/error feedback
4. Use admin interface to analyze posts and manage semantic data
5. Pipeline automatically configures: Fetch (WordPress) → AI → Publish (Structured Data)

**Manual Pipeline Creation** (Advanced)
```php
// Create pipeline using service class
$pipeline_service = new DM_StructuredData_CreatePipeline();
$result = $pipeline_service->create_pipeline();

if ($result['success']) {
    echo "Pipeline created successfully!";
    echo "Pipeline ID: " . $result['pipeline_id'];
    echo "Flow ID: " . $result['flow_id'];
} else {
    echo "Error: " . $result['error'];
}

// Check if pipeline exists
if ($pipeline_service->pipeline_exists()) {
    echo "Structured Data Analysis Pipeline is available";
}
```

## Usage Examples

### Basic Content Analysis

The plugin works through Data Machine's pipeline system:

1. **WordPress Fetch**: Data Machine's WordPress handler retrieves post content
2. **AI Analysis**: AI step analyzes content using the `save_semantic_analysis` tool
3. **Structured Data Storage**: Plugin handler stores analysis to WordPress post meta
4. **Schema Enhancement**: Yoast integration injects AI enrichment into schema output

### Retrieving Semantic Data

```php
// Get semantic data for a post
$post_id = 123;
$semantic_data = DM_StructuredData_Handler::get_structured_data($post_id);

if ($semantic_data) {
    echo "Content Type: " . $semantic_data['content_type'];
    echo "Audience Level: " . $semantic_data['audience_level'];
    echo "Complexity Score: " . $semantic_data['complexity_score'];
}
```

### Checking Data Status

```php
// Check if post has semantic data
if (DM_StructuredData_Handler::has_structured_data($post_id)) {
    echo "Post has AI-generated semantic data";
}

// Check if data needs updating
if (DM_StructuredData_Handler::needs_update($post_id)) {
    echo "Content has changed, semantic data should be refreshed";
}
```

### Custom Schema Enhancement

```php
// Get AI enrichment object for custom schema integration
$enrichment = DM_StructuredData_YoastIntegration::get_enriched_schema_for_post($post_id);

if ($enrichment) {
    // Add to custom schema
    $custom_schema['aiEnrichment'] = $enrichment;
}
```

## API Reference

### DM_StructuredData_Handler

Core handler class for processing AI tool calls and managing semantic data.

#### Methods

**`handle_tool_call($parameters, $context)`**
- Processes AI analysis results and stores semantic metadata
- **Parameters**: 
  - `$parameters` (array): AI tool parameters with semantic classifications
  - `$context` (array): Pipeline context including post ID and content
- **Returns**: Array with success status and message

**`get_structured_data($post_id)`**
- Retrieves stored semantic data for a post
- **Parameters**: `$post_id` (int): WordPress post ID
- **Returns**: Array of semantic metadata or false if none exists

**`has_structured_data($post_id)`**
- Checks if post has valid semantic data
- **Parameters**: `$post_id` (int): WordPress post ID  
- **Returns**: Boolean indicating data existence

**`needs_update($post_id)`**
- Determines if semantic data should be refreshed based on content changes
- **Parameters**: `$post_id` (int): WordPress post ID
- **Returns**: Boolean indicating if update is needed

### DM_StructuredData_YoastIntegration

Yoast SEO integration class for schema enhancement.

#### Methods

**`enhance_schema_graph($graph, $context)`**
- Enhances Yoast schema graph with AI enrichment data
- **Parameters**:
  - `$graph` (array): Yoast schema graph
  - `$context` (array): Schema generation context
- **Returns**: Enhanced schema graph with aiEnrichment properties

**`get_enriched_schema_for_post($post_id)`**
- Generates AI enrichment object for custom integrations
- **Parameters**: `$post_id` (int): WordPress post ID
- **Returns**: AI enrichment object or null if no data

## Semantic Metadata Fields

The AI analysis extracts the following semantic classifications:

### Content Classification

**`content_type`** - Primary content category
- Values: `tutorial`, `guide`, `reference`, `opinion`, `review`, `how-to`, `announcement`, `case-study`, `comparison`
- Example: `"tutorial"`

**`primary_intent`** - Main content purpose  
- Values: `educational`, `commercial`, `informational`, `entertainment`, `promotional`, `problem-solving`
- Example: `"educational"`

### Audience Targeting

**`audience_level`** - Target skill level
- Values: `beginner`, `intermediate`, `advanced`, `expert`
- Example: `"intermediate"`

**`skill_prerequisites`** - Required knowledge/skills
- Type: Array of strings
- Example: `["PHP basics", "WordPress hooks", "JavaScript"]`

### Content Characteristics  

**`content_characteristics`** - Content traits and style
- Values: `practical`, `theoretical`, `step-by-step`, `code-heavy`, `visual`, `reference`, `hands-on`, `conceptual`
- Type: Array of strings
- Example: `["practical", "step-by-step", "code-heavy"]`

**`actionability`** - Implementation level
- Values: `theoretical`, `practical`, `step-by-step`, `reference-only`, `immediately-actionable`
- Example: `"immediately-actionable"`

### Complexity Metrics

**`complexity_score`** - Difficulty rating
- Type: Integer (1-10)
- Scale: 1=very simple, 10=expert level
- Example: `6`

**`estimated_completion_time`** - Implementation time
- Type: Integer (minutes)
- Example: `45`

## Schema Output Example

Enhanced Yoast schema with AI enrichment:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Building WordPress Custom Post Types",
  "author": {
    "@type": "Person", 
    "name": "Chris Huber"
  },
  "datePublished": "2025-01-20T10:00:00Z",
  "aiEnrichment": {
    "contentType": "tutorial",
    "audienceLevel": "intermediate", 
    "skillPrerequisites": ["PHP basics", "WordPress development"],
    "contentCharacteristics": ["practical", "step-by-step", "code-heavy"],
    "primaryIntent": "educational",
    "actionability": "immediately-actionable",
    "complexityScore": 6,
    "estimatedCompletionTime": 45
  }
}
```

## Data Storage

Semantic metadata is stored in WordPress post meta:

**Meta Key**: `_dm_structured_data`  
**Storage Format**: Serialized array with sanitized values
**Includes**: All semantic fields plus generation metadata

```php
// Example stored data structure
[
    'generated_at' => 1705750200,
    'ai_model' => 'claude-3-sonnet',
    'content_hash' => 'abc123...',
    'plugin_version' => '1.0.0',
    'content_type' => 'tutorial',
    'audience_level' => 'intermediate',
    'skill_prerequisites' => ['PHP basics', 'WordPress hooks'],
    'content_characteristics' => ['practical', 'step-by-step'],
    'primary_intent' => 'educational',
    'actionability' => 'immediately-actionable', 
    'complexity_score' => 6,
    'estimated_completion_time' => 45
]
```

## Testing and Validation

### Verify Installation

```php
// Check if Data Machine is active
if (class_exists('DataMachine\\Core\\Engine\\Actions\\DataMachineActions')) {
    echo "Data Machine is active";
}

// Check if plugin is registered
if (class_exists('DM_StructuredData_Handler')) {
    echo "Structured Data plugin is loaded";
}
```

### Test Semantic Analysis

1. **Create Pipeline**: Use admin interface (Data Machine → Structured Data) to create pipeline automatically
2. **Analyze Posts**: Use post search and analysis features in admin interface
3. **Manual Execution**: Test individual posts via admin interface or programmatically:
   ```php
   // Get pipeline components
   $flow_id = get_option('dm_structured_data_flow_id');
   $fetch_step_id = get_option('dm_structured_data_fetch_step_id');
   
   // Configure for specific post
   do_action('dm_update_flow_handler', $fetch_step_id, 'wordpress_fetch', [
       'wordpress_fetch' => [
           'post_id' => $post_id
       ]
   ]);
   
   // Execute analysis
   do_action('dm_run_flow_now', $flow_id);
   ```
4. **Monitor Status**: Check pipeline creation and execution status via admin interface
5. **Validate Data**: Check post meta for `_dm_structured_data` and Yoast schema output

### Debug Pipeline Processing

```php
// Check if pipeline exists
$pipeline_service = new DM_StructuredData_CreatePipeline();
if ($pipeline_service->pipeline_exists()) {
    echo "Pipeline exists and is ready";
} else {
    echo "Pipeline not found - create it first";
}

// Verify pipeline components
$pipelines = apply_filters('dm_get_pipelines', []);
foreach ($pipelines as $pipeline) {
    if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
        echo "Pipeline ID: " . $pipeline['pipeline_id'];
        break;
    }
}

// Data Machine logging for background job
do_action('dm_log', 'info', 'Testing structured data pipeline', [
    'flow_id' => get_option('dm_structured_data_flow_id'),
    'post_id' => $post_id
]);

// Manual execution using stored IDs
$flow_id = get_option('dm_structured_data_flow_id');
do_action('dm_run_flow_now', $flow_id);

// Monitor pipeline execution
// Check Data Machine → Jobs for pipeline execution status
```

## Extending the Plugin

### Custom Semantic Fields

Add custom fields to the AI analysis tool:

```php
add_filter('ai_tools', function($tools) {
    if (isset($tools['save_semantic_analysis']['parameters'])) {
        $tools['save_semantic_analysis']['parameters']['custom_field'] = [
            'type' => 'string',
            'description' => 'Custom semantic classification',
            'required' => false
        ];
    }
    return $tools;
});
```

### Custom Schema Enhancement

Hook into schema generation for custom enhancements:

```php
add_filter('wpseo_schema_graph', function($graph, $context) {
    // Custom schema modifications
    return $graph;
}, 20, 2); // Run after plugin enhancement
```

### Alternative Schema Integration

For non-Yoast implementations:

```php
// Get semantic data for custom schema
$semantic_data = DM_StructuredData_Handler::get_structured_data(get_the_ID());

if ($semantic_data) {
    // Build custom schema
    $schema = [
        '@type' => 'Article',
        'aiEnrichment' => DM_StructuredData_YoastIntegration::get_enriched_schema_for_post(get_the_ID())
    ];
}
```

## Troubleshooting

### Common Issues

**Plugin Not Working**
- Verify Data Machine plugin is installed and activated
- Check WordPress/PHP version requirements
- Confirm admin interface is accessible at Data Machine → Structured Data

**Pipeline Creation Failed**
- Check immediate error response in admin interface
- Verify Data Machine is properly loaded: `has_filter('dm_handlers')`
- Review Data Machine logs for creation errors
- Ensure Data Machine handlers are properly registered

**No Semantic Data Generated**
- Confirm pipeline exists and is properly configured
- Check if analysis was triggered via admin interface or programmatically
- Verify post ID is available in pipeline context
- Review Data Machine Jobs page for execution errors

**Schema Not Enhanced** (Yoast Integration)
- Install and activate Yoast SEO plugin if you want automatic schema enhancement
- Check if post has semantic data stored
- Verify schema type is supported (Article, BlogPosting, WebPage, CollectionPage)
- **Note**: Plugin works without Yoast - semantic data is still stored and accessible via API

### Debug Mode

Enable debugging to trace pipeline execution:

```php
// Browser debugging
window.dmDebugMode = true;

// PHP debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Data Machine logs are at:
// /wp-content/uploads/data-machine-logs/data-machine.log
// View via Data Machine → Logs admin page

// Check recent logs programmatically
$recent_logs = apply_filters('dm_log_file', [], 'get_recent', 100);
```

## Support

For technical support and feature requests:
- **Developer**: Chris Huber
- **Website**: https://chubes.net
- **Main Project**: [Data Machine](https://github.com/chubes4/data-machine)
- **Documentation**: Refer to Data Machine plugin documentation for pipeline configuration

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html