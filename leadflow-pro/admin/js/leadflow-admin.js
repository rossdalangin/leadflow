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

		function safeJsonParse(json, defaultVal = {}) {
			try { return json ? JSON.parse(json) : defaultVal; } catch (e) { return defaultVal; }
		}

		function getScoreColor(score) {
			if (score >= 80) return 'green';
			if (score >= 50) return 'orange';
			return 'red';
		}

		/**
		 * API WRAPPER
		 */
		function apiRequest(endpoint, method = 'GET', data = null) {
			return $.ajax({
				url: apiUrl + endpoint,
				method: method,
				data: data instanceof FormData ? data : (data ? JSON.stringify(data) : null),
				contentType: data instanceof FormData ? false : 'application/json',
				processData: !(data instanceof FormData),
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
							<button class="button button-small edit-lead-btn" data-id="${lead.id}">Edit</button>
							<button class="button button-small manual-audit" data-id="${lead.id}">Audit</button>
							<button class="button button-small delete-lead-btn-row" data-id="${lead.id}" style="color:#d63638;">Delete</button>
						</td>
					</tr>
				`);
				tbody.append(row);
			});
		}

		if ($('#leadTableBody').length) fetchLeads();
		$('#applyFilters').on('click', fetchLeads);

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
						<th class="check-column"><input type="checkbox" class="discovery-item-check" value="${index}"></th>
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

		/**
		 * ANALYTICS & DASHBOARD
		 */
		function renderCharts() {
			$.ajax({
				url: apiUrl + '/analytics/overview',
				method: 'GET',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(data) {
					// Status Chart
					new Chart(document.getElementById('leadsStatusChart'), {
						type: 'bar',
						data: {
							labels: data.status_counts.map(s => s.status),
							datasets: [{ label: 'Leads', data: data.status_counts.map(s => s.count), backgroundColor: '#6366f1' }]
						},
						options: { indexAxis: 'y' }
					});

					// Sentiment Chart
					if ($('#sentimentPulseChart').length && data.sentiment_pulse) {
						new Chart(document.getElementById('sentimentPulseChart'), {
							type: 'doughnut',
							data: {
								labels: data.sentiment_pulse.map(s => s.sentiment),
								datasets: [{ data: data.sentiment_pulse.map(s => s.count), backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#64748b'] }]
							}
						});
					}
				}
			});
		}

		if ($('#leadsStatusChart').length) renderCharts();

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
			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: JSON.stringify({ prompt: 'Ping', context: { provider, feature: 'test_connection' } }),
				contentType: 'application/json',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(response) {
					alert(provider + ' connected! Response: ' + response.result);
					btn.text('Test Connection').prop('disabled', false);
				}
			});
		});

		// Bulk Actions
		$('#applyBulkStatus').on('click', function() {
			const selectedIds = $('.lead-checkbox:checked').map(function() { return $(this).val(); }).get();
			const newStatus = $('#bulkStatusUpdate').val();
			if (!selectedIds.length || !newStatus) return;
			selectedIds.forEach(id => updateLeadStatus(id, newStatus));
			alert('Bulk update complete!');
			fetchLeads();
		});

		function updateLeadStatus(leadId, status) {
			$.ajax({
				url: apiUrl + '/leads/' + leadId,
				method: 'POST',
				data: { status },
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); }
			});
		}

		// Initializations
		$('.close-modal').on('click', function() { $('.leadflow-modal').fadeOut(); });

	});
})(jQuery);
