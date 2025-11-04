/**
 * HubSpot Ecommerce Frontend JavaScript
 */

(function($) {
    'use strict';

    var HubSpotEcommerce = {
        init: function() {
            this.addToCart();
            this.updateCart();
            this.removeFromCart();
            this.processCheckout();
            this.updateCartCount();
            this.handleDataDeletion();
        },

        /**
         * Add to cart
         */
        addToCart: function() {
            $(document).on('click', '.add-to-cart, .add-to-cart-quick', function(e) {
                e.preventDefault();

                var $button = $(this);
                var productId = $button.data('product-id') || $button.closest('form').data('product-id');
                var quantity = 1;

                // If from product page form, get quantity
                if ($button.hasClass('add-to-cart')) {
                    var $form = $button.closest('form');
                    quantity = parseInt($form.find('input[name="quantity"]').val()) || 1;
                }

                $button.prop('disabled', true).text(hubspotEcommerce.adding || 'Adding...');

                $.ajax({
                    url: hubspotEcommerce.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'hs_add_to_cart',
                        nonce: hubspotEcommerce.nonce,
                        product_id: productId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            HubSpotEcommerce.showMessage($button, 'success', response.data.message || 'Added to cart!');
                            HubSpotEcommerce.updateCartCount(response.data.cart_count);

                            // Update button text
                            $button.text(hubspotEcommerce.added || 'Added!');

                            setTimeout(function() {
                                $button.prop('disabled', false).text(hubspotEcommerce.add_to_cart || 'Add to Cart');
                            }, 2000);
                        } else {
                            HubSpotEcommerce.showMessage($button, 'error', response.data.message || 'Error adding to cart');
                            $button.prop('disabled', false).text(hubspotEcommerce.add_to_cart || 'Add to Cart');
                        }
                    },
                    error: function() {
                        HubSpotEcommerce.showMessage($button, 'error', 'Error adding to cart');
                        $button.prop('disabled', false).text(hubspotEcommerce.add_to_cart || 'Add to Cart');
                    }
                });
            });
        },

        /**
         * Update cart quantity
         */
        updateCart: function() {
            var updateTimeout;

            $(document).on('change', '.quantity-input', function() {
                var $input = $(this);
                var productId = $input.data('product-id');
                var quantity = parseInt($input.val()) || 0;

                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(function() {
                    $.ajax({
                        url: hubspotEcommerce.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'hs_update_cart',
                            nonce: hubspotEcommerce.nonce,
                            product_id: productId,
                            quantity: quantity
                        },
                        success: function(response) {
                            if (response.success) {
                                if (quantity === 0) {
                                    // Remove the row
                                    $input.closest('.cart-item').fadeOut(300, function() {
                                        $(this).remove();
                                        HubSpotEcommerce.updateCartTotals();
                                        HubSpotEcommerce.checkEmptyCart();
                                    });
                                } else {
                                    HubSpotEcommerce.updateCartTotals();
                                }
                                HubSpotEcommerce.updateCartCount(response.data.cart_count);
                            }
                        }
                    });
                }, 500);
            });
        },

        /**
         * Remove from cart
         */
        removeFromCart: function() {
            $(document).on('click', '.remove-item', function(e) {
                e.preventDefault();

                var $button = $(this);
                var productId = $button.data('product-id');

                if (!confirm(hubspotEcommerce.confirm_remove || 'Remove this item from cart?')) {
                    return;
                }

                $.ajax({
                    url: hubspotEcommerce.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'hs_remove_from_cart',
                        nonce: hubspotEcommerce.nonce,
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('.cart-item').fadeOut(300, function() {
                                $(this).remove();
                                HubSpotEcommerce.updateCartTotals();
                                HubSpotEcommerce.updateCartCount(response.data.cart_count);
                                HubSpotEcommerce.checkEmptyCart();
                            });
                        }
                    }
                });
            });
        },

        /**
         * Process checkout
         */
        processCheckout: function() {
            $('#checkout-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $button = $form.find('button[type="submit"]');
                var $message = $('.checkout-message');

                $button.prop('disabled', true).text(hubspotEcommerce.processing || 'Processing...');
                $message.html('');

                var formData = $form.serializeArray();
                formData.push(
                    { name: 'action', value: 'hs_process_checkout' },
                    { name: 'nonce', value: hubspotEcommerce.nonce }
                );

                $.ajax({
                    url: hubspotEcommerce.ajax_url,
                    type: 'POST',
                    data: $.param(formData),
                    success: function(response) {
                        if (response.success) {
                            $message.html('<div class="notice success">' + response.data.message + '</div>');

                            // Redirect to order confirmation
                            if (response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 1000);
                            }
                        } else {
                            $message.html('<div class="notice error">' + response.data.message + '</div>');
                            $button.prop('disabled', false).text(hubspotEcommerce.place_order || 'Place Order');
                        }
                    },
                    error: function() {
                        $message.html('<div class="notice error">An error occurred. Please try again.</div>');
                        $button.prop('disabled', false).text(hubspotEcommerce.place_order || 'Place Order');
                    }
                });
            });
        },

        /**
         * Update cart count in header
         */
        updateCartCount: function(count) {
            if (typeof count !== 'undefined') {
                $('.hubspot-cart-count').text(count);
            }
        },

        /**
         * Update cart totals on cart page
         */
        updateCartTotals: function() {
            $.ajax({
                url: hubspotEcommerce.ajax_url,
                type: 'POST',
                data: {
                    action: 'hs_get_cart',
                    nonce: hubspotEcommerce.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var total = HubSpotEcommerce.formatPrice(response.data.cart_total);
                        $('.cart-total-amount').text(total);
                    }
                }
            });
        },

        /**
         * Check if cart is empty and show message
         */
        checkEmptyCart: function() {
            if ($('.cart-item').length === 0) {
                location.reload();
            }
        },

        /**
         * Show message near button
         */
        showMessage: function($button, type, message) {
            var $container = $button.closest('form').find('.add-to-cart-message');
            if ($container.length === 0) {
                $container = $('<div class="add-to-cart-message"></div>');
                $button.after($container);
            }

            $container.removeClass('success error').addClass(type).html(message).fadeIn();

            setTimeout(function() {
                $container.fadeOut();
            }, 3000);
        },

        /**
         * Format price
         */
        formatPrice: function(amount) {
            var symbols = {
                'USD': '$',
                'EUR': '€',
                'GBP': '£',
                'JPY': '¥'
            };

            var currency = hubspotEcommerce.currency || 'USD';
            var symbol = symbols[currency] || currency + ' ';

            return symbol + parseFloat(amount).toFixed(2);
        },

        /**
         * Handle data deletion request
         */
        handleDataDeletion: function() {
            $('#request-data-deletion').on('click', function(e) {
                e.preventDefault();

                var confirmMessage = 'Are you sure you want to request deletion of all your personal data?\n\n' +
                                   'This action includes:\n' +
                                   '- Your account information\n' +
                                   '- Shopping cart data\n' +
                                   '- Email preferences\n\n' +
                                   'Note: Order records required by law will be retained with anonymized customer information.\n\n' +
                                   'You will receive a confirmation email to complete this request.';

                if (!confirm(confirmMessage)) {
                    return;
                }

                var $button = $(this);
                $button.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: hubspotEcommerce.rest_url + 'hubspot-ecommerce/v1/gdpr/delete-request',
                    type: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', hubspotEcommerce.rest_nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Deletion request sent!\n\nPlease check your email and follow the confirmation link to complete the deletion process.');
                            $button.text('Request Sent');
                        } else {
                            alert('Failed to send deletion request. Please try again or contact support.');
                            $button.prop('disabled', false).text('Request Data Deletion');
                        }
                    },
                    error: function(xhr) {
                        console.error('Deletion request error:', xhr);
                        alert('Failed to send deletion request. Please try again or contact support.');
                        $button.prop('disabled', false).text('Request Data Deletion');
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        HubSpotEcommerce.init();
    });

})(jQuery);
