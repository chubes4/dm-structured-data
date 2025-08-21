<?php
/**
 * DM Structured Data Admin Page Template
 *
 * Main page template for the Structured Data Management admin interface.
 * Contains analysis controls and data management table.
 *
 * @package DM_StructuredData\Admin\Templates
 */

if (!defined('WPINC')) {
    die;
}

// Prepare data for template - no hardcoded AI providers

// Get posts with structured data for the table  
$structured_data_posts = [];

// Simple query for posts with structured data
$posts_query = new WP_Query([
    'meta_key' => '_dm_structured_data',
    'meta_compare' => 'EXISTS',
    'posts_per_page' => 50,
    'post_status' => 'any',
    'post_type' => ['post', 'page']
]);

if ($posts_query->have_posts()) {
    while ($posts_query->have_posts()) {
        $posts_query->the_post();
        $post_id = get_the_ID();
        $structured_data = get_post_meta($post_id, '_dm_structured_data', true);
        
        if ($structured_data && is_array($structured_data)) {
            $structured_data_posts[] = [
                'post_id' => $post_id,
                'post_title' => get_the_title(),
                'content_type' => $structured_data['content_type'] ?? 'N/A',
                'audience_level' => $structured_data['audience_level'] ?? 'N/A',
                'complexity_score' => $structured_data['complexity_score'] ?? null,
                'generated_at' => $structured_data['generated_at'] ?? time()
            ];
        }
    }
    wp_reset_postdata();
}

?>
<div class="wrap dm-structured-data">
    <h1>Structured Data Management</h1>
    <p>AI-powered semantic analysis for WordPress content. Analyze posts to generate structured data that enhances search engine understanding.</p>
    
    <?php 
    // Check if pipeline exists - get admin page instance
    global $dm_structured_data_admin_page;
    if (!$dm_structured_data_admin_page || !$dm_structured_data_admin_page->pipeline_exists()): 
    ?>
    <div class="notice notice-info">
        <p><strong>Setup Required:</strong> The Structured Data Analysis Pipeline needs to be created before you can analyze content.</p>
        <p>
            <button type="button" id="create-pipeline-btn" class="button button-primary">
                Create Structured Data Pipeline
            </button>
            <span class="spinner" id="create-pipeline-spinner"></span>
        </p>
        <div id="create-pipeline-result" style="margin-top: 10px;"></div>
    </div>
    <?php endif; ?>
    
    <?php if (!defined('WPSEO_VERSION') && !class_exists('WPSEO_Options')): ?>
    <div class="notice notice-info">
        <p><strong>Optional Enhancement:</strong> Install <a href="<?php echo admin_url('plugin-install.php?s=yoast+seo&tab=search&type=term'); ?>">Yoast SEO</a> to automatically enhance your schema markup with AI-generated semantic data.</p>
    </div>
    <?php endif; ?>
    
    <!-- Analysis Interface -->
    <div class="dm-analysis-section">
        <h2>Content Analysis</h2>
        
        <div class="dm-analysis-controls">
            <div class="dm-analysis-form">
                <div class="dm-form-group">
                    <label for="post-search">Select Post to Analyze</label>
                    <div class="dm-post-search">
                        <input type="text" id="post-search" placeholder="Search for posts..." autocomplete="off">
                        <div id="post-search-results" class="dm-search-results"></div>
                    </div>
                </div>
                
                <div class="dm-form-group">
                    <button type="button" id="add-structured-data-btn" class="button button-primary" disabled>
                        Add Structured Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Management Table -->
    <div class="dm-data-section">
        <h2>Existing Structured Data</h2>
        
        <div class="dm-data-controls">
            <input type="search" id="data-search" class="dm-data-search" placeholder="Search structured data...">
            
            <div class="dm-bulk-actions">
                <select id="bulk-action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="reanalyze">Re-analyze</option>
                </select>
                <button type="button" id="apply-bulk" class="button" disabled>Apply</button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th class="manage-column column-title">Post Title</th>
                    <th class="manage-column column-content-type">Content Type</th>
                    <th class="manage-column column-audience">Audience Level</th>
                    <th class="manage-column column-complexity">Complexity</th>
                    <th class="manage-column column-date">Analyzed</th>
                    <th class="manage-column column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($structured_data_posts)) : ?>
                    <tr class="no-items">
                        <td colspan="7">No structured data found. Analyze some posts to get started!</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($structured_data_posts as $post_data) : ?>
                        <tr data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                            <th class="check-column">
                                <input type="checkbox" name="post[]" value="<?php echo esc_attr($post_data['post_id']); ?>">
                            </th>
                            <td class="column-title">
                                <strong>
                                    <a href="<?php echo esc_url(get_edit_post_link($post_data['post_id'])); ?>" target="_blank">
                                        <?php echo esc_html($post_data['post_title']); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(get_permalink($post_data['post_id'])); ?>" target="_blank">View</a> |
                                    </span>
                                    <span class="edit">
                                        <a href="#" class="edit-structured-data" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">Edit Data</a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-content-type">
                                <span class="editable" data-field="content_type" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                                    <?php echo esc_html($post_data['content_type'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="column-audience">
                                <span class="editable" data-field="audience_level" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                                    <?php echo esc_html($post_data['audience_level'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="column-complexity">
                                <span class="editable" data-field="complexity_score" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                                    <?php echo isset($post_data['complexity_score']) ? esc_html($post_data['complexity_score'] . '/10') : 'N/A'; ?>
                                </span>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(date('M j, Y', $post_data['generated_at'] ?? time())); ?>
                            </td>
                            <td class="column-actions">
                                <div class="actions">
                                    <button type="button" class="button button-small reanalyze-post" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                                        Re-analyze
                                    </button>
                                    <button type="button" class="button button-small button-link-delete delete-structured-data" data-post-id="<?php echo esc_attr($post_data['post_id']); ?>">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>