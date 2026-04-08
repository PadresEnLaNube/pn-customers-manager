<?php
/**
 * Contact Messages management page.
 *
 * Renders the admin list of contact form submissions
 * and provides AJAX handlers for mark-read / delete.
 *
 * @since   1.0.7
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Contact_Messages {

  private $plugin_name;
  private $version;

  private static $per_page = 20;

  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version     = $version;
  }

  /**
   * Get number of unread messages.
   *
   * @return int
   */
  public static function get_unread_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_contact_messages';
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read = 0");
  }

  /**
   * Render the messages admin page.
   */
  public static function render_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('No tienes permiso para acceder a esta página.', 'pn-customers-manager'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_contact_messages';

    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset       = ($current_page - 1) * self::$per_page;

    $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $messages = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        self::$per_page,
        $offset
      )
    );

    $total_pages  = ceil($total / self::$per_page);
    $unread_count = self::get_unread_count();
    $nonce        = wp_create_nonce('pn-customers-manager-nonce');
    $ajax_url     = admin_url('admin-ajax.php');
    ?>
    <div class="pn-customers-manager-contact-messages-wrap pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mt-50 pn-customers-manager-mb-50">
      <h1 class="pn-customers-manager-mb-30">
        <?php esc_html_e('Mensajes de contacto', 'pn-customers-manager'); ?>
        <?php if ($unread_count > 0): ?>
          <span class="pn-customers-manager-badge"><?php echo esc_html($unread_count); ?></span>
        <?php endif; ?>
      </h1>

      <?php if (empty($messages)): ?>
        <p><?php esc_html_e('No hay mensajes de contacto todavía.', 'pn-customers-manager'); ?></p>
      <?php else: ?>
        <table class="pn-customers-manager-messages-table">
          <thead>
            <tr>
              <th class="pn-customers-manager-messages-col-status"><?php esc_html_e('Estado', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Nombre', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Email', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Asunto', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Página', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Fecha', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Acciones', 'pn-customers-manager'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $msg): ?>
              <tr class="pn-customers-manager-message-row <?php echo $msg->is_read ? 'pn-customers-manager-message-read' : 'pn-customers-manager-message-unread'; ?>" data-id="<?php echo esc_attr($msg->id); ?>">
                <td class="pn-customers-manager-messages-col-status">
                  <?php if ($msg->is_read): ?>
                    <span class="pn-customers-manager-status-dot pn-customers-manager-status-read" title="<?php esc_attr_e('Leído', 'pn-customers-manager'); ?>"></span>
                  <?php else: ?>
                    <span class="pn-customers-manager-status-dot pn-customers-manager-status-unread" title="<?php esc_attr_e('No leído', 'pn-customers-manager'); ?>"></span>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html($msg->contact_name); ?></td>
                <td><a href="mailto:<?php echo esc_attr($msg->contact_email); ?>"><?php echo esc_html($msg->contact_email); ?></a></td>
                <td><?php echo esc_html($msg->contact_subject ?: '—'); ?></td>
                <td>
                  <?php if (!empty($msg->source_url)): ?>
                    <a href="<?php echo esc_url($msg->source_url); ?>" target="_blank" title="<?php echo esc_attr($msg->source_url); ?>">
                      <?php echo esc_html($msg->source_title ?: wp_parse_url($msg->source_url, PHP_URL_PATH)); ?>
                    </a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($msg->created_at))); ?></td>
                <td class="pn-customers-manager-messages-actions">
                  <a href="#" class="pn-customers-manager-msg-toggle" title="<?php esc_attr_e('Ver mensaje', 'pn-customers-manager'); ?>">&#9660;</a>
                  <?php if (!$msg->is_read): ?>
                    <a href="#" class="pn-customers-manager-msg-mark-read" data-id="<?php echo esc_attr($msg->id); ?>" title="<?php esc_attr_e('Marcar como leído', 'pn-customers-manager'); ?>">&#10003;</a>
                  <?php endif; ?>
                  <a href="#" class="pn-customers-manager-msg-delete" data-id="<?php echo esc_attr($msg->id); ?>" title="<?php esc_attr_e('Eliminar', 'pn-customers-manager'); ?>">&#10005;</a>
                </td>
              </tr>
              <tr class="pn-customers-manager-message-detail" data-id="<?php echo esc_attr($msg->id); ?>" style="display:none;">
                <td colspan="7">
                  <div class="pn-customers-manager-message-body">
                    <p><strong><?php esc_html_e('De:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($msg->contact_name); ?> &lt;<?php echo esc_html($msg->contact_email); ?>&gt;</p>
                    <p><strong><?php esc_html_e('Para:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($msg->recipient_email); ?></p>
                    <?php if (!empty($msg->source_url)): ?>
                      <p><strong><?php esc_html_e('Página de origen:', 'pn-customers-manager'); ?></strong> <a href="<?php echo esc_url($msg->source_url); ?>" target="_blank"><?php echo esc_html($msg->source_url); ?></a></p>
                    <?php endif; ?>
                    <p><strong><?php esc_html_e('IP:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($msg->ip_address); ?></p>
                    <hr>
                    <div class="pn-customers-manager-white-space-pre-wrap"><?php echo esc_html($msg->contact_message); ?></div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
          <div class="pn-customers-manager-messages-pagination pn-customers-manager-mt-20">
            <?php
            $base_url = admin_url('admin.php?page=pn_customers_manager_contact_messages');
            for ($i = 1; $i <= $total_pages; $i++):
              $class = ($i === $current_page) ? 'pn-customers-manager-pagination-current' : '';
            ?>
              <a href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>" class="pn-customers-manager-pagination-link <?php echo esc_attr($class); ?>"><?php echo esc_html($i); ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php
    wp_enqueue_script(
      'pn-customers-manager-contact-messages',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-contact-messages.js',
      [],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    wp_localize_script('pn-customers-manager-contact-messages', 'pnCmContactMessages', [
      'ajaxUrl' => $ajax_url,
      'nonce'   => $nonce,
      'i18n'    => [
        'confirmDelete' => __('¿Estás seguro de que quieres eliminar este mensaje?', 'pn-customers-manager'),
      ],
    ]);
    ?>
    <?php
  }
}
