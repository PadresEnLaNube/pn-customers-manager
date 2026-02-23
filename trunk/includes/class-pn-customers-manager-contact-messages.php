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

    <script>
    (function() {
      var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
      var nonce   = '<?php echo esc_js($nonce); ?>';

      // Toggle message detail
      document.querySelectorAll('.pn-customers-manager-msg-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var row = this.closest('tr');
          var id = row.getAttribute('data-id');
          var detail = document.querySelector('tr.pn-customers-manager-message-detail[data-id="' + id + '"]');
          if (detail) {
            detail.style.display = detail.style.display === 'none' ? '' : 'none';
            this.textContent = detail.style.display === 'none' ? '\u25BC' : '\u25B2';
          }

          // Auto mark as read when opening
          if (row.classList.contains('pn-customers-manager-message-unread')) {
            markRead(id, row);
          }
        });
      });

      // Mark as read
      document.querySelectorAll('.pn-customers-manager-msg-mark-read').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var id = this.getAttribute('data-id');
          var row = document.querySelector('tr.pn-customers-manager-message-row[data-id="' + id + '"]');
          markRead(id, row);
        });
      });

      function markRead(id, row) {
        var fd = new FormData();
        fd.append('action', 'pn_customers_manager_ajax');
        fd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_mark_read');
        fd.append('pn_customers_manager_ajax_nonce', nonce);
        fd.append('message_id', id);

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (!data.error_key || data.error_key === '') {
              if (row) {
                row.classList.remove('pn-customers-manager-message-unread');
                row.classList.add('pn-customers-manager-message-read');
                var dot = row.querySelector('.pn-customers-manager-status-dot');
                if (dot) {
                  dot.classList.remove('pn-customers-manager-status-unread');
                  dot.classList.add('pn-customers-manager-status-read');
                }
                var markBtn = row.querySelector('.pn-customers-manager-msg-mark-read');
                if (markBtn) markBtn.remove();
              }
              // Update badge
              var badge = document.querySelector('.pn-customers-manager-badge');
              if (badge && data.unread_count !== undefined) {
                if (data.unread_count > 0) {
                  badge.textContent = data.unread_count;
                } else {
                  badge.remove();
                }
              }
            }
          });
      }

      // Delete
      document.querySelectorAll('.pn-customers-manager-msg-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          if (!confirm('<?php echo esc_js(__('¿Estás seguro de que quieres eliminar este mensaje?', 'pn-customers-manager')); ?>')) return;
          var id = this.getAttribute('data-id');

          var fd = new FormData();
          fd.append('action', 'pn_customers_manager_ajax');
          fd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_delete');
          fd.append('pn_customers_manager_ajax_nonce', nonce);
          fd.append('message_id', id);

          fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (!data.error_key || data.error_key === '') {
                var row = document.querySelector('tr.pn-customers-manager-message-row[data-id="' + id + '"]');
                var detail = document.querySelector('tr.pn-customers-manager-message-detail[data-id="' + id + '"]');
                if (row) row.remove();
                if (detail) detail.remove();
                // Update badge
                var badge = document.querySelector('.pn-customers-manager-badge');
                if (badge && data.unread_count !== undefined) {
                  if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                  } else {
                    badge.remove();
                  }
                }
              }
            });
        });
      });
    })();
    </script>
    <?php
  }
}
