(function($) {
    // ElegantToastr Plugin
    $.elegantToastr = (function() {
        // Default options
        const defaults = {
            position: 'bottom-right',
            duration: 5000,
            closeButton: true,
            progressBar: true,
            escapeHtml: true,
            newestOnTop: true,
            preventDuplicates: false,
            showAnimation: 'fadeIn', // fadeIn, slideIn, bounceIn
            hideAnimation: 'fadeOut', // fadeOut, slideOut, bounceOut
            onShow: null,
            onHide: null,
            onClick: null
        };

        // Create container if not exists
        function getContainer(options) {
            const position = options.position;
            let $container = $(`.toast-container.${position}`);
            
            if (!$container.length) {
                $container = $('<div></div>')
                    .addClass(`toast-container ${position}`)
                    .appendTo('body');
            }
            
            return $container;
        }

        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Get icon based on toast type
        function getIcon(type) {
            const icons = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
                error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
                info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>'
            };
            
            return `<div class="toast-icon icon-${type}">${icons[type]}</div>`;
        }

        // Create toast element
        function createToast(type, title, message, options) {
            const $toast = $('<div></div>').addClass(`toast toast-${type}`);
            
            // Add icon
            $toast.append(getIcon(type));
            
            // Add content
            const $content = $('<div></div>').addClass('toast-content');
            
            if (title) {
                const titleHtml = options.escapeHtml ? escapeHtml(title) : title;
                $content.append(`<div class="toast-title">${titleHtml}</div>`);
            }
            
            if (message) {
                const messageHtml = options.escapeHtml ? escapeHtml(message) : message;
                $content.append(`<div class="toast-message">${messageHtml}</div>`);
            }
            
            $toast.append($content);
            
            // Add close button
            if (options.closeButton) {
                const $closeButton = $('<div></div>')
                    .addClass('toast-close')
                    .on('click', function(e) {
                        e.stopPropagation();
                        hideToast($toast, options);
                    });
                
                $toast.append($closeButton);
            }
            
            // Add progress bar
            if (options.progressBar) {
                const $progress = $('<div></div>').addClass('toast-progress');
                const $progressBar = $('<div></div>').addClass('toast-progress-bar');
                
                $progress.append($progressBar);
                $toast.append($progress);
                
                // Animate progress bar
                $progressBar.css({
                    'animation': `progress ${options.duration / 1000}s linear forwards`
                });
            }
            
            // Click event
            if (typeof options.onClick === 'function') {
                $toast.on('click', function() {
                    options.onClick.call($toast);
                });
            }
            
            return $toast;
        }

        // Show toast
        function showToast($toast, options) {
            // Add to container
            const $container = getContainer(options);
            
            if (options.newestOnTop) {
                $container.prepend($toast);
            } else {
                $container.append($toast);
            }
            
            // Show animation
            setTimeout(function() {
                $toast.addClass('show');
                
                // Pulse animation for success
                if ($toast.hasClass('toast-success')) {
                    $toast.css('animation', 'pulse 0.5s');
                }
                
                if (typeof options.onShow === 'function') {
                    options.onShow.call($toast);
                }
            }, 10);
            
            // Auto hide
            if (options.duration > 0) {
                $toast.data('timeoutId', setTimeout(function() {
                    hideToast($toast, options);
                }, options.duration));
            }
            
            return $toast;
        }

        // Hide toast
        function hideToast($toast, options) {
            clearTimeout($toast.data('timeoutId'));
            
            $toast.removeClass('show');
            
            setTimeout(function() {
                $toast.remove();
                
                if (typeof options.onHide === 'function') {
                    options.onHide.call($toast);
                }
            }, 300);
        }

        // Public methods
        return {
            success: function(title, message, options) {
                const settings = $.extend({}, defaults, options);
                const $toast = createToast('success', title, message, settings);
                return showToast($toast, settings);
            },
            error: function(title, message, options) {
                const settings = $.extend({}, defaults, options);
                const $toast = createToast('error', title, message, settings);
                return showToast($toast, settings);
            },
            info: function(title, message, options) {
                const settings = $.extend({}, defaults, options);
                const $toast = createToast('info', title, message, settings);
                return showToast($toast, settings);
            },
            warning: function(title, message, options) {
                const settings = $.extend({}, defaults, options);
                const $toast = createToast('warning', title, message, settings);
                return showToast($toast, settings);
            },
            clear: function(position) {
                if (position) {
                    $(`.toast-container.${position}`).remove();
                } else {
                    $('.toast-container').remove();
                }
            },
            options: function(options) {
                $.extend(defaults, options);
            }
        };
    })();
})(jQuery);