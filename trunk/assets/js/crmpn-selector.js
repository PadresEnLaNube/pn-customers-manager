(function($) {
    'use strict';

    class CRMPN_Selector {
        constructor(element, options = {}) {
            this.element = element;
            this.options = {
                multiple: $(element).prop('multiple'),
                searchable: true,
                placeholder: 'Select an option...',
                searchThreshold: 5,
                ...options
            };
            
            this.selectedValues = [];
            this.isOpen = false;
            // Get placeholder from attribute or empty option
            this.placeholder = $(this.element).attr('placeholder') || $(this.element).find('option[value=""]').text() || '';
            this.init();
        }

        init() {
            // Create the selector structure
            this.createStructure();
            this.bindEvents();
            // Only on initialization, sync visual with original select
            this.updateDisplay();
        }

        createStructure() {
            const wrapper = $('<div class="crmpn-selector"></div>');
            const control = $('<div class="crmpn-selector__control"></div>');
            const valueContainer = $('<div class="crmpn-selector__value-container"></div>');
            const input = $('<input type="text" class="crmpn-selector__input" />');
            const indicator = $('<span class="crmpn-selector__indicator"><i class="material-icons-outlined crmpn-selector__indicator-icon">keyboard_arrow_down</i></span>');
            
            valueContainer.append(input);
            control.append(valueContainer, indicator);
            wrapper.append(control);
            
            this.menu = $('<div class="crmpn-selector__menu" style="display: none;"></div>');
            
            if (this.options.searchable) {
                const searchContainer = $('<div class="crmpn-selector__search"></div>');
                const searchInput = $('<input type="text" placeholder="Search..." />');
                searchContainer.append(searchInput);
                this.menu.append(searchContainer);
                this.searchInput = searchInput;
            }
            
            this.optionsContainer = $('<div class="crmpn-selector__options"></div>');
            this.menu.append(this.optionsContainer);
            
            wrapper.append(this.menu);
            $(this.element)
                .css({
                    position: 'absolute',
                    opacity: 0.01,
                    height: '1px',
                    width: '1px',
                    padding: 0,
                    margin: '-1px',
                    overflow: 'hidden',
                    clip: 'rect(0,0,0,0)',
                    whiteSpace: 'nowrap',
                    border: 0
                })
                .after(wrapper);
            
            this.wrapper = wrapper;
            this.control = control;
            this.valueContainer = valueContainer;
            this.input = input;
        }

        bindEvents() {
            // Toggle menu
            this.control.on('click', (e) => {
                e.stopPropagation();
                this.toggleMenu();
            });

            // Close menu when clicking outside
            $(document).on('click', (e) => {
                if (!this.wrapper.has(e.target).length) {
                    this.closeMenu();
                }
            });

            // Handle option selection
            this.menu.on('click', '.crmpn-selector__option', (e) => {
                const option = $(e.currentTarget);
                const value = option.data('value');
                const label = option.text();

                const wasSelected = this.selectedValues.includes(value);

                if (this.options.multiple) {
                    this.toggleValue(value, label);
                } else {
                    this.setValue(value, label);
                    this.closeMenu();
                }

                // Update class according to new state
                if (this.options.multiple) {
                    if (wasSelected) {
                        option.removeClass('-selector__option--is-selected');
                    } else {
                        option.addClass('-selector__option--is-selected');
                    }
                }
            });

            // Handle search
            if (this.options.searchable) {
                this.searchInput.on('input', (e) => {
                    this.filterOptions(e.target.value);
                });
            }

            // Handle keyboard navigation
            this.input.on('keydown', (e) => {
                switch(e.key) {
                    case 'Enter':
                        e.preventDefault();
                        this.toggleMenu();
                        break;
                    case 'Escape':
                        this.closeMenu();
                        break;
                }
            });
        }

        toggleMenu() {
            if (this.isOpen) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        }

        openMenu() {
            this.isOpen = true;
            this.control.addClass('-selector__control--is-open');
            this.menu.show();
            this.updateOptions();
            // Change icon to arrow up
            this.wrapper.find('.crmpn-selector__indicator-icon').text('keyboard_arrow_up');

            // Auto-focus on search input if it's visible
            if (this.options.searchable && this.searchInput && this.searchInput.parent().is(':visible')) {
                // Use setTimeout to ensure the menu is fully rendered before focusing
                setTimeout(() => {
                    this.searchInput.focus();
                }, 10);
            }
        }

        closeMenu() {
            this.isOpen = false;
            this.control.removeClass('-selector__control--is-open');
            this.menu.hide();
            // Change icon to arrow down
            this.wrapper.find('.crmpn-selector__indicator-icon').text('keyboard_arrow_down');
        }

        updateOptions() {
            const options = $(this.element).find('option');
            this.optionsContainer.empty();

            options.each((_, option) => {
                const $option = $(option);
                const value = $option.val();
                const label = $option.text();

                // Do not show empty option
                if (value === '') return;

                const isSelected = this.selectedValues.includes(value);

                const optionElement = $('<div class="crmpn-selector__option"></div>')
                    .text(label)
                    .data('value', value);

                if (isSelected) {
                    optionElement.addClass('-selector__option--is-selected');
                }

                this.optionsContainer.append(optionElement);
            });

            // Show search if options exceed threshold
            if (this.options.searchable && options.length > this.options.searchThreshold) {
                this.searchInput.parent().show();
            } else {
                this.searchInput.parent().hide();
            }
        }

        filterOptions(searchTerm) {
            const options = this.optionsContainer.find('.crmpn-selector__option');
            const term = searchTerm.toLowerCase();

            // Function to normalize text by removing accents
            const normalizeText = (text) => {
                return text.normalize('NFD')
                          .replace(/[\u0300-\u036f]/g, '') // Remove diacritics (accents)
                          .toLowerCase();
            };

            options.each((_, option) => {
                const $option = $(option);
                const text = $option.text();
                const normalizedText = normalizeText(text);
                const normalizedTerm = normalizeText(term);
                $option.toggle(normalizedText.includes(normalizedTerm));
            });
        }

        toggleValue(value, label) {
            if (value === '') return;
            if (this.selectedValues.includes(value)) {
                this.removeAllSelectedValue(value);
                return;
            }
            this.selectedValues.push(value);
            this.addSelectedValue(value, label);
            this.updateOriginalSelect();
        }

        setValue(value, label) {
            this.selectedValues = [value];
            this.valueContainer.empty();
            this.addSelectedValue(value, label);
            this.updateOriginalSelect();
        }

        addSelectedValue(value, label) {
            // Remove placeholder if exists
            this.valueContainer.find('.crmpn-selector__placeholder').remove();
            if (this.options.multiple) {
                const valueElement = $('<div class="crmpn-selector__multi-value"></div>');
                const labelElement = $('<span class="crmpn-selector__multi-value__label"></span>').text(label);
                const removeButton = $('<span class="crmpn-selector__multi-value__remove"><i class="material-icons-outlined crmpn-icon-close">close</i></span>');

                valueElement.attr('data-value', value);

                removeButton.on('click', (e) => {
                    e.stopPropagation();
                    this.removeAllSelectedValue(value);
                    // Also remove selected class from the option in the dropdown menu
                    this.optionsContainer.find('.crmpn-selector__option').each(function() {
                        if ($(this).data('value') == value) {
                            $(this).removeClass('-selector__option--is-selected');
                        }
                    });
                });

                valueElement.append(labelElement, removeButton);
                this.valueContainer.append(valueElement);
            } else {
                // Single select: text + x to remove
                const valueElement = $('<span class="crmpn-selector__single-value"></span>');
                const labelElement = $('<span class="crmpn-selector__single-value__label"></span>').text(label);
                const removeButton = $('<span class="crmpn-selector__single-value__remove"><i class="material-icons-outlined crmpn-icon-close">close</i></span>');
                removeButton.on('click', (e) => {
                    e.stopPropagation();
                    // Clear selection
                    this.selectedValues = [];
                    this.valueContainer.empty();
                    this.valueContainer.find('.crmpn-selector__placeholder').remove();
                    this.updateOriginalSelect();
                    if (this.placeholder) {
                        this.input.hide();
                        this.valueContainer.append('<span class="crmpn-selector__placeholder">' + this.placeholder + '</span>');
                    }
                });
                valueElement.append(labelElement, removeButton);
                this.valueContainer.append(valueElement);
            }
        }

        removeAllSelectedValue(value) {
            // Remove all occurrences from the array
            this.selectedValues = this.selectedValues.filter(v => v !== value);
            // Remove all visual elements
            this.valueContainer.find(`[data-value="${value}"]`).remove();
            this.updateOriginalSelect();
            // If there are no selected values, show the placeholder and hide the input
            if (this.selectedValues.length === 0 && this.placeholder) {
                this.input.hide();
                this.valueContainer.find('.crmpn-selector__placeholder').remove();
                this.valueContainer.append('<span class="crmpn-selector__placeholder">' + this.placeholder + '</span>');
            } else {
                this.input.show();
            }
        }

        updateOriginalSelect() {
            const $select = $(this.element);
            
            if (this.options.multiple) {
                $select.find('option').prop('selected', false);
                this.selectedValues.forEach(value => {
                    $select.find(`option[value="${value}"]`).prop('selected', true);
                });
            } else {
                $select.val(this.selectedValues[0]);
            }

            $select.trigger('change');
        }

        updateDisplay() {
            const $select = $(this.element);
            const selectedOptions = $select.find('option:selected');
            this.selectedValues = [];
            this.valueContainer.empty();
            this.valueContainer.find('.crmpn-selector__placeholder').remove();

            // For single select: if only the empty value is selected, show only the placeholder
            if (
                !this.options.multiple &&
                selectedOptions.length === 1 &&
                selectedOptions[0].value === '' &&
                (selectedOptions.length === 0 && this.placeholder) ||
                (selectedOptions.length === 1 && selectedOptions[0].value === '' && this.placeholder)
            ) {
                this.input.hide();
                this.valueContainer.append('<span class="crmpn-selector__placeholder">' + this.placeholder + '</span>');
                return;
            } else {
                this.input.show();
            }

            selectedOptions.each((_, option) => {
                const $option = $(option);
                const value = $option.val();
                const label = $option.text();
                if (value === '') return; // Skip empty value (placeholder)
                this.selectedValues.push(value);
                this.addSelectedValue(value, label);
            });
        }
    }

    // jQuery plugin initialization
    $.fn.CRMPN_Selector = function(options) {
        return this.each(function() {
            if (!$(this).data('-selector')) {
                $(this).data('-selector', new CRMPN_Selector(this, options));
            }
        });
    };

})(jQuery); 