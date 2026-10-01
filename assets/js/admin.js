/**
 * Elementor Component Sync Admin JavaScript
 */

(function($) {
	'use strict';

	const ECS_Admin = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Tab switching
			$(document).on('click', '.nav-tab', this.switchTab.bind(this));

			// Export type change
			$(document).on('change', '#ecs-export-type', this.handleExportTypeChange.bind(this));

			// Export buttons
			$(document).on('click', '#ecs-export-btn', this.handleSingleExport.bind(this));
			$(document).on('click', '#ecs-export-library-btn', this.handleLibraryExport.bind(this));

			// Import file selection
			$(document).on('change', '#ecs-import-file', this.handleFileSelect.bind(this));

			// Import button
			$(document).on('click', '#ecs-import-btn', this.handleImport.bind(this));
		},

		switchTab: function(e) {
			e.preventDefault();

			const $tab = $(e.currentTarget);
			const tabName = $tab.data('tab');

			// Remove active class from all tabs and contents
			$('.nav-tab').removeClass('nav-tab-active');
			$('.ecs-tab-content').removeClass('active');

			// Add active class to clicked tab and corresponding content
			$tab.addClass('nav-tab-active');
			$('#ecs-' + tabName).addClass('active');
		},

		handleExportTypeChange: function(e) {
			const exportType = $(e.target).val();

			if (exportType === 'single') {
				$('#ecs-single-export').show();
				$('#ecs-multiple-export').hide();
			} else {
				$('#ecs-single-export').hide();
				$('#ecs-multiple-export').show();
			}
		},

		handleSingleExport: function(e) {
			e.preventDefault();

			const componentId = $('#ecs-export-component').val();

			if (!componentId) {
				this.showMessage(
					'error',
					ecsData.strings.selectComponent,
					'#ecs-export-message'
				);
				return;
			}

			const $btn = $('#ecs-export-btn');
			$btn.prop('disabled', true);
			this.showLoader($btn);

			$.ajax({
				url: ecsData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'ecs_export_component',
					nonce: ecsData.nonce,
					component_id: componentId,
					export_type: 'component'
				},
				success: (response) => {
					if (response.success) {
						this.downloadJSON(
							response.data.data,
							response.data.filename
						);
						this.showMessage(
							'success',
							ecsData.strings.exportSuccess,
							'#ecs-export-message'
						);
					} else {
						this.showMessage(
							'error',
							response.data,
							'#ecs-export-message'
						);
					}
				},
				error: () => {
					this.showMessage(
						'error',
						ecsData.strings.error,
						'#ecs-export-message'
					);
				},
				complete: () => {
					$btn.prop('disabled', false);
					this.hideLoader($btn);
				}
			});
		},

		handleLibraryExport: function(e) {
			e.preventDefault();

			const componentIds = [];
			$('.ecs-component-checkbox:checked').each(function() {
				componentIds.push($(this).val());
			});

			if (componentIds.length === 0) {
				this.showMessage(
					'error',
					ecsData.strings.selectComponent,
					'#ecs-export-message'
				);
				return;
			}

			const $btn = $('#ecs-export-library-btn');
			$btn.prop('disabled', true);
			this.showLoader($btn);

			$.ajax({
				url: ecsData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'ecs_export_component',
					nonce: ecsData.nonce,
					component_ids: componentIds,
					export_type: 'library'
				},
				success: (response) => {
					if (response.success) {
						this.downloadJSON(
							response.data.data,
							response.data.filename
						);
						this.showMessage(
							'success',
							ecsData.strings.exportSuccess,
							'#ecs-export-message'
						);
					} else {
						this.showMessage(
							'error',
							response.data,
							'#ecs-export-message'
						);
					}
				},
				error: () => {
					this.showMessage(
						'error',
						ecsData.strings.error,
						'#ecs-export-message'
					);
				},
				complete: () => {
					$btn.prop('disabled', false);
					this.hideLoader($btn);
				}
			});
		},

		handleFileSelect: function(e) {
			const files = e.target.files;

			if (!files || files.length === 0) {
				$('#ecs-import-preview').hide();
				return;
			}

			const file = files[0];

			if (file.type !== 'application/json') {
				this.showMessage(
					'error',
					'Please select a valid JSON file',
					'#ecs-import-message'
				);
				return;
			}

			// Preview the file
			const reader = new FileReader();
			reader.onload = (event) => {
				try {
					const data = JSON.parse(event.target.result);
					this.showImportPreview(data);
				} catch (e) {
					this.showMessage(
						'error',
						'Invalid JSON file',
						'#ecs-import-message'
					);
				}
			};
			reader.readAsText(file);
		},

		showImportPreview: function(data) {
			const previewContent = $('#ecs-preview-content');
			let html = '';

			if (data.export_type === 'library') {
				html += '<div class="preview-item">';
				html += '<span class="preview-title">Library with ' + data.total + ' component(s)</span>';
				html += '</div>';

				if (data.components && data.components.length > 0) {
					data.components.forEach((component, index) => {
						const title = component.post_data && component.post_data.post_title ? component.post_data.post_title : 'Untitled';
						html += '<div class="preview-item">';
						html += '<span class="preview-title">' + (index + 1) + '. ' + this.escapeHtml(title) + '</span>';
						html += '</div>';
					});
				}
			} else {
				const title = data.post_data && data.post_data.post_title ? data.post_data.post_title : 'Untitled';
				html += '<div class="preview-item">';
				html += '<span class="preview-title">' + this.escapeHtml(title) + '</span>';
				html += '<span class="preview-type">Exported on: ' + data.exported_at + '</span>';
				html += '</div>';
			}

			previewContent.html(html);
			$('#ecs-import-preview').show();
		},

		handleImport: function(e) {
			e.preventDefault();

			const fileInput = $('#ecs-import-file')[0];
			const files = fileInput.files;

			if (!files || files.length === 0) {
				this.showMessage(
					'error',
					ecsData.strings.selectFile,
					'#ecs-import-message'
				);
				return;
			}

			const formData = new FormData();
			formData.append('action', 'ecs_import_component');
			formData.append('nonce', ecsData.nonce);
			formData.append('component_file', files[0]);

			const $btn = $('#ecs-import-btn');
			$btn.prop('disabled', true);
			this.showLoader($btn, ecsData.strings.importing);

			$.ajax({
				url: ecsData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				dataType: 'json',
				success: (response) => {
					if (response.success) {
						const message = '<strong>' + ecsData.strings.importSuccess + '</strong><br>' + response.data.message;
						this.showMessage(
							'success',
							message,
							'#ecs-import-message'
						);

						// Clear file input
						fileInput.value = '';
						$('#ecs-import-preview').hide();
					} else {
						this.showMessage(
							'error',
							response.data,
							'#ecs-import-message'
						);
					}
				},
				error: () => {
					this.showMessage(
						'error',
						ecsData.strings.error,
						'#ecs-import-message'
					);
				},
				complete: () => {
					$btn.prop('disabled', false);
					this.hideLoader($btn);
				}
			});
		},

		downloadJSON: function(data, filename) {
			const jsonString = JSON.stringify(data, null, 2);
			const blob = new Blob([jsonString], { type: 'application/json' });
			const url = URL.createObjectURL(blob);
			const link = document.createElement('a');

			link.href = url;
			link.download = filename;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(url);
		},

		showMessage: function(type, message, containerSelector) {
			const $container = $(containerSelector);
			$container
				.removeClass('success error info')
				.addClass(type)
				.html(message)
				.show();

			// Auto-hide success messages after 5 seconds
			if (type === 'success') {
				setTimeout(() => {
					$container.fadeOut(500);
				}, 5000);
			}
		},

		showLoader: function($element, text) {
			const originalText = $element.text();
			const loaderText = text || $element.data('loading-text') || $element.text();

			$element.data('original-text', originalText);
			$element.html(
				'<span class="ecs-loading"></span>' + loaderText
			);
		},

		hideLoader: function($element) {
			const originalText = $element.data('original-text') || $element.text();
			$element.text(originalText);
		},

		escapeHtml: function(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, (m) => map[m]);
		}
	};

	$(document).ready(function() {
		ECS_Admin.init();
	});

})(jQuery);
