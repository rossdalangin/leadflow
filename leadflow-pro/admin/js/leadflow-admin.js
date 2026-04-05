(function($) {
	'use strict';

	$(function() {
		const apiUrl = leadflowData.apiUrl;
		const nonce = leadflowData.nonce;
		const isPro = leadflowData.isPro;

		// Tab Switching Logic
		$('.nav-tab-wrapper a').on('click', function(e) {
			e.preventDefault();
			const target = $(this).attr('href').substring(1);
			$('.settings-section').hide();
			$('#' + target).show();
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
		});

		// Lead View Switching
		$('.leadflow-tabs .tab-btn').on('click', function() {
			const view = $(this).data('view');
			if (view === 'kanban' && !isPro) {
				alert('Kanban view is a Pro feature.');
				return;
			}
			$('.tab-btn').removeClass('active');
			$(this).addClass('active');
			$('#leadTableView, #leadKanbanView').hide();
			$('#lead' + view.charAt(0).toUpperCase() + view.slice(1) + 'View').show();
			if (view === 'kanban') {
				loadKanbanData();
			}
		});

		// Fetch Leads for Table
		function fetchLeads() {
			$.ajax({
				url: apiUrl + '/leads',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					renderLeadTable(data);
				}
			});
		}

		function renderLeadTable(leads) {
			const tbody = $('#leadTableBody');
			tbody.empty();
			leads.forEach(lead => {
				tbody.append(`
					<tr>
						<td><strong>${lead.business_name}</strong></td>
						<td><a href="${lead.website_url}" target="_blank">${lead.website_url}</a></td>
						<td>${lead.email}</td>
						<td><span class="status-badge status-${lead.status.toLowerCase()}">${lead.status}</span></td>
						<td>${lead.updated_at}</td>
						<td>
							<button class="button button-small view-lead" data-id="${lead.id}">View</button>
						</td>
					</tr>
				`);
			});
		}

		// Initial Load
		if ($('#leadTableBody').length) {
			fetchLeads();
		}

		// Add Lead Modal
		$('#addLeadBtn').on('click', function(e) {
			e.preventDefault();
			$('#addLeadModal').fadeIn();
		});

		$('.close-modal').on('click', function() {
			$('.leadflow-modal').fadeOut();
		});

		$('#addLeadForm').on('submit', function(e) {
			e.preventDefault();
			const data = $(this).serialize();
			$.ajax({
				url: apiUrl + '/leads',
				method: 'POST',
				data: data,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					$('#addLeadModal').fadeOut();
					fetchLeads();
					alert('Lead added successfully!');
				},
				error: function(err) {
					alert('Error: ' + err.responseJSON.message);
				}
			});
		});

		// AI Writer
		$('.ai-writer-btn').on('click', function() {
			const btn = $(this);
			const originalText = btn.text();
			btn.text('Generating...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					prompt: 'Write a cold email to a local business offering web design services. Mention that their site lacks SSL.',
					context: { feature: 'email_writer' }
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					btn.closest('.step-card').find('textarea').val(response.result);
					btn.text(originalText).prop('disabled', false);
				}
			});
		});

		// Kanban Logic (Pro)
		function loadKanbanData() {
			if (!isPro) return;

			$.ajax({
				url: apiUrl + '/leads',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(leads) {
					$('.kanban-items').empty();
					leads.forEach(lead => {
						const item = $(`
							<div class="kanban-item" data-id="${lead.id}">
								<strong>${lead.business_name}</strong>
								<p>${lead.email || 'No email'}</p>
							</div>
						`);
						$(`.kanban-column[data-status="${lead.status}"] .kanban-items`).append(item);
					});
					initDragAndDrop();
				}
			});
		}

		function initDragAndDrop() {
			$('.kanban-item').attr('draggable', true);

			$('.kanban-item').on('dragstart', function(e) {
				e.originalEvent.dataTransfer.setData('leadId', $(this).data('id'));
			});

			$('.kanban-column').on('dragover', function(e) {
				e.preventDefault();
			});

			$('.kanban-column').on('drop', function(e) {
				e.preventDefault();
				const leadId = e.originalEvent.dataTransfer.getData('leadId');
				const newStatus = $(this).data('status');

				updateLeadStatus(leadId, newStatus);
				$(this).find('.kanban-items').append($(`.kanban-item[data-id="${leadId}"]`));
			});
		}

		function updateLeadStatus(leadId, status) {
			$.ajax({
				url: apiUrl + '/leads/' + leadId,
				method: 'POST', // EDITABLE via REST
				data: { status: status },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				}
			});
		}

		// Test AI Connection
		$('.test-ai-connection').on('click', function() {
			const provider = $(this).data('provider');
			const btn = $(this);
			btn.text('Testing...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					prompt: 'Hello, respond with "OK" if you are active.',
					context: { provider: provider, feature: 'test_connection' }
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					alert(provider + ' connection successful: ' + response.result);
					btn.text('Test Connection').prop('disabled', false);
				},
				error: function() {
					alert(provider + ' connection failed.');
					btn.text('Test Connection').prop('disabled', false);
				}
			});
		});
	});

})(jQuery);
