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

		// Discovery Source Switching
		$('.leadflow-discovery .tab-btn').on('click', function() {
			const source = $(this).data('source');
			$('.leadflow-discovery .tab-btn').removeClass('active');
			$(this).addClass('active');
			$('#discoverySource').val(source);

			if (source === 'google') {
				$('#locationInputWrapper').show();
				$('#discoveryLabel').text('Keyword (e.g. Dentist, Plumber)');
			} else {
				$('#locationInputWrapper').hide();
				$('#discoveryLabel').text(source === 'linkedin' ? 'Industry or Job Title' : 'Facebook Group Keyword');
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

		// Bulk Import Selected
		$('#importSelectedLeads').on('click', function() {
			const selectedIndices = [];
			$('.discovery-item-check:checked').each(function() {
				selectedIndices.push($(this).val());
			});

			if (selectedIndices.length === 0) {
				alert('Please select at least one lead to import.');
				return;
			}

			const btn = $(this);
			const originalText = btn.text();
			btn.text('Bulk Importing...').prop('disabled', true);

			let processed = 0;
			selectedIndices.forEach(index => {
				const lead = window.currentDiscoveryResults[index];
				$.ajax({
					url: apiUrl + '/leads',
					method: 'POST',
					data: lead,
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', nonce);
					},
					success: function() {
						processed++;
						if (processed === selectedIndices.length) {
							alert('Bulk import complete!');
							btn.text(originalText).prop('disabled', false);
							location.reload();
						}
					}
				});
			});
		});

		// Inbox Item Click
		$(document).on('click', '.inbox-item', function() {
			const leadId = $(this).data('lead-id');
			const leadName = $(this).find('.inbox-item-lead').text();

			// Find the lead data from the table (simulated state)
			// In a real app, you'd fetch the full lead object

			$('#viewLeadName').text(leadName);
			$('.inbox-item').removeClass('active');
			$(this).addClass('active');
			$('#inboxReply').show();
			$('#aiLeadTools').show().find('button').data('lead-id', leadId);
			$('.export-data-btn, .delete-lead-btn').data('lead-id', leadId);

			loadThread(leadId);
			loadLeadSidebar(leadId);
		});

		function loadLeadSidebar(leadId) {
			const sidebar = $('#leadSidebarContent');
			sidebar.html('<p>Loading audit data...</p>');

			$.ajax({
				url: apiUrl + '/leads', // Filter by ID in real app
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(leads) {
					const lead = leads.find(l => l.id == leadId);
					if (lead && lead.audit_data) {
						const audit = JSON.parse(lead.audit_data);
						let html = `
							<div class="audit-summary">
								<p><strong>Website:</strong> <a href="${lead.website_url}" target="_blank">${lead.website_url}</a></p>
								<ul class="audit-checklist">
									<li class="${audit.has_ssl ? 'success' : 'danger'}">${audit.has_ssl ? '✅ SSL Secure' : '❌ No SSL'}</li>
									<li class="${audit.is_mobile_responsive ? 'success' : 'danger'}">${audit.is_mobile_responsive ? '✅ Mobile Friendly' : '❌ Not Mobile Responsive'}</li>
									<li class="${audit.outdated_design ? 'danger' : 'success'}">${audit.outdated_design ? '❌ Outdated Design' : '✅ Modern Design'}</li>
									<li>⏱️ Load Time: ${audit.load_time}s</li>
								</ul>
								<p><strong>Social Links:</strong></p>
								<div class="social-pills">
									${Object.entries(JSON.parse(lead.social_links)).map(([platform, link]) => `<a href="${link}" target="_blank" class="social-pill ${platform}">${platform}</a>`).join('')}
								</div>
							</div>
						`;
						sidebar.html(html);
					} else {
						sidebar.html('<p>No audit data found. Try refreshing the lead.</p>');
					}
				}
			});
		}

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
			$.ajax({
				url: apiUrl + '/analytics/overview',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					// Leads by Status Chart
					const statusLabels = data.status_counts.map(s => s.status);
					const statusValues = data.status_counts.map(s => s.count);

					new Chart(document.getElementById('leadsStatusChart'), {
						type: 'doughnut',
						data: {
							labels: statusLabels,
							datasets: [{
								data: statusValues,
								backgroundColor: ['#2271b1', '#72aee6', '#3582c4', '#0073aa', '#f0b849', '#d63638', '#008a20']
							}]
						}
					});

					// Update KPI values if elements exist
					if ($('.leadflow-kpi-grid').length) {
						$('.kpi-card:nth-child(3) .kpi-value').text( (data.metrics.sent > 0 ? Math.round((data.metrics.opened / data.metrics.sent) * 100) : 0) + '%' );
					}
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
			const source = $('#discoverySource').val();
			const keyword = $('#discoveryKeyword').val();
			const location = $('#discoveryLocation').val();
			const btn = $('#startDiscoveryBtn');

			btn.text('Discovering...').prop('disabled', true);

			const endpoint = source === 'google' ? '/discovery/search' : '/discovery/social';
			const ajaxData = source === 'google' ? { keyword: keyword, location: location } : { keyword: keyword, source: source };

			$.ajax({
				url: apiUrl + endpoint,
				method: 'GET',
				data: ajaxData,
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

		// Toggle email fields in campaign builder
		$(document).on('change', '.step-type', function() {
			const type = $(this).val();
			const card = $(this).closest('.step-card');
			if (type === 'email') {
				card.find('.email-fields').show();
			} else {
				card.find('.email-fields').hide();
			}
		});

		// Add Step in Campaign Builder
		$('#addStepBtn').on('click', function() {
			const stepCount = $('.step-card').length + 1;
			const newStep = `
				<div class="step-card">
					<h4>Step ${stepCount}</h4>
					<p><label>Step Type</label><br>
						<select name="step[${stepCount}][type]" class="step-type">
							<option value="email">Email</option>
							<option value="linkedin">LinkedIn Connection/Message</option>
							<option value="facebook">Facebook Group Outreach</option>
						</select>
					</p>
					<div class="email-fields">
						<p><label>Subject</label><br><input type="text" name="step[${stepCount}][subject]" class="step-subject" value="Follow up ${stepCount}"></p>
						<button type="button" class="button ai-subject-btn">✨ AI: Generate Subject</button>
					</div>
					<p><label>Delay (Days)</label><br><input type="number" name="step[${stepCount}][delay]" value="3"></p>
					<p><label>Message Body</label><br><textarea name="step[${stepCount}][body]" class="step-body" rows="5"></textarea></p>
					<button type="button" class="button ai-writer-btn">✨ AI: Write this for me</button>
				</div>
			`;
			$('#sequenceSteps').append(newStep);
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
						const score = calculateCompleteness(lead);
						const item = $(`
							<div class="kanban-item" data-id="${lead.id}">
								<div class="kanban-item-header">
									<strong>${lead.business_name}</strong>
									<span class="score-pill score-${getScoreColor(score)}">${score}%</span>
								</div>
								<p class="kanban-item-url">${lead.website_url || ''}</p>
								<p class="kanban-item-email">${lead.email || 'No email'}</p>
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
