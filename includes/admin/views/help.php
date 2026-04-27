<?php
/**
 * Help Page View - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

$tutorial_url = esc_url(plugins_url('docs/TUTORIAL.md', SPF_PLUGIN_FILE));
$developer_docs_url = defined('SPF_GITHUB_REPO_URL') ? esc_url(SPF_GITHUB_REPO_URL) : esc_url('https://github.com/syntekpro/syntekpro-forms');
$designer_docs_url = esc_url(admin_url('admin.php?page=syntekpro-forms-new'));
?>

<div class="wrap spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title spf-title-with-icon">
            <span class="dashicons dashicons-editor-help"></span>
            <?php esc_html_e('Help', 'syntekpro-forms'); ?>
        </h1>
        <a class="button button-secondary" href="<?php echo esc_url(admin_url('index.php#spf_entries_dashboard_widget')); ?>">
            <?php esc_html_e('Go to WP Dashboard Widget', 'syntekpro-forms'); ?>
        </a>
    </div>

    <p class="description spf-about-intro">
        <?php esc_html_e('Find documentation for users, developers, and designers.', 'syntekpro-forms'); ?>
    </p>

    <div class="spf-about-grid">
        <div class="spf-about-card">
            <h3><?php esc_html_e('User Documentation', 'syntekpro-forms'); ?></h3>
            <p><?php esc_html_e('Step-by-step guidance for building forms, configuring notifications, using entries, and troubleshooting common issues.', 'syntekpro-forms'); ?></p>
            <p>
                <a href="<?php echo $tutorial_url; ?>" target="_blank" class="button button-primary"><?php esc_html_e('Open User Docs', 'syntekpro-forms'); ?></a>
            </p>
        </div>

        <div class="spf-about-card">
            <h3><?php esc_html_e('Developer Documentation', 'syntekpro-forms'); ?></h3>
            <p><?php esc_html_e('Reference for hooks, filters, REST API endpoints, extension patterns, and code-level implementation details.', 'syntekpro-forms'); ?></p>
            <p>
                <a href="<?php echo $developer_docs_url; ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php esc_html_e('Open Developer Docs', 'syntekpro-forms'); ?></a>
            </p>
            <p class="spf-setting-description" style="margin-top:10px;">
                <?php esc_html_e('REST base:', 'syntekpro-forms'); ?>
                <code><?php echo esc_html('/wp-json/syntekpro-forms/v1'); ?></code>
            </p>
        </div>

        <div class="spf-about-card">
            <h3><?php esc_html_e('Designer Documentation', 'syntekpro-forms'); ?></h3>
            <p><?php esc_html_e('UI-focused guidance for themes, layout styling, field spacing, typography, and form presentation best practices.', 'syntekpro-forms'); ?></p>
            <p>
                <a href="<?php echo $designer_docs_url; ?>" class="button button-secondary"><?php esc_html_e('Open Designer Docs', 'syntekpro-forms'); ?></a>
            </p>
        </div>
    </div>
</div>
