/**
 * PN_CUSTOMERS_MANAGER - Tutorial Onboarding
 *
 * @package PN_CUSTOMERS_MANAGER
 * @since 1.0.0
 */

(function ($) {
  'use strict';

  var currentStep = 1;
  var totalSteps = 5;
  var $overlay = $('#pn-customers-manager-tutorial-overlay');
  var $spotlight = $('.pn-customers-manager-tutorial-spotlight');
  var $tutorialBox = $('.pn-customers-manager-tutorial-box');

  // Initialize tutorial
  function initTutorial() {
    if ($overlay.length === 0) return;

    // Allow scrolling on body when tutorial is active
    $('body').css('overflow', 'auto');

    // Update spotlight position on scroll
    $(window).on('scroll.tutorial', function() {
      var $currentStep = $('.pn-customers-manager-tutorial-step:visible');
      if ($currentStep.length > 0) {
        // Debounce the positioning to avoid too many calls
        clearTimeout(window.tutorialScrollTimeout);
        window.tutorialScrollTimeout = setTimeout(function() {
          positionTutorial($currentStep);
        }, 50);
      }
    });

    // Show the tutorial overlay
    $overlay.fadeIn(300, function () {
      // Wait a bit more for everything to settle
      setTimeout(function() {
        showStep(1);
      }, 100);
    });
  }

  // Show specific step
  function showStep(step) {
    currentStep = step;

    // Hide all steps
    $('.pn-customers-manager-tutorial-step').hide();

    // Show current step
    var $currentStep = $('.pn-customers-manager-tutorial-step[data-step="' + step + '"]');
    $currentStep.fadeIn(300, function() {
      // After fadeIn completes, ensure positioning happens
      setTimeout(function() {
        processStepPositioning($currentStep);
      }, 50);
    });
  }

  // Process positioning for a step (separated for clarity)
  function processStepPositioning($currentStep) {
    // Open the target section if it's collapsed FIRST, then position
    var target = $currentStep.data('target');
    if (target) {
      var $target = $(target);
      if ($target.length > 0) {
        // If it's a section, expand it BEFORE positioning
        if ($target.hasClass('pn-customers-manager-section-wrapper')) {
          var $toggleContent = $target.find('.pn-customers-manager-toggle-content');
          if ($toggleContent.hasClass('pn-customers-manager-display-none-soft')) {
            // Expand the section first
            $target.find('.pn-customers-manager-toggle').click();

            // Wait for animation to complete, then position and scroll
            setTimeout(function () {
              // Now position with the expanded section
              positionTutorial($currentStep);

              // Force a second positioning after a delay to ensure everything is loaded
              setTimeout(function() {
                positionTutorial($currentStep);
              }, 100);

              // Scroll to target with offset for better visibility
              var targetOffset = $target.offset().top - 100;
              $('html, body').animate({ scrollTop: targetOffset }, 400);
            }, 450);
            return; // Exit here, everything will happen after animation
          }
        }

        // Section is already expanded, position immediately
        positionTutorial($currentStep);

        // Force a second positioning to ensure dimensions are correct
        setTimeout(function() {
          positionTutorial($currentStep);
        }, 100);

        // Scroll to target with offset for better visibility
        setTimeout(function () {
          var targetOffset = $target.offset().top - 100;
          $('html, body').animate({ scrollTop: targetOffset }, 400);
        }, 100);
      } else {
        // Target not found, just position centered
        positionTutorial($currentStep);
      }
    } else {
      // No target specified, just position centered
      positionTutorial($currentStep);
    }
  }

  // Position tutorial elements around target
  function positionTutorial($step) {
    var windowWidth = $(window).width();
    var target = $step.data('target');

    console.log('Positioning tutorial for target:', target);

    // On medium and small screens (< 1200px), always center the tutorial box
    if (windowWidth < 1200) {
      console.log('Small screen, centering');
      $spotlight.hide();
      $tutorialBox.css({
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        maxWidth: '420px',
        width: 'calc(100% - 40px)'
      });
      return;
    }

    if (!target) {
      // No specific target, center the tutorial box
      console.log('No target, centering');
      $spotlight.hide();
      $tutorialBox.css({
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        maxWidth: '420px',
        width: '90%'
      });
      return;
    }

    var $target = $(target);
    console.log('Target element:', $target, 'Length:', $target.length, 'Visible:', $target.is(':visible'));

    if ($target.length === 0) {
      console.log('Target not found');
      $spotlight.hide();
      $tutorialBox.css({
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        maxWidth: '420px',
        width: '90%'
      });
      return;
    }

    // Wait a bit more to ensure section is fully expanded and visible
    if (!$target.is(':visible')) {
      console.log('Target not visible yet, waiting...');
      setTimeout(function() {
        positionTutorial($step);
      }, 200);
      return;
    }

    // Show and position spotlight (only on large screens)
    // Use getBoundingClientRect for fixed positioning
    var targetRect = $target[0].getBoundingClientRect();
    var targetWidth = $target.outerWidth();
    var targetHeight = $target.outerHeight();
    var scrollTop = $(window).scrollTop();
    var scrollLeft = $(window).scrollLeft();

    console.log('Target dimensions:', {
      rect: targetRect,
      width: targetWidth,
      height: targetHeight,
      scrollTop: scrollTop,
      scrollLeft: scrollLeft
    });

    // Ensure we have valid dimensions
    if (!targetRect || targetWidth === 0 || targetHeight === 0) {
      console.log('Invalid dimensions, retrying...');
      setTimeout(function() {
        positionTutorial($step);
      }, 200);
      return;
    }

    // Position spotlight using fixed positioning (relative to viewport)
    console.log('Showing spotlight at:', targetRect.top - 10, targetRect.left - 10);
    $spotlight.css({
      display: 'block',
      top: (targetRect.top - 10) + 'px',
      left: (targetRect.left - 10) + 'px',
      width: (targetWidth + 20) + 'px',
      height: (targetHeight + 20) + 'px'
    });

    // Position tutorial box near the target (using fixed positioning to match spotlight)
    var boxWidth = 400;
    var boxLeft = targetRect.left + targetWidth + 30;
    var boxTop = targetRect.top;

    // If box would go off right side, position it to the left
    if (boxLeft + boxWidth > windowWidth) {
      boxLeft = targetRect.left - boxWidth - 30;
    }

    // If box would go off left side, position it centered
    if (boxLeft < 0) {
      boxLeft = (windowWidth - boxWidth) / 2;
      boxTop = targetRect.top + targetHeight + 20;
    }

    // Ensure box stays on screen vertically
    var boxHeight = $tutorialBox.outerHeight();
    var windowHeight = $(window).height();
    if (boxTop + boxHeight > windowHeight) {
      boxTop = windowHeight - boxHeight - 20;
    }
    if (boxTop < 20) {
      boxTop = 20;
    }

    $tutorialBox.css({
      position: 'fixed',
      top: boxTop + 'px',
      left: boxLeft + 'px',
      maxWidth: boxWidth + 'px',
      width: boxWidth + 'px',
      transform: 'none'
    });

    // Adjust on window resize
    $(window).off('resize.tutorial').on('resize.tutorial', function () {
      positionTutorial($step);
    });
  }

  // Next step
  $(document).on('click', '.pn-customers-manager-tutorial-next', function (e) {
    e.preventDefault();
    if (currentStep < totalSteps) {
      showStep(currentStep + 1);
    }
  });

  // Previous step
  $(document).on('click', '.pn-customers-manager-tutorial-prev', function (e) {
    e.preventDefault();
    if (currentStep > 1) {
      showStep(currentStep - 1);
    }
  });

  // Skip tutorial
  $(document).on('click', '.pn-customers-manager-tutorial-skip', function (e) {
    e.preventDefault();
    if (confirm('Are you sure you want to skip the tutorial? You can always view it again from the help section.')) {
      closeTutorial(false);
    }
  });

  // Finish tutorial
  $(document).on('click', '.pn-customers-manager-tutorial-finish', function (e) {
    e.preventDefault();
    closeTutorial(true);
  });

  // Close tutorial with ESC key
  $(document).on('keydown.tutorial', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      if ($overlay.is(':visible')) {
        e.preventDefault();
        if (confirm('Are you sure you want to skip the tutorial? You can always view it again from the help section.')) {
          closeTutorial(false);
        }
      }
    }
  });

  // Close tutorial
  function closeTutorial(completed) {
    // Collapse all expanded sections before closing
    $('.pn-customers-manager-section-wrapper').each(function() {
      var $section = $(this);
      var $content = $section.find('.pn-customers-manager-toggle-content');

      // If section is expanded, collapse it
      if (!$content.hasClass('pn-customers-manager-display-none-soft')) {
        $section.find('.pn-customers-manager-toggle').click();
      }
    });

    // Scroll to top of the page
    $('html, body').animate({ scrollTop: 0 }, 400);

    $overlay.fadeOut(300, function () {
      $overlay.remove();
    });

    // Re-enable body scrolling
    $('body').css('overflow', '');

    // Save completion status
    $.ajax({
      url: pnCustomersManagerTutorial.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'pn_customers_manager_complete_tutorial',
        pn_customers_manager_ajax_nonce: pnCustomersManagerTutorial.nonce,
        completed: completed ? 1 : 0
      }
    });

    // Clean up event listeners
    $(window).off('resize.tutorial');
    $(window).off('scroll.tutorial');
    $(document).off('keydown.tutorial');
    $(document).off('mousedown.tutorialdrag');
    $(document).off('mousemove.tutorialdrag');
    $(document).off('mouseup.tutorialdrag');

    // Clear any pending scroll timeout
    if (window.tutorialScrollTimeout) {
      clearTimeout(window.tutorialScrollTimeout);
    }
  }

  // Make tutorial draggable by header
  var isDragging = false;
  var dragOffset = { x: 0, y: 0 };

  $(document).on('mousedown.tutorialdrag', '.pn-customers-manager-tutorial-header', function (e) {
    // Don't drag if clicking on skip button
    if ($(e.target).closest('.pn-customers-manager-tutorial-skip').length > 0) {
      return;
    }

    isDragging = true;

    // Get current position
    var tutorialPos = $tutorialBox.offset();

    // Calculate offset from mouse to tutorial box top-left corner
    dragOffset.x = e.pageX - tutorialPos.left;
    dragOffset.y = e.pageY - tutorialPos.top;

    // Change cursor
    $tutorialBox.css('cursor', 'grabbing');
    $('.pn-customers-manager-tutorial-header').css('cursor', 'grabbing');

    // Prevent text selection while dragging
    e.preventDefault();
  });

  $(document).on('mousemove.tutorialdrag', function (e) {
    if (!isDragging) return;

    // Calculate new position
    var newLeft = e.pageX - dragOffset.x;
    var newTop = e.pageY - dragOffset.y;

    // Get window dimensions
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();
    var boxWidth = $tutorialBox.outerWidth();
    var boxHeight = $tutorialBox.outerHeight();

    // Keep within bounds
    newLeft = Math.max(10, Math.min(newLeft, windowWidth - boxWidth - 10));
    newTop = Math.max(10, Math.min(newTop, windowHeight - boxHeight - 10));

    // Position the tutorial box
    $tutorialBox.css({
      position: 'fixed',
      left: newLeft + 'px',
      top: newTop + 'px',
      transform: 'none'
    });

    // Hide spotlight while dragging for better UX
    $spotlight.hide();
  });

  $(document).on('mouseup.tutorialdrag', function (e) {
    if (isDragging) {
      isDragging = false;

      // Reset cursor
      $tutorialBox.css('cursor', '');
      $('.pn-customers-manager-tutorial-header').css('cursor', 'move');
    }
  });

  // Initialize on document ready
  $(document).ready(function () {
    // Ensure page is fully rendered before initializing
    setTimeout(function () {
      initTutorial();

      // Set cursor on header to indicate it's draggable
      setTimeout(function() {
        $('.pn-customers-manager-tutorial-header').css('cursor', 'move');
      }, 400);
    }, 600);
  });

})(jQuery);
