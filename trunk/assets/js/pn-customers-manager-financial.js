/**
 * PN Customers Manager - Financial Management System
 *
 * @since      1.1.151
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/assets/js
 */

(function($) {
  'use strict';

  var PN_CM_Financial = {
    currentCPT: '',
    currentCPTLabel: '',

    init: function() {
      this.bindEvents();
    },

    bindEvents: function() {
      // Enable/disable CPT checkbox - handled by forms system visibility
      // but we need to save state to the enabled_cpts array
      $(document).on('change', '.pn-cm-financial-enable-cpt', this.toggleCPTEnabled.bind(this));

      // Configure fields button
      $(document).on('click', '.pn-cm-configure-fields', this.openFieldsConfiguration.bind(this));

      // Add new field
      $(document).on('click', '#pn-cm-add-field-btn', this.addField.bind(this));

      // Remove field
      $(document).on('click', '.pn-cm-remove-field-btn', this.removeField.bind(this));

      // Save fields configuration
      $(document).on('click', '#pn-cm-save-fields-btn', this.saveFieldsConfiguration.bind(this));

      // Import PN template
      $(document).on('click', '.pn-cm-import-template-btn', this.importTemplate.bind(this));

      // Add field from selector
      $(document).on('click', '.pn-cm-field-type-card', this.addFieldFromType.bind(this));

      // Change field type select
      $(document).on('change', '.pn-cm-field-type-select', this.handleFieldTypeChange.bind(this));

      // Toggle options for select/radio
      $(document).on('click', '.pn-cm-toggle-options', this.toggleFieldOptions.bind(this));

      // Add option to select/radio field
      $(document).on('click', '.pn-cm-add-option-btn', this.addOption.bind(this));

      // Remove option from select/radio field
      $(document).on('click', '.pn-cm-remove-option-btn', this.removeOption.bind(this));

      // Move option up
      $(document).on('click', '.pn-cm-move-option-up', this.moveOptionUp.bind(this));

      // Move option down
      $(document).on('click', '.pn-cm-move-option-down', this.moveOptionDown.bind(this));

      // Toggle financial section in front-end
      $(document).on('click', '[data-toggle="financial"]', function() {
        var $content = $('.pn-cm-toggle-financial');
        var $icon = $(this).find('.pn-cm-toggle-icon');

        $content.slideToggle(300);
        $icon.text($content.is(':visible') ? 'expand_less' : 'expand_more');
      });

    },

    toggleCPTEnabled: function(e) {
      var $checkbox = $(e.target);
      // Extract CPT from checkbox ID: pn_cm_financial_enable_{cpt}
      var checkboxId = $checkbox.attr('id');
      if (!checkboxId) return;

      var cpt = checkboxId.replace('pn_cm_financial_enable_', '');
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
          alert('Failed to toggle CPT');
          $checkbox.prop('checked', !enabled);
        }
      });
    },

    openFieldsConfiguration: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);

      this.currentCPT = $btn.data('cpt');
      this.currentCPTLabel = $btn.data('label');

      // Debug: Log values
      console.log('CPT:', this.currentCPT, 'Label:', this.currentCPTLabel);
      console.log('Button:', $btn);
      console.log('Button HTML:', $btn[0].outerHTML);

      if (!this.currentCPT) {
        alert('Error: CPT no encontrado en el botón. Por favor, contacta con soporte.');
        console.error('Button attributes:', $btn[0].attributes);
        return;
      }

      // Load fields configuration modal
      $.ajax({
        url: pn_customers_manager_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'pn_customers_manager_ajax',
          pn_customers_manager_ajax_type: 'get_financial_fields_modal',
          pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
          cpt: this.currentCPT,
          cpt_label: this.currentCPTLabel
        },
        beforeSend: function() {
          $btn.prop('disabled', true).html('<i class="material-icons-outlined">hourglass_empty</i> Loading...');
        },
        success: function(response) {
          console.log('AJAX Response:', response);
          $btn.prop('disabled', false).html('<i class="material-icons-outlined">settings</i> Configure Fields');

          var data;
          try {
            data = typeof response === 'string' ? JSON.parse(response) : response;
          } catch (e) {
            console.error('JSON Parse Error:', e, response);
            alert('Error al procesar la respuesta del servidor');
            return;
          }

          console.log('Parsed data:', data);

          if (data.error_key) {
            console.error('Server error:', data.error_key, data.error_content);
            alert(data.error_content || 'Error loading fields configuration');
            return;
          }

          if (!data.html) {
            console.error('No HTML in response:', data);
            alert('Error: No se recibió contenido del modal');
            return;
          }

          // Show modal
          console.log('Injecting HTML into modal body');
          $('#pn-cm-fields-modal-body').html(data.html);
          $('#pn-cm-fields-modal-title').text('Configurar campos: ' + this.currentCPTLabel);

          console.log('Opening modal');
          if (window.pn_customers_manager_Popups) {
            pn_customers_manager_Popups.open('pn-cm-financial-fields-modal');
          } else {
            console.error('pn_customers_manager_Popups not available');
            alert('Error: Sistema de popups no disponible');
          }
        }.bind(this),
        error: function(xhr, status, error) {
          console.error('AJAX Error:', status, error, xhr);
          $btn.prop('disabled', false).html('<i class="material-icons-outlined">settings</i> Configure Fields');
          alert('Error al cargar la configuración de campos: ' + error);
        }
      });
    },

    addField: function(e) {
      e.preventDefault();
      var index = $('.pn-cm-field-item').length;
      var fieldHtml = this.getEmptyFieldHTML(index);
      $('#pn-cm-fields-list').append(fieldHtml);

      // Scroll to new field
      var $newField = $('.pn-cm-field-item').last();
      $newField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    addFieldFromType: function(e) {
      var $card = $(e.currentTarget);
      var fieldType = $card.data('type');
      var index = $('.pn-cm-field-item').length;

      var fieldHtml = this.getFieldHTMLByType(index, fieldType);
      $('#pn-cm-fields-list').append(fieldHtml);

      // Scroll to new field
      var $newField = $('.pn-cm-field-item').last();
      $newField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    getEmptyFieldHTML: function(index) {
      return `
        <div class="pn-cm-field-item" data-index="${index}">
          <div class="pn-cm-field-header">
            <span class="pn-cm-field-number">${index + 1}</span>
            <input type="text"
                   class="pn-cm-field-label-input"
                   name="field_label_${index}"
                   placeholder="Field Label"
                   value="">
            <button type="button" class="pn-cm-remove-field-btn">
              <i class="material-icons-outlined">delete</i>
            </button>
          </div>
          <div class="pn-cm-field-body">
            <div class="pn-cm-field-row">
              <div class="pn-cm-field-col">
                <label>Field ID</label>
                <input type="text" name="field_id_${index}" class="pn-cm-input" placeholder="pn_cm_financial_field">
              </div>
              <div class="pn-cm-field-col">
                <label>Field Type</label>
                <select name="field_type_${index}" class="pn-cm-select pn-cm-field-type-select">
                  <option value="text">Text</option>
                  <option value="number">Number</option>
                  <option value="email">Email</option>
                  <option value="url">URL</option>
                  <option value="date">Date</option>
                  <option value="time">Time</option>
                  <option value="color">Color</option>
                  <option value="textarea">Textarea</option>
                  <option value="select">Select</option>
                  <option value="checkbox">Checkbox</option>
                  <option value="radio">Radio</option>
                  <option value="range">Range</option>
                </select>
              </div>
            </div>
            <div class="pn-cm-field-row">
              <div class="pn-cm-field-col pn-cm-field-required-wrapper">
                <label class="pn-cm-field-required-label">Required Field</label>
                <label class="pn-customers-manager-switch">
                  <input type="checkbox" name="field_required_${index}" class="pn-customers-manager-checkbox pn-customers-manager-checkbox-switch">
                  <span class="pn-customers-manager-slider pn-customers-manager-round"></span>
                </label>
              </div>
            </div>
            <div class="pn-cm-field-options" style="display:none;" data-field-index="${index}">
              <div class="pn-cm-options-header">
                <label>Options</label>
                <button type="button" class="pn-cm-btn pn-cm-btn-small pn-cm-add-option-btn" data-field-index="${index}">
                  <i class="material-icons-outlined">add</i>
                  Add Option
                </button>
              </div>
              <div class="pn-cm-options-list" data-field-index="${index}">
                <!-- Options will be added here -->
              </div>
            </div>
          </div>
        </div>
      `;
    },

    getFieldHTMLByType: function(index, type) {
      // Similar to getEmptyFieldHTML but pre-filled with type
      var html = this.getEmptyFieldHTML(index);
      // We'll set the selected type via jQuery after insertion
      setTimeout(function() {
        $('[name="field_type_' + index + '"]').val(type).trigger('change');
      }, 50);
      return html;
    },

    removeField: function(e) {
      e.preventDefault();
      if (!confirm('Remove this field?')) return;

      $(e.currentTarget).closest('.pn-cm-field-item').slideUp(300, function() {
        $(this).remove();
        // Re-index
        $('.pn-cm-field-item').each(function(i) {
          $(this).find('.pn-cm-field-number').text(i + 1);
        });
      });
    },

    handleFieldTypeChange: function(e) {
      var $select = $(e.target);
      var $fieldItem = $select.closest('.pn-cm-field-item');
      var $optionsDiv = $fieldItem.find('.pn-cm-field-options');
      var type = $select.val();

      if (type === 'select' || type === 'radio') {
        $optionsDiv.slideDown(200);
      } else {
        $optionsDiv.slideUp(200);
      }
    },

    toggleFieldOptions: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      var $options = $btn.next('.pn-cm-field-options-content');
      $options.slideToggle(200);
    },

    addOption: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      var fieldIndex = $btn.data('field-index');
      var $optionsList = $('.pn-cm-options-list[data-field-index="' + fieldIndex + '"]');
      var optionIndex = $optionsList.find('.pn-cm-option-item').length;

      var optionHtml = `
        <div class="pn-cm-option-item" data-option-index="${optionIndex}">
          <div class="pn-cm-option-grip">
            <i class="material-icons-outlined">drag_indicator</i>
          </div>
          <div class="pn-cm-option-inputs">
            <input type="text"
                   class="pn-cm-input pn-cm-option-value"
                   placeholder="Value"
                   value="">
            <input type="text"
                   class="pn-cm-input pn-cm-option-label"
                   placeholder="Label"
                   value="">
          </div>
          <div class="pn-cm-option-actions">
            <button type="button" class="pn-cm-btn-icon pn-cm-move-option-up" title="Move up">
              <i class="material-icons-outlined">arrow_upward</i>
            </button>
            <button type="button" class="pn-cm-btn-icon pn-cm-move-option-down" title="Move down">
              <i class="material-icons-outlined">arrow_downward</i>
            </button>
            <button type="button" class="pn-cm-btn-icon pn-cm-remove-option-btn" title="Remove">
              <i class="material-icons-outlined">delete</i>
            </button>
          </div>
        </div>
      `;

      $optionsList.append(optionHtml);

      // Focus on the value input of the new option
      $optionsList.find('.pn-cm-option-item').last().find('.pn-cm-option-value').focus();
    },

    removeOption: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      var $option = $btn.closest('.pn-cm-option-item');

      if (!confirm('Remove this option?')) return;

      $option.slideUp(200, function() {
        $(this).remove();
      });
    },

    moveOptionUp: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      var $option = $btn.closest('.pn-cm-option-item');
      var $prev = $option.prev('.pn-cm-option-item');

      if ($prev.length) {
        $option.insertBefore($prev);
        // Add a brief highlight effect
        $option.css('background', '#e3f2fd');
        setTimeout(function() {
          $option.css('background', '');
        }, 300);
      }
    },

    moveOptionDown: function(e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      var $option = $btn.closest('.pn-cm-option-item');
      var $next = $option.next('.pn-cm-option-item');

      if ($next.length) {
        $option.insertAfter($next);
        // Add a brief highlight effect
        $option.css('background', '#e3f2fd');
        setTimeout(function() {
          $option.css('background', '');
        }, 300);
      }
    },

    saveFieldsConfiguration: function(e) {
      e.preventDefault();
      var fields = [];

      $('.pn-cm-field-item').each(function() {
        var index = $(this).data('index');
        var field = {
          id: $(this).find('[name="field_id_' + index + '"]').val(),
          label: $(this).find('[name="field_label_' + index + '"]').val(),
          type: $(this).find('[name="field_type_' + index + '"]').val(),
          input: 'input',
          required: $(this).find('[name="field_required_' + index + '"]').is(':checked')
        };

        // Handle input type
        if (field.type === 'textarea') {
          field.input = 'textarea';
        } else if (field.type === 'select' || field.type === 'radio') {
          field.input = field.type;

          // Get options from the options manager
          field.options = {};
          var $optionsList = $(this).find('.pn-cm-options-list[data-field-index="' + index + '"]');
          $optionsList.find('.pn-cm-option-item').each(function() {
            var key = $(this).find('.pn-cm-option-value').val().trim();
            var label = $(this).find('.pn-cm-option-label').val().trim();
            if (key) {
              field.options[key] = label || key;
            }
          });
        }

        // Add type-specific attributes
        if (field.type === 'number') {
          field.step = '0.01';
        } else if (field.type === 'range') {
          field.min = '0';
          field.max = '100';
        }

        fields.push(field);
      });

      if (fields.length === 0) {
        alert('Please add at least one field');
        return;
      }

      var $btn = $(e.currentTarget);
      var originalHTML = $btn.html();

      $.ajax({
        url: pn_customers_manager_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'pn_customers_manager_ajax',
          pn_customers_manager_ajax_type: 'save_financial_fields',
          pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
          post_type: this.currentCPT,
          fields: JSON.stringify(fields)
        },
        beforeSend: function() {
          $btn.prop('disabled', true).html('<i class="material-icons-outlined">hourglass_empty</i> Saving...');
        },
        success: function(response) {
          var data = typeof response === 'string' ? JSON.parse(response) : response;

          if (data.error_key) {
            $btn.prop('disabled', false).html(originalHTML);
            alert(data.error_content || 'Error saving fields');
            return;
          }

          $btn.html('<i class="material-icons-outlined">check</i> Saved!');
          setTimeout(function() {
            if (window.pn_customers_manager_Popups) {
              pn_customers_manager_Popups.close();
            }
            location.reload();
          }, 1000);
        },
        error: function() {
          $btn.prop('disabled', false).html(originalHTML);
          alert('Failed to save fields');
        }
      });
    },

    importTemplate: function(e) {
      e.preventDefault();

      var $btn = $(e.currentTarget);
      var cpt = $btn.data('cpt');

      if (!cpt) {
        alert('CPT not found');
        return;
      }

      if (!confirm('This will replace all current fields with the template. Continue?')) {
        return;
      }

      var originalHTML = $btn.html();

      $.ajax({
        url: pn_customers_manager_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'pn_customers_manager_ajax',
          pn_customers_manager_ajax_type: 'import_financial_template',
          pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
          cpt: cpt
        },
        beforeSend: function() {
          $btn.prop('disabled', true).html('<i class="material-icons-outlined">hourglass_empty</i> Importing...');
        },
        success: function(response) {
          var data = typeof response === 'string' ? JSON.parse(response) : response;

          if (data.error_key) {
            $btn.prop('disabled', false).html(originalHTML);
            alert(data.error_content || 'Error importing template');
            return;
          }

          // Show success and reload page to update field count
          $btn.html('<i class="material-icons-outlined">check</i> Imported!');
          setTimeout(function() {
            location.reload();
          }, 500);
        },
        error: function() {
          $btn.prop('disabled', false).html(originalHTML);
          alert('Failed to import template');
        }
      });
    }
  };

  // Initialize on document ready
  $(document).ready(function() {
    PN_CM_Financial.init();
  });

})(jQuery);
