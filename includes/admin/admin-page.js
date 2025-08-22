/**
 * Structured Data Management Page JavaScript
 * 
 * Handles UI interactions for the comprehensive structured data admin interface.
 */

jQuery(document).ready(function($) {
    
    const postSearch = $('#post-search');
    const postSearchResults = $('#post-search-results');
    const addStructuredDataBtn = $('#add-structured-data-btn');
    const dataSearch = $('#data-search');
    const bulkAction = $('#bulk-action');
    const applyBulkBtn = $('#apply-bulk');
    const cbSelectAll = $('#cb-select-all');
    
    let selectedPostId = null;
    let searchTimeout = null;
    
    // Initialize
    init();
    
    function init() {
        bindEvents();
        updateAddButtonState();
    }
    
    function bindEvents() {
        // Post search functionality
        postSearch.on('input', handlePostSearch);
        postSearch.on('focus', function() {
            if (postSearchResults.children().length > 0) {
                postSearchResults.show();
            }
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#post-search, #post-search-results').length) {
                postSearchResults.hide();
            }
        });
        
        $(document).on('click', '.search-result-item', handlePostSelect);
        
        
        // Add structured data button
        addStructuredDataBtn.on('click', handleAddStructuredData);
        
        // Data table interactions
        $(document).on('click', '.editable', handleInlineEdit);
        $(document).on('click', '.reanalyze-post', handleReanalyze);
        $(document).on('click', '.delete-structured-data', handleDelete);
        $(document).on('click', '.edit-structured-data', handleEditData);
        
        // Bulk actions
        cbSelectAll.on('change', handleSelectAll);
        $(document).on('change', 'input[name=\"post[]\"]', updateBulkActions);
        applyBulkBtn.on('click', handleBulkAction);
        
        // Data search
        dataSearch.on('input', handleDataSearch);
    }
    
    /**
     * Handle post search
     */
    function handlePostSearch() {
        const searchTerm = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 2) {
            postSearchResults.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchPosts(searchTerm);
        }, 300);
    }
    
    /**
     * Search for posts via AJAX
     */
    function searchPosts(term) {
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_search_posts',
                search: term,
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.posts);
                }
            }
        });
    }
    
    /**
     * Display search results
     */
    function displaySearchResults(posts) {
        postSearchResults.empty();
        
        if (posts.length === 0) {
            postSearchResults.append('<div class=\"search-result-item no-results\">No posts found</div>');
        } else {
            posts.forEach(function(post) {
                const item = $(`
                    <div class=\"search-result-item\" data-post-id=\"${post.id}\">
                        <div class=\"result-title\">${escapeHtml(post.title)}</div>
                        <div class=\"result-meta\">${post.type} - ${post.date}</div>
                    </div>
                `);
                postSearchResults.append(item);
            });
        }
        
        postSearchResults.show();
    }
    
    /**
     * Handle post selection from search results
     */
    function handlePostSelect() {
        const postId = $(this).data('post-id');
        const postTitle = $(this).find('.result-title').text();
        
        selectedPostId = postId;
        postSearch.val(postTitle);
        postSearchResults.hide();
        
        updateAddButtonState();
    }
    
    
    /**
     * Update add structured data button state
     */
    function updateAddButtonState() {
        addStructuredDataBtn.prop('disabled', !selectedPostId);
    }
    
    /**
     * Handle add structured data button click
     */
    function handleAddStructuredData() {
        if (!selectedPostId) return;
        
        addStructuredDataBtn.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_analyze',
                post_id: selectedPostId,
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Structured data added successfully! Refresh page to see results.', 'success');
                    addStructuredDataBtn.text('Add Structured Data').prop('disabled', false);
                } else {
                    showNotice('Failed to add structured data: ' + response.data, 'error');
                    addStructuredDataBtn.text('Add Structured Data').prop('disabled', false);
                }
            },
            error: function() {
                showNotice('Network error occurred', 'error');
                addStructuredDataBtn.text('Add Structured Data').prop('disabled', false);
            }
        });
    }
    
    
    /**
     * Handle inline editing
     */
    function handleInlineEdit() {
        const $this = $(this);
        const field = $this.data('field');
        const postId = $this.data('post-id');
        const currentValue = $this.text();
        
        const input = $('<input type=\"text\" class=\"inline-edit-input\">');
        input.val(currentValue);
        
        $this.html(input);
        input.focus().select();
        
        input.on('blur keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            const newValue = $(this).val();
            
            if (newValue === currentValue) {
                $this.text(currentValue);
                return;
            }
            
            updateField(postId, field, newValue, $this);
        });
    }
    
    /**
     * Update field via AJAX
     */
    function updateField(postId, field, value, element) {
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_update_field',
                post_id: postId,
                field: field,
                value: value,
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    element.text(value);
                    showNotice('Field updated successfully', 'success');
                } else {
                    element.text(element.data('original-value') || 'N/A');
                    showNotice('Failed to update field', 'error');
                }
            },
            error: function() {
                element.text(element.data('original-value') || 'N/A');
                showNotice('Network error occurred', 'error');
            }
        });
    }
    
    /**
     * Handle re-analyze button
     */
    function handleReanalyze() {
        const postId = $(this).data('post-id');
        
        if (!confirm('Re-analyze this post? This will overwrite existing structured data.')) {
            return;
        }
        
        // Implement re-analysis logic
        showNotice('Re-analysis queued', 'info');
    }
    
    /**
     * Handle delete button
     */
    function handleDelete() {
        const postId = $(this).data('post-id');
        const row = $(this).closest('tr');
        
        if (!confirm(dmStructuredData.strings.confirm_delete)) {
            return;
        }
        
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_delete',
                post_id: postId,
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                    showNotice('Structured data deleted', 'success');
                } else {
                    showNotice('Failed to delete data', 'error');
                }
            }
        });
    }
    
    /**
     * Handle edit data button
     */
    function handleEditData() {
        const postId = $(this).data('post-id');
        // Would open a modal for detailed editing
        console.log('Edit data for post:', postId);
    }
    
    /**
     * Handle select all checkbox
     */
    function handleSelectAll() {
        const checked = $(this).prop('checked');
        $('input[name=\"post[]\"]').prop('checked', checked);
        updateBulkActions();
    }
    
    /**
     * Update bulk action button state
     */
    function updateBulkActions() {
        const selectedCount = $('input[name=\"post[]\"]:checked').length;
        applyBulkBtn.prop('disabled', selectedCount === 0 || !bulkAction.val());
    }
    
    /**
     * Handle bulk action
     */
    function handleBulkAction() {
        const action = bulkAction.val();
        const selectedIds = [];
        
        $('input[name=\"post[]\"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (!action || selectedIds.length === 0) return;
        
        if (!confirm(dmStructuredData.strings.confirm_bulk_delete)) {
            return;
        }
        
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_bulk_action',
                bulk_action: action,
                post_ids: selectedIds,
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    if (action === 'delete') {
                        selectedIds.forEach(function(postId) {
                            $('tr[data-post-id=\"' + postId + '\"]').fadeOut(function() {
                                $(this).remove();
                            });
                        });
                    }
                    
                    cbSelectAll.prop('checked', false);
                    updateBulkActions();
                } else {
                    showNotice('Bulk action failed', 'error');
                }
            }
        });
    }
    
    /**
     * Handle data table search
     */
    function handleDataSearch() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.wp-list-table tbody tr:not(.no-items)').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        const notice = $(`
            <div class=\"notice notice-${type} is-dismissible\">
                <p>${escapeHtml(message)}</p>
                <button type=\"button\" class=\"notice-dismiss\">
                    <span class=\"screen-reader-text\">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut();
        });
        
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    /**
     * Handle Create Pipeline button click
     */
    $('#create-pipeline-btn').on('click', function() {
        const $button = $(this);
        const $spinner = $('#create-pipeline-spinner');
        const $result = $('#create-pipeline-result');
        
        // Show loading state
        $button.prop('disabled', true).text('Creating Pipeline...');
        $spinner.addClass('is-active');
        $result.html('<div class="notice notice-info inline"><p>Creating pipeline...</p></div>');
        
        $.ajax({
            url: dmStructuredData.ajax_url,
            type: 'POST',
            data: {
                action: 'dm_structured_data_create_pipeline',
                nonce: dmStructuredData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p><strong>Success!</strong> ' + escapeHtml(response.data.message) + ' Refreshing page...</p></div>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $result.html('<div class="notice notice-error inline"><p><strong>Error:</strong> ' + escapeHtml(response.data) + '</p></div>');
                    $button.prop('disabled', false).text('Create Structured Data Pipeline');
                    $spinner.removeClass('is-active');
                }
            },
            error: function(xhr, status, error) {
                $result.html('<div class="notice notice-error inline"><p><strong>Error:</strong> Failed to create pipeline. Please try again.</p></div>');
                $button.prop('disabled', false).text('Create Structured Data Pipeline');
                $spinner.removeClass('is-active');
            }
        });
    });

    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
});