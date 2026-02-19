<?php
/**
 * Add-ons Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

$addons_dir = SPF_ADDONS_DIR;
$notices = array();

if (!file_exists($addons_dir)) {
    wp_mkdir_p($addons_dir);
}

if (!empty($_POST['spf_addon_action']) && current_user_can('manage_options')) {
    check_admin_referer('spf_manage_addons', 'spf_manage_addons_nonce');

    $addon_action = sanitize_text_field(wp_unslash($_POST['spf_addon_action']));

    if ($addon_action === 'upload_addon') {
        if (empty($_FILES['spf_addon_file']) || empty($_FILES['spf_addon_file']['name'])) {
            $notices[] = array(
                'type' => 'error',
                'message' => __('Please choose a PHP add-on file to upload.', 'syntekpro-forms'),
            );
        } else {
            $uploaded_name = sanitize_file_name(wp_unslash($_FILES['spf_addon_file']['name']));
            $extension = strtolower(pathinfo($uploaded_name, PATHINFO_EXTENSION));

            if ($extension !== 'php') {
                $notices[] = array(
                    'type' => 'error',
                    'message' => __('Only .php files are allowed for add-ons.', 'syntekpro-forms'),
                );
            } else {
                $target_name = wp_unique_filename($addons_dir, $uploaded_name);
                $target_path = trailingslashit($addons_dir) . $target_name;

                if (!is_uploaded_file($_FILES['spf_addon_file']['tmp_name'])) {
                    $notices[] = array(
                        'type' => 'error',
                        'message' => __('The uploaded file is invalid. Please try again.', 'syntekpro-forms'),
                    );
                } elseif (move_uploaded_file($_FILES['spf_addon_file']['tmp_name'], $target_path)) {
                    $notices[] = array(
                        'type' => 'success',
                        'message' => sprintf(
                            __('Add-on uploaded successfully: %s', 'syntekpro-forms'),
                            esc_html($target_name)
                        ),
                    );
                } else {
                    $notices[] = array(
                        'type' => 'error',
                        'message' => __('Could not move the uploaded file to the add-ons directory.', 'syntekpro-forms'),
                    );
                }
            }
        }
    }

    if ($addon_action === 'create_starter_addon') {
        $starter_filename = wp_unique_filename($addons_dir, 'syntekpro-forms-addon.php');
        $starter_path = trailingslashit($addons_dir) . $starter_filename;
        $starter_contents = "<?php\n/**\n * SyntekPro Forms Add-on\n * Generated from the Add-ons page.\n */\n\nif (!defined('ABSPATH')) {\n    exit;\n}\n\nadd_action('plugins_loaded', function () {\n    // Add your add-on logic here.\n});\n";

        $written = file_put_contents($starter_path, $starter_contents);

        if ($written === false) {
            $notices[] = array(
                'type' => 'error',
                'message' => __('Could not create a starter add-on file. Check directory permissions.', 'syntekpro-forms'),
            );
        } else {
            $notices[] = array(
                'type' => 'success',
                'message' => sprintf(
                    __('Starter add-on created: %s', 'syntekpro-forms'),
                    esc_html($starter_filename)
                ),
            );
        }
    }

    if ($addon_action === 'save_addon_status') {
        $current_files = glob(trailingslashit($addons_dir) . '*.php');
        $current_files = is_array($current_files) ? $current_files : array();

        $all_hashes = array();
        foreach ($current_files as $addon_file) {
            $resolved = realpath($addon_file);
            if (!$resolved) {
                continue;
            }
            $all_hashes[] = md5($resolved);
        }

        $enabled_hashes = array();
        if (!empty($_POST['spf_enabled_addons']) && is_array($_POST['spf_enabled_addons'])) {
            $enabled_hashes = array_map(
                'sanitize_text_field',
                wp_unslash($_POST['spf_enabled_addons'])
            );
        }

        $featured_hashes = array();
        if (!empty($_POST['spf_featured_addons']) && is_array($_POST['spf_featured_addons'])) {
            $featured_hashes = array_map(
                'sanitize_text_field',
                wp_unslash($_POST['spf_featured_addons'])
            );
        }

        $enabled_hashes = array_values(array_intersect($all_hashes, $enabled_hashes));
        $disabled_hashes = array_values(array_diff($all_hashes, $enabled_hashes));
        $featured_hashes = array_values(array_intersect($all_hashes, $featured_hashes));

        if (count($featured_hashes) > 3) {
            $featured_hashes = array_slice($featured_hashes, 0, 3);
        }

        update_option('spf_disabled_addons', $disabled_hashes);
        update_option('spf_featured_addons', $featured_hashes);

        $notices[] = array(
            'type' => 'success',
            'message' => __('Add-on statuses updated. Reload this page to apply changes.', 'syntekpro-forms'),
        );
    }
}

$available_files = glob(trailingslashit($addons_dir) . '*.php');
$available_files = is_array($available_files) ? $available_files : array();
$loaded_files = isset($loaded_addons) && is_array($loaded_addons) ? $loaded_addons : array();
$loaded_lookup = array();
foreach ($loaded_files as $loaded_file) {
    $resolved_loaded = realpath($loaded_file);
    if ($resolved_loaded) {
        $loaded_lookup[$resolved_loaded] = true;
    }
}

$disabled_addons = get_option('spf_disabled_addons', array());
if (!is_array($disabled_addons)) {
    $disabled_addons = array();
}

$featured_addons_saved = get_option('spf_featured_addons', array());
if (!is_array($featured_addons_saved)) {
    $featured_addons_saved = array();
}

if (!function_exists('get_file_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$guess_dashicon = static function ($file_name, $description = '') {
    $haystack = strtolower($file_name . ' ' . $description);

    if (strpos($haystack, 'webhook') !== false || strpos($haystack, 'api') !== false) {
        return 'dashicons-randomize';
    }
    if (strpos($haystack, 'email') !== false || strpos($haystack, 'mail') !== false) {
        return 'dashicons-email-alt';
    }
    if (strpos($haystack, 'spam') !== false || strpos($haystack, 'captcha') !== false || strpos($haystack, 'bot') !== false) {
        return 'dashicons-shield';
    }
    if (strpos($haystack, 'crm') !== false || strpos($haystack, 'hubspot') !== false || strpos($haystack, 'salesforce') !== false) {
        return 'dashicons-groups';
    }
    if (strpos($haystack, 'analytics') !== false || strpos($haystack, 'track') !== false || strpos($haystack, 'report') !== false) {
        return 'dashicons-chart-area';
    }

    return 'dashicons-admin-plugins';
};

$resolve_addon_asset_url = static function ($asset, $addon_file_path) {
    $asset = trim((string) $asset);
    if ($asset === '') {
        return '';
    }

    if (filter_var($asset, FILTER_VALIDATE_URL)) {
        return esc_url_raw($asset);
    }

    $asset_path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $asset);
    $candidate = dirname($addon_file_path) . DIRECTORY_SEPARATOR . ltrim($asset_path, DIRECTORY_SEPARATOR);

    if (!file_exists($candidate)) {
        return '';
    }

    $resolved_candidate = realpath($candidate);
    $resolved_plugin_dir = realpath(SPF_PLUGIN_DIR);
    if (!$resolved_candidate || !$resolved_plugin_dir) {
        return '';
    }

    $candidate_norm = wp_normalize_path($resolved_candidate);
    $plugin_dir_norm = trailingslashit(wp_normalize_path($resolved_plugin_dir));

    if (strpos($candidate_norm, $plugin_dir_norm) !== 0) {
        return '';
    }

    $relative_path = ltrim(substr($candidate_norm, strlen($plugin_dir_norm)), '/');
    return esc_url_raw(SPF_PLUGIN_URL . $relative_path);
};

$addon_rows = array();
foreach ($available_files as $available_file) {
    $resolved_available = realpath($available_file);
    if (!$resolved_available) {
        continue;
    }

    $hash = md5($resolved_available);
    $is_enabled = !in_array($hash, $disabled_addons, true);
    $header_data = get_file_data($resolved_available, array(
        'name' => 'Add-on Name',
        'description' => 'Description',
        'author' => 'Author',
        'version' => 'Version',
        'icon' => 'Icon',
        'graphic' => 'Graphic',
    ));

    $display_name = !empty($header_data['name']) ? $header_data['name'] : basename($resolved_available, '.php');
    $description = !empty($header_data['description']) ? $header_data['description'] : __('No description provided in add-on header.', 'syntekpro-forms');
    $icon_meta = isset($header_data['icon']) ? trim((string) $header_data['icon']) : '';
    $graphic_meta = isset($header_data['graphic']) ? trim((string) $header_data['graphic']) : '';

    $icon_url = '';
    $dashicon = '';
    if ($icon_meta !== '') {
        if (preg_match('/^dashicons-[a-z0-9-]+$/i', $icon_meta)) {
            $dashicon = sanitize_html_class($icon_meta);
        } else {
            $icon_url = $resolve_addon_asset_url($icon_meta, $resolved_available);
        }
    }

    if ($dashicon === '' && $icon_url === '') {
        $dashicon = $guess_dashicon((string) $display_name, (string) $description);
    }

    $graphic_url = '';
    if ($graphic_meta !== '') {
        $graphic_url = $resolve_addon_asset_url($graphic_meta, $resolved_available);
    }

    $addon_rows[] = array(
        'path' => $resolved_available,
        'name' => basename($resolved_available),
        'display_name' => $display_name,
        'description' => $description,
        'author' => !empty($header_data['author']) ? $header_data['author'] : '',
        'version' => !empty($header_data['version']) ? $header_data['version'] : '',
        'dashicon' => $dashicon,
        'icon_url' => $icon_url,
        'graphic_url' => $graphic_url,
        'hash' => $hash,
        'enabled' => $is_enabled,
        'featured' => in_array($hash, $featured_addons_saved, true),
        'loaded' => isset($loaded_lookup[$resolved_available]),
    );
}

$enabled_count = count(array_filter($addon_rows, function ($row) {
    return !empty($row['enabled']);
}));

$available_count = is_array($available_files) ? count($available_files) : 0;
$loaded_count = count($loaded_files);

$featured_addons = array();
$featured_lookup = array();

foreach ($featured_addons_saved as $saved_hash) {
    foreach ($addon_rows as $row) {
        if ($row['hash'] !== $saved_hash || isset($featured_lookup[$row['hash']])) {
            continue;
        }

        $featured_addons[] = $row;
        $featured_lookup[$row['hash']] = true;

        if (count($featured_addons) >= 3) {
            break 2;
        }
    }
}

foreach ($addon_rows as $row) {
    if (isset($featured_lookup[$row['hash']])) {
        continue;
    }

    if (!empty($row['graphic_url'])) {
        $featured_addons[] = $row;
        $featured_lookup[$row['hash']] = true;
    }
    if (count($featured_addons) >= 3) {
        break;
    }
}

if (count($featured_addons) < 3) {
    foreach ($addon_rows as $row) {
        if (isset($featured_lookup[$row['hash']])) {
            continue;
        }
        $featured_addons[] = $row;
        $featured_lookup[$row['hash']] = true;
        if (count($featured_addons) >= 3) {
            break;
        }
    }
}
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
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php _e('SyntekPro Forms Add-ons', 'syntekpro-forms'); ?>
        </h1>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=syntekpro-forms-addons')); ?>">
            <?php esc_html_e('Refresh', 'syntekpro-forms'); ?>
        </a>
    </div>

    <p class="description"><?php _e('Upload add-ons here or drop PHP files into the add-ons folder. Add-ons in that folder are auto-loaded.', 'syntekpro-forms'); ?></p>

    <?php if (!empty($notices)): ?>
        <div class="spf-addon-notices">
            <?php foreach ($notices as $notice): ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible"><p><?php echo esc_html($notice['message']); ?></p></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($featured_addons)): ?>
        <div class="spf-addon-hero-strip">
            <div class="spf-addon-hero-head">
                <h2><?php esc_html_e('Featured Add-ons', 'syntekpro-forms'); ?></h2>
                <p><?php esc_html_e('Quick visual overview of your top add-ons.', 'syntekpro-forms'); ?></p>
            </div>
            <div class="spf-addon-hero-grid">
                <?php foreach ($featured_addons as $row): ?>
                    <div class="spf-addon-hero-card" data-enabled="<?php echo !empty($row['enabled']) ? '1' : '0'; ?>">
                        <div class="spf-addon-hero-media"<?php echo !empty($row['graphic_url']) ? ' style="background-image:url(' . esc_url($row['graphic_url']) . ');"' : ''; ?>>
                            <span class="spf-addon-hero-overlay"></span>
                            <div class="spf-addon-hero-icon">
                                <?php if (!empty($row['icon_url'])): ?>
                                    <img src="<?php echo esc_url($row['icon_url']); ?>" alt="<?php echo esc_attr($row['display_name']); ?>" />
                                <?php else: ?>
                                    <span class="dashicons <?php echo esc_attr($row['dashicon']); ?>"></span>
                                <?php endif; ?>
                            </div>
                            <span class="spf-addon-status-badge <?php echo !empty($row['enabled']) ? 'is-loaded' : 'is-not-loaded'; ?>">
                                <?php echo !empty($row['enabled']) ? esc_html__('Enabled', 'syntekpro-forms') : esc_html__('Disabled', 'syntekpro-forms'); ?>
                            </span>
                        </div>
                        <div class="spf-addon-hero-body">
                            <h3><?php echo esc_html($row['display_name']); ?></h3>
                            <p><?php echo esc_html(wp_trim_words((string) $row['description'], 16, '...')); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="spf-addon-summary-grid">
        <div class="spf-addon-summary-card">
            <h3><?php esc_html_e('Loaded Add-ons', 'syntekpro-forms'); ?></h3>
            <p class="spf-addon-summary-number"><?php echo esc_html((string) $loaded_count); ?></p>
        </div>
        <div class="spf-addon-summary-card">
            <h3><?php esc_html_e('Enabled Add-ons', 'syntekpro-forms'); ?></h3>
            <p class="spf-addon-summary-number"><?php echo esc_html((string) $enabled_count); ?></p>
        </div>
        <div class="spf-addon-summary-card">
            <h3><?php esc_html_e('Available .php Files', 'syntekpro-forms'); ?></h3>
            <p class="spf-addon-summary-number"><?php echo esc_html((string) $available_count); ?></p>
        </div>
        <div class="spf-addon-summary-card">
            <h3><?php esc_html_e('Add-ons Folder', 'syntekpro-forms'); ?></h3>
            <p><code><?php echo esc_html($addons_dir); ?></code></p>
        </div>
    </div>

    <div class="spf-addon-actions-grid">
        <div class="spf-addon-action-card">
            <h2><?php esc_html_e('Upload Add-on', 'syntekpro-forms'); ?></h2>
            <p><?php esc_html_e('Upload a PHP add-on file and it will be loaded automatically on the next page load.', 'syntekpro-forms'); ?></p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('spf_manage_addons', 'spf_manage_addons_nonce'); ?>
                <input type="hidden" name="spf_addon_action" value="upload_addon" />
                <p>
                    <input type="file" name="spf_addon_file" accept=".php" required />
                </p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Upload Add-on', 'syntekpro-forms'); ?></button>
                </p>
            </form>
        </div>

        <div class="spf-addon-action-card">
            <h2><?php esc_html_e('Create Starter Add-on', 'syntekpro-forms'); ?></h2>
            <p><?php esc_html_e('Generate a starter PHP file in the add-ons folder that you can edit as a custom extension.', 'syntekpro-forms'); ?></p>
            <form method="post">
                <?php wp_nonce_field('spf_manage_addons', 'spf_manage_addons_nonce'); ?>
                <input type="hidden" name="spf_addon_action" value="create_starter_addon" />
                <p>
                    <button type="submit" class="button"><?php esc_html_e('Create Starter File', 'syntekpro-forms'); ?></button>
                </p>
            </form>
        </div>
    </div>

    <div class="spf-addon-action-card" style="margin-top:16px;">
        <h2><?php esc_html_e('Enable / Disable Add-ons', 'syntekpro-forms'); ?></h2>
        <p><?php esc_html_e('Showcase view with icon cards for each add-on. Toggle and save to apply changes.', 'syntekpro-forms'); ?></p>
        <?php if (!empty($addon_rows)): ?>
            <form method="post">
                <?php wp_nonce_field('spf_manage_addons', 'spf_manage_addons_nonce'); ?>
                <input type="hidden" name="spf_addon_action" value="save_addon_status" />
                <div style="margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                    <label for="spf-addon-status-filter"><strong><?php esc_html_e('Filter:', 'syntekpro-forms'); ?></strong></label>
                    <select id="spf-addon-status-filter">
                        <option value="all"><?php esc_html_e('All Add-ons', 'syntekpro-forms'); ?></option>
                        <option value="enabled"><?php esc_html_e('Enabled Only', 'syntekpro-forms'); ?></option>
                        <option value="disabled"><?php esc_html_e('Disabled Only', 'syntekpro-forms'); ?></option>
                    </select>
                </div>
                <div class="spf-addon-showcase-grid">
                    <?php foreach ($addon_rows as $row): ?>
                        <div class="spf-addon-showcase-card" data-enabled="<?php echo !empty($row['enabled']) ? '1' : '0'; ?>">
                            <?php if (!empty($row['graphic_url'])): ?>
                                <div class="spf-addon-showcase-graphic">
                                    <img src="<?php echo esc_url($row['graphic_url']); ?>" alt="<?php echo esc_attr($row['display_name']); ?>" />
                                </div>
                            <?php endif; ?>

                            <div class="spf-addon-showcase-top">
                                <div class="spf-addon-showcase-icon">
                                    <?php if (!empty($row['icon_url'])): ?>
                                        <img src="<?php echo esc_url($row['icon_url']); ?>" alt="<?php echo esc_attr($row['display_name']); ?>" />
                                    <?php else: ?>
                                        <span class="dashicons <?php echo esc_attr($row['dashicon']); ?>"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="spf-addon-showcase-meta">
                                    <h3><?php echo esc_html($row['display_name']); ?></h3>
                                    <p><?php echo esc_html($row['description']); ?></p>
                                </div>
                            </div>

                            <div class="spf-addon-showcase-badges">
                                <span class="spf-addon-status-badge <?php echo !empty($row['enabled']) ? 'is-loaded' : 'is-not-loaded'; ?>">
                                    <?php echo !empty($row['enabled']) ? esc_html__('Enabled', 'syntekpro-forms') : esc_html__('Disabled', 'syntekpro-forms'); ?>
                                </span>
                                <?php if (!empty($row['featured'])): ?>
                                    <span class="spf-addon-status-badge is-loaded"><?php esc_html_e('Featured', 'syntekpro-forms'); ?></span>
                                <?php endif; ?>
                                <span class="spf-addon-status-badge <?php echo !empty($row['loaded']) ? 'is-loaded' : 'is-not-loaded'; ?>">
                                    <?php echo !empty($row['loaded']) ? esc_html__('Loaded', 'syntekpro-forms') : esc_html__('Not Loaded', 'syntekpro-forms'); ?>
                                </span>
                            </div>

                            <div class="spf-addon-showcase-switch">
                                <label>
                                    <input type="checkbox" name="spf_enabled_addons[]" value="<?php echo esc_attr($row['hash']); ?>" <?php checked($row['enabled']); ?> />
                                    <span><?php esc_html_e('Enable this add-on', 'syntekpro-forms'); ?></span>
                                </label>
                                <label>
                                    <input type="checkbox" name="spf_featured_addons[]" value="<?php echo esc_attr($row['hash']); ?>" <?php checked(!empty($row['featured'])); ?> />
                                    <span><?php esc_html_e('Pin as featured', 'syntekpro-forms'); ?></span>
                                </label>
                            </div>

                            <div class="spf-addon-showcase-details">
                                <p><strong><?php esc_html_e('File:', 'syntekpro-forms'); ?></strong> <?php echo esc_html($row['name']); ?></p>
                                <?php if (!empty($row['author'])): ?>
                                    <p><strong><?php esc_html_e('Author:', 'syntekpro-forms'); ?></strong> <?php echo esc_html($row['author']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($row['version'])): ?>
                                    <p><strong><?php esc_html_e('Version:', 'syntekpro-forms'); ?></strong> <?php echo esc_html($row['version']); ?></p>
                                <?php endif; ?>
                                <p><code><?php echo esc_html($row['path']); ?></code></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:12px;">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Add-on Status', 'syntekpro-forms'); ?></button>
                </p>
            </form>
        <?php else: ?>
            <p><?php esc_html_e('No add-on files available to enable or disable yet.', 'syntekpro-forms'); ?></p>
        <?php endif; ?>
    </div>

</div>

<script>
(function($) {
    $(function() {
        var $filter = $('#spf-addon-status-filter');
        var $rows = $('.spf-addon-showcase-card');

        if (!$filter.length || !$rows.length) {
            return;
        }

        $filter.on('change', function() {
            var mode = $(this).val();
            $rows.each(function() {
                var isEnabled = $(this).data('enabled') === 1 || $(this).data('enabled') === '1';
                var show = true;

                if (mode === 'enabled') {
                    show = isEnabled;
                } else if (mode === 'disabled') {
                    show = !isEnabled;
                }

                $(this).toggle(show);
            });
        });
    });
})(jQuery);
</script>
