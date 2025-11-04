/**
 * Privacy Tools Admin Interface JavaScript
 *
 * @package HubSpot_Ecommerce
 */

(function($) {
    'use strict';

    const PrivacyTools = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.maybeRefreshStats();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Manual cleanup buttons
            $(document).on('click', '[data-cleanup-type]', this.handleManualCleanup.bind(this));

            // Export compliance report
            $('#hs-export-compliance-report').on('click', this.handleExportReport.bind(this));

            // Refresh stats button (if exists)
            $('#hs-refresh-stats').on('click', this.handleRefreshStats.bind(this));
        },

        /**
         * Handle manual cleanup
         */
        handleManualCleanup: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const cleanupType = $button.data('cleanup-type');

            if (!confirm(hubspotPrivacyTools.strings.confirm_cleanup)) {
                return;
            }

            this.runCleanup($button, cleanupType);
        },

        /**
         * Run cleanup task
         */
        runCleanup: function($button, cleanupType) {
            const $row = $button.closest('tr');

            // Show loading state
            $button.prop('disabled', true).text('Running...');
            $row.addClass('hs-loading');

            $.ajax({
                url: hubspotPrivacyTools.ajax_url,
                type: 'POST',
                data: {
                    action: 'hs_run_manual_cleanup',
                    nonce: hubspotPrivacyTools.nonce,
                    cleanup_type: cleanupType
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', hubspotPrivacyTools.strings.cleanup_success);
                        this.displayCleanupResults(response.data.results);
                        this.refreshStats();
                    } else {
                        this.showNotice('error', response.data.message || hubspotPrivacyTools.strings.cleanup_error);
                    }
                },
                error: (xhr) => {
                    this.showNotice('error', hubspotPrivacyTools.strings.cleanup_error);
                    console.error('Cleanup error:', xhr);
                },
                complete: () => {
                    // Reset button state
                    $button.prop('disabled', false).text('Run Now');
                    $row.removeClass('hs-loading');
                }
            });
        },

        /**
         * Display cleanup results
         */
        displayCleanupResults: function(results) {
            const $resultsDiv = $('<div class="hs-cleanup-results"></div>');
            const $resultsList = $('<ul></ul>');

            $.each(results, function(task, result) {
                const taskName = task.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                let message = taskName + ': ';

                if (result.deleted !== undefined) {
                    message += result.deleted + ' records deleted';
                } else if (result.archived !== undefined) {
                    message += result.archived + ' records archived';
                } else if (result.message) {
                    message += result.message;
                } else {
                    message += 'Completed';
                }

                $resultsList.append($('<li></li>').text(message));
            });

            $resultsDiv.append('<h3>Cleanup Results</h3>').append($resultsList);

            // Insert results after the cleanup section
            $('.hs-privacy-section').first().after($resultsDiv);

            // Auto-remove after 10 seconds
            setTimeout(() => {
                $resultsDiv.fadeOut(() => $resultsDiv.remove());
            }, 10000);
        },

        /**
         * Handle export report
         */
        handleExportReport: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);

            $button.prop('disabled', true).text('Generating...');

            $.ajax({
                url: hubspotPrivacyTools.ajax_url,
                type: 'POST',
                data: {
                    action: 'hs_export_compliance_report',
                    nonce: hubspotPrivacyTools.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', 'Report generated successfully');
                        this.downloadReport(response.data.report);
                    } else {
                        this.showNotice('error', 'Failed to generate report');
                    }
                },
                error: (xhr) => {
                    this.showNotice('error', 'Failed to generate report');
                    console.error('Export error:', xhr);
                },
                complete: () => {
                    $button.prop('disabled', false).text('Export Compliance Report');
                }
            });
        },

        /**
         * Download report as JSON
         */
        downloadReport: function(report) {
            const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(report, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute('href', dataStr);
            downloadAnchor.setAttribute('download', 'compliance-report-' + this.getDateString() + '.json');
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        },

        /**
         * Get current date string
         */
        getDateString: function() {
            const now = new Date();
            return now.getFullYear() + '-' +
                   String(now.getMonth() + 1).padStart(2, '0') + '-' +
                   String(now.getDate()).padStart(2, '0');
        },

        /**
         * Handle refresh stats
         */
        handleRefreshStats: function(e) {
            e.preventDefault();
            this.refreshStats();
        },

        /**
         * Refresh statistics
         */
        refreshStats: function() {
            $.ajax({
                url: hubspotPrivacyTools.ajax_url,
                type: 'POST',
                data: {
                    action: 'hs_get_retention_stats',
                    nonce: hubspotPrivacyTools.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatsDisplay(response.data);
                    }
                },
                error: (xhr) => {
                    console.error('Stats refresh error:', xhr);
                }
            });
        },

        /**
         * Update stats display
         */
        updateStatsDisplay: function(stats) {
            // Update cart sessions
            if (stats.cart_sessions) {
                $('.hs-stat-card').eq(0).find('.hs-stat-number').text(stats.cart_sessions.total);
                $('.hs-stat-card').eq(0).find('.hs-stat-detail').text(
                    stats.cart_sessions.expiring_soon + ' expiring within 7 days'
                );
            }

            // Update orders
            if (stats.orders) {
                $('.hs-stat-card').eq(1).find('.hs-stat-number').text(stats.orders.total);
            }

            // Add visual feedback
            $('.hs-stats-grid').addClass('hs-stats-updated');
            setTimeout(() => {
                $('.hs-stats-grid').removeClass('hs-stats-updated');
            }, 1000);
        },

        /**
         * Maybe refresh stats on page load
         */
        maybeRefreshStats: function() {
            // Refresh stats every 5 minutes if page is active
            setInterval(() => {
                if (!document.hidden) {
                    this.refreshStats();
                }
            }, 5 * 60 * 1000);
        },

        /**
         * Show notice message
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="hs-notice hs-notice-' + type + '">' + message + '</div>');

            $('.hubspot-privacy-tools').prepend($notice);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.hubspot-privacy-tools').length) {
            PrivacyTools.init();
        }
    });

    // Add animation for stats update
    const style = document.createElement('style');
    style.textContent = `
        .hs-stats-updated .hs-stat-card {
            animation: hs-pulse 0.5s ease;
        }

        @keyframes hs-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    `;
    document.head.appendChild(style);

})(jQuery);
