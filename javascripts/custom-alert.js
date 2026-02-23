/**
 * custom-alert.js - Custom Popup System to Replace JavaScript alert()
 * Provides professional, styled alerts with optional custom behavior
 */

(function($) {
    'use strict';

    // Custom Alert Manager
    window.CustomAlert = {
        
        // Initialize alert overlay once
        init: function() {
            if ($('#customAlertOverlay').length) return;
            
            const overlay = `
                <div id="customAlertOverlay" class="custom-alert-overlay"></div>
                <div id="customAlertModal" class="custom-alert-modal" role="dialog" aria-labelledby="alertTitle">
                    <div class="custom-alert-content">
                        <div class="custom-alert-header">
                            <h2 id="alertTitle" class="custom-alert-title">Alert</h2>
                            <button type="button" class="custom-alert-close" aria-label="Close">&times;</button>
                        </div>
                        <div class="custom-alert-body">
                            <p id="alertMessage">Message</p>
                        </div>
                        <div class="custom-alert-footer">
                            <button type="button" class="custom-alert-btn" id="alertOkBtn">OK</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(overlay);
            
            // Event handlers
            $('#customAlertOverlay, .custom-alert-close').click(function() {
                CustomAlert.close();
            });
            
            $('#alertOkBtn').click(function() {
                CustomAlert.close();
            });
            
            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#customAlertModal').is(':visible')) {
                    CustomAlert.close();
                }
            });
        },
        
        /**
         * Show error alert
         * @param {string} message - Error message to display
         * @param {function} callback - Optional callback function
         */
        error: function(message, callback) {
            this.init();
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-error');
            $('#alertTitle').text('Error');
            $('#alertMessage').html(message);
            $('#alertOkBtn').text('OK');
            
            this._show(callback);
            
            // Log error to console
            console.error('[CustomAlert]', message);
        },
        
        /**
         * Show success alert
         * @param {string} message - Success message to display
         * @param {function} callback - Optional callback function
         */
        success: function(message, callback) {
            this.init();
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-success');
            $('#alertTitle').text('Success');
            $('#alertMessage').html(message);
            $('#alertOkBtn').text('OK');
            
            this._show(callback);
        },
        
        /**
         * Show warning alert
         * @param {string} message - Warning message to display
         * @param {function} callback - Optional callback function
         */
        warning: function(message, callback) {
            this.init();
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-warning');
            $('#alertTitle').text('Warning');
            $('#alertMessage').html(message);
            $('#alertOkBtn').text('OK');
            
            this._show(callback);
        },
        
        /**
         * Show info alert
         * @param {string} message - Information message to display
         * @param {function} callback - Optional callback function
         */
        info: function(message, callback) {
            this.init();
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-info');
            $('#alertTitle').text('Information');
            $('#alertMessage').html(message);
            $('#alertOkBtn').text('OK');
            
            this._show(callback);
        },
        
        /**
         * Show database error alert with technical details
         * @param {string} message - User-friendly message
         * @param {string} technicalDetails - Technical error details
         * @param {function} callback - Optional callback function
         */
        databaseError: function(message, technicalDetails = '', callback) {
            this.init();
            
            let detailsHtml = message;
            if (technicalDetails && technicalDetails.trim()) {
                detailsHtml += '<hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">';
                detailsHtml += '<small style="color: #666; display: block; margin-top: 10px;">';
                detailsHtml += '<strong>Technical Details:</strong><br>';
                detailsHtml += this._escapeHtml(technicalDetails);
                detailsHtml += '</small>';
            }
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-error');
            $('#alertTitle').text('Database Error');
            $('#alertMessage').html(detailsHtml);
            $('#alertOkBtn').text('OK');
            
            this._show(callback);
            
            console.error('[DatabaseError]', message, technicalDetails);
        },
        
        /**
         * Show confirm dialog with Yes/No buttons
         * @param {string} message - Confirmation message
         * @param {function} onConfirm - Callback if user clicks Yes
         * @param {function} onCancel - Callback if user clicks No
         */
        confirm: function(message, onConfirm, onCancel) {
            this.init();
            
            $('#customAlertModal').attr('class', 'custom-alert-modal alert-confirm');
            $('#alertTitle').text('Confirm Action');
            $('#alertMessage').html(message);
            
            // Clear existing buttons
            const footer = $('#customAlertModal .custom-alert-footer');
            footer.html(`
                <button type="button" class="custom-alert-btn btn-cancel">Cancel</button>
                <button type="button" class="custom-alert-btn btn-confirm">Confirm</button>
            `);
            
            // Remove old handlers
            footer.off('click').on('click', '.btn-confirm', function() {
                CustomAlert.close();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            }).on('click', '.btn-cancel', function() {
                CustomAlert.close();
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            });
            
            // Show modal
            $('#customAlertOverlay').fadeIn(200);
            $('#customAlertModal').fadeIn(200);
            
            // Focus confirm button
            $('.btn-confirm').focus();
        },
        
        /**
         * Internal show method
         */
        _show: function(callback) {
            $('#customAlertOverlay').fadeIn(200);
            $('#customAlertModal').fadeIn(200);
            
            // One-time callback for OK button
            $('#alertOkBtn').off('click').on('click', function() {
                CustomAlert.close();
                if (typeof callback === 'function') {
                    callback();
                }
            });
            
            // Focus OK button
            $('#alertOkBtn').focus();
        },
        
        /**
         * Close the alert modal
         */
        close: function() {
            $('#customAlertOverlay').fadeOut(200);
            $('#customAlertModal').fadeOut(200);
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        _escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    /**
     * Replace native alert with custom alert
     * Maintains backward compatibility
     */
    window.alert = function(message) {
        CustomAlert.init();
        CustomAlert.info(message);
    };
    
    /**
     * jQuery plugin to show alert
     */
    $.alert = function(message, type = 'info', callback) {
        switch(type.toLowerCase()) {
            case 'error':
                CustomAlert.error(message, callback);
                break;
            case 'success':
                CustomAlert.success(message, callback);
                break;
            case 'warning':
                CustomAlert.warning(message, callback);
                break;
            case 'confirm':
                // For confirm, callback is onConfirm, additional arg is onCancel
                CustomAlert.confirm(message, callback, arguments[3]);
                break;
            default:
                CustomAlert.info(message, callback);
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        CustomAlert.init();
    });
    
})(jQuery);
