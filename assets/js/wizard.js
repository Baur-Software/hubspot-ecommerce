/**
 * HubSpot Ecommerce Setup Wizard JavaScript
 */

(function($) {
    'use strict';

    var HubSpotWizard = {
        /**
         * Initialize
         */
        init: function() {
            this.checkCommerceHub();
            this.checkPaymentProcessor();
        },

        /**
         * Check Commerce Hub status
         */
        checkCommerceHub: function() {
            $('#check-commerce-hub').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var originalText = $button.text();

                $button.text('Checking...').prop('disabled', true);

                $.ajax({
                    url: hubspotWizard.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'check_commerce_hub_status',
                        nonce: hubspotWizard.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.enabled) {
                            // Commerce Hub is enabled - reload page
                            location.reload();
                        } else {
                            // Still not enabled
                            alert(response.data.message || 'Commerce Hub is not yet enabled. Please complete setup in HubSpot first.');
                            $button.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error checking Commerce Hub status. Please try again.');
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Check payment processor status
         */
        checkPaymentProcessor: function() {
            $('#check-payment-processor').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var originalText = $button.text();

                $button.text('Checking...').prop('disabled', true);

                $.ajax({
                    url: hubspotWizard.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'check_payment_processor_status',
                        nonce: hubspotWizard.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.configured) {
                            // Payment processor is configured - reload page
                            location.reload();
                        } else {
                            // Still not configured
                            alert(response.data.message || 'Payment processor is not yet configured. Please complete setup in HubSpot first.');
                            $button.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error checking payment processor status. Please try again.');
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        HubSpotWizard.init();
    });

})(jQuery);
