<?php
/**
 * Webhook Queue Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

$status = isset($status) ? (string) $status : 'failed';
$items = isset($items) && is_array($items) ? $items : array();
$stats = isset($stats) && is_array($stats) ? $stats : array('all' => 0, 'failed' => 0, 'pending' => 0, 'success' => 0);

$base_url = admin_url('admin.php?page=syntekpro-forms-webhook-queue');
?>
<div class="wrap spf-admin-page spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title spf-title-with-icon">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e('Webhook Retry Manager', 'syntekpro-forms'); ?>
        </h1>

        <div class="spf-nav-right">
            <form method="post" class="spf-inline-form">
                <?php wp_nonce_field('spf_webhook_queue_action', 'spf_webhook_queue_nonce'); ?>
                <input type="hidden" name="spf_webhook_queue_action" value="run_processor_now">
                <button type="submit" class="button button-primary"><?php esc_html_e('Run Processor Now', 'syntekpro-forms'); ?></button>
            </form>
            <a class="button" href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Refresh', 'syntekpro-forms'); ?></a>
        </div>
    </div>

    <p class="description"><?php esc_html_e('Queued webhooks are retried automatically with backoff. Use this page to monitor failures and retry manually.', 'syntekpro-forms'); ?></p>

    <div class="spf-addon-summary-grid spf-summary-grid-spaced">
        <div class="spf-addon-summary-card"><h3><?php esc_html_e('All', 'syntekpro-forms'); ?></h3><p class="spf-addon-summary-number"><?php echo esc_html((string) ($stats['all'] ?? 0)); ?></p></div>
        <div class="spf-addon-summary-card"><h3><?php esc_html_e('Failed', 'syntekpro-forms'); ?></h3><p class="spf-addon-summary-number"><?php echo esc_html((string) ($stats['failed'] ?? 0)); ?></p></div>
        <div class="spf-addon-summary-card"><h3><?php esc_html_e('Pending', 'syntekpro-forms'); ?></h3><p class="spf-addon-summary-number"><?php echo esc_html((string) ($stats['pending'] ?? 0)); ?></p></div>
        <div class="spf-addon-summary-card"><h3><?php esc_html_e('Success', 'syntekpro-forms'); ?></h3><p class="spf-addon-summary-number"><?php echo esc_html((string) ($stats['success'] ?? 0)); ?></p></div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php
            $tabs = array(
                'failed' => __('Failed', 'syntekpro-forms'),
                'pending' => __('Pending', 'syntekpro-forms'),
                'success' => __('Success', 'syntekpro-forms'),
                'all' => __('All', 'syntekpro-forms'),
            );
            foreach ($tabs as $key => $label):
                $active = $status === $key;
                ?>
                <a class="button<?php echo $active ? ' button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('page' => 'syntekpro-forms-webhook-queue', 'status' => $key), admin_url('admin.php'))); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <form method="post" style="display:inline;">
                <?php wp_nonce_field('spf_webhook_queue_action', 'spf_webhook_queue_nonce'); ?>
                <input type="hidden" name="spf_webhook_queue_action" value="retry_failed">
                <button type="submit" class="button"><?php esc_html_e('Retry All Failed', 'syntekpro-forms'); ?></button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Delete all successful webhook logs?', 'syntekpro-forms')); ?>');">
                <?php wp_nonce_field('spf_webhook_queue_action', 'spf_webhook_queue_nonce'); ?>
                <input type="hidden" name="spf_webhook_queue_action" value="clear_success">
                <button type="submit" class="button"><?php esc_html_e('Clear Success Logs', 'syntekpro-forms'); ?></button>
            </form>
        </div>
    </div>

    <div class="spf-admin-content-card" style="background:#fff;border:1px solid #ccd0d4;border-radius:6px;overflow:auto;">
        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php esc_html_e('ID', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Status', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Form / Entry', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Webhook URL', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Attempts', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Next Attempt', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Last Error', 'syntekpro-forms'); ?></th>
                <th><?php esc_html_e('Actions', 'syntekpro-forms'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8"><?php esc_html_e('No webhook queue items found for this filter.', 'syntekpro-forms'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>#<?php echo esc_html((string) $item->id); ?></td>
                        <td>
                            <span class="spf-status spf-status-<?php echo esc_attr($item->status === 'failed' ? 'inactive' : 'active'); ?>">
                                <?php echo esc_html(ucfirst((string) $item->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html(sprintf(__('Form #%1$d / Entry #%2$d', 'syntekpro-forms'), (int) $item->form_id, (int) $item->entry_id)); ?>
                        </td>
                        <td style="max-width:350px;word-break:break-all;">
                            <a href="<?php echo esc_url((string) $item->webhook_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html((string) $item->webhook_url); ?></a>
                        </td>
                        <td><?php echo esc_html((string) $item->attempts); ?> / <?php echo esc_html((string) $item->max_attempts); ?></td>
                        <td><?php echo !empty($item->next_attempt_at) ? esc_html((string) $item->next_attempt_at) : '&mdash;'; ?></td>
                        <td style="max-width:300px;word-break:break-word;">
                            <?php echo !empty($item->error_message) ? esc_html((string) $item->error_message) : '&mdash;'; ?>
                        </td>
                        <td>
                            <?php if ($item->status === 'failed' || $item->status === 'pending'): ?>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('spf_webhook_queue_action', 'spf_webhook_queue_nonce'); ?>
                                    <input type="hidden" name="spf_webhook_queue_action" value="retry_item">
                                    <input type="hidden" name="item_id" value="<?php echo esc_attr((string) $item->id); ?>">
                                    <button type="submit" class="button button-small"><?php esc_html_e('Retry', 'syntekpro-forms'); ?></button>
                                </form>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
