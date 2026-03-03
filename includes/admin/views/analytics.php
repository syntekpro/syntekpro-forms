<?php
/**
 * Analytics dashboard view
 */

if (!defined('ABSPATH')) {
    exit;
}

$days_options = array(7, 14, 30, 60, 90);
?>

<div class="wrap spf-admin-list-wrap">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro Logo">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title"><?php _e('Analytics', 'syntekpro-forms'); ?></h1>
        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="syntekpro-forms-analytics">
            <label for="spf-analytics-days"><?php _e('Date window', 'syntekpro-forms'); ?></label>
            <select id="spf-analytics-days" name="days">
                <?php foreach ($days_options as $opt): ?>
                    <option value="<?php echo (int) $opt; ?>" <?php selected((int) $days, (int) $opt); ?>>
                        <?php echo sprintf(_n('%d day', '%d days', $opt, 'syntekpro-forms'), $opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-primary"><?php _e('Apply', 'syntekpro-forms'); ?></button>
        </form>
    </div>

    <div class="spf-admin-content-card" style="padding:16px;">
        <h2><?php _e('Conversion Funnel by Form', 'syntekpro-forms'); ?></h2>
        <?php if (empty($analytics_summary)): ?>
            <p><?php _e('No analytics data available yet. Open a form and start submitting to populate this dashboard.', 'syntekpro-forms'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php _e('Form', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Views', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Starts', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Completions', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Abandons', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Dropoff Events', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Completion Rate', 'syntekpro-forms'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($analytics_summary as $row): ?>
                    <?php
                    $starts = (int) ($row['start'] ?? 0);
                    $completions = (int) ($row['complete'] ?? 0);
                    $completion_rate = $starts > 0 ? round(($completions / $starts) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($row['form_title'] !== '' ? $row['form_title'] : '#' . (int) $row['form_id']); ?></td>
                        <td><?php echo (int) ($row['view'] ?? 0); ?></td>
                        <td><?php echo $starts; ?></td>
                        <td><?php echo $completions; ?></td>
                        <td><?php echo (int) ($row['abandon'] ?? 0); ?></td>
                        <td><?php echo (int) ($row['field_dropoff'] ?? 0); ?></td>
                        <td><?php echo esc_html(number_format_i18n($completion_rate, 2)); ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="spf-admin-content-card" style="padding:16px;margin-top:16px;">
        <h2><?php _e('Top Field Dropoff', 'syntekpro-forms'); ?></h2>
        <?php if (empty($field_dropoff)): ?>
            <p><?php _e('No field dropoff data yet.', 'syntekpro-forms'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php _e('Form ID', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Field', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Events', 'syntekpro-forms'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($field_dropoff as $item): ?>
                    <tr>
                        <td><?php echo (int) $item->form_id; ?></td>
                        <td><?php echo esc_html((string) $item->field_name); ?></td>
                        <td><?php echo (int) $item->total; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
