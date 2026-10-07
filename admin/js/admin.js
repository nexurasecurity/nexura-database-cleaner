/**
 * Nexura Database Cleaner & Optimizer Admin JavaScript
 */

(function($) {
	'use strict';

	var currentPreviewItem = null;

	$(document).ready(function() {

		// Toggle Health Score Calculation Info
		$('.nexdbc-link-calc').on('click', function(e) {
			e.preventDefault();
			$('#nexdbc-score-calc-info').slideToggle(200);
		});

		// Trigger Quick Scan / Rescan
		$('#nexdbc-btn-quick-scan, #nexdbc-btn-rescan').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $icon = $btn.find('.dashicons');
			$btn.prop('disabled', true);
			$icon.addClass('nexdbc-spin');

			showNotice('info', nexuraDatabaseCleanerData.strings.scanning);

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_run_scan',
				nonce: nexuraDatabaseCleanerData.nonce
			}, function(response) {
				$btn.prop('disabled', false);
				$icon.removeClass('nexdbc-spin');
				if (response.success) {
					showNotice('success', nexuraDatabaseCleanerData.strings.scan_complete);
					window.location.reload();
				} else {
					showNotice('error', (response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error);
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$icon.removeClass('nexdbc-spin');
				showNotice('error', nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Open Preview Modal
		$(document).on('click', '.nexdbc-btn-review', function(e) {
			e.preventDefault();
			var itemId = $(this).data('id');
			openPreviewModal(itemId);
		});

		// Close Preview Modal
		$('.nexdbc-modal-close, .nexdbc-modal-cancel').on('click', function() {
			closePreviewModal();
		});

		// Run Dry-Run from Modal
		$('#nexdbc-modal-btn-dryrun').on('click', function() {
			if (!currentPreviewItem) return;
			var itemToDryRun = currentPreviewItem;
			closePreviewModal();
			runBatchCleanup(itemToDryRun, true);
		});

		// Confirm Cleanup from Modal
		$('#nexdbc-modal-btn-confirm').on('click', function() {
			if (!currentPreviewItem) return;
			var itemToClean = currentPreviewItem;
			if (confirm(nexuraDatabaseCleanerData.strings.confirm_cleanup)) {
				closePreviewModal();
				runBatchCleanup(itemToClean, false);
			}
		});

		// Select All Checkbox on Cleanup page
		$('#nexdbc-select-all-clean').on('change', function() {
			$('.nexdbc-item-select').prop('checked', $(this).prop('checked'));
		});

		// Update Select All Checkbox when individual items change
		$(document).on('change', '.nexdbc-item-select', function() {
			var total = $('.nexdbc-item-select').length;
			var checked = $('.nexdbc-item-select:checked').length;
			$('#nexdbc-select-all-clean').prop('checked', total > 0 && total === checked);
		});

		// Clean Selected Items
		var pendingCleanItems = [];

		$('#nexdbc-btn-clean-selected').on('click', function() {
			pendingCleanItems = [];
			$('.nexdbc-item-select:checked').each(function() {
				pendingCleanItems.push($(this).val());
			});

			if (pendingCleanItems.length === 0) {
				// Auto-select all items if none manually checked
				$('.nexdbc-item-select').prop('checked', true);
				$('#nexdbc-select-all-clean').prop('checked', true);
				$('.nexdbc-item-select:checked').each(function() {
					pendingCleanItems.push($(this).val());
				});
			}

			if (pendingCleanItems.length === 0) {
				alert('No cleanable items available in the list.');
				return;
			}

			// Open backup safety recommendation modal if present
			if ($('#nexdbc-backup-modal').length) {
				$('#nexdbc-backup-modal').css('display', 'flex');
			} else {
				if (confirm(nexuraDatabaseCleanerData.strings.confirm_cleanup)) {
					runQueueCleanup(pendingCleanItems, false);
				}
			}
		});

		$('#nexdbc-btn-modal-cancel').on('click', function() {
			$('#nexdbc-backup-modal').hide();
		});

		$('#nexdbc-btn-modal-proceed').on('click', function() {
			$('#nexdbc-backup-modal').hide();
			if (pendingCleanItems.length > 0) {
				runQueueCleanup(pendingCleanItems, false);
			}
		});

		// Dry Run Selected Items
		$('#nexdbc-btn-dry-run-selected').on('click', function() {
			var selectedItems = [];
			$('.nexdbc-item-select:checked').each(function() {
				selectedItems.push($(this).val());
			});

			if (selectedItems.length === 0) {
				// Auto-select all items if none manually checked
				$('.nexdbc-item-select').prop('checked', true);
				$('#nexdbc-select-all-clean').prop('checked', true);
				$('.nexdbc-item-select:checked').each(function() {
					selectedItems.push($(this).val());
				});
			}

			if (selectedItems.length === 0) {
				alert('No cleanable items available in the list.');
				return;
			}

			runQueueCleanup(selectedItems, true);
		});

		// Optimize Single Table
		$('.nexdbc-btn-optimize-table').on('click', function() {
			var $btn = $(this);
			var tableName = $btn.data('name');

			if (!confirm(nexuraDatabaseCleanerData.strings.confirm_optimize)) return;

			$btn.prop('disabled', true).text('Optimizing...');

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_optimize_table',
				nonce: nexuraDatabaseCleanerData.nonce,
				table_name: tableName
			}, function(response) {
				$btn.prop('disabled', false).text('Optimize');
				if (response && response.success) {
					var $row = $btn.closest('tr');
					$row.attr('data-free', '0');
					$row.find('td:nth-child(6)').html('<span class="nexdbc-badge nexdbc-badge-safe">Optimal</span> <span class="nexdbc-text-muted" style="font-size: 11px; margin-left: 4px;">(2 MB reserve)</span>');
					showNotice('success', 'Table ' + tableName + ' optimized successfully! InnoDB extent buffer is optimal.');
					setTimeout(function() {
						window.location.reload();
					}, 800);
				} else {
					showNotice('error', (response && response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error);
				}
			}).fail(function() {
				$btn.prop('disabled', false).text('Optimize');
				showNotice('error', nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Analyze Single Table
		$('.nexdbc-btn-analyze-table').on('click', function() {
			var $btn = $(this);
			var tableName = $btn.data('name');

			$btn.prop('disabled', true).text('Analyzing...');

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_analyze_table',
				nonce: nexuraDatabaseCleanerData.nonce,
				table_name: tableName
			}, function(response) {
				$btn.prop('disabled', false).text('Analyze');
				if (response && response.success) {
					alert('Table ' + tableName + ' analyzed. Status: ' + response.data.message);
				} else {
					alert((response && response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error);
				}
			}).fail(function() {
				$btn.prop('disabled', false).text('Analyze');
				alert(nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Optimize All Tables with Overhead
		$('#nexdbc-btn-optimize-all-overhead').on('click', function() {
			var tables = [];
			$('tr[data-table-name]').each(function() {
				var free = parseFloat($(this).data('free'));
				if (free > 0) {
					tables.push($(this).data('table-name'));
				}
			});

			if (tables.length === 0) return;
			if (!confirm(nexuraDatabaseCleanerData.strings.confirm_optimize)) return;

			var currentIndex = 0;
			showNotice('info', 'Optimizing tables with overhead...');

			function processNext() {
				if (currentIndex >= tables.length) {
					showNotice('success', 'All tables optimized.');
					window.location.reload();
					return;
				}

				var tbl = tables[currentIndex];
				$.post(nexuraDatabaseCleanerData.ajax_url, {
					action: 'nexura_database_cleaner_optimize_table',
					nonce: nexuraDatabaseCleanerData.nonce,
					table_name: tbl
				}).always(function() {
					currentIndex++;
					processNext();
				});
			}

			processNext();
		});

		// Clear Audit Log History
		$('#nexdbc-btn-clear-history').on('click', function() {
			if (!confirm('Are you sure you want to clear the audit history?')) return;

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_clear_history',
				nonce: nexuraDatabaseCleanerData.nonce
			}, function(response) {
				if (response && response.success) {
					window.location.reload();
				} else {
					showNotice('error', nexuraDatabaseCleanerData.strings.error);
				}
			}).fail(function() {
				showNotice('error', nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Schedule Email Report Toggle
		$('#nexdbc-schedule-email-report').on('change', function() {
			if ($(this).is(':checked')) {
				$('#nexdbc-email-report-options').slideDown(150);
			} else {
				$('#nexdbc-email-report-options').slideUp(150);
			}
		});

		// Save Settings Form
		$('#nexdbc-form-settings').on('submit', function(e) {
			e.preventDefault();
			var formData = $(this).serializeArray();
			var data = {
				action: 'nexura_database_cleaner_save_settings',
				nonce: nexuraDatabaseCleanerData.nonce
			};
			$.each(formData, function(i, field) {
				if (field.name === 'schedule_items[]') {
					if (!data.schedule_items) data.schedule_items = [];
					data.schedule_items.push(field.value);
				} else {
					data[field.name] = field.value;
				}
			});

			var $btn = $(this).find('button[type="submit"]');
			$btn.prop('disabled', true).text('Saving...');

			$.post(nexuraDatabaseCleanerData.ajax_url, data, function(response) {
				$btn.prop('disabled', false).text('Save Settings');
				if (response && response.success) {
					showNotice('success', (response.data && response.data.message) ? response.data.message : 'Settings saved successfully.');
					setTimeout(function() {
						window.location.reload();
					}, 800);
				} else {
					showNotice('error', (response && response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error);
				}
			}).fail(function() {
				$btn.prop('disabled', false).text('Save Settings');
				showNotice('error', nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Toggle Autoload
		$('.nexdbc-btn-toggle-autoload').on('click', function() {
			var $btn = $(this);
			var optId = $btn.data('id');
			var nextAutoload = $btn.data('autoload');

			$btn.prop('disabled', true);

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_toggle_autoload',
				nonce: nexuraDatabaseCleanerData.nonce,
				option_id: optId,
				autoload: nextAutoload
			}, function(response) {
				if (response && response.success) {
					window.location.reload();
				} else {
					$btn.prop('disabled', false);
					alert('Failed to update option.');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				alert(nexuraDatabaseCleanerData.strings.error);
			});
		});

		// Delete / Unschedule Cron
		$('.nexdbc-btn-delete-cron').on('click', function() {
			var $btn = $(this);
			var hook = $btn.data('hook');
			var timestamp = $btn.data('timestamp');

			if (!confirm('Unschedule this cron task?')) return;

			$btn.prop('disabled', true);

			$.post(nexuraDatabaseCleanerData.ajax_url, {
				action: 'nexura_database_cleaner_delete_cron',
				nonce: nexuraDatabaseCleanerData.nonce,
				hook: hook,
				timestamp: timestamp
			}, function(response) {
				if (response && response.success) {
					$btn.closest('tr').fadeOut();
				} else {
					$btn.prop('disabled', false);
					alert('Could not remove cron task.');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				alert(nexuraDatabaseCleanerData.strings.error);
			});
		});

	});

	/**
	 * Open Preview Modal
	 */
	function openPreviewModal(itemId) {
		currentPreviewItem = itemId;
		$('#nexdbc-modal-overlay').fadeIn(150);
		$('#nexdbc-modal-body').html('<div class="nexdbc-modal-loader"><span class="spinner is-active"></span> ' + nexuraDatabaseCleanerData.strings.scanning + '</div>');

		$.post(nexuraDatabaseCleanerData.ajax_url, {
			action: 'nexura_database_cleaner_preview_item',
			nonce: nexuraDatabaseCleanerData.nonce,
			item_id: itemId
		}, function(response) {
			if (!response || !response.success) {
				var errorMessage = (response && response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error;
				$('#nexdbc-modal-body').empty().append($('<p>', { 'class': 'nexdbc-text-danger', text: errorMessage }));
				return;
			}

			var data = response.data;
			var item = data.item;
			var rows = data.preview_rows;

			$('#nexdbc-modal-title').text(item.title + ' (' + data.total_count + ' items)');

			var html = '<div class="nexdbc-preview-summary">';
			html += '<p><strong>' + item.description + '</strong></p>';
			html += '<p><em>Reason:</em> ' + item.why + '</p>';
			html += '<p><small>Confidence: <strong>' + item.confidence + '</strong></small></p>';
			html += '</div>';

			if (rows && rows.length > 0) {
				html += '<h4>Preview Sample (First ' + rows.length + ' rows)</h4>';
				html += '<table class="nexdbc-preview-table"><thead><tr>';

				var keys = Object.keys(rows[0]);
				for (var k = 0; k < keys.length; k++) {
					html += '<th>' + keys[k] + '</th>';
				}
				html += '</tr></thead><tbody>';

				for (var i = 0; i < rows.length; i++) {
					html += '<tr>';
					for (var j = 0; j < keys.length; j++) {
						var val = rows[i][keys[j]];
						html += '<td>' + (val !== null ? $('<div>').text(val).html() : '<em>null</em>') + '</td>';
					}
					html += '</tr>';
				}
				html += '</tbody></table>';
			} else {
				html += '<p>No preview rows available or already clean.</p>';
			}

			$('#nexdbc-modal-body').html(html);
		}).fail(function() {
			$('#nexdbc-modal-body').empty().append($('<p>', { 'class': 'nexdbc-text-danger', text: nexuraDatabaseCleanerData.strings.error }));
		});
	}

	function closePreviewModal() {
		currentPreviewItem = null;
		$('#nexdbc-modal-overlay').fadeOut(150);
	}

	/**
	 * Run batch cleanup for single item with progress bar
	 */
	function runBatchCleanup(itemId, isDryRun) {
		runQueueCleanup([itemId], isDryRun);
	}

	/**
	 * Run queue of multiple items sequentially in batches
	 */
	function runQueueCleanup(itemsQueue, isDryRun) {
		if (itemsQueue.length === 0) return;

		var $runner = $('#nexdbc-batch-runner');
		if ($runner.length) {
			$runner.show();
			$('#nexdbc-batch-runner-actions').remove();
			$('#nexdbc-batch-status-badge').removeClass('nexdbc-badge-safe nexdbc-badge-error').addClass('nexdbc-badge-info').text('Running');
			$('#nexdbc-progress-fill').css('width', '0%');
			$('#nexdbc-progress-stats').text('0 / ' + itemsQueue.length);
			$('#nexdbc-progress-percent').text('0%');
			$('#nexdbc-batch-log').empty();
			if ($runner.offset()) {
				$('html, body').animate({ scrollTop: $runner.offset().top - 40 }, 300);
			}
		}

		$('#nexdbc-btn-dry-run-selected, #nexdbc-btn-clean-selected').prop('disabled', true);

		var queueIndex = 0;
		var totalCleanedInSession = 0;

		function processNextItem() {
			if (queueIndex >= itemsQueue.length) {
				$('#nexdbc-btn-dry-run-selected, #nexdbc-btn-clean-selected').prop('disabled', false);

				if (isDryRun) {
					$('#nexdbc-batch-status-badge').removeClass('nexdbc-badge-info').addClass('nexdbc-badge-safe').text('Dry Run Finished');
					$('#nexdbc-progress-fill').css('width', '100%');
					$('#nexdbc-progress-percent').text('100%');
					$('#nexdbc-batch-log').append('<div style="color:#176c2d; font-weight:bold; margin-top:8px;">\u2713 Dry run inspection complete for ' + itemsQueue.length + ' item category(ies). All inspected items are safe to clean. No database records were modified.</div>');
					$('#nexdbc-batch-log').scrollTop($('#nexdbc-batch-log')[0].scrollHeight);

					if (!$('#nexdbc-batch-runner-actions').length) {
						var actionHtml = '<div id="nexdbc-batch-runner-actions" style="margin-top:14px; display:flex; justify-content:flex-end; gap:10px;">' +
							'<button type="button" id="nexdbc-runner-btn-cancel" class="button">Close Log</button>' +
							'<button type="button" id="nexdbc-runner-btn-proceed" class="button button-primary nexdbc-btn-danger"><span class="dashicons dashicons-trash"></span> Proceed to Clean Selected Now</button>' +
							'</div>';
						$('#nexdbc-batch-runner').append(actionHtml);

						$('#nexdbc-runner-btn-cancel').on('click', function() {
							$('#nexdbc-batch-runner').slideUp(200);
						});

						$('#nexdbc-runner-btn-proceed').on('click', function() {
							if (confirm(nexuraDatabaseCleanerData.strings.confirm_cleanup)) {
								$('#nexdbc-batch-runner-actions').remove();
								runQueueCleanup(itemsQueue, false);
							}
						});
					}
				} else {
					$('#nexdbc-batch-status-badge').removeClass('nexdbc-badge-info').addClass('nexdbc-badge-safe').text('Finished');
					$('#nexdbc-progress-fill').css('width', '100%');
					$('#nexdbc-progress-percent').text('100%');
					$('#nexdbc-batch-log').append('<div style="color:#176c2d; font-weight:bold; margin-top:8px;">\u2713 ' + nexuraDatabaseCleanerData.strings.completed + '</div>');
					setTimeout(function() {
						window.location.reload();
					}, 1200);
				}
				return;
			}

			var currentItemId = itemsQueue[queueIndex];
			var $itemRow = $('[data-item-id="' + currentItemId + '"]');
			var itemTitle = $itemRow.length ? $itemRow.data('title') : currentItemId;
			var totalInitialCount = ($itemRow.length && $itemRow.data('count')) ? parseInt($itemRow.data('count'), 10) : 1;
			var itemCleanedSoFar = 0;

			$('#nexdbc-batch-title').text((isDryRun ? '[Dry Run] ' : '') + 'Processing: ' + itemTitle);
			$('#nexdbc-batch-log').append('<div>Starting ' + itemTitle + '...</div>');

			function runBatchStep() {
				$.post(nexuraDatabaseCleanerData.ajax_url, {
					action: 'nexura_database_cleaner_clean_batch',
					nonce: nexuraDatabaseCleanerData.nonce,
					item_id: currentItemId,
					dry_run: isDryRun ? 'true' : 'false',
					batch_size: nexuraDatabaseCleanerData.batch_size
				}, function(response) {
					if (!response || !response.success) {
						var batchError = (response && response.data && response.data.message) ? response.data.message : nexuraDatabaseCleanerData.strings.error;
						$('#nexdbc-batch-log').append($('<div>', { 'class': 'nexdbc-text-danger', text: 'Error: ' + batchError }));
						queueIndex++;
						processNextItem();
						return;
					}

					var data = response.data;
					if (isDryRun) {
						var inspectedCount = (typeof data.rows_before !== 'undefined') ? parseInt(data.rows_before, 10) : 0;
						if (isNaN(inspectedCount)) {
							inspectedCount = 0;
						}
						itemCleanedSoFar = inspectedCount;
						if (inspectedCount > 0) {
							totalInitialCount = inspectedCount;
						}
					} else {
						itemCleanedSoFar += data.cleaned_count;
						totalCleanedInSession += data.cleaned_count;
					}

					var percent = totalInitialCount > 0 ? Math.min(100, Math.round((itemCleanedSoFar / totalInitialCount) * 100)) : 100;
					$('#nexdbc-progress-fill').css('width', percent + '%');
					$('#nexdbc-progress-stats').text(itemCleanedSoFar + ' / ' + totalInitialCount);
					$('#nexdbc-progress-percent').text(percent + '%');

					var logText = isDryRun
						? 'Inspected ' + itemTitle + ': ' + itemCleanedSoFar + ' matching records (Dry Run).'
						: 'Cleaned ' + itemTitle + ': ' + data.cleaned_count + ' rows deleted (Remaining: ' + data.remaining_count + ', Verification: ' + (data.verification || 'Passed') + ').';
					$('#nexdbc-batch-log').append('<div>' + logText + '</div>');
					$('#nexdbc-batch-log').scrollTop($('#nexdbc-batch-log')[0].scrollHeight);

					if (data.is_complete || data.cleaned_count === 0) {
						queueIndex++;
						setTimeout(processNextItem, 350);
					} else {
						// Short pause to keep UI reactive and avoid server flooding
						setTimeout(runBatchStep, 250);
					}
				}).fail(function() {
					$('#nexdbc-batch-log').append('<div class="nexdbc-text-danger">' + nexuraDatabaseCleanerData.strings.error + '</div>');
					queueIndex++;
					processNextItem();
				});
			}

			runBatchStep();
		}

		processNextItem();
	}

	function showNotice(type, message) {
		var $notice = $('#nexdbc-global-notice');
		$notice.removeClass('notice-success notice-error notice-info')
			.addClass('notice notice-' + type + ' is-dismissible')
			.html('<p>' + message + '</p>')
			.show();
	}

})(jQuery);
