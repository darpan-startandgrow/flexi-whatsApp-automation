/**
 * Flexi WhatsApp Automation – Chat Widget Script
 *
 * Handles the floating chat widget toggle, display delay, and analytics.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var widget = document.getElementById('fwa-chat-widget');
		if (!widget) {
			return;
		}

		var toggle = widget.querySelector('.fwa-chat-toggle');
		var panel = widget.querySelector('.fwa-chat-panel');
		var closeBtn = widget.querySelector('.fwa-chat-close');
		var delay = parseInt(widget.getAttribute('data-delay'), 10) || 0;
		var isOpen = false;

		// Show widget after delay.
		if (delay > 0) {
			setTimeout(function () {
				widget.style.display = 'block';
			}, delay * 1000);
		} else {
			widget.style.display = 'block';
		}

		// Toggle panel.
		if (toggle) {
			toggle.addEventListener('click', function () {
				isOpen = !isOpen;
				panel.style.display = isOpen ? 'block' : 'none';
			});
		}

		// Close button.
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				isOpen = false;
				panel.style.display = 'none';
			});
		}

		// Close on outside click.
		document.addEventListener('click', function (e) {
			if (isOpen && !widget.contains(e.target)) {
				isOpen = false;
				panel.style.display = 'none';
			}
		});

		// Analytics tracking (if enabled).
		var agentLinks = widget.querySelectorAll('[data-fwa-track="chat-click"]');
		agentLinks.forEach(function (link) {
			link.addEventListener('click', function () {
				var agentName = this.getAttribute('data-fwa-agent') || 'unknown';
				// Fire a custom event for analytics integration.
				if (typeof window.CustomEvent === 'function') {
					window.dispatchEvent(new CustomEvent('fwa_chat_click', {
						detail: { agent: agentName }
					}));
				}
				// Google Analytics integration (if available).
				if (typeof gtag === 'function') {
					gtag('event', 'fwa_chat_click', {
						event_category: 'WhatsApp Chat',
						event_label: agentName
					});
				}
			});
		});
	});
})();
