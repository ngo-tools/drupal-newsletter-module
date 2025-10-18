/**
 * @file
 * JavaScript for NGO Tools Newsletter signup form.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.ngoToolsNewsletter = {
    attach: function (context, settings) {
      // Add any custom JavaScript behavior here if needed.
      // The form uses standard Drupal form submission.
      
      // Example: Add form submission feedback
      const forms = context.querySelectorAll('.ngo-tools-newsletter-form');
      forms.forEach(function (form) {
        if (form.dataset.ngoToolsProcessed) {
          return;
        }
        form.dataset.ngoToolsProcessed = 'true';
        
        // You can add custom AJAX handling here if needed
        // For now, the form uses standard Drupal form submission
      });
    }
  };

})(Drupal);
