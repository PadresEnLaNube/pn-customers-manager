/**
 * AI Offer Generator Wizard JavaScript.
 *
 * Handles wizard navigation, form validation, and AJAX generation.
 *
 * @package PN_CUSTOMERS_MANAGER
 * @since   1.0.70
 */

(function($) {
	'use strict';

	/**
	 * Offer Generator Wizard class.
	 */
	class OfferGeneratorWizard {
		constructor($container) {
			this.$container = $container;
			this.$form = $container.find('#pn-cm-offer-gen-form');
			this.$steps = $container.find('.pn-cm-offer-gen-step');
			this.currentStep = 1;
			this.totalSteps = this.$steps.length;

			this.init();
		}

		/**
		 * Initialize wizard.
		 */
		init() {
			this.bindEvents();
			this.updateProgress();
		}

		/**
		 * Bind event handlers.
		 */
		bindEvents() {
			const self = this;

			// Next button
			this.$container.on('click', '.pn-cm-offer-gen-next', function(e) {
				e.preventDefault();
				self.nextStep();
			});

			// Previous button
			this.$container.on('click', '.pn-cm-offer-gen-prev', function(e) {
				e.preventDefault();
				self.prevStep();
			});

			// Generate button
			this.$container.on('click', '.pn-cm-offer-gen-generate', function(e) {
				e.preventDefault();
				self.generateOffer();
			});

			// Retry button
			this.$container.on('click', '.pn-cm-offer-gen-retry', function(e) {
				e.preventDefault();
				self.hideError();
				self.goToStep(1);
			});

			// Radio cards
			this.$container.on('click', '.pn-cm-offer-gen-radio-card', function() {
				$(this).find('input[type="radio"]').prop('checked', true);
			});
		}

		/**
		 * Go to next step.
		 */
		nextStep() {
			if (!this.validateCurrentStep()) {
				return;
			}

			if (this.currentStep < this.totalSteps) {
				this.currentStep++;
				this.goToStep(this.currentStep);
			}
		}

		/**
		 * Go to previous step.
		 */
		prevStep() {
			if (this.currentStep > 1) {
				this.currentStep--;
				this.goToStep(this.currentStep);
			}
		}

		/**
		 * Navigate to specific step.
		 */
		goToStep(step) {
			this.currentStep = step;

			// Hide all steps
			this.$steps.removeClass('pn-cm-offer-gen-step-active');

			// Show current step
			this.$steps.filter('[data-step="' + step + '"]').addClass('pn-cm-offer-gen-step-active');

			// Update progress
			this.updateProgress();

			// Scroll to top
			this.$container[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		/**
		 * Update progress bar.
		 */
		updateProgress() {
			const percentage = (this.currentStep / this.totalSteps) * 100;
			this.$container.find('.pn-cm-offer-gen-wizard-progress-fill').css('width', percentage + '%');
			this.$container.find('.pn-cm-offer-gen-wizard-progress-text').text(
				pnCMOfferGen.strings.step_of.replace('%1$d', this.currentStep).replace('%2$d', this.totalSteps)
			);
		}

		/**
		 * Validate current step.
		 */
		validateCurrentStep() {
			const $currentStep = this.$steps.filter('[data-step="' + this.currentStep + '"]');
			const $requiredFields = $currentStep.find('[required]');
			let isValid = true;

			$requiredFields.each(function() {
				const $field = $(this);
				const value = $field.val().trim();

				if (value === '') {
					isValid = false;
					$field.addClass('pn-cm-offer-gen-field-error');

					// Show error message
					if (!$field.next('.pn-cm-offer-gen-field-error-msg').length) {
						$field.after('<span class="pn-cm-offer-gen-field-error-msg">' + pnCMOfferGen.strings.field_required + '</span>');
					}
				} else {
					$field.removeClass('pn-cm-offer-gen-field-error');
					$field.next('.pn-cm-offer-gen-field-error-msg').remove();
				}
			});

			return isValid;
		}

		/**
		 * Generate offer via AJAX.
		 */
		generateOffer() {
			if (!this.validateCurrentStep()) {
				return;
			}

			const formData = this.$form.serialize();
			const $generateBtn = this.$container.find('.pn-cm-offer-gen-generate');

			// Show loading state
			$generateBtn.prop('disabled', true);
			$generateBtn.find('.pn-cm-offer-gen-btn-text').hide();
			$generateBtn.find('.pn-cm-offer-gen-spinner').show();

			$.ajax({
				url: pnCMOfferGen.ajax_url,
				type: 'POST',
				data: formData + '&action=pn_cm_generate_offer',
				dataType: 'json',
				success: (response) => {
					if (response.success) {
						this.showSuccess(response.data);
					} else {
						this.showError(response.data.message || pnCMOfferGen.strings.generation_failed);
					}
				},
				error: (xhr, status, error) => {
					console.error('AJAX Error:', status, error);
					this.showError(pnCMOfferGen.strings.ajax_error);
				},
				complete: () => {
					// Hide loading state
					$generateBtn.prop('disabled', false);
					$generateBtn.find('.pn-cm-offer-gen-btn-text').show();
					$generateBtn.find('.pn-cm-offer-gen-spinner').hide();
				}
			});
		}

		/**
		 * Show success result.
		 */
		showSuccess(data) {
			this.$form.hide();
			this.$container.find('.pn-cm-offer-gen-wizard-progress').hide();

			const $result = this.$container.find('.pn-cm-offer-gen-result');
			$result.find('.pn-cm-offer-gen-result-message').text(data.message);
			$result.find('.pn-cm-offer-gen-edit-link').attr('href', data.edit_url);
			$result.show();

			// Scroll to result
			$result[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

			// Optional: Auto-redirect after 3 seconds
			setTimeout(() => {
				window.location.href = data.edit_url;
			}, 3000);
		}

		/**
		 * Show error message.
		 */
		showError(message) {
			const $error = this.$container.find('.pn-cm-offer-gen-error');
			$error.find('.pn-cm-offer-gen-error-message').text(message);
			$error.show();

			// Scroll to error
			$error[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
		}

		/**
		 * Hide error message.
		 */
		hideError() {
			this.$container.find('.pn-cm-offer-gen-error').hide();
		}
	}

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function() {
		$('.pn-cm-offer-gen-wizard').each(function() {
			new OfferGeneratorWizard($(this));
		});
	});

})(jQuery);
