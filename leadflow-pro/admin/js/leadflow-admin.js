(function($) {
	'use strict';

	$(function() {
		const apiUrl = leadflowData.apiUrl;
		const nonce = leadflowData.nonce;
		const isPro = leadflowData.isPro;

		/**
		 * UI HELPERS
		 */
		function showUpgradeModal(featureName) {
			const modal = $('#upgradeNudgeModal');
			modal.find('.feature-name').text(featureName);
			modal.fadeIn();
		}

		function escapeHtml(text) {
			if (!text) return '';
			const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		}

		/**
		 * API WRAPPER
		 */
		function apiRequest(endpoint, method = 'GET', data = null) {
			return $.ajax({
				url: apiUrl + endpoint,
				method: method,
				data: data ? JSON.stringify(data) : null,
				contentType: 'application/json',
				beforeSend: function(xhr) { xhr.setRequestHeader("X-WP-Nonce", nonce); }
			});
		}

		/**
		 * CORE NAVIGATION
		 */
		$('.nav-tab-wrapper a').on('click', function(e) {
			e.preventDefault();
			const target = $(this).attr('href').substring(1);
			$('.settings-section').hide();
			$('#' + target).show();
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
		});

		$('.leadflow-tabs .tab-btn').on('click', function() {
			const view = $(this).data('view');
			if (view === 'kanban' && !isPro) { showUpgradeModal('Kanban Pipeline View'); return; }
			$('.tab-btn').removeClass('active');
			$(this).addClass('active');
			$('#leadTableView, #leadKanbanView').hide();
			$('#lead' + view.charAt(0).toUpperCase() + view.slice(1) + 'View').show();
			if (view === 'kanban') loadKanbanData();
		});

		/**
		 * CRM & LEADS
		 */
		function fetchLeads() {
			const status = $('#leadStatusFilter').val();
			const search = $('#leadSearch').val();
			const metaKey = $('#leadMetaKeyFilter').val();
			const metaValue = $('#leadMetaValueFilter').val();

			$.ajax({
				url: apiUrl + "/leads",
				method: "GET",
				data: { status, search, meta_key: metaKey, meta_value: metaValue },
				beforeSend: function(xhr) { xhr.setRequestHeader("X-WP-Nonce", nonce); },
				success: function(data) { renderLeadTable(data); }
			});
		}

		function renderLeadTable(leads) {
			const tbody = $('#leadTableBody');
			if (!tbody.length) return;
			tbody.empty();
			const users = leadflowData.users || [];

			leads.forEach(lead => {
				const score = lead.completeness_score || 0;
				const tagsHtml = (lead.tags || []).map(t => `<span class="status-badge" style="font-size:0.65rem; margin-right:4px;">${escapeHtml(t.name)}</span>`).join('');
				const row = $(`
					<tr>
						<th class="check-column"><input type="checkbox" class="lead-checkbox" value="${lead.id}"></th>
						<td><strong>${escapeHtml(lead.business_name)}</strong><br>${tagsHtml}</td>
						<td><span class="score-pill score-${getScoreColor(score)}">${score}%</span></td>
						<td><a href="${escapeHtml(lead.website_url)}" target="_blank">${escapeHtml(lead.website_url)}</a></td>
						<td>${escapeHtml(lead.email)}</td>
						<td>
							<select class="inline-assignee-update" data-id="${lead.id}">
								<option value="">Unassigned</option>
								${users.map(u => `<option value="${u.id}" ${lead.assigned_to == u.id ? 'selected' : ''}>${escapeHtml(u.name)}</option>`).join('')}
							</select>
						</td>
						<td>${tagsHtml}</td>
						<td>
							<select class="inline-status-update" data-id="${lead.id}">
								<option value="New" ${lead.status === 'New' ? 'selected' : ''}>New</option>
								<option value="Contacted" ${lead.status === 'Contacted' ? 'selected' : ''}>Contacted</option>
								<option value="Replied" ${lead.status === 'Replied' ? 'selected' : ''}>Replied</option>
								<option value="Qualified" ${lead.status === 'Qualified' ? 'selected' : ''}>Qualified</option>
								<option value="Proposal Sent" ${lead.status === 'Proposal Sent' ? 'selected' : ''}>Proposal Sent</option>
								<option value="Closed Won" ${lead.status === 'Closed Won' ? 'selected' : ''}>Closed Won</option>
								<option value="Closed Lost" ${lead.status === 'Closed Lost' ? 'selected' : ''}>Closed Lost</option>
							</select>
						</td>
						<td>${escapeHtml(lead.updated_at)}</td>
						<td>
							<button class="button button-small view-lead" data-id="${lead.id}">View</button>
							<button class="button button-small manual-audit" data-id="${lead.id}">Audit</button>
							<button class="button button-small delete-lead-btn-row" data-id="${lead.id}" style="color:#d63638;">Delete</button>
						</td>
					</tr>
				`);
				tbody.append(row);
			});
		}

		function getScoreColor(score) {
			if (score >= 80) return 'green';
			if (score >= 50) return 'orange';
			return 'red';
		}

		if ($('#leadTableBody').length) fetchLeads();
		$('#applyFilters').on('click', fetchLeads);

		$(document).on('click', '.manual-audit', function() {
			const id = $(this).data('id');
			const btn = $(this);
			btn.text('Auditing...').prop('disabled', true);
			apiRequest('/leads/' + id + '/audit', 'POST').done(() => {
				alert('Audit request queued!');
				btn.text('Audit').prop('disabled', false);
			});
		});

		$(document).on('click', '.delete-lead-btn-row', function() {
			if (!confirm('Are you sure you want to delete this lead?')) return;
			const id = $(this).data('id');
			apiRequest('/leads/' + id, 'DELETE').done(fetchLeads);
		});

		$(document).on('change', '.inline-status-update', function() {
			const id = $(this).data('id');
			const status = $(this).val();
			updateLeadStatus(id, status);
		});

		/**
		 * KANBAN VIEW
		 */
		function loadKanbanData() {
			$.ajax({
				url: apiUrl + "/leads",
				method: "GET",
				beforeSend: function(xhr) { xhr.setRequestHeader("X-WP-Nonce", nonce); },
				success: function(leads) {
					const columns = ['New', 'Contacted', 'Replied', 'Qualified', 'Proposal Sent', 'Closed Won', 'Closed Lost'];
					columns.forEach(status => {
						const columnCards = $(`.kanban-column[data-status="${status}"] .kanban-items`);
						if (!columnCards.length) return;
						columnCards.empty();
						leads.filter(l => l.status === status).forEach(lead => {
							columnCards.append(`
								<div class="kanban-card" data-id="${lead.id}" draggable="true">
									<strong>${escapeHtml(lead.business_name)}</strong>
									<div class="score-pill score-${getScoreColor(lead.completeness_score)}">${lead.completeness_score}%</div>
								</div>
							`);
						});
					});
					setupKanbanDragDrop();
				}
			});
		}

		function setupKanbanDragDrop() {
			$('.kanban-card').on('dragstart', function(e) {
				e.originalEvent.dataTransfer.setData('leadId', $(this).data('id'));
			});
			$('.kanban-column').on('dragover', function(e) { e.preventDefault(); });
			$('.kanban-column').on('drop', function(e) {
				e.preventDefault();
				const leadId = e.originalEvent.dataTransfer.getData('leadId');
				const newStatus = $(this).data('status');
				updateLeadStatus(leadId, newStatus).done(() => {
					setTimeout(loadKanbanData, 200);
				});
			});
		}

		/**
		 * DISCOVERY
		 */
		$('.leadflow-discovery .tab-btn').on('click', function() {
			const source = $(this).data('source');
			$('.leadflow-discovery .tab-btn').removeClass('active');
			$(this).addClass('active');
			$('#discoverySource').val(source);
			$('#locationInputWrapper').toggle(source === 'google');
		});

		$('#discoverySearchForm').on('submit', function(e) {
			e.preventDefault();
			const source = $('#discoverySource').val();
			const keyword = $('#discoveryKeyword').val();
			const location = $('#discoveryLocation').val();
			const btn = $('#startDiscoveryBtn');

			btn.text('Discovering...').prop('disabled', true);
			const endpoint = source === 'google' ? '/discovery/search' : '/discovery/social';
			const ajaxData = source === 'google' ? { keyword, location } : { keyword, source };

			$.ajax({
				url: apiUrl + endpoint,
				method: 'GET',
				data: ajaxData,
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(data) {
					renderDiscoveryResults(data);
					$('#discoveryResults').fadeIn();
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
						<th class="check-column"><input type="checkbox" class="discovery-item-check" data-index="${index}"></th>
						<td><strong>${escapeHtml(lead.business_name)}</strong></td>
						<td><a href="${escapeHtml(lead.website_url)}" target="_blank">${escapeHtml(lead.website_url)}</a></td>
						<td>${escapeHtml(lead.phone)}</td>
						<td><span class="status-badge status-discovery">${escapeHtml(lead.lead_source)}</span></td>
						<td><button class="button button-small import-lead" data-index="${index}">Import</button></td>
					</tr>
				`);
			});
			window.currentDiscoveryResults = leads;
		}

		$(document).on('click', '.import-lead', function() {
			const index = $(this).data('index');
			const lead = window.currentDiscoveryResults[index];
			const btn = $(this);

			btn.text('Importing...').prop('disabled', true);
			apiRequest('/leads', 'POST', lead).done(() => {
				btn.text('Imported!').removeClass('button-primary');
			}).fail(err => {
				alert('Import failed: ' + (err.responseJSON ? err.responseJSON.message : 'Unknown error'));
				btn.text('Import').prop('disabled', false);
			});
		});

		$('#importSelectedLeads').on('click', function() {
			const selectedIndexes = $('.discovery-item-check:checked').map(function() { return $(this).data('index'); }).get();
			if (!selectedIndexes.length) return;

			const btn = $(this);
			btn.text('Importing ' + selectedIndexes.length + ' leads...').prop('disabled', true);

			const promises = selectedIndexes.map(idx => apiRequest('/leads', 'POST', window.currentDiscoveryResults[idx]));

			$.when.apply($, promises).then(() => {
				alert('Bulk import complete!');
				btn.text('Bulk Import Selected').prop('disabled', false);
				$('.discovery-item-check:checked').closest('tr').css('opacity', 0.5);
			});
		});

		/**
		 * ANALYTICS & DASHBOARD
		 */
		function renderCharts() {
			$.ajax({
				url: apiUrl + '/analytics/overview',
				method: 'GET',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(data) {
					if (document.getElementById('leadsStatusChart')) {
						new Chart(document.getElementById('leadsStatusChart'), {
							type: 'bar',
							data: {
								labels: data.status_counts.map(s => s.status),
								datasets: [{ label: 'Leads', data: data.status_counts.map(s => s.count), backgroundColor: '#6366f1' }]
							},
							options: { indexAxis: 'y' }
						});
					}

					if (document.getElementById('sentimentPulseChart') && data.sentiment_pulse) {
						new Chart(document.getElementById('sentimentPulseChart'), {
							type: 'doughnut',
							data: {
								labels: data.sentiment_pulse.map(s => s.sentiment),
								datasets: [{ data: data.sentiment_pulse.map(s => s.count), backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#64748b'] }]
							}
						});
					}

					if (document.getElementById('leadsSourceChart')) {
						new Chart(document.getElementById('leadsSourceChart'), {
							type: 'pie',
							data: {
								labels: data.source_counts.map(s => s.source),
								datasets: [{ data: data.source_counts.map(s => s.count), backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444'] }]
							}
						});
					}

					if (document.getElementById('leadsAssigneeChart')) {
						new Chart(document.getElementById('leadsAssigneeChart'), {
							type: 'bar',
							data: {
								labels: data.assignee_counts.map(a => a.name),
								datasets: [{ label: 'Leads Assigned', data: data.assignee_counts.map(a => a.count), backgroundColor: '#10b981' }]
							}
						});
					}
				}
			});
		}

		if ($('#leadsStatusChart').length) {
			renderCharts();
			loadRecentActivity();
			loadCampaignStats();
		}

		function loadRecentActivity() {
			apiRequest('/analytics/activity').done(function(data) {
				const tbody = $('#recentActivityBody');
				tbody.empty();
				data.forEach(act => {
					tbody.append(`<tr><td>${escapeHtml(act.activity)}</td><td>${escapeHtml(act.lead)}</td><td>${act.created_at}</td></tr>`);
				});
			});
		}

		function loadCampaignStats() {
			apiRequest('/analytics/campaigns').done(function(data) {
				const tbody = $('#campaignStatsBody');
				tbody.empty();
				data.forEach(c => {
					tbody.append(`<tr><td><strong>${escapeHtml(c.name)}</strong></td><td>${c.sent}</td><td>${c.opens}</td><td>${c.clicks}</td><td>${c.replies}</td></tr>`);
				});
			});
		}

		/**
		 * SYSTEM LOGS & HEALTH
		 */
		function loadSystemLogs() {
			const tbody = $('#systemLogsBody');
			if (!tbody.length) return;
			$.ajax({
				url: apiUrl + '/settings/logs',
				method: 'GET',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(logs) {
					tbody.empty();
					logs.forEach(log => {
						tbody.append(`<tr><td>${log.created_at}</td><td>${log.module}</td><td>${log.level}</td><td>${log.message}</td></tr>`);
					});
				}
			});
		}

		$('#refreshLogsBtn').on('click', loadSystemLogs);
		$('a[href="#logs"]').on('click', loadSystemLogs);

		// AI Connection Test
		$('.test-ai-connection').on('click', function() {
			const provider = $(this).data('provider');
			const btn = $(this);
			btn.text('Testing...').prop('disabled', true);
			apiRequest('/ai/complete', 'POST', { prompt: 'Ping', context: { provider, feature: 'test_connection' } })
			.done(function(response) {
				alert(provider + ' connected! Response: ' + response.result);
			})
			.always(() => btn.text('Test Connection').prop('disabled', false));
		});

		// Bulk Actions
		$('#applyBulkStatus').on('click', function() {
			const selectedIds = $('.lead-checkbox:checked').map(function() { return $(this).val(); }).get();
			const newStatus = $('#bulkStatusUpdate').val();
			if (!selectedIds.length || !newStatus) return;

			const promises = selectedIds.map(id => updateLeadStatus(id, newStatus));
			$.when.apply($, promises).then(() => {
				alert('Bulk update complete!');
				fetchLeads();
			});
		});

		function updateLeadStatus(leadId, status) {
			return apiRequest('/leads/' + leadId, 'POST', { status: status });
		}

		/**
		 * CAMPAIGNS & OUTREACH
		 */
		$('#addStepBtn').on('click', function() {
			const container = $('#sequenceSteps');
			const index = container.find('.campaign-step-card').length + 1;
			container.append(`
				<div class="campaign-step-card chart-box" data-index="${index}" style="margin-bottom:15px; padding:15px;">
					<h4>Step ${index} <span class="remove-step" style="float:right; cursor:pointer;">&times;</span></h4>
					<p><label>Delay (Days)</label><br><input type="number" class="step-delay" value="${index === 1 ? 0 : 2}"></p>
					<p><label>Type</label><br>
						<select class="step-type">
							<option value="email">Email</option>
							<option value="linkedin">LinkedIn Connection</option>
							<option value="call">Phone Call</option>
						</select>
					</p>
					<p><label>Subject (for Emails)</label><br><input type="text" class="step-subject" style="width:100%"></p>
					<p><label>Body / Task Description</label><br><textarea class="step-body" rows="4" style="width:100%"></textarea></p>
				</div>
			`);
		});

		$(document).on('click', '.remove-step', function() { $(this).closest('.campaign-step-card').remove(); });

		$('#campaignBuilderForm').on('submit', function(e) {
			e.preventDefault();
			const steps = [];
			$('.campaign-step-card').each(function() {
				steps.push({
					delay: $(this).find('.step-delay').val(),
					type: $(this).find('.step-type').val(),
					subject: $(this).find('.step-subject').val(),
					body: $(this).find('.step-body').val()
				});
			});

			const data = {
				name: $('#campaignName').val(),
				status_filter: $('#campaignStatusFilter').val(),
				start_hour: $('#campaignStartHour').val(),
				end_hour: $('#campaignEndHour').val(),
				skip_weekends: $('#campaignSkipWeekends').is(':checked') ? 1 : 0,
				steps: steps
			};

			apiRequest('/campaigns', 'POST', data).done(function() {
				alert('Campaign saved!');
				location.reload();
			});
		});

		/**
		 * INBOX & CONVERSATIONS
		 */
		function loadInbox() {
			if (!$('#inboxItems').length) return;
			apiRequest('/inbox').done(function(threads) {
				const list = $('#inboxItems');
				list.empty();
				threads.forEach(t => {
					list.append(`
						<div class="inbox-item" data-id="${t.id}">
							<div class="inbox-item-header">
								<span class="inbox-item-lead">${escapeHtml(t.business_name)}</span>
								<span class="inbox-item-time">${t.last_reply}</span>
							</div>
						</div>
					`);
				});
			});
		}

		if ($('#inboxItems').length) loadInbox();

		$(document).on('click', '.inbox-item', function() {
			const leadId = $(this).data('id');
			const leadName = $(this).find('.inbox-item-lead').text();
			$('.inbox-item').removeClass('active');
			$(this).addClass('active');
			$('#inboxReply').show();
			$('#viewLeadName').text(leadName);
			loadThread(leadId);
		});

		function loadThread(leadId) {
			apiRequest('/leads/' + leadId + '/activity').done(function(data) {
				const container = $('#inboxThread');
				container.empty();
				const activities = [...(data.notes||[]), ...(data.emails||[])].sort((a,b) => new Date(a.created_at) - new Date(b.created_at));

				if (!activities.length) {
					container.append('<div class="inbox-placeholder">No activity yet.</div>');
				}

				activities.forEach(act => {
					const isOutbound = act.subject || (act.content && (act.content.startsWith('Outbound') || act.content.startsWith('Sent')));
					container.append(`
						<div class="message-bubble ${isOutbound ? 'outbound' : 'inbound'}">
							<div class="msg-meta">${act.created_at}</div>
							<div class="msg-body">${escapeHtml(act.content || act.subject)}</div>
						</div>
					`);
				});
				container.scrollTop(container[0].scrollHeight);
			});
		}

		$('#viewLeadBtn').on('click', function() {
			const leadId = $('.inbox-item.active').data('id');
			if (leadId) openLeadModal(leadId);
		});

		function openLeadModal(id) {
			apiRequest('/leads/' + id).done(function(lead) {
				$('#detailLeadName').text(lead.business_name);
				$('#proposalUrl').val(lead.proposal_url);
				$('#leadDetailModal').fadeIn();
				loadThreadInModal(id);
			});
		}

		function loadThreadInModal(leadId) {
			apiRequest('/leads/' + leadId + '/activity').done(function(data) {
				const container = $('#detailLeadThread');
				container.empty();
				const activities = [...(data.notes||[]), ...(data.emails||[])].sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
				activities.forEach(act => {
					const isOutbound = act.subject || (act.content && act.content.startsWith('Outbound'));
					container.append(`<div class="message-bubble ${isOutbound ? 'outbound' : 'inbound'}" style="font-size:0.8rem; margin-bottom:10px; padding:10px; border-radius:8px; background:${isOutbound?'#f0f7ff':'#f9f9f9'};">
						<strong>${act.created_at}</strong><br>${escapeHtml(act.content || act.subject)}
					</div>`);
				});
			});
		}

		$('#sendReplyBtn').on('click', function() {
			const leadId = $('.inbox-item.active').data('id');
			const message = $('#replyText').val();
			if (!leadId || !message) return;

			$(this).prop('disabled', true).text('Sending...');
			apiRequest('/inbox/reply', 'POST', { lead_id: leadId, message: message }).done(() => {
				$('#replyText').val('');
				loadThread(leadId);
			}).always(() => {
				$(this).prop('disabled', false).text('Send Reply');
			});
		});

		/**
		 * GLOBAL TASKS
		 */
		function loadTasks() {
			const tbody = $('#pulseTableBody');
			if (!tbody.length) return;
			apiRequest('/tasks').done(function(tasks) {
				tbody.empty();
				tasks.forEach(task => {
					tbody.append(`
						<tr>
							<td>${escapeHtml(task.business_name)}</td>
							<td>${task.status}</td>
							<td>${escapeHtml(task.description)}</td>
							<td>
								<button class="button complete-task" data-id="${task.id}">Complete</button>
							</td>
						</tr>
					`);
				});
			});
		}

		if ($('#pulseTableBody').length) loadTasks();

		$(document).on('click', '.complete-task', function() {
			const id = $(this).data('id');
			apiRequest('/tasks/' + id, 'POST', { status: 'completed' }).done(loadTasks);
		});

		$('#testImapBtn').on('click', function() {
			const btn = $(this);
			btn.text('Testing...').prop('disabled', true);
			apiRequest('/settings/test-imap', 'POST').done(() => {
				alert('IMAP Connection Successful!');
			}).fail(err => {
				alert('IMAP Connection Failed: ' + (err.responseJSON ? err.responseJSON.message : 'Unknown error'));
			}).always(() => {
				btn.text('Test IMAP Connection').prop('disabled', false);
			});
		});

	});
})(jQuery);
