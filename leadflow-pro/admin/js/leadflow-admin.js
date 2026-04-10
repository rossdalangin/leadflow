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

		/**
		 * API WRAPPER
		 */
		function apiRequest(endpoint, method = 'GET', data = null) {
			const options = {
				url: apiUrl + endpoint,
				method: method,
				beforeSend: function(xhr) { xhr.setRequestHeader("X-WP-Nonce", nonce); }
			};

			if (data) {
				if (method === 'GET') {
					options.data = data;
				} else {
					options.data = JSON.stringify(data);
					options.contentType = 'application/json';
				}
			}

			return $.ajax(options);
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

			apiRequest("/leads", "GET", { status, search, meta_key: metaKey, meta_value: metaValue })
			.done(function(data) { renderLeadTable(data); });
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
							<button class="button button-small manual-enrich" data-id="${lead.id}">Enrich</button>
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

		$(document).on('click', '.view-lead', function() {
			const id = $(this).data('id');
			if ($('#leadDetailModal').length) {
				window.currentLeadId = id;
				openLeadModal(id);
			} else {
				window.location.href = leadflowData.adminUrl + 'admin.php?page=leadflow-leads&lead_id=' + id;
			}
		});

		// Auto-open lead if ID in URL
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.has('lead_id') && $('#leadDetailModal').length) {
			const id = urlParams.get('lead_id');
			window.currentLeadId = id;
			setTimeout(() => openLeadModal(id), 500);
		}

		$('.ai-summarize-btn').on('click', function() {
			const id = window.currentLeadId || $('.inbox-item.active').data('id');
			if (!id) return;
			const btn = $(this);
			btn.text('✨ AI Thinking...').prop('disabled', true);
			apiRequest('/leads/' + id + '/ai-hook', 'POST').done(res => {
				alert('AI Outreach Hook Generated: ' + res.hook);
				loadThreadInModal(id);
			}).fail(() => alert('AI Hook failed.'))
			.always(() => btn.text('✨ AI: Hook').prop('disabled', false));
		});

		$('.ai-score-btn').on('click', function() {
			const id = window.currentLeadId || $('.inbox-item.active').data('id');
			if (!id) return;
			const btn = $(this);
			btn.text('✨ AI Scoring...').prop('disabled', true);
			apiRequest('/ai/complete', 'POST', { prompt: 'Score lead', context: { feature: 'lead_scorer', lead_id: id } }).done(res => {
				alert('AI Lead Score: ' + res.result);
			}).fail(() => alert('AI Scoring failed.'))
			.always(() => btn.text('✨ AI: Score').prop('disabled', false));
		});

		$(document).on('click', '.manual-enrich', function() {
			const id = $(this).data('id');
			const btn = $(this);
			btn.text('Enriching...').prop('disabled', true);
			apiRequest('/leads/' + id + '/enrich', 'POST').done(res => {
				alert('Lead enriched! Data found: ' + JSON.stringify(res.data));
				fetchLeads();
			}).fail(err => {
				alert('Enrichment failed: ' + (err.responseJSON ? err.responseJSON.message : 'Unknown error'));
			}).always(() => btn.text('Enrich').prop('disabled', false));
		});

		$(document).on('click', '.manual-audit', function() {
			const id = $(this).data('id');
			const btn = $(this);
			btn.text('Auditing...').prop('disabled', true);
			apiRequest('/leads/' + id + '/audit', 'POST').done(() => {
				alert('Audit request queued!');
			}).fail(() => alert('Audit failed.'))
			.always(() => btn.text('Audit').prop('disabled', false));
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

		$('#generateReportBtn').on('click', function() {
			const id = window.currentLeadId;
			if (!id) return;
			window.open(leadflowData.adminUrl + 'admin.php?page=leadflow-audit-report&lead_id=' + id, '_blank');
		});

		$('#saveProposalBtn').on('click', function() {
			const id = window.currentLeadId;
			const url = $('#proposalUrl').val();
			if (!id) return;

			const btn = $(this);
			btn.text('Saving...').prop('disabled', true);
			apiRequest('/leads/' + id, 'POST', { proposal_url: url }).done(() => {
				alert('Proposal URL saved!');
				fetchLeads();
			}).always(() => btn.text('Save Proposal').prop('disabled', false));
		});

		/**
		 * KANBAN VIEW
		 */
		function loadKanbanData() {
			apiRequest("/leads", "GET").done(function(leads) {
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

			apiRequest(endpoint, 'GET', ajaxData).done(function(data) {
				renderDiscoveryResults(data);
				$('#discoveryResults').fadeIn();
				if (!ajaxData.api_key && source === 'google') {
					$('#discoveryResults').prepend('<div class="notice notice-warning inline"><p><strong>Demo Mode:</strong> You are seeing simulated results because no Google Places API key is configured.</p></div>');
				}
			}).fail(() => alert('Discovery failed.'))
			.always(() => btn.text('Start Discovery').prop('disabled', false));
		});

		$('#clearDiscoveryResults').on('click', function() {
			$('#discoveryResultsBody').empty();
			$('#discoveryResults').fadeOut();
			window.currentDiscoveryResults = [];
		});

		function renderDiscoveryResults(leads) {
			const tbody = $('#discoveryResultsBody');
			tbody.empty();
			if (!leads || !leads.length) {
				tbody.append('<tr><td colspan="6" style="text-align:center;">No leads found. Check your API keys and parameters.</td></tr>');
				return;
			}
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
				btn.text('Imported!').removeClass('button-primary').prop('disabled', true);
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
				btn.text('Import Selected as CRM Leads').prop('disabled', false);
				$('.discovery-item-check:checked').closest('tr').css('opacity', 0.5).find('.import-lead').text('Imported!').prop('disabled', true);
			});
		});

		/**
		 * ANALYTICS & DASHBOARD
		 */
		function renderCharts() {
			apiRequest('/analytics/overview', 'GET').done(function(data) {
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
			});
		}

		if ($('#leadsStatusChart').length) {
			renderCharts();
			loadRecentActivity();
			loadCampaignStats();
			loadDailyPulse();
		}

		function loadDailyPulse() {
			apiRequest('/analytics/overview', 'GET').done(function(data) {
				const pulseTbody = $('#pulseTableBody');
				const queuePulse = $('#queuePulseStats');
				if (!pulseTbody.length || !data.daily_pulse) return;

				pulseTbody.empty();
				if (!data.daily_pulse.leads.length) {
					pulseTbody.append('<tr><td colspan="4" style="text-align:center;">All systems normal. No critical leads.</td></tr>');
				} else {
					data.daily_pulse.leads.forEach(lead => {
						pulseTbody.append(`
							<tr>
								<td><strong>${escapeHtml(lead.business_name)}</strong></td>
								<td><span class="status-badge">${lead.status}</span></td>
								<td>${escapeHtml(lead.reason)}</td>
								<td><button class="button button-small view-lead" data-id="${lead.id}">Fix Now</button></td>
							</tr>
						`);
					});
				}

				if (queuePulse.length) {
					const stats = data.daily_pulse.queue_stats;
					queuePulse.html(`
						Scraper: ${stats.scraper} pending | Outreach: ${stats.outreach} scheduled | Tasks: ${stats.tasks} pending
					`);
				}
			});
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
		 * CAMPAIGNS LIST
		 */
		function fetchCampaigns() {
			apiRequest('/campaigns').done(function(data) {
				const tbody = $('#campaignListBody');
				if (!tbody.length) return;
				tbody.empty();
				data.forEach(c => {
					tbody.append(`
						<tr>
							<td><strong>${escapeHtml(c.name)}</strong></td>
							<td><span class="status-badge">${c.status_filter}</span></td>
							<td><span class="status-badge ${c.is_active == 1 ? 'status-replied' : 'status-discovery'}">${c.is_active == 1 ? 'Active' : 'Paused'}</span></td>
							<td>${c.created_at}</td>
							<td>
								<button class="button button-small edit-campaign" data-id="${c.id}">Edit</button>
								<button class="button button-small toggle-campaign" data-id="${c.id}">${c.is_active == 1 ? 'Pause' : 'Resume'}</button>
								<button class="button button-small delete-campaign" data-id="${c.id}" style="color:red;">Delete</button>
							</td>
						</tr>
					`);
				});
			});
		}

		if ($('#campaignListBody').length) fetchCampaigns();

		$(document).on('click', '.toggle-campaign', function() {
			const id = $(this).data('id');
			const btn = $(this);
			const isActive = btn.text() === 'Pause' ? 0 : 1;
			apiRequest('/campaigns/' + id, 'POST', { is_active: isActive }).done(fetchCampaigns);
		});

		$(document).on('click', '.delete-campaign', function() {
			if (!confirm('Are you sure you want to delete this campaign?')) return;
			const id = $(this).data('id');
			apiRequest('/campaigns/' + id, 'DELETE').done(fetchCampaigns);
		});

		$(document).on('click', '.edit-campaign', function() {
			const id = $(this).data('id');
			apiRequest('/campaigns', 'GET', { id: id }).done(function(campaign) {
				$('#campaignId').val(campaign.id);
				$('#campaignName').val(campaign.name);
				$('#campaignStatusFilter').val(campaign.status_filter);
				$('#campaignStartHour').val(campaign.start_hour);
				$('#campaignEndHour').val(campaign.end_hour);
				$('#campaignSkipWeekends').prop('checked', campaign.skip_weekends == 1);

				const container = $('#sequenceSteps');
				container.empty();

				apiRequest('/templates').done(function(templates) {
					(campaign.steps || []).forEach((step, idx) => {
						const index = idx + 1;
						const selectedTemplates = step.template_ids ? step.template_ids.split(',') : [];

						container.append(`
							<div class="campaign-step-card chart-box" data-index="${index}" style="margin-bottom:15px; padding:15px;">
								<div style="float:right;">
									<span class="move-step-up" style="cursor:pointer; margin-right:10px;" title="Move Up">🔼</span>
									<span class="move-step-down" style="cursor:pointer; margin-right:10px;" title="Move Down">🔽</span>
									<span class="remove-step" style="cursor:pointer;" title="Remove Step">&times;</span>
								</div>
								<h4>Step <span class="step-num-display">${index}</span></h4>
								<p><label>Delay (Days)</label><br><input type="number" class="step-delay" value="${step.delay_days}"></p>
								<p><label>Type</label><br>
									<select class="step-type">
										<option value="email" ${step.step_type === 'email' ? 'selected' : ''}>Email</option>
										<option value="linkedin" ${step.step_type === 'linkedin' ? 'selected' : ''}>LinkedIn Connection</option>
										<option value="call" ${step.step_type === 'call' ? 'selected' : ''}>Phone Call</option>
									</select>
								</p>
								<div class="email-fields">
									<p><label>Templates for A/B Testing (Optional)</label><br>
										<select class="step-templates" multiple style="width:100%">
											${(templates || []).map(t => `<option value="${t.id}" ${selectedTemplates.includes(t.id.toString()) ? 'selected' : ''}>${escapeHtml(t.name)}</option>`).join('')}
										</select>
									</p>
									<p><label>Default Subject</label><br><input type="text" class="step-subject" value="${escapeHtml(step.subject)}" style="width:100%"></p>
									<p><label>Default Body</label><br><textarea class="step-body" rows="4" style="width:100%">${escapeHtml(step.body)}</textarea></p>
								</div>
							</div>
						`);
					});

					$('#campaignBuilderModal').fadeIn();
				});
			});
		});

		$('#createCampaignBtn').on('click', function(e) {
			e.preventDefault();
			$('#campaignId').val('');
			$('#campaignBuilderForm')[0].reset();
			$('#sequenceSteps').empty();
			$('#campaignBuilderModal').fadeIn();
		});

		/**
		 * SYSTEM LOGS & HEALTH
		 */
		function loadSystemLogs() {
			const tbody = $('#systemLogsBody');
			if (!tbody.length) return;
			apiRequest('/settings/logs', 'GET').done(function(logs) {
				tbody.empty();
				logs.forEach(log => {
					tbody.append(`<tr><td>${log.created_at}</td><td>${log.module}</td><td>${log.level}</td><td>${log.message}</td></tr>`);
				});
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
			.fail(() => alert('AI Connection failed.'))
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
		 * CAMPAIGNS & OUTREACH BUILDER
		 */
		$('#addStepBtn').on('click', function() {
			const container = $('#sequenceSteps');
			const index = container.find('.campaign-step-card').length + 1;
			apiRequest('/templates').done(function(templates) {
				const templateOptions = (templates || []).map(t => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('');
				container.append(`
					<div class="campaign-step-card chart-box" data-index="${index}" style="margin-bottom:15px; padding:15px;">
						<div style="float:right;">
							<span class="move-step-up" style="cursor:pointer; margin-right:10px;" title="Move Up">🔼</span>
							<span class="move-step-down" style="cursor:pointer; margin-right:10px;" title="Move Down">🔽</span>
							<span class="remove-step" style="cursor:pointer;" title="Remove Step">&times;</span>
						</div>
						<h4>Step <span class="step-num-display">${index}</span></h4>
						<p><label>Delay (Days)</label><br><input type="number" class="step-delay" value="${index === 1 ? 0 : 2}"></p>
						<p><label>Type</label><br>
							<select class="step-type">
								<option value="email">Email</option>
								<option value="linkedin">LinkedIn Connection</option>
								<option value="call">Phone Call</option>
							</select>
						</p>
						<div class="email-fields">
							<p><label>Templates for A/B Testing (Optional)</label><br>
								<select class="step-templates" multiple style="width:100%">${templateOptions}</select>
							</p>
							<p><label>Default Subject</label><br><input type="text" class="step-subject" style="width:100%"></p>
							<p><label>Default Body / Task Description</label><br><textarea class="step-body" rows="4" style="width:100%"></textarea></p>
						</div>
					</div>
				`);
			});
		});

		$(document).on('click', '.remove-step', function() {
			$(this).closest('.campaign-step-card').remove();
			reindexCampaignSteps();
		});

		$(document).on('click', '.move-step-up', function() {
			const card = $(this).closest('.campaign-step-card');
			card.prev('.campaign-step-card').before(card);
			reindexCampaignSteps();
		});

		$(document).on('click', '.move-step-down', function() {
			const card = $(this).closest('.campaign-step-card');
			card.next('.campaign-step-card').after(card);
			reindexCampaignSteps();
		});

		function reindexCampaignSteps() {
			$('.campaign-step-card').each(function(i) {
				$(this).data('index', i + 1);
				$(this).find('.step-num-display').text(i + 1);
			});
		}

		$('#campaignBuilderForm').on('submit', function(e) {
			e.preventDefault();
			const steps = [];
			$('.campaign-step-card').each(function() {
				steps.push({
					delay: $(this).find('.step-delay').val(),
					type: $(this).find('.step-type').val(),
					template_ids: $(this).find('.step-templates').val() ? $(this).find('.step-templates').val().join(',') : '',
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

			const id = $('#campaignId').val();
			const url = id ? '/campaigns/' + id : '/campaigns';

			apiRequest(url, 'POST', data).done(function() {
				alert('Campaign saved!');
				location.reload();
			}).fail(() => alert('Failed to save campaign.'));
		});

		/**
		 * INBOX & CONVERSATIONS
		 */
		function loadInbox() {
			if (!$('#inboxItems').length) return;
			apiRequest('/inbox').done(function(threads) {
				const list = $('#inboxItems');
				list.empty();
				if (!threads || !threads.length) {
					list.append('<div style="padding:20px; text-align:center; color:#64748b;">No replies yet. Outreach to get started!</div>');
					return;
				}
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
			if (leadId) {
				window.currentLeadId = leadId;
				openLeadModal(leadId);
			}
		});

		function openLeadModal(id) {
			apiRequest('/leads/' + id).done(function(lead) {
				$('#detailLeadName').text(lead.business_name);
				$('#proposalUrl').val(lead.proposal_url);
				$('#leadDetailModal').fadeIn();

				const audit = safeJsonParse(lead.audit_data);
				const sidebar = $('#detailLeadSidebar');
				sidebar.empty();

				if (audit.cms && audit.cms !== 'unknown') {
					sidebar.append(`
						<div class="audit-item chart-box" style="background:#f8fafc; padding:15px; border-radius:8px;">
							<p><strong>CMS:</strong> ${audit.cms}</p>
							<p><strong>SSL:</strong> ${audit.has_ssl ? '✅ Secure' : '❌ Unsecured'}</p>
							<p><strong>Mobile:</strong> ${audit.is_mobile_responsive ? '✅ Responsive' : '❌ Not Responsive'}</p>
							<p><strong>Load Time:</strong> ${audit.load_time}s</p>
							<button class="button button-small manual-audit" data-id="${id}" style="margin-top:10px;">Regenerate Audit</button>
						</div>
					`);
				} else {
					sidebar.append('<p>No audit data yet.</p><button class="button button-small manual-audit" data-id="${id}">Start Audit Now</button>');
				}

				updateLeadTimeline(lead);
				loadThreadInModal(id);
			});
		}

		function updateLeadTimeline(lead) {
			const steps = $('.timeline-step');
			const status = lead.status;
			const audit = safeJsonParse(lead.audit_data);

			steps.find('.step-icon').css({'background': '#fff', 'border-color': '#cbd5e1'});

			// Step 1: Discovered (Always active)
			steps.filter('[data-step="New"]').find('.step-icon').css({'background': '#6366f1', 'color': '#fff', 'border-color': '#6366f1'});

			// Step 2: Audited
			if (audit.cms) {
				steps.filter('[data-step="Audited"]').find('.step-icon').css({'background': '#6366f1', 'color': '#fff', 'border-color': '#6366f1'});
			}

			// Step 3: Contacted
			if (['Contacted', 'Replied', 'Qualified', 'Proposal Sent', 'Closed Won'].includes(status)) {
				steps.filter('[data-step="Contacted"]').find('.step-icon').css({'background': '#6366f1', 'color': '#fff', 'border-color': '#6366f1'});
			}

			// Step 4: Qualified
			if (['Qualified', 'Proposal Sent', 'Closed Won'].includes(status)) {
				steps.filter('[data-step="Qualified"]').find('.step-icon').css({'background': '#10b981', 'color': '#fff', 'border-color': '#10b981'});
			}
		}

		function loadThreadInModal(leadId) {
			apiRequest('/leads/' + leadId + '/activity').done(function(data) {
				const container = $('#detailLeadThread');
				container.empty();
				const activities = [...(data.notes||[]), ...(data.emails||[])].sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
				if (!activities.length) {
					container.append('<div class="inbox-placeholder">No activity yet.</div>');
					return;
				}
				activities.forEach(act => {
					const isOutbound = act.subject || (act.content && (act.content.startsWith('Outbound') || act.content.startsWith('Sent')));
					container.append(`<div class="message-bubble ${isOutbound ? 'outbound' : 'inbound'}" style="font-size:0.8rem; margin-bottom:10px; padding:10px; border-radius:8px; background:${isOutbound?'#f0f7ff':'#fff'}; border:1px solid #eee;">
						<strong>${act.created_at}</strong><br>${escapeHtml(act.content || act.subject)}
					</div>`);
				});
				container.scrollTop(container[0].scrollHeight);
			});
		}

		$('.ai-reply-btn').on('click', function() {
			const leadId = $('.inbox-item.active').data('id');
			if (!leadId) return;
			const btn = $(this);
			btn.text('✨ AI Drafting...').prop('disabled', true);

			apiRequest('/ai/complete', 'POST', { prompt: 'Suggest reply', context: { feature: 'reply_suggestion', lead_id: leadId } }).done(res => {
				const suggestion = res.result || 'AI was unable to generate a suggestion.';
				$('#aiDraftContent').html(escapeHtml(suggestion).replace(/\n/g, '<br>'));
				$('#aiDraftBox').fadeIn();
			}).fail(() => alert('AI suggestion failed.'))
			.always(() => btn.text('✨ AI: Re-Draft').prop('disabled', false));
		});

		$('#useAiDraftBtn').on('click', function() {
			$('#replyText').val($('#aiDraftContent').text().replace(/<br>/g, '\n'));
			$('#aiDraftBox').fadeOut();
		});

		$('#sendReplyBtn').on('click', function() {
			const leadId = $('.inbox-item.active').data('id');
			const message = $('#replyText').val();
			if (!leadId || !message) return;

			$(this).prop('disabled', true).text('Sending...');
			apiRequest('/inbox/reply', 'POST', { lead_id: leadId, message: message }).done(() => {
				$('#replyText').val('');
				loadThread(leadId);
			}).fail(() => alert('Failed to send reply.'))
			.always(() => {
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
				if (!tasks || !tasks.length) {
					tbody.append('<tr><td colspan="4" style="text-align:center;">No pending tasks. Great job!</td></tr>');
					return;
				}
				tasks.forEach(task => {
					tbody.append(`
						<tr>
							<td>${escapeHtml(task.business_name)}</td>
							<td><span class="status-badge status-contacted">${task.status}</span></td>
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

		/**
		 * GLOBAL TASKS PAGE
		 */
		function fetchGlobalTasks() {
			const status = $('#taskStatusFilter').val();
			apiRequest('/tasks', 'GET', { status: status }).done(function(tasks) {
				const tbody = $('#globalTasksBody');
				if (!tbody.length) return;
				tbody.empty();
				if (tasks.length === 0) {
					tbody.append('<tr><td colspan="6">No tasks found.</td></tr>');
					return;
				}
				tasks.forEach(t => {
					tbody.append(`
						<tr>
							<td>${t.due_date}</td>
							<td><strong>${t.business_name}</strong></td>
							<td><span class="status-badge status-contacted">${t.task_type}</span></td>
							<td>${t.description}</td>
							<td>${t.assignee_name || 'Unassigned'}</td>
							<td>
								<button class="button button-small toggle-task-global" data-id="${t.id}" data-status="${t.status}">${t.status === 'pending' ? 'Complete' : 'Re-open'}</button>
								<button class="button button-small view-lead" data-id="${t.lead_id}">Go to Lead</button>
							</td>
						</tr>
					`);
				});
			});
		}

		if ($('#globalTasksBody').length) fetchGlobalTasks();
		$('#applyTaskFilters').on('click', fetchGlobalTasks);

		$(document).on('click', '.toggle-task-global', function() {
			const id = $(this).data('id');
			const newStatus = $(this).data('status') === 'pending' ? 'completed' : 'pending';
			apiRequest('/tasks/' + id, 'POST', { status: newStatus }).done(fetchGlobalTasks);
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

		// Initializations
		$('.close-modal').on('click', function() { $('.leadflow-modal').fadeOut(); });

	});
})(jQuery);
