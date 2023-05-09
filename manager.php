<?php
/*
Plugin Name: Manage Plugin Installations
Description: Un plugin per tracciare le installazioni del tuo plugin su altri siti WordPress.
Version: 1.0
Author: Your Name
*/

function mpi_create_installations_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'plugin_installations';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        site_url varchar(255) NOT NULL,
        blog_name varchar(255) NOT NULL,
        admin_email varchar(255) NOT NULL,
        installation_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY  (site_url)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'mpi_create_installations_table');

function mpi_register_routes() {
    register_rest_route('manage-plugin-installations/v1', '/notify', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'mpi_notify_plugin_installation',
    ));
}
add_action('rest_api_init', 'mpi_register_routes');

function mpi_notify_plugin_installation($request) {
    global $wpdb;

    $site_url = $request->get_param('site_url');
    $blog_name = $request->get_param('blog_name');
    $admin_email = $request->get_param('admin_email');

    $table_name = $wpdb->prefix . 'plugin_installations';

    $result = $wpdb->replace($table_name, array(
        'site_url' => $site_url,
        'blog_name' => $blog_name,
        'admin_email' => $admin_email
    ));

    if ($result === false) {
        return new WP_Error('db_error', 'Errore nel salvataggio dei dati nel database', array('status' => 500));
    }

    return new WP_REST_Response(array('success' => true), 200);
}

function mpi_admin_menu() {
    add_menu_page('Gestione Installazioni Plugin', 'Installazioni Plugin', 'manage_options', 'mpi_installations_list', 'mpi_render_installations_list_page', 'dashicons-admin-plugins', 3);
}
add_action('admin_menu', 'mpi_admin_menu');

function mpi_render_installations_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'plugin_installations';

    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi necessari per accedere a questa pagina.'));
    }

    $installations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY installation_date DESC", ARRAY_A);
    ?>

    <div class="wrap">
        <h1>Installazioni plugin</h1>

        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>URL sito</th>
                <th>Nome blog</th>
                <th>Email Amministratore</th>
                <th>Data installazione</th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th>URL sito</th>
                <th>Nome blog</th>
                <th>Email Amministratore</th>
                <th>Data installazione</th>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ($installations as $installation) : ?>
                <tr>
                    <td><?php echo esc_html($installation['site_url']); ?></a></td>
                    <td><?php echo esc_html($installation['blog_name']); ?></td>
                    <td><?php echo esc_html($installation['admin_email']); ?></td>
                    <td><?php echo esc_html($installation['installation_date']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </tbody>
        </table>
    </div>
<?php
}