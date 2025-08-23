# DM Structured Data WordPress Plugin

AI-powered semantic analysis extension for Data Machine that enhances WordPress structured data through automated content classification and Yoast SEO integration.

## Architecture Overview

The plugin extends Data Machine's pipeline system with semantic content analysis capabilities that automatically classify WordPress content and inject AI-enriched properties into Yoast-generated schema markup.

**Core Pattern**: Admin Interface → Synchronous Pipeline Creation → WordPress Content Analysis → AI Enhancement → Schema Output

## Plugin Structure

```
dm-structured-data/
├── dm-structured-data.php          # Main plugin file
├── includes/
│   ├── StructuredDataHandler.php   # Data Machine handler implementation
│   ├── CreatePipeline.php          # Pipeline creation service
│   ├── YoastIntegration.php        # Schema enhancement integration
│   └── admin/
│       ├── AdminPage.php           # AJAX-powered admin interface
│       ├── admin-page.js           # Admin interface JavaScript
│       ├── admin-page.css          # Admin interface styling
│       └── templates/              # Admin page templates
```

## Data Machine Integration

### Handler Registration
- **Handler Type**: `publish` (triggers on content publication)
- **Handler Key**: `structured_data`
- **Class**: `DM_StructuredData_Handler`

### AI Tool Registration
- **Tool Name**: `save_semantic_analysis`
- **Purpose**: Extract semantic metadata from content for AI crawler optimization
- **Integration**: Automatic via Data Machine pipeline system

### Pipeline Management
- **Creation Method**: Synchronous service via CreatePipeline class
- **Pipeline Name**: "Structured Data Analysis Pipeline"
- **Admin Interface**: Data Machine → Structured Data
- **Status Tracking**: Immediate response with success/error feedback

## Key Classes

### DM_StructuredData_Handler
Processes AI tool calls and manages semantic data storage.

**Primary Methods**:
- `handle_tool_call($parameters, $context)` - Processes AI analysis results
- `get_structured_data($post_id)` - Retrieves stored semantic data
- `has_structured_data($post_id)` - Checks data existence
- `needs_update($post_id)` - Validates content freshness

**Data Storage**: WordPress post meta `_dm_structured_data`

### DM_StructuredData_CreatePipeline
Synchronous pipeline creation service for Data Machine integration.

**Primary Methods**:
- `create_pipeline()` - Creates complete pipeline with all steps atomically
- `pipeline_exists()` - Checks if structured data pipeline exists
- `get_flow_step_id($step_type)` - Gets flow step ID for specific step type

### DM_StructuredData_YoastIntegration
Enhances Yoast schema with AI-generated semantic properties.

**Integration Point**: `wpseo_schema_graph` filter
**Target Schema Types**: Article, BlogPosting, WebPage, CollectionPage
**Enhancement Method**: Injects `aiEnrichment` object into schema

## AI Tool Parameters

The `save_semantic_analysis` tool accepts these semantic classification parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `content_type` | string | Primary classification: tutorial, guide, reference, opinion, review, how-to, announcement, case-study, comparison |
| `audience_level` | string | Target skill level: beginner, intermediate, advanced, expert |
| `skill_prerequisites` | array | Required knowledge/skills (e.g., "PHP basics", "WordPress hooks") |
| `content_characteristics` | array | Content traits: practical, theoretical, step-by-step, code-heavy, visual, reference, hands-on, conceptual |
| `primary_intent` | string | Main purpose: educational, commercial, informational, entertainment, promotional, problem-solving |
| `actionability` | string | Implementation level: theoretical, practical, step-by-step, reference-only, immediately-actionable |
| `complexity_score` | integer | Difficulty rating 1-10 (1=simple, 10=expert) |
| `estimated_completion_time` | integer | Implementation time in minutes |

## Schema Enhancement Pattern

