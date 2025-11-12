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
        },

        /**
         * Add to cart
         */
        addToCart: function() {
            $(document).on('click', '.add-to-cart, .add-to-cart-quick, .hs-add-to-cart-checkout, .hs-buy-button', function(e) {
                e.preventDefault();

                var $button = $(this);
                var productId = $button.data('product-id') || $button.closest('form').data('product-id');
                var quantity = 1;
                var redirectToCheckout = $button.hasClass('hs-add-to-cart-checkout');
                var originalButtonText = $button.find('.button-text').text() || $button.text();

                // If from product page form, get quantity
                if ($button.hasClass('add-to-cart')) {
                    var $form = $button.closest('form');
                    quantity = parseInt($form.find('input[name="quantity"]').val()) || 1;
                }

                // If from buy button wrapper with quantity input
                var $wrapper = $button.closest('.hs-buy-button-wrapper');
                if ($wrapper.length) {
                    var $quantityInput = $wrapper.find('.hs-quantity-input');
                    if ($quantityInput.length) {
                        quantity = parseInt($quantityInput.val()) || 1;
                    }
                }

                $button.prop('disabled', true);
                if ($button.find('.button-text').length) {
                    $button.find('.button-text').text(hubspotEcommerce.adding || 'Adding...');
                } else {
                    $button.text(hubspotEcommerce.adding || 'Adding...');
                }

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

                            // If redirect to checkout flag is set, redirect after adding
                            if (redirectToCheckout) {
                                if ($button.find('.button-text').length) {
                                    $button.find('.button-text').text('Redirecting...');
                                } else {
                                    $button.text('Redirecting...');
                                }
                                window.location.href = '/checkout/';
                            } else {
                                // Update button text
                                if ($button.find('.button-text').length) {
                                    $button.find('.button-text').text(hubspotEcommerce.added || 'Added!');
                                } else {
                                    $button.text(hubspotEcommerce.added || 'Added!');
                                }

                                setTimeout(function() {
                                    $button.prop('disabled', false);
                                    if ($button.find('.button-text').length) {
                                        $button.find('.button-text').text(originalButtonText);
                                    } else {
                                        $button.text(originalButtonText);
                                    }
                                }, 2000);
                            }
                        } else {
                            HubSpotEcommerce.showMessage($button, 'error', response.data.message || 'Error adding to cart');
                            $button.prop('disabled', false);
                            if ($button.find('.button-text').length) {
                                $button.find('.button-text').text(originalButtonText);
                            } else {
                                $button.text(originalButtonText);
                            }
                        }
                    },
                    error: function() {
                        HubSpotEcommerce.showMessage($button, 'error', 'Error adding to cart');
                        $button.prop('disabled', false);
                        if ($button.find('.button-text').length) {
                            $button.find('.button-text').text(originalButtonText);
                        } else {
                            $button.text(originalButtonText);
                        }
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
         * Update cart count in header and floating widget
         */
        updateCartCount: function(count) {
            if (typeof count !== 'undefined') {
                // Update inline badge (legacy)
                $('.hubspot-cart-count').text(count);

                // Update floating cart widget
                var $floatingCart = $('#hs-floating-cart');
                var $badge = $floatingCart.find('.hs-cart-count-badge');

                $badge.text(count);

                // Show/hide widget based on count
                if (count > 0) {
                    $floatingCart.removeClass('hidden');
                    // Add pulse animation when items are added
                    $floatingCart.addClass('pulse');
                    setTimeout(function() {
                        $floatingCart.removeClass('pulse');
                    }, 500);
                } else {
                    $floatingCart.addClass('hidden');
                }
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
            // Comprehensive currency formatting data
            var currencyData = {
                'USD': {symbol: '$', decimals: 2, position: 'before'},
                'EUR': {symbol: '€', decimals: 2, position: 'before'},
                'GBP': {symbol: '£', decimals: 2, position: 'before'},
                'JPY': {symbol: '¥', decimals: 0, position: 'before'},
                'AUD': {symbol: '$', decimals: 2, position: 'before'},
                'CAD': {symbol: '$', decimals: 2, position: 'before'},
                'CHF': {symbol: 'Fr', decimals: 2, position: 'before'},
                'CNY': {symbol: '¥', decimals: 2, position: 'before'},
                'SEK': {symbol: 'kr', decimals: 2, position: 'after'},
                'NZD': {symbol: '$', decimals: 2, position: 'before'},
                'MXN': {symbol: '$', decimals: 2, position: 'before'},
                'SGD': {symbol: '$', decimals: 2, position: 'before'},
                'HKD': {symbol: '$', decimals: 2, position: 'before'},
                'NOK': {symbol: 'kr', decimals: 2, position: 'after'},
                'KRW': {symbol: '₩', decimals: 0, position: 'before'},
                'TRY': {symbol: '₺', decimals: 2, position: 'before'},
                'RUB': {symbol: '₽', decimals: 2, position: 'after'},
                'INR': {symbol: '₹', decimals: 2, position: 'before'},
                'BRL': {symbol: 'R$', decimals: 2, position: 'before'},
                'ZAR': {symbol: 'R', decimals: 2, position: 'before'},
                'DKK': {symbol: 'kr', decimals: 2, position: 'after'},
                'PLN': {symbol: 'zł', decimals: 2, position: 'after'},
                'THB': {symbol: '฿', decimals: 2, position: 'before'},
                'IDR': {symbol: 'Rp', decimals: 0, position: 'before'},
                'HUF': {symbol: 'Ft', decimals: 0, position: 'after'},
                'CZK': {symbol: 'Kč', decimals: 2, position: 'after'},
                'ILS': {symbol: '₪', decimals: 2, position: 'before'},
                'CLP': {symbol: '$', decimals: 0, position: 'before'},
                'PHP': {symbol: '₱', decimals: 2, position: 'before'},
                'AED': {symbol: 'د.إ', decimals: 2, position: 'before'},
                'COP': {symbol: '$', decimals: 0, position: 'before'},
                'SAR': {symbol: '﷼', decimals: 2, position: 'before'},
                'MYR': {symbol: 'RM', decimals: 2, position: 'before'},
                'RON': {symbol: 'lei', decimals: 2, position: 'after'}
            };

            var currency = hubspotEcommerce.currency || 'USD';
            var data = currencyData[currency] || {symbol: currency + ' ', decimals: 2, position: 'before'};

            var formattedNumber = parseFloat(amount).toFixed(data.decimals);

            if (data.position === 'before') {
                return data.symbol + formattedNumber;
            } else {
                return formattedNumber + ' ' + data.symbol;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        HubSpotEcommerce.init();
    });

})(jQuery);
