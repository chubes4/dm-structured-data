# Data Machine - Structured Data Extension

**⚠️ EXPERIMENTAL PLUGIN** - This is an experimental extension for testing AI-powered structured data enhancement concepts.

AI-powered semantic analysis for enhanced WordPress structured data via [Data Machine](https://github.com/chubes/data-machine) pipelines. This plugin automatically analyzes WordPress content using AI and injects semantic metadata into Yoast SEO schema markup to improve AI crawler understanding and search engine optimization.

## Features

- **Automated Semantic Analysis**: AI-powered content classification and metadata extraction
- **Yoast SEO Integration**: Seamless enhancement of existing schema markup
- **Content Intelligence**: Extracts audience level, complexity, prerequisites, and actionability
- **AI Crawler Optimization**: Structured data designed for modern AI search systems
- **WordPress Native**: Built on WordPress standards with proper sanitization and security

## Requirements

- WordPress 5.0 or higher
- PHP 8.0 or higher
- **Data Machine plugin** (required dependency)
- Yoast SEO plugin (recommended for schema enhancement)

## Installation

1. **Install Data Machine Plugin** (required dependency)
   ```bash
   # Install from: https://github.com/chubes/data-machine
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

## Configuration

### Data Machine Pipeline Setup

The plugin automatically registers with Data Machine's pipeline system. No manual configuration required.

**Automatic Registration**:
- Handler: `structured_data` (publish type)
- AI Tool: `save_semantic_analysis`
- Trigger: Content publication/update events

### WordPress Integration

Create or update a Data Machine pipeline that includes the semantic analysis tool:

```php
// Example pipeline configuration
$pipeline_config = [
    'name' => 'Content Semantic Analysis',
    'triggers' => ['post_publish', 'post_update'],
    'ai_tools' => ['save_semantic_analysis'],
    'handlers' => ['structured_data']
];
```

## Usage Examples

### Basic Content Analysis

When you publish or update WordPress content, the plugin automatically:

1. **Analyzes Content**: AI examines post content for semantic metadata
2. **Extracts Classifications**: Determines content type, audience level, complexity
3. **Stores Metadata**: Saves analysis to WordPress post meta
4. **Enhances Schema**: Injects AI enrichment into Yoast schema output

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
    "estimatedCompletionTime": 45,
    "aiAnalysisDate": "2025-01-20T14:30:00Z",
    "aiModel": "claude-3-sonnet"
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

1. **Create Test Content**: Publish a new post with substantial content
2. **Trigger Analysis**: Data Machine pipeline should automatically process
3. **Verify Data**: Check post meta for `_dm_structured_data`
4. **Validate Schema**: Inspect page source for enhanced Yoast schema

### Debug Pipeline Processing

```php
// Enable Data Machine debugging
add_filter('dm_debug_mode', '__return_true');

// Check semantic data after content publication
add_action('save_post', function($post_id) {
    if (DM_StructuredData_Handler::has_structured_data($post_id)) {
        error_log("Semantic data saved for post {$post_id}");
    }
});
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
- Ensure proper file permissions

**No Semantic Data Generated**
- Confirm Data Machine pipeline includes `save_semantic_analysis` tool
- Check if content triggers pipeline execution
- Verify post ID is available in pipeline context

**Schema Not Enhanced**
- Ensure Yoast SEO is active and generating schema
- Check if post has semantic data stored
- Verify schema type is supported (Article, BlogPosting, WebPage, CollectionPage)

### Debug Mode

Enable debugging to trace pipeline execution:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug.log for pipeline and AI tool execution
tail -f /path/to/wordpress/wp-content/debug.log
```

## Support

For technical support and feature requests:
- **Developer**: Chris Huber
- **Website**: https://chubes.net
- **Main Project**: [Data Machine](https://github.com/chubes/data-machine)
- **Documentation**: Refer to Data Machine plugin documentation for pipeline configuration

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html