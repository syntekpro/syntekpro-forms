<?php
/**
 * Forms List View - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spf_forms ORDER BY created_at DESC");
?>

<div class="wrap spf-admin-list-wrap">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right">
        </div>
    </div>

    <div class="spf-admin-page-title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="spf-admin-page-title"><?php _e('Forms List', 'syntekpro-forms'); ?> 
            <button type="button" class="button button-primary" id="spf-add-new-form" style="margin-left: 15px; vertical-align: middle;">
                <?php _e('Add New', 'syntekpro-forms'); ?>
            </button>
        </h2>
        <div class="spf-header-stats">
            <span class="spf-stat-item"><strong><?php echo count($forms); ?></strong> <?php _e('Total Forms', 'syntekpro-forms'); ?></span>
        </div>
    </div>
    
    <div class="spf-admin-content-card">
        <?php if (empty($forms)): ?>
        <div class="spf-empty-state">
            <p><?php _e('No forms found. Create your first form!', 'syntekpro-forms'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-new'); ?>" class="button button-primary">
                <?php _e('Create Form', 'syntekpro-forms'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
                            <button class="button button-small spf-duplicate-form" data-form-id="<?php echo $form->id; ?>">
                                <?php _e('Duplicate', 'syntekpro-forms'); ?>
                            </button>
            <thead>
                <tr>
                    <th><?php _e('Title', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Shortcode', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Entries', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Created', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Status', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Actions', 'syntekpro-forms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): 
                    $entries_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d",
                        $form->id
                    ));
                ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $form->id); ?>">
                                    <?php echo esc_html($form->title); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <code>[syntekpro_form id="<?php echo $form->id; ?>"]</code>
                            <button class="button button-small spf-copy-shortcode" data-shortcode='[syntekpro_form id="<?php echo $form->id; ?>"]'>
                                <?php _e('Copy', 'syntekpro-forms'); ?>
                            </button>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries&form_id=' . $form->id); ?>">
                                <?php echo $entries_count; ?>
                            </a>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime((string)$form->created_at)); ?></td>
                        <td>
                            <span class="spf-status spf-status-<?php echo esc_attr($form->status); ?>">
                                <?php echo esc_html(ucfirst((string)$form->status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $form->id); ?>" class="button button-small">
                                <?php _e('Edit', 'syntekpro-forms'); ?>
                            </a>
                            <button class="button button-small spf-duplicate-form" data-form-id="<?php echo $form->id; ?>">
                                <?php _e('Duplicate', 'syntekpro-forms'); ?>
                            </button>
                            <button class="button button-small spf-delete-form" data-form-id="<?php echo $form->id; ?>">
                                <?php _e('Delete', 'syntekpro-forms'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>

    <div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
        <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
            <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:32px !important;width:32px !important;max-height:32px !important;max-width:32px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
        </a>
    </div>
</div>

<!-- Template Selection Modal -->
<div id="spf-template-modal" class="spf-modal" style="display: none;">
    <div class="spf-modal-content spf-template-modal-content">
        <div class="spf-modal-header">
            <h2><?php _e('Select a Form Template', 'syntekpro-forms'); ?></h2>
            <span class="spf-modal-close">&times;</span>
        </div>
        
        <div class="spf-modal-body" style="padding: 0;">
            <div style="padding: 20px 30px; background: #fff; border-bottom: 1px solid #f0f0f1;">
                <p style="margin: 0; color: #646970; font-size: 15px;"><?php _e('Choose a starting point for your new form. Each template comes with its own color scheme and pre-configured fields.', 'syntekpro-forms'); ?></p>
            </div>
            
            <div class="spf-template-grid">
                <?php 
                if (class_exists('SyntekPro_Forms_Templates')) {
                    $templates = SyntekPro_Forms_Templates::get_templates();
                    if (!empty($templates)) {
                        foreach ($templates as $id => $template): 
                            $primary_color = isset($template['settings']['primary_color']) ? $template['settings']['primary_color'] : '#2271b1';
                        ?>
                            <div class="spf-template-item" data-template-id="<?php echo esc_attr($id); ?>">
                                <div class="spf-template-preview">
                                    <div class="spf-template-icon">
                                        <span class="dashicons <?php echo esc_attr($template['icon']); ?>"></span>
                                    </div>
                                    <div class="spf-template-color-bar" style="background-color: <?php echo esc_attr($primary_color); ?>;"></div>
                                </div>
                                <div class="spf-template-info">
                                    <h3><?php echo esc_html($template['title']); ?></h3>
                                    <p><?php echo esc_html($template['description']); ?></p>
                                </div>
                                <div class="spf-template-footer">
                                    <button type="button" class="button button-primary spf-use-template"><?php _e('Use Template', 'syntekpro-forms'); ?></button>
                                </div>
                            </div>
                        <?php 
                        endforeach;
                    } else {
                        echo '<p style="padding: 30px;">' . __('No templates found in class.', 'syntekpro-forms') . '</p>';
                    }
                } else {
                    echo '<p style="padding: 30px;">' . __('Template class not found.', 'syntekpro-forms') . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.spf-admin-content-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    padding: 20px;
}
.spf-empty-state {
    text-align: center;
    padding: 50px 20px;
}
.spf-status {
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.spf-status-active {
    background: #d4edda;
    color: #155724;
}
.spf-status-inactive {
    background: #f8d7da;
    color: #721c24;
}
.spf-stat-item {
    font-size: 14px;
    color: #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode
    $('.spf-copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode);
        $(this).text('Copied!');
        setTimeout(() => {
            $(this).text('Copy');
        }, 2000);
    });

    // Add New Form Modal
    if (window.location.search.indexOf('add-new=1') !== -1) {
        $('#spf-template-modal').css('display', 'flex').hide().fadeIn();
    }

    $('#spf-add-new-form').on('click', function() {
    
    // Duplicate form
    $('.spf-duplicate-form').on('click', function() {
        var formId = $(this).data('form-id');
        var $button = $(this);
        var originalText = $button.text();

        $button.prop('disabled', true).text('<?php echo esc_js(__('Duplicating...', 'syntekpro-forms')); ?>');

        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_duplicate_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success && response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || '<?php echo esc_js(__('Failed to duplicate form.', 'syntekpro-forms')); ?>');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'syntekpro-forms')); ?>');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
        $('#spf-template-modal').css('display', 'flex').hide().fadeIn();
    });

    $('.spf-modal-close, .spf-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('spf-modal-close')) {
            $('#spf-template-modal').fadeOut();
        }
    });

    // Use Template
    $(document).on('click', '.spf-template-item', function(e) {
        e.preventDefault();
        
        var templateId = $(this).data('template-id');
        var $btn = $(this).find('.spf-use-template');
        
        if (typeof spfAdmin === 'undefined') {
            alert('Error: spfAdmin is not defined');
            return;
        }

        $btn.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_create_form_from_template',
                nonce: spfAdmin.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || 'Error creating form');
                    $btn.prop('disabled', false).text('Use Template');
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Use Template');
            }
        });
    });
    
    // Delete form
    $('.spf-delete-form').on('click', function() {
        if (!confirm('Are you sure you want to delete this form?')) {
            return;
        }
        
        var formId = $(this).data('form-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_delete_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error deleting form');
                }
            }
        });
    });
});
</script>