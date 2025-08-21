# DM Structured Data WordPress Plugin

AI-powered semantic analysis extension for Data Machine that enhances WordPress structured data through automated content classification and Yoast SEO integration.

## Architecture Overview

The plugin extends Data Machine's pipeline system with semantic content analysis capabilities that automatically classify WordPress content and inject AI-enriched properties into Yoast-generated schema markup.

**Core Pattern**: Admin Interface → Action Scheduler Pipeline Creation → WordPress Content Analysis → AI Enhancement → Schema Output

## Plugin Structure

```
dm-structured-data/
├── dm-structured-data.php          # Main plugin file with Action Scheduler hooks
├── includes/
│   ├── StructuredDataHandler.php   # Data Machine handler implementation
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
- **Creation Method**: Action Scheduler background job
- **Pipeline Name**: "Structured Data Analysis Pipeline"
- **Admin Interface**: Data Machine → Structured Data
- **Status Tracking**: WordPress options and AJAX polling

## Key Classes

### DM_StructuredData_Handler
Processes AI tool calls and manages semantic data storage.

**Primary Methods**:
- `handle_tool_call($parameters, $context)` - Processes AI analysis results
- `get_structured_data($post_id)` - Retrieves stored semantic data
- `has_structured_data($post_id)` - Checks data existence
- `needs_update($post_id)` - Validates content freshness

**Data Storage**: WordPress post meta `_dm_structured_data`

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
- **Required**: Action Scheduler (for background pipeline creation - included with WooCommerce or Data Machine)
- **Enhanced**: Yoast SEO plugin (provides base schema for enhancement)
- **WordPress**: 5.0+ with PHP 8.0+

## Configuration Requirements

Minimal configuration through admin interface:
1. Navigate to Data Machine → Structured Data
2. Click "Create Pipeline" to initiate background setup
3. Monitor creation status via AJAX polling
4. Use admin interface to analyze posts and manage data

Plugin automatically:
1. Registers with Data Machine pipeline system
2. Hooks into Yoast schema generation
3. Provides comprehensive admin management interface

## Data Flow

### Pipeline Creation Flow
1. **Admin Trigger**: User clicks "Create Pipeline" in admin interface
2. **Background Job**: Action Scheduler queues `dm_structured_data_create_pipeline_async`
3. **Pipeline Setup**: Background job creates pipeline, steps, and flow configuration
4. **Status Tracking**: Creation status stored in WordPress options
5. **Admin Polling**: Interface polls status and updates UI when completed

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
- Automated pipeline creation via Action Scheduler
- Real-time status polling and progress updates
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
- Pipeline creation status tracking
- Analysis progress indicators
- Error handling and user feedback

### Key AJAX Endpoints
- `dm_structured_data_create_pipeline` - Trigger background pipeline creation
- `dm_structured_data_check_status` - Poll pipeline creation status
- `dm_structured_data_analyze` - Analyze individual posts
- `dm_structured_data_bulk_action` - Bulk operations (delete, reanalyze)
- `dm_structured_data_search_posts` - Post search functionality

## Action Scheduler Integration

### Background Pipeline Creation
- **Hook**: `dm_structured_data_create_pipeline_async`
- **Function**: `dm_structured_data_create_pipeline_job()`
- **Purpose**: Reliable pipeline creation outside of admin request context
- **Benefits**: Avoids wp_send_json interruption issues during complex setup

### Pipeline Creation Steps
1. Check Data Machine dependency availability
2. Create pipeline using `dm_create` action
3. Query created pipeline by name to get pipeline_id
4. Create fetch, AI, and publish steps
5. Configure flow handlers for each step
6. Store pipeline IDs in WordPress options
7. Update creation status to 'completed'

### Status Management
Pipeline creation status tracked via WordPress options:
- `dm_structured_data_creation_status`: 'not_started', 'in_progress', 'completed', 'failed'
- `dm_structured_data_pipeline_id`: Created pipeline ID
- `dm_structured_data_flow_id`: Created flow ID  
- `dm_structured_data_fetch_step_id`: Fetch step ID for targeting

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