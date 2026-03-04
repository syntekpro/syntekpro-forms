<?php
/**
 * PDF / Print Export for Entries
 *
 * Generates a styled HTML document that can be saved as PDF via the
 * browser's print dialog (no external library required).
 *
 * @package SyntekPro_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SyntekPro_Forms_PDF_Export {

    /**
     * Bootstrap: register AJAX handler.
     */
    public static function init() {
        add_action( 'wp_ajax_spf_export_entry_pdf', array( __CLASS__, 'handle_export' ) );
    }

    /**
     * AJAX handler – outputs a print-ready HTML page for one or more entries.
     */
    public static function handle_export() {
        check_ajax_referer( 'spf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'syntekpro-forms' ) );
        }

        $entry_ids = isset( $_GET['entry_ids'] ) ? array_map( 'absint', (array) $_GET['entry_ids'] ) : array();

        if ( empty( $entry_ids ) ) {
            wp_die( __( 'No entries specified.', 'syntekpro-forms' ) );
        }

        global $wpdb;

        $placeholders = implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) );
        $entries = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*, f.title AS form_title
             FROM {$wpdb->prefix}spf_entries e
             LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id
             WHERE e.id IN ($placeholders)
             ORDER BY e.created_at DESC",
            $entry_ids
        ) );

        if ( empty( $entries ) ) {
            wp_die( __( 'Entries not found.', 'syntekpro-forms' ) );
        }

        self::render_html( $entries );
        exit;
    }

    /**
     * Render the print-friendly HTML page.
     *
     * @param array $entries Array of entry objects.
     */
    private static function render_html( $entries ) {
        $site_name = get_bloginfo( 'name' );
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<title><?php printf( esc_html__( 'Entries Export – %s', 'syntekpro-forms' ), esc_html( $site_name ) ); ?></title>
<style>
    @media print { .no-print { display: none !important; } }
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1d2327; margin: 0; padding: 20px 40px; font-size: 13px; line-height: 1.6; }
    h1 { font-size: 20px; margin: 0 0 20px; }
    .entry-card { border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 24px; page-break-inside: avoid; }
    .entry-header { display: flex; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #0073aa; }
    .entry-header h2 { margin: 0; font-size: 16px; color: #0073aa; }
    .entry-meta { color: #646970; font-size: 12px; }
    .field-row { display: flex; padding: 6px 0; border-bottom: 1px solid #f0f0f1; }
    .field-label { width: 200px; min-width: 200px; font-weight: 600; color: #1d2327; }
    .field-value { flex: 1; color: #3c434a; word-break: break-word; }
    .print-btn { position: fixed; bottom: 20px; right: 20px; background: #0073aa; color: #fff; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 14px; z-index: 9999; }
    .print-btn:hover { background: #005a87; }
</style>
</head>
<body>
<h1><?php printf( esc_html__( '%s – Form Entries Export', 'syntekpro-forms' ), esc_html( $site_name ) ); ?></h1>
<p class="entry-meta"><?php printf( esc_html__( 'Generated on %s', 'syntekpro-forms' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></p>

<?php foreach ( $entries as $entry ) :
    $data = json_decode( (string) $entry->entry_data, true );
    if ( ! is_array( $data ) ) { $data = array(); }
?>
<div class="entry-card">
    <div class="entry-header">
        <h2><?php echo esc_html( $entry->form_title ?: __( 'Unknown Form', 'syntekpro-forms' ) ); ?></h2>
        <span class="entry-meta"><?php printf( esc_html__( 'Entry #%d', 'syntekpro-forms' ), (int) $entry->id ); ?></span>
    </div>

    <div class="field-row">
        <div class="field-label"><?php esc_html_e( 'Submitted', 'syntekpro-forms' ); ?></div>
        <div class="field-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $entry->created_at ) ) ); ?></div>
    </div>
    <div class="field-row">
        <div class="field-label"><?php esc_html_e( 'IP Address', 'syntekpro-forms' ); ?></div>
        <div class="field-value"><?php echo esc_html( (string) $entry->ip_address ); ?></div>
    </div>

    <?php foreach ( $data as $key => $value ) : ?>
    <div class="field-row">
        <div class="field-label"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></div>
        <div class="field-value"><?php echo is_array( $value ) ? esc_html( implode( ', ', $value ) ) : nl2br( esc_html( (string) $value ) ); ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<button class="print-btn no-print" onclick="window.print();"><?php esc_html_e( 'Print / Save as PDF', 'syntekpro-forms' ); ?></button>
</body>
</html>
        <?php
    }
}

// Auto-init when loaded.
SyntekPro_Forms_PDF_Export::init();
