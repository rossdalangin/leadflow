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

		// Inbox Item Click
		$(document).on('click', '.inbox-item', function() {
			const leadId = $(this).data('lead-id');
			const leadName = $(this).find('.inbox-item-lead').text();

			$('#viewLeadName').text(leadName);
			$('.inbox-item').removeClass('active');
			$(this).addClass('active');
			$('#inboxReply').show();
			$('#aiLeadTools').show().find('button').data('lead-id', leadId);
			$('.export-data-btn, .delete-lead-btn').data('lead-id', leadId);

			loadThread(leadId);
		});

		function loadTags() {
			$.ajax({
				url: apiUrl + '/tags',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(tags) {
					const select = $('#addTagSelect');
					select.find('option:not(:first)').remove();
					tags.forEach(tag => {
						select.append(`<option value="${tag.id}">${tag.name}</option>`);
					});
				}
			});
		}

		if ($('#addTagSelect').length) {
			loadTags();
		}

		function loadThread(leadId) {
			$('#inboxThread').html('<p>Loading conversation...</p>');

			$.ajax({
				url: apiUrl + '/leads/' + leadId + '/activity',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const thread = $('#inboxThread');
					thread.empty();

					const items = [...data.notes, ...data.emails];
					items.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

					items.forEach(item => {
						const type = item.subject ? 'email' : 'note';
						const content = item.body || item.content || item.subject;
						thread.append(`
							<div class="thread-item ${type}">
								<div class="thread-meta">${item.created_at}</div>
								<div class="thread-content">${content}</div>
							</div>
						`);
					});

					if (items.length === 0) {
						thread.html('<p>No activity yet.</p>');
					}
				}
			});
		}

		// Send Reply
		$('#sendReplyBtn').on('click', function() {
			const leadId = $('.inbox-item.active').data('lead-id');
			const message = $('#replyText').val();
			if (!message) return;

			$(this).text('Sending...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/inbox/reply',
				method: 'POST',
				data: { lead_id: leadId, message: message },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					$('#replyText').val('');
					$('#sendReplyBtn').text('Send Reply').prop('disabled', false);
					alert('Reply sent!');
					loadThread(leadId);
				}
			});
		});

		function renderLeadTable(leads) {
			const tbody = $('#leadTableBody');
			tbody.empty();
			leads.forEach(lead => {
				const audit = lead.audit_data ? JSON.parse(lead.audit_data) : {};
				const score = calculateCompleteness(lead);
				tbody.append(`
					<tr>
						<td><strong>${lead.business_name}</strong></td>
						<td><span class="score-pill score-${getScoreColor(score)}">${score}%</span></td>
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

		// AI Score Lead
		$(document).on('click', '.ai-score-btn', function() {
			const btn = $(this);
			const leadId = btn.data('lead-id');
			btn.text('Scoring...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'lead_scorer',
						lead_data: { id: leadId }, // Simplified
						audit_data: {} // In real app, fetch from state
					}
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					alert('AI Lead Score: ' + response.result);
					btn.text('✨ AI: Score Lead').prop('disabled', false);
				}
			});
		});

		// Export Data (GDPR)
		$(document).on('click', '.export-data-btn', function() {
			const leadId = $(this).data('lead-id');
			window.open(apiUrl + '/leads/' + leadId + '/export?_wpnonce=' + nonce);
		});

		// Delete Lead (GDPR)
		$(document).on('click', '.delete-lead-btn', function() {
			if (!confirm('Are you sure you want to PERMANENTLY delete all data for this lead?')) return;

			const leadId = $(this).data('lead-id');
			$.ajax({
				url: apiUrl + '/leads/' + leadId,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Lead deleted successfully.');
					location.reload();
				}
			});
		});

		// AI Summarize Audit
		$(document).on('click', '.ai-summarize-btn', function() {
			const btn = $(this);
			const leadId = btn.data('lead-id');
			btn.text('Summarizing...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'audit_insight',
						audit_results: {} // In real app, fetch from state
					}
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					alert('AI Audit Summary: ' + response.result);
					btn.text('✨ AI: Summarize Audit').prop('disabled', false);
				}
			});
		});

		function calculateCompleteness(lead) {
			let score = 0;
			if (lead.business_name) score += 20;
			if (lead.website_url) score += 20;
			if (lead.email) score += 30;
			if (lead.phone) score += 15;
			if (lead.social_links && lead.social_links !== '[]') score += 15;
			return score;
		}

		function getScoreColor(score) {
			if (score >= 80) return 'green';
			if (score >= 50) return 'orange';
			return 'red';
		}

		if ($('#leadsStatusChart').length) {
			renderCharts();
			fetchCampaignStats();
		}

		function fetchCampaignStats() {
			$.ajax({
				url: apiUrl + '/analytics/campaigns',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const tbody = $('#campaignStatsBody');
					tbody.empty();
					data.forEach(stat => {
						tbody.append(`
							<tr>
								<td><strong>${stat.name}</strong></td>
								<td>${stat.sent}</td>
								<td>${stat.opens}</td>
								<td>${stat.clicks}</td>
								<td>${stat.replies}</td>
							</tr>
						`);
					});
				}
			});
		}

		function renderCharts() {
			// Fetch status data from API (simplified for this example)
			$.ajax({
				url: apiUrl + '/leads',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(leads) {
					const counts = {};
					leads.forEach(l => {
						counts[l.status] = (counts[l.status] || 0) + 1;
					});

					new Chart(document.getElementById('leadsStatusChart'), {
						type: 'doughnut',
						data: {
							labels: Object.keys(counts),
							datasets: [{
								data: Object.values(counts),
								backgroundColor: ['#2271b1', '#72aee6', '#3582c4', '#0073aa']
							}]
						}
					});
				}
			});

			// Outreach performance chart (mock data for visualization)
			new Chart(document.getElementById('outreachChart'), {
				type: 'line',
				data: {
					labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
					datasets: [{
						label: 'Emails Sent',
						data: [12, 19, 3, 5, 2, 3, 7],
						borderColor: '#2271b1',
						tension: 0.1
					}]
				}
			});
		}

		// Discovery Search
		$('#discoverySearchForm').on('submit', function(e) {
			e.preventDefault();
			const keyword = $('#discoveryKeyword').val();
			const location = $('#discoveryLocation').val();
			const btn = $('#startDiscoveryBtn');

			btn.text('Discovering...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/discovery/search',
				method: 'GET',
				data: { keyword: keyword, location: location },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					renderDiscoveryResults(data);
					$('#discoveryResults').fadeIn();
					btn.text('Start Discovery').prop('disabled', false);
				},
				error: function(err) {
					alert('Error: ' + (err.responseJSON ? err.responseJSON.message : 'Discovery failed.'));
					btn.text('Start Discovery').prop('disabled', false);
				}
			});
		});

		function renderDiscoveryResults(leads) {
			const tbody = $('#discoveryResultsBody');
			tbody.empty();
			leads.forEach((lead, index) => {
				tbody.append(`
					<tr>
						<th class="check-column"><input type="checkbox" class="discovery-item-check" value="${index}"></th>
						<td><strong>${lead.business_name}</strong></td>
						<td><a href="${lead.website_url}" target="_blank">${lead.website_url}</a></td>
						<td>${lead.phone}</td>
						<td><span class="status-badge status-discovery">${lead.lead_source}</span></td>
						<td>
							<button class="button button-small import-lead" data-index="${index}">Import</button>
						</td>
					</tr>
				`);
			});

			// Store current discovery results globally for easy import
			window.currentDiscoveryResults = leads;
		}

		// Import Lead from Discovery
		$(document).on('click', '.import-lead', function() {
			const index = $(this).data('index');
			const lead = window.currentDiscoveryResults[index];
			importLead(lead, $(this));
		});

		function importLead(lead, btn) {
			btn.text('Importing...').prop('disabled', true);
			$.ajax({
				url: apiUrl + '/leads',
				method: 'POST',
				data: lead,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					btn.text('Imported').addClass('button-disabled');
				},
				error: function(err) {
					alert('Import failed: ' + err.responseJSON.message);
					btn.text('Import').prop('disabled', false);
				}
			});
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
					btn.closest('.step-card').find('.step-body').val(response.result);
					btn.text(originalText).prop('disabled', false);
				}
			});
		});

		// AI Subject Line
		$('.ai-subject-btn').on('click', function() {
			const btn = $(this);
			const originalText = btn.text();
			btn.text('Generating...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'subject_generator',
						business_name: 'the target business'
					}
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					btn.closest('.step-card').find('.step-subject').val(response.result);
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
