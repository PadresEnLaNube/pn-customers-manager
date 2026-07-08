/**
 * PN_CUSTOMERS_MANAGER - Financial Management
 *
 * @package PN_CUSTOMERS_MANAGER
 * @since 1.1.160
 */

jQuery(document).ready(function($) {
  // Add category
  $('.pn-cm-add-category').on('click', function() {
    var cpt = $(this).data('cpt');
    var $manager = $(this).closest('.pn-cm-categories-manager');

    var name = prompt('Category name:');
    if (!name) return;

    var type = confirm('Is this an INCOME category? (Cancel for EXPENSE)') ? 'income' : 'expense';
    var color = prompt('Category color (hex):', type === 'income' ? '#10b981' : '#ef4444');

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'add_financial_category',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        cpt: cpt,
        name: name,
        type: type,
        color: color
      },
      success: function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.error_key) {
          alert(data.error_content || 'Error adding category');
        } else {
          location.reload();
        }
      },
      error: function() {
        alert('Error adding category');
      }
    });
  });

  // Delete category
  $(document).on('click', '.pn-cm-delete-category', function() {
    if (!confirm('Are you sure you want to delete this category?')) {
      return;
    }

    var categoryId = $(this).data('id');
    var cpt = $(this).closest('.pn-cm-categories-manager').data('cpt');

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'delete_financial_category',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        cpt: cpt,
        category_id: categoryId
      },
      success: function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.error_key) {
          alert(data.error_content || 'Error deleting category');
        } else {
          location.reload();
        }
      },
      error: function() {
        alert('Error deleting category');
      }
    });
  });

  // Toggle CPT enable
  $('.pn-cm-financial-enable-cpt').on('change', function() {
    var $checkbox = $(this);
    var cpt = $checkbox.attr('id').replace('pn_cm_financial_enable_', '');
    var enabled = $checkbox.is(':checked');

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'toggle_financial_cpt',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        post_type: cpt,
        enabled: enabled ? '1' : '0'
      },
      beforeSend: function() {
        $checkbox.prop('disabled', true);
      },
      success: function(response) {
        $checkbox.prop('disabled', false);
        var data = typeof response === 'string' ? JSON.parse(response) : response;

        if (data.error_key) {
          alert(data.error_content || 'Error toggling CPT');
          $checkbox.prop('checked', !enabled);
          return;
        }

        // Trigger forms system visibility update
        if (typeof window.cm_pn_form_update === 'function') {
          window.cm_pn_form_update();
        }
      },
      error: function() {
        $checkbox.prop('disabled', false);
        alert('Error toggling CPT');
        $checkbox.prop('checked', !enabled);
      }
    });
  });
});