```php
// Original Yoast schema
{
  "@type": "Article",
  "headline": "Content Title",
  // ... standard properties
}

// Enhanced with AI enrichment
{
  "@type": "Article", 
  "headline": "Content Title",
  // ... standard properties
  "aiEnrichment": {
    "contentType": "tutorial",
    "audienceLevel": "intermediate",
    "skillPrerequisites": ["PHP basics", "WordPress hooks"],
    "contentCharacteristics": ["practical", "step-by-step"],
    "primaryIntent": "educational", 
    "actionability": "immediately-actionable",
    "complexityScore": 6,
    "estimatedCompletionTime": 45
  }
}
```

## Dependencies

- **Required**: Data Machine plugin (provides pipeline system and AI tool infrastructure)
- **Enhanced**: Yoast SEO plugin (provides base schema for enhancement)
- **WordPress**: 5.0+ with PHP 8.0+

## Configuration Requirements

Minimal configuration through admin interface:
1. Navigate to Data Machine → Structured Data
2. Click "Create Pipeline" for immediate setup
3. Pipeline created synchronously with instant feedback
4. Use admin interface to analyze posts and manage data

Plugin automatically:
1. Registers with Data Machine pipeline system
2. Hooks into Yoast schema generation
3. Provides comprehensive admin management interface

## Data Flow

### Pipeline Creation Flow
1. **Admin Trigger**: User clicks "Create Pipeline" in admin interface
2. **Service Call**: CreatePipeline service executes synchronously
3. **Pipeline Setup**: Service creates pipeline, steps, and flow configuration
4. **Immediate Response**: Success/error feedback returned instantly
5. **UI Update**: Interface refreshes to show created pipeline

### Content Analysis Flow
1. **Analysis Request**: Admin interface or programmatic trigger
2. **Post Targeting**: WordPress fetch handler configured for specific post ID
3. **Pipeline Execution**: Data Machine processes content through AI analysis
4. **Data Storage**: Semantic metadata saved to `_dm_structured_data` post meta
5. **Schema Enhancement**: Yoast schema generation enhanced with `aiEnrichment` properties
6. **Output**: Enhanced structured data visible to AI crawlers and search engines

## Admin Interface Features

### DM_StructuredData_AdminPage
Provides comprehensive management interface with:

**Pipeline Management**:
- Synchronous pipeline creation via CreatePipeline service
- Immediate success/error feedback
- Pipeline existence verification by name detection

**Content Analysis**:
- Post search and selection interface
- Individual post analysis with AJAX feedback
- Bulk analysis operations for multiple posts

**Data Management**:
- View and edit semantic metadata inline
- Delete structured data for individual posts
- Bulk delete and re-analysis operations

**Status Monitoring**:
- Analysis progress indicators
- Error handling and user feedback
- Immediate pipeline creation feedback

### Key AJAX Endpoints
- `dm_structured_data_create_pipeline` - Create pipeline synchronously
- `dm_structured_data_analyze` - Analyze individual posts
- `dm_structured_data_bulk_action` - Bulk operations (delete, reanalyze)
- `dm_structured_data_search_posts` - Post search functionality

## Pipeline Creation Implementation

### CreatePipeline Service Pattern
- **Service Class**: `DM_StructuredData_CreatePipeline`
- **Method**: `create_pipeline()`
- **Purpose**: Clean, testable pipeline creation with immediate feedback
- **Benefits**: Synchronous execution, clear error handling, service isolation

### Pipeline Creation Steps
1. Check Data Machine dependency availability
2. Create pipeline using `dm_create_pipeline` filter
3. Create fetch, AI, and publish steps using `dm_create_step` filters
4. Get auto-created flow ID from pipeline
5. Configure AI step with structured data tools
6. Configure flow handlers for each step type
7. Store pipeline IDs in WordPress options

### Data Storage
Pipeline component IDs stored in WordPress options:
- `dm_structured_data_pipeline_id`: Created pipeline ID
- `dm_structured_data_flow_id`: Created flow ID

## Pipeline Detection Pattern

The plugin uses name-based pipeline detection instead of stored IDs:

```php
public function pipeline_exists() {
    $pipelines = apply_filters('dm_get_pipelines', []);
    foreach ($pipelines as $pipeline) {
        if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
            return true;
        }
    }
    return false;
}
```

This ensures pipeline availability verification even if options are cleared.