<?php
/**
 * Tutorial Onboarding Manager.
 *
 * This class handles the interactive tutorial/onboarding experience for new users.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage pn-customers-manager/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Tutorial
{
  /**
   * Check if tutorial should be displayed
   *
   * @return bool
   */
  public static function should_display()
  {
    return !get_option('pn_customers_manager_tutorial_completed', false);
  }

  /**
   * Enqueue tutorial assets
   */
  public static function enqueue_assets()
  {
    if (!self::should_display()) {
      return;
    }

    wp_enqueue_style(
      'pn-customers-manager-tutorial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-tutorial.css',
      [],
      PN_CUSTOMERS_MANAGER_VERSION
    );

    wp_enqueue_script(
      'pn-customers-manager-tutorial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-tutorial.js',
      ['jquery'],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    wp_localize_script('pn-customers-manager-tutorial', 'pnCustomersManagerTutorial', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
    ]);
  }

  /**
   * Render tutorial HTML
   */
  public static function render()
  {
    if (!self::should_display()) {
      return;
    }
    ?>
    <!-- Tutorial Onboarding Overlay -->
    <div id="pn-customers-manager-tutorial-overlay" class="pn-customers-manager-tutorial-overlay">
      <div class="pn-customers-manager-tutorial-spotlight"></div>
      <div class="pn-customers-manager-tutorial-box">
        <?php self::render_step_welcome(); ?>
        <?php self::render_step_marketing(); ?>
        <?php self::render_step_referrals(); ?>
        <?php self::render_step_woocommerce(); ?>
        <?php self::render_step_finish(); ?>
      </div>
    </div>
    <?php
  }

  /**
   * Render Step 1: Welcome
   */
  private static function render_step_welcome()
  {
    ?>
    <div class="pn-customers-manager-tutorial-step" data-step="1" data-target=".pn-customers-manager-options">
      <div class="pn-customers-manager-tutorial-header">
        <h2><?php esc_html_e('Welcome to PN Customers Manager', 'pn-customers-manager'); ?></h2>
        <button type="button" class="pn-customers-manager-tutorial-skip" title="<?php esc_attr_e('Skip tutorial', 'pn-customers-manager'); ?>">
          <i class="material-icons-outlined">close</i>
        </button>
      </div>
      <div class="pn-customers-manager-tutorial-content">
        <p><?php esc_html_e('Let us guide you through the main features of this powerful CRM and customer management plugin.', 'pn-customers-manager'); ?></p>
        <ul class="pn-customers-manager-tutorial-features">
          <li><i class="material-icons-outlined">check_circle</i> <?php esc_html_e('Manage organizations, budgets and invoices', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">check_circle</i> <?php esc_html_e('AI-powered WhatsApp and Instagram chat integration', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">check_circle</i> <?php esc_html_e('Referral system with tracking and rewards', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">check_circle</i> <?php esc_html_e('Sales projections and business analytics', 'pn-customers-manager'); ?></li>
        </ul>
      </div>
      <div class="pn-customers-manager-tutorial-footer">
        <?php self::render_progress(1, 5); ?>
        <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-tutorial-next">
          <?php esc_html_e('Get Started', 'pn-customers-manager'); ?> <i class="material-icons-outlined">arrow_forward</i>
        </button>
      </div>
    </div>
    <?php
  }

  /**
   * Render Step 2: Marketing Configuration
   */
  private static function render_step_marketing()
  {
    ?>
    <div class="pn-customers-manager-tutorial-step" data-step="2" data-target="#pn_customers_manager_marketing_parent_section_start">
      <div class="pn-customers-manager-tutorial-header">
        <h2><?php esc_html_e('Marketing & Company Info', 'pn-customers-manager'); ?></h2>
        <button type="button" class="pn-customers-manager-tutorial-skip" title="<?php esc_attr_e('Skip tutorial', 'pn-customers-manager'); ?>">
          <i class="material-icons-outlined">close</i>
        </button>
      </div>
      <div class="pn-customers-manager-tutorial-content">
        <p><?php esc_html_e('Configure your company information, branding, and marketing materials that will be used throughout the CRM.', 'pn-customers-manager'); ?></p>
        <ul class="pn-customers-manager-tutorial-features">
          <li><i class="material-icons-outlined">business</i> <?php esc_html_e('Set company name, logo and contact details', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">description</i> <?php esc_html_e('Customize business offers and presentations', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">palette</i> <?php esc_html_e('Brand your documents and communications', 'pn-customers-manager'); ?></li>
        </ul>
      </div>
      <div class="pn-customers-manager-tutorial-footer">
        <?php self::render_progress(2, 5); ?>
        <?php self::render_navigation(); ?>
      </div>
    </div>
    <?php
  }

  /**
   * Render Step 3: Referral System
   */
  private static function render_step_referrals()
  {
    ?>
    <div class="pn-customers-manager-tutorial-step" data-step="3" data-target="#pn_customers_manager_referral_section_start">
      <div class="pn-customers-manager-tutorial-header">
        <h2><?php esc_html_e('Referral System', 'pn-customers-manager'); ?></h2>
        <button type="button" class="pn-customers-manager-tutorial-skip" title="<?php esc_attr_e('Skip tutorial', 'pn-customers-manager'); ?>">
          <i class="material-icons-outlined">close</i>
        </button>
      </div>
      <div class="pn-customers-manager-tutorial-content">
        <p><?php esc_html_e('Enable and configure a powerful referral system to grow your customer base through word-of-mouth marketing.', 'pn-customers-manager'); ?></p>
        <ul class="pn-customers-manager-tutorial-features">
          <li><i class="material-icons-outlined">link</i> <?php esc_html_e('Generate unique referral links for customers', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">qr_code</i> <?php esc_html_e('Create QR codes with custom branding', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">leaderboard</i> <?php esc_html_e('Track referrals and display rankings', 'pn-customers-manager'); ?></li>
        </ul>
        <p class="pn-customers-manager-tutorial-tip">
          <i class="material-icons-outlined">lightbulb</i>
          <span>
            <strong><?php esc_html_e('Tip:', 'pn-customers-manager'); ?></strong>
            <?php esc_html_e('Enable automatic reminders to encourage customers to share their referral links.', 'pn-customers-manager'); ?>
          </span>
        </p>
      </div>
      <div class="pn-customers-manager-tutorial-footer">
        <?php self::render_progress(3, 5); ?>
        <?php self::render_navigation(); ?>
      </div>
    </div>
    <?php
  }

  /**
   * Render Step 4: WooCommerce Integration
   */
  private static function render_step_woocommerce()
  {
    ?>
    <div class="pn-customers-manager-tutorial-step" data-step="4" data-target="#pn_customers_manager_woocommerce_section_start">
      <div class="pn-customers-manager-tutorial-header">
        <h2><?php esc_html_e('WooCommerce Integration', 'pn-customers-manager'); ?></h2>
        <button type="button" class="pn-customers-manager-tutorial-skip" title="<?php esc_attr_e('Skip tutorial', 'pn-customers-manager'); ?>">
          <i class="material-icons-outlined">close</i>
        </button>
      </div>
      <div class="pn-customers-manager-tutorial-content">
        <p><?php esc_html_e('Seamlessly integrate with WooCommerce to enhance your e-commerce capabilities with advanced features.', 'pn-customers-manager'); ?></p>
        <ul class="pn-customers-manager-tutorial-features">
          <li><i class="material-icons-outlined">shopping_cart</i> <?php esc_html_e('Custom checkout fields for better data', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">event</i> <?php esc_html_e('Calendar booking products for services', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">qr_code_scanner</i> <?php esc_html_e('QR code tickets for events and bookings', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">summarize</i> <?php esc_html_e('Monthly accounting summaries and reports', 'pn-customers-manager'); ?></li>
        </ul>
      </div>
      <div class="pn-customers-manager-tutorial-footer">
        <?php self::render_progress(4, 5); ?>
        <?php self::render_navigation(); ?>
      </div>
    </div>
    <?php
  }

  /**
   * Render Step 5: Finish
   */
  private static function render_step_finish()
  {
    ?>
    <div class="pn-customers-manager-tutorial-step" data-step="5" data-target=".pn-customers-manager-settings-footer">
      <div class="pn-customers-manager-tutorial-header">
        <h2><?php esc_html_e('You\'re All Set!', 'pn-customers-manager'); ?></h2>
      </div>
      <div class="pn-customers-manager-tutorial-content">
        <p><?php esc_html_e('You now know the basics of PN Customers Manager. Start by creating your first organization or exploring the CRM features.', 'pn-customers-manager'); ?></p>
        <ul class="pn-customers-manager-tutorial-features">
          <li><i class="material-icons-outlined">business</i> <?php esc_html_e('Create organizations and contacts', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">filter_alt</i> <?php esc_html_e('Set up sales funnels to track progress', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">receipt</i> <?php esc_html_e('Generate budgets and invoices', 'pn-customers-manager'); ?></li>
          <li><i class="material-icons-outlined">chat</i> <?php esc_html_e('Connect WhatsApp and Instagram chats', 'pn-customers-manager'); ?></li>
        </ul>
        <p class="pn-customers-manager-tutorial-tip">
          <i class="material-icons-outlined">help_outline</i>
          <span>
            <strong><?php esc_html_e('Need help?', 'pn-customers-manager'); ?></strong>
            <?php esc_html_e('Check our documentation or use the tooltips throughout the interface for guidance.', 'pn-customers-manager'); ?>
          </span>
        </p>
      </div>
      <div class="pn-customers-manager-tutorial-footer">
        <?php self::render_progress(5, 5); ?>
        <div class="pn-customers-manager-tutorial-navigation">
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent pn-customers-manager-tutorial-prev">
            <i class="material-icons-outlined">arrow_back</i> <?php esc_html_e('Back', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-tutorial-finish">
            <?php esc_html_e('Finish Tutorial', 'pn-customers-manager'); ?> <i class="material-icons-outlined">done</i>
          </button>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * Render progress indicator
   *
   * @param int $current Current step number
   * @param int $total Total number of steps
   */
  private static function render_progress($current, $total)
  {
    $percentage = ($current / $total) * 100;
    ?>
    <div class="pn-customers-manager-tutorial-progress">
      <span class="pn-customers-manager-tutorial-progress-text">
        <?php
        // translators: %1$d is the current step number, %2$d is the total number of steps
        echo esc_html(sprintf(__('Step %1$d of %2$d', 'pn-customers-manager'), $current, $total)); ?>
      </span>
      <div class="pn-customers-manager-tutorial-progress-bar">
        <div class="pn-customers-manager-tutorial-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
      </div>
    </div>
    <?php
  }

  /**
   * Render navigation buttons (back/next)
   */
  private static function render_navigation()
  {
    ?>
    <div class="pn-customers-manager-tutorial-navigation">
      <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent pn-customers-manager-tutorial-prev">
        <i class="material-icons-outlined">arrow_back</i> <?php esc_html_e('Back', 'pn-customers-manager'); ?>
      </button>
      <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-tutorial-next">
        <?php esc_html_e('Next', 'pn-customers-manager'); ?> <i class="material-icons-outlined">arrow_forward</i>
      </button>
    </div>
    <?php
  }

  /**
   * Mark tutorial as completed
   *
   * @param bool $completed Whether tutorial was completed or skipped
   */
  public static function mark_completed($completed = true)
  {
    // Always store a truthy value — both "completed" and "skipped" mean
    // "don't show again". WordPress stores false as '' which get_option
    // returns as falsy, causing should_display() to re-show the tutorial.
    update_option('pn_customers_manager_tutorial_completed', $completed ? 'completed' : 'skipped');
  }

  /**
   * Reset tutorial (for testing or re-showing)
   */
  public static function reset()
  {
    delete_option('pn_customers_manager_tutorial_completed');
  }
}
