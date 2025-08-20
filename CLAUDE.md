# DM Structured Data WordPress Plugin

AI-powered semantic analysis extension for Data Machine that enhances WordPress structured data through automated content classification and Yoast SEO integration.

## Architecture Overview

The plugin extends Data Machine's pipeline system with semantic content analysis capabilities that automatically classify WordPress content and inject AI-enriched properties into Yoast-generated schema markup.

**Core Pattern**: WordPress Content → Data Machine Pipeline → AI Analysis → Enhanced Schema Output

## Plugin Structure

```
dm-structured-data/
├── dm-structured-data.php          # Main plugin file and registration
├── includes/
│   ├── StructuredDataHandler.php   # Data Machine handler implementation
│   └── YoastIntegration.php        # Schema enhancement integration
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
- **Enhanced**: Yoast SEO plugin (provides base schema for enhancement)
- **WordPress**: 5.0+ with PHP 8.0+

## Configuration Requirements

No additional configuration required. Plugin automatically:
1. Registers with Data Machine pipeline system
2. Hooks into Yoast schema generation 
3. Activates on content publication events

## Data Flow

1. **Content Creation**: User publishes/updates WordPress content
2. **Pipeline Trigger**: Data Machine detects publish event via registered handler
3. **AI Analysis**: AI model analyzes content using `save_semantic_analysis` tool
4. **Data Storage**: Semantic metadata saved to `_dm_structured_data` post meta
5. **Schema Enhancement**: Yoast schema generation enhanced with `aiEnrichment` properties
6. **Output**: Enhanced structured data visible to AI crawlers and search engines