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
   * Get number of unread messages (excluding spam).
   *
   * @return int
   */
  public static function get_unread_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_contact_messages';
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read = 0 AND is_spam = 0");
  }

  /**
   * Get number of spam messages.
   *
   * @return int
   */
  public static function get_spam_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_contact_messages';
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_spam = 1");
  }

  /**
   * Sanitize and normalize a raw view value from the request.
   *
   * @param string $raw
   * @return string 'inbox' | 'spam'
   */
  private static function normalize_view($raw) {
    $view = sanitize_key((string) $raw);
    return in_array($view, ['inbox', 'spam'], true) ? $view : 'inbox';
  }

  /**
   * Render the tabs bar HTML (inbox / spam) for a given active view.
   *
   * @param string $view
   * @return string
   */
  public static function render_tabs($view) {
    $view         = self::normalize_view($view);
    $unread_count = self::get_unread_count();
    $spam_count   = self::get_spam_count();
    $base_url     = admin_url('admin.php?page=pn_customers_manager_contact_messages');

    ob_start();
    ?>
    <div class="pn-customers-manager-messages-tabs pn-customers-manager-mb-20" id="pn-cm-contact-messages-tabs">
      <a href="<?php echo esc_url(add_query_arg('view', 'inbox', $base_url)); ?>" class="pn-customers-manager-messages-tab <?php echo $view === 'inbox' ? 'pn-customers-manager-messages-tab-active' : ''; ?>" data-view="inbox">
        <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">inbox</span>
        <?php esc_html_e('Bandeja de entrada', 'pn-customers-manager'); ?>
        <?php if ($unread_count > 0): ?>
          <span class="pn-customers-manager-badge"><?php echo esc_html($unread_count); ?></span>
        <?php endif; ?>
      </a>
      <a href="<?php echo esc_url(add_query_arg('view', 'spam', $base_url)); ?>" class="pn-customers-manager-messages-tab <?php echo $view === 'spam' ? 'pn-customers-manager-messages-tab-active' : ''; ?>" data-view="spam">
        <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">report</span>
        <?php esc_html_e('Spam', 'pn-customers-manager'); ?>
        <?php if ($spam_count > 0): ?>
          <span class="pn-customers-manager-badge"><?php echo esc_html($spam_count); ?></span>
        <?php endif; ?>
      </a>
    </div>
    <?php
    return (string) ob_get_clean();
  }

  /**
   * Render the messages list (table + pagination) for a given view and page.
   *
   * This method is used both by render_page() on initial page load and by
   * the AJAX handler `pn_cm_contact_list` when switching tabs, so the same
   * markup is produced either way.
   *
   * @param string $view  'inbox' | 'spam'
   * @param int    $paged 1-based page number.
   * @return string
   */
  public static function render_messages_list($view, $paged = 1) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_contact_messages';

    $view  = self::normalize_view($view);
    $where = ($view === 'spam') ? 'WHERE is_spam = 1' : 'WHERE is_spam = 0';

    $paged  = max(1, intval($paged));
    $offset = ($paged - 1) * self::$per_page;

    $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
    $messages = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        self::$per_page,
        $offset
      )
    );

    $total_pages = (int) ceil($total / self::$per_page);
    $base_url    = admin_url('admin.php?page=pn_customers_manager_contact_messages');

    ob_start();
    ?>
    <?php if (empty($messages)): ?>
      <p>
        <?php if ($view === 'spam'): ?>
          <?php esc_html_e('No hay mensajes marcados como spam.', 'pn-customers-manager'); ?>
        <?php else: ?>
          <?php esc_html_e('No hay mensajes de contacto todavía.', 'pn-customers-manager'); ?>
        <?php endif; ?>
      </p>
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
            <tr class="pn-customers-manager-message-row <?php echo $msg->is_read ? 'pn-customers-manager-message-read' : 'pn-customers-manager-message-unread'; ?><?php echo !empty($msg->is_spam) ? ' pn-customers-manager-message-spam' : ''; ?>" data-id="<?php echo esc_attr($msg->id); ?>">
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
                <a href="#" class="pn-customers-manager-msg-toggle" title="<?php esc_attr_e('Ver mensaje', 'pn-customers-manager'); ?>">
                  <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">expand_more</span>
                </a>
                <?php if (!$msg->is_read && empty($msg->is_spam)): ?>
                  <a href="#" class="pn-customers-manager-msg-mark-read" data-id="<?php echo esc_attr($msg->id); ?>" title="<?php esc_attr_e('Marcar como leído', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">done</span>
                  </a>
                <?php endif; ?>
                <?php if (empty($msg->is_spam)): ?>
                  <a href="#" class="pn-customers-manager-msg-mark-spam" data-id="<?php echo esc_attr($msg->id); ?>" title="<?php esc_attr_e('Marcar como spam', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">report</span>
                  </a>
                <?php else: ?>
                  <a href="#" class="pn-customers-manager-msg-mark-spam" data-id="<?php echo esc_attr($msg->id); ?>" data-unmark="1" title="<?php esc_attr_e('No es spam', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">report_off</span>
                  </a>
                <?php endif; ?>
                <a href="#" class="pn-customers-manager-msg-delete" data-id="<?php echo esc_attr($msg->id); ?>" title="<?php esc_attr_e('Eliminar', 'pn-customers-manager'); ?>">
                  <span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">delete</span>
                </a>
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
          $pagination_base = add_query_arg('view', $view, $base_url);
          for ($i = 1; $i <= $total_pages; $i++):
            $class = ($i === $paged) ? 'pn-customers-manager-pagination-current' : '';
          ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $i, $pagination_base)); ?>" class="pn-customers-manager-pagination-link <?php echo esc_attr($class); ?>"><?php echo esc_html($i); ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <?php
    return (string) ob_get_clean();
  }

  /**
   * Render the messages admin page.
   */
  public static function render_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('No tienes permiso para acceder a esta página.', 'pn-customers-manager'));
    }

    $view         = self::normalize_view(isset($_GET['view']) ? $_GET['view'] : '');
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
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

      <?php echo self::render_tabs($view); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

      <div id="pn-cm-contact-messages-list" class="pn-customers-manager-messages-list-wrap" data-view="<?php echo esc_attr($view); ?>">
        <?php echo self::render_messages_list($view, $current_page); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
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
      'view'    => $view,
      'i18n'    => [
        'confirmDelete'   => __('¿Estás seguro de que quieres eliminar este mensaje?', 'pn-customers-manager'),
        'confirmMarkSpam' => __('¿Marcar este mensaje como spam?', 'pn-customers-manager'),
        'markAsSpam'      => __('Marcar como spam', 'pn-customers-manager'),
        'notSpam'         => __('No es spam', 'pn-customers-manager'),
      ],
    ]);
    ?>
    <?php
  }
}
