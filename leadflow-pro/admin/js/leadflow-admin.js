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

		// Toggle Email Provider Settings
		$('select[name="leadflow_email_provider"]').on('change', function() {
			const provider = $(this).val();
			if (provider === 'gmail') {
				$('.gmail-only').show();
				$('.smtp-only').hide();
			} else {
				$('.gmail-only').hide();
				$('.smtp-only').show();
			}
		}).trigger('change');

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
					// Render AI Usage Bars
					const usageContainer = $('#aiUsageBars');
					usageContainer.empty();
					data.ai_usage.forEach(u => {
						const budget = leadflowData.budgets[u.provider] || 50000;
						const percent = Math.min((u.total_tokens / budget) * 100, 100);
						usageContainer.append(`
							<div style="margin-bottom:15px;">
								<div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:5px;">
									<span>${u.provider.toUpperCase()}</span>
									<span>${Number(u.total_tokens).toLocaleString()} / ${Number(budget).toLocaleString()} tokens</span>
								</div>
								<div style="background:#eee; height:10px; border-radius:5px; overflow:hidden;">
									<div style="background:var(--leadflow-primary); width:${percent}%; height:100%;"></div>
								</div>
							</div>
						`);
					});

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
					window.currentLead = lead;
					if (lead && lead.audit_data) {
						const audit = safeJsonParse(lead.audit_data);
						const socialLinks = safeJsonParse(lead.social_links, {});
						let html = `
							<div class="audit-summary">
								<p><strong>Website:</strong> <a href="${escapeHtml(lead.website_url)}" target="_blank">${escapeHtml(lead.website_url)}</a></p>
								<ul class="audit-checklist">
									<li class="${audit.has_ssl ? 'success' : 'danger'}">${audit.has_ssl ? '✅ SSL Secure' : '❌ No SSL'}</li>
									<li class="${audit.is_mobile_responsive ? 'success' : 'danger'}">${audit.is_mobile_responsive ? '✅ Mobile Friendly' : '❌ Not Mobile Responsive'}</li>
									<li class="${audit.outdated_design ? 'danger' : 'success'}">${audit.outdated_design ? '❌ Outdated Design' : '✅ Modern Design'}</li>
									<li>⏱️ Load Time: ${audit.load_time || 0}s</li>
								</ul>
								<p><strong>Social Links:</strong></p>
								<div class="social-pills">
							${Object.entries(socialLinks).map(([platform, link]) => `<a href="${escapeHtml(link)}" target="_blank" class="social-pill ${escapeHtml(platform.replace('.com', ''))}">${escapeHtml(platform.replace('.com', ''))}</a>`).join('')}
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

		// AI Suggest Reply
		$(document).on('click', '.ai-reply-btn', function() {
			const btn = $(this);
			const leadId = $('.inbox-item.active').data('lead-id') || (window.currentLead ? window.currentLead.id : null);
			if (!leadId) return;

			btn.text('Thinking...').prop('disabled', true);

			// Pull last inbound email content from thread
			let lastInbound = $('#inboxThread .thread-item.email').last().find('.thread-content').text();
			if (!lastInbound) {
				lastInbound = 'I am interested in your services, tell me more.';
			}

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'reply_suggestion',
						lead_id: leadId,
						inbound_text: lastInbound
					}
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					$('#replyText').val(response.result);
					btn.text('✨ AI: Suggest Reply').prop('disabled', false);
				},
				error: function() {
					alert('AI reply generation failed.');
					btn.text('✨ AI: Suggest Reply').prop('disabled', false);
				}
			});
		});

		// Edit Lead Modal Trigger
		$(document).on('click', '.edit-lead-btn', function() {
			const id = $(this).data('id');
			$.ajax({
				url: apiUrl + '/leads',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(leads) {
					const lead = leads.find(l => l.id == id);
					if (lead) {
						$('#editLeadId').val(lead.id);
						$('#editFirstName').val(lead.first_name);
						$('#editBusinessName').val(lead.business_name);
						$('#editWebsiteUrl').val(lead.website_url);
						$('#editEmail').val(lead.email);
						$('#editPhone').val(lead.phone);
						$('#editLeadModal').fadeIn();
					}
				}
			});
		});

		$('#editLeadForm').on('submit', function(e) {
			e.preventDefault();
			const id = $('#editLeadId').val();
			const data = $(this).serialize();

			$.ajax({
				url: apiUrl + '/leads/' + id,
				method: 'POST', // EDITABLE
				data: data,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					$('#editLeadModal').fadeOut();
					fetchLeads();
					alert('Lead updated successfully!');
				},
				error: function(err) {
					alert('Error: ' + err.responseJSON.message);
				}
			});
		});

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

		function escapeHtml(text) {
			if (!text) return '';
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		}

		function safeJsonParse(json, defaultVal = {}) {
			try {
				return json ? JSON.parse(json) : defaultVal;
			} catch (e) {
				return defaultVal;
			}
		}

		function renderLeadTable(leads) {
			const tbody = $('#leadTableBody');
			tbody.empty();

			const users = leadflowData.users || [];

			leads.forEach(lead => {
				const audit = safeJsonParse(lead.audit_data);
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

		// Initial Load
		if ($('#leadTableBody').length) {
			fetchLeads();
		}

		// View Lead Detail
		$(document).on('click', '.view-lead', function() {
			const leadId = $(this).data('id');
			$('#leadDetailModal').fadeIn();

			// Load activity into the modal thread
			$.ajax({
				url: apiUrl + '/leads/' + leadId + '/activity',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const thread = $('#detailLeadThread');
					thread.empty();
					const items = [...data.notes, ...data.emails];
					items.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
					items.forEach(item => {
						const type = item.subject ? 'email' : 'note';
						const content = item.body || item.content || item.subject;
						thread.append(`<div class="thread-item ${type}"><div class="thread-meta">${item.created_at}</div><div class="thread-content">${content}</div></div>`);
					});
				}
			});

			// Load lead data into the modal sidebar
			$.ajax({
				url: apiUrl + '/leads',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(leads) {
					const lead = leads.find(l => l.id == leadId);
					if (!lead) return;
					window.currentLead = lead; // Update global context for AI buttons
					$('#detailLeadName').text(escapeHtml(lead.business_name));

					if (lead.audit_data) {
						const audit = safeJsonParse(lead.audit_data);
						let html = `
							<div class="audit-summary">
								<div class="audit-score-gauge" style="text-align:center; margin-bottom:20px;">
									<div style="font-size:3rem;">${audit.has_ssl && audit.is_mobile_responsive ? '✅' : '⚠️'}</div>
									<strong>Audit Status</strong>
								</div>
								<ul class="audit-checklist">
									<li class="${audit.has_ssl ? 'success' : 'danger'}">${audit.has_ssl ? '✅ SSL Certificate Found' : '❌ No SSL (Security Risk)'}</li>
									<li class="${audit.is_mobile_responsive ? 'success' : 'danger'}">${audit.is_mobile_responsive ? '✅ Mobile Responsive' : '❌ Not Mobile Friendly'}</li>
									<li class="${audit.has_contact_form ? 'success' : 'danger'}">${audit.has_contact_form ? '✅ Contact Form Detected' : '❌ No Contact Form Found'}</li>
									<li class="${audit.outdated_design ? 'danger' : 'success'}">${audit.outdated_design ? '❌ Outdated Design (Old Copyright)' : '✅ Modern Design Signals'}</li>
									<li style="font-weight:bold; border-top:1px solid #eee; padding-top:10px; margin-top:10px;">⏱️ Response Time: ${audit.load_time || 0}s</li>
								</ul>
								<p><button class="button button-small manual-audit" data-id="${lead.id}">🔄 Re-Run Audit</button></p>
							</div>`;
						$('#detailLeadSidebar').html(html);
					}

					$('#detailAiTools button').data('lead-id', leadId);
					$('#proposalUrl').val(lead.proposal_url || '');
					$('#saveProposalBtn').data('id', leadId);
				}
			});
		});

		// Save Proposal URL
		$(document).on('click', '#saveProposalBtn', function() {
			const id = $(this).data('id');
			const url = $('#proposalUrl').val();
			const btn = $(this);

			btn.prop('disabled', true).text('Saving...');

			$.ajax({
				url: apiUrl + '/leads/' + id,
				method: 'POST',
				data: { proposal_url: url },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Proposal URL saved!');
					btn.prop('disabled', false).text('Save Proposal');
				}
			});
		});

		// CSV Import
		$('#csvImportForm').on('submit', function(e) {
			e.preventDefault();
			const formData = new FormData();
			formData.append('leads_csv', $(this).find('input[name="leads_csv"]')[0].files[0]);

			const btn = $(this).find('button');
			btn.text('Importing...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/discovery/import-csv',
				method: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					alert('Successfully imported ' + response.count + ' leads!');
					btn.text('Upload and Import').prop('disabled', false);
					fetchLeads();
				},
				error: function() {
					alert('CSV Import failed.');
					btn.text('Upload and Import').prop('disabled', false);
				}
			});
		});

		function fetchCampaigns() {
			$.ajax({
				url: apiUrl + '/campaigns',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(campaigns) {
					const tbody = $('#campaignListBody');
					tbody.empty();
					campaigns.forEach(c => {
						tbody.append(`
							<tr>
								<td><strong>${escapeHtml(c.name)}</strong></td>
								<td>${escapeHtml(c.status_filter)}</td>
								<td><span class="status-badge ${c.is_active == 1 ? 'status-replied' : 'status-new'}">${c.is_active == 1 ? 'Active' : 'Paused'}</span></td>
								<td>${escapeHtml(c.created_at)}</td>
								<td>
									<button class="button button-small toggle-campaign" data-id="${c.id}" data-active="${c.is_active}">${c.is_active == 1 ? 'Pause' : 'Activate'}</button>
									<button class="button button-small edit-campaign-btn" data-id="${c.id}">Edit</button>
									<button class="button button-small delete-campaign" data-id="${c.id}" style="color:#d63638;">Delete</button>
								</td>
							</tr>
						`);
					});
				}
			});
		}

		if ($('#campaignListBody').length) {
			fetchCampaigns();
		}

		// Campaign Actions (Toggle/Delete)
		$(document).on('click', '.toggle-campaign', function() {
			const id = $(this).data('id');
			const isActive = $(this).data('active');

			$.ajax({
				url: apiUrl + '/campaigns/' + id,
				method: 'POST',
				data: { is_active: isActive == 1 ? 0 : 1 },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					fetchCampaigns();
				}
			});
		});

		$(document).on('click', '.delete-campaign', function() {
			const id = $(this).data('id');
			if (!confirm('Are you sure you want to PERMANENTLY delete this campaign and all its steps?')) return;

			$.ajax({
				url: apiUrl + '/campaigns/' + id,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					fetchCampaigns();
				}
			});
		});

		// Select All Leads
		$('#selectAllLeads').on('change', function() {
			$('.lead-checkbox').prop('checked', $(this).prop('checked'));
		});

		// Apply Bulk Status
		$('#applyBulkStatus').on('click', function() {
			const selectedIds = [];
			$('.lead-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});
			const newStatus = $('#bulkStatusUpdate').val();

			if (selectedIds.length === 0 || !newStatus) return;

			selectedIds.forEach(id => {
				updateLeadStatus(id, newStatus);
			});
			alert('Bulk status update complete!');
			fetchLeads();
		});

		// Bulk Audit Leads
		$('#bulkAuditLeads').on('click', function() {
			const selectedIds = [];
			$('.lead-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			if (selectedIds.length === 0) return;

			const btn = $(this);
			btn.text('Queueing...').prop('disabled', true);

			let processed = 0;
			selectedIds.forEach(id => {
				$.ajax({
					url: apiUrl + '/leads/' + id + '/audit',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', nonce);
					},
					success: function() {
						processed++;
						if (processed === selectedIds.length) {
							alert('Bulk audit queued for ' + selectedIds.length + ' leads!');
							btn.text('Bulk Audit').prop('disabled', false);
						}
					}
				});
			});
		});

		// Bulk Delete Leads
		$('#bulkDeleteLeads').on('click', function() {
			const selectedIds = [];
			$('.lead-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			if (selectedIds.length === 0) return;
			if (!confirm('Permanently delete ' + selectedIds.length + ' leads?')) return;

			let processed = 0;
			selectedIds.forEach(id => {
				$.ajax({
					url: apiUrl + '/leads/' + id,
					method: 'DELETE',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', nonce);
					},
					success: function() {
						processed++;
						if (processed === selectedIds.length) {
							alert('Bulk deletion complete!');
							fetchLeads();
						}
					}
				});
			});
		});

		// AI Score Lead
		$(document).on('click', '.ai-score-btn', function() {
			const btn = $(this);
			const leadId = btn.data('lead-id');
			const lead = window.currentLead;

			if (!lead) return;

			btn.text('Scoring...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'lead_scorer',
						lead_data: lead,
						audit_data: safeJsonParse(lead.audit_data)
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

		// Individual Delete Lead (from table row)
		$(document).on('click', '.delete-lead-btn-row', function() {
			if (!confirm('Are you sure you want to PERMANENTLY delete this lead and all its data?')) return;

			const leadId = $(this).data('id');
			const btn = $(this);
			btn.prop('disabled', true);

			$.ajax({
				url: apiUrl + '/leads/' + leadId,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					btn.closest('tr').fadeOut(function() {
						$(this).remove();
					});
				}
			});
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

		// AI Write Personalized Email
		$(document).on('click', '.ai-write-personalized-btn', function() {
			const btn = $(this);
			const lead = window.currentLead;

			if (!lead) return;

			btn.text('Writing...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'email_writer',
						lead_data: lead,
						audit_data: safeJsonParse(lead.audit_data)
					}
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(response) {
					$('#replyText').val(response.result);
					btn.text('✨ AI: Personalized Email').prop('disabled', false);
				}
			});
		});

		// AI Summarize Audit
		$(document).on('click', '.ai-summarize-btn', function() {
			const btn = $(this);
			const leadId = btn.data('lead-id');
			const lead = window.currentLead;

			if (!lead || !lead.audit_data) {
				alert('No audit data to summarize.');
				return;
			}

			btn.text('Summarizing...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/ai/complete',
				method: 'POST',
				data: {
					context: {
						feature: 'audit_insight',
						audit_results: safeJsonParse(lead.audit_data)
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
			fetchRecentActivity();
		}

		function fetchRecentActivity() {
			$.ajax({
				url: apiUrl + '/analytics/activity',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const tbody = $('#recentActivityBody');
					tbody.empty();
					data.forEach(act => {
						tbody.append(`
							<tr>
								<td>${escapeHtml(act.activity)}</td>
								<td><strong>${escapeHtml(act.lead)}</strong></td>
								<td>${escapeHtml(act.created_at)}</td>
							</tr>
						`);
					});
				}
			});
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

					if ($('#leadsSourceChart').length) {
						const sourceLabels = data.source_counts.map(s => s.source);
						const sourceValues = data.source_counts.map(s => s.count);

						new Chart(document.getElementById('leadsSourceChart'), {
							type: 'bar',
							data: {
								labels: sourceLabels,
								datasets: [{
									label: 'Leads',
									data: sourceValues,
									backgroundColor: '#6366f1'
								}]
							}
						});
					}

					// Update KPI values if elements exist
					if ($('.leadflow-kpi-grid').length) {
						$('.kpi-card:nth-child(3) .kpi-value').text( (data.metrics.sent > 0 ? Math.round((data.metrics.opened / data.metrics.sent) * 100) : 0) + '%' );
						$('#topTemplateName').text(data.metrics.top_template || 'None yet');
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

		function loadSavedSearches() {
			$.ajax({
				url: apiUrl + '/discovery/saved-searches',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const tbody = $('#savedSearchesBody');
					tbody.empty();
					data.forEach(s => {
						tbody.append(`
							<tr>
								<td><strong>${escapeHtml(s.name)}</strong></td>
								<td>${escapeHtml(s.source)}</td>
								<td>${escapeHtml(s.keyword)}</td>
								<td>${escapeHtml(s.location || '-')}</td>
								<td>
									<button class="button button-small run-saved-search" data-source="${s.source}" data-keyword="${s.keyword}" data-location="${s.location}">Run</button>
									<button class="button button-small delete-saved-search" data-id="${s.id}" style="color:#d63638;">Delete</button>
								</td>
							</tr>
						`);
					});
				}
			});
		}

		if ($('#savedSearchesBody').length) {
			loadSavedSearches();
		}

		$('#saveSearchBtn').on('click', function() {
			const name = prompt('Enter a name for this search:');
			if (!name) return;

			const data = {
				name: name,
				source: $('#discoverySource').val(),
				keyword: $('#discoveryKeyword').val(),
				location: $('#discoveryLocation').val()
			};

			$.ajax({
				url: apiUrl + '/discovery/saved-searches',
				method: 'POST',
				data: data,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Search parameters saved!');
					loadSavedSearches();
				}
			});
		});

		$(document).on('click', '.delete-saved-search', function() {
			const id = $(this).data('id');
			if (!confirm('Delete this saved search?')) return;

			$.ajax({
				url: apiUrl + '/discovery/saved-searches/' + id,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					loadSavedSearches();
				}
			});
		});

		$(document).on('click', '.run-saved-search', function() {
			const source = $(this).data('source');
			const keyword = $(this).data('keyword');
			const location = $(this).data('location');

			$('#discoverySource').val(source);
			$('#discoveryKeyword').val(keyword);
			$('#discoveryLocation').val(location);

			$('.leadflow-discovery .tab-btn').removeClass('active');
			$(`.leadflow-discovery .tab-btn[data-source="${source}"]`).addClass('active').trigger('click');

			$('#discoverySearchForm').trigger('submit');
		});

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
				const row = $(`
					<tr>
						<th class="check-column"><input type="checkbox" class="discovery-item-check" value="${index}"></th>
						<td><strong>${escapeHtml(lead.business_name)}</strong></td>
						<td><a href="${escapeHtml(lead.website_url)}" target="_blank">${escapeHtml(lead.website_url)}</a></td>
						<td>${escapeHtml(lead.phone)}</td>
						<td><span class="status-badge status-discovery">${escapeHtml(lead.lead_source)}</span></td>
						<td>
							<button class="button button-small import-lead" data-index="${index}">Import</button>
						</td>
					</tr>
				`);
				tbody.append(row);
			});

			// Store current discovery results globally for easy import
			window.currentDiscoveryResults = leads;
		}

		// Export Discovery Results to CSV
		$('#exportDiscoveryResults').on('click', function() {
			const leads = window.currentDiscoveryResults;
			if (!leads || leads.length === 0) return;

			let csv = 'Business Name,Website,Phone,Source\n';
			leads.forEach(l => {
				csv += `"${l.business_name}","${l.website_url}","${l.phone}","${l.lead_source}"\n`;
			});

			const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
			const link = document.createElement('a');
			const url = URL.createObjectURL(blob);
			link.setAttribute('href', url);
			link.setAttribute('download', 'leadflow_discovery_export.csv');
			link.style.visibility = 'hidden';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		});

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

		// Create/Edit Campaign
		$('#createCampaignBtn').on('click', function(e) {
			e.preventDefault();
			$('#campaignId').val('');
			$('#campaignBuilderForm')[0].reset();
			$('#sequenceSteps').empty();
			$('#addStepBtn').trigger('click'); // Add first step
			$('#campaignBuilderModal').fadeIn();
		});

		$(document).on('click', '.edit-campaign-btn', function() {
			const id = $(this).data('id');
			$.ajax({
				url: apiUrl + '/campaigns',
				method: 'GET',
				data: { id: id },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(c) {
					$('#campaignId').val(c.id);
					$('#campaignName').val(c.name);
					$('#campaignStatusFilter').val(c.status_filter);
					$('#sequenceSteps').empty();

					(c.steps || []).forEach((step, index) => {
						addStepToBuilder(step, index + 1);
					});

					$('#campaignBuilderModal').fadeIn();
				}
			});
		});

		$('#campaignBuilderForm').on('submit', function(e) {
			e.preventDefault();
			const campaignId = $('#campaignId').val();
			const steps = [];
			$('.step-card').each(function(index) {
				steps.push({
					order: index + 1,
					type: $(this).find('.step-type').val(),
					template_id: $(this).find('.template-selector').val() || null,
					subject: $(this).find('.step-subject').val(),
					body: $(this).find('.step-body').val(),
					delay: $(this).find('input[type="number"]').val()
				});
			});

			const data = {
				name: $('#campaignName').val(),
				status_filter: $('#campaignStatusFilter').val(),
				steps: steps
			};

			const url = campaignId ? apiUrl + '/campaigns/' + campaignId : apiUrl + '/campaigns';

			$.ajax({
				url: url,
				method: 'POST',
				data: JSON.stringify(data),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					$('#campaignBuilderModal').fadeOut();
					alert(campaignId ? 'Campaign updated!' : 'Campaign created and activated!');
					location.reload();
				}
			});
		});

		function addStepToBuilder(data = {}, count = null) {
			const stepCount = count || $('.step-card').length + 1;
			const newStep = `
				<div class="step-card">
					<div style="float:right;">
						<select class="template-selector" style="font-size:0.75rem;">
							<option value="">Load Template...</option>
						</select>
					</div>
					<h4>Step ${stepCount}</h4>
					<p><label>Step Type</label><br>
						<select name="step[${stepCount}][type]" class="step-type">
							<option value="email" ${data.step_type === 'email' ? 'selected' : ''}>Email</option>
							<option value="linkedin" ${data.step_type === 'linkedin' ? 'selected' : ''}>LinkedIn Connection/Message</option>
							<option value="facebook" ${data.step_type === 'facebook' ? 'selected' : ''}>Facebook Group Outreach</option>
						</select>
					</p>
					<div class="email-fields" style="${data.step_type !== 'email' && data.step_type ? 'display:none;' : ''}">
						<p><label>Subject</label><br><input type="text" name="step[${stepCount}][subject]" class="step-subject" value="${data.subject || 'Follow up ' + stepCount}"></p>
						<button type="button" class="button ai-subject-btn">✨ AI: Generate Subject</button>
					</div>
					<p><label>Delay (Days)</label><br><input type="number" name="step[${stepCount}][delay]" value="${data.delay_days || 3}"></p>
					<p><label>Message Body</label><br><textarea name="step[${stepCount}][body]" class="step-body" rows="5" style="width:100%;">${data.body || ''}</textarea></p>
					<div style="display:flex; gap:10px; justify-content: space-between;">
						<div>
							<button type="button" class="button ai-writer-btn">✨ AI: Write this for me</button>
							<button type="button" class="button step-preview-btn">👁️ Preview</button>
						</div>
						<div>
							<button type="button" class="button move-step-up">↑</button>
							<button type="button" class="button move-step-down">↓</button>
							<button type="button" class="button remove-step-btn" style="color:#d63638;">Delete Step</button>
						</div>
					</div>
				</div>
			`;
			$('#sequenceSteps').append(newStep);
			loadTemplateOptions();
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
		$(document).on('click', '.ai-writer-btn', function() {
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
					<p><label>Message Body</label><br><textarea name="step[${stepCount}][body]" class="step-body" rows="5" style="width:100%;"></textarea></p>
					<div style="display:flex; gap:10px; justify-content: space-between;">
						<div>
							<button type="button" class="button ai-writer-btn">✨ AI: Write this for me</button>
							<button type="button" class="button step-preview-btn">👁️ Preview</button>
						</div>
						<div>
							<button type="button" class="button move-step-up">↑</button>
							<button type="button" class="button move-step-down">↓</button>
							<button type="button" class="button remove-step-btn" style="color:#d63638;">Delete Step</button>
						</div>
					</div>
				</div>
			`;
			$('#sequenceSteps').append(newStep);
		});

		// Remove Step
		$(document).on('click', '.remove-step-btn', function() {
			if (confirm('Are you sure you want to remove this step?')) {
				$(this).closest('.step-card').fadeOut(function() {
					$(this).remove();
					// Re-index steps
					$('.step-card').each(function(index) {
						$(this).find('h4').text('Step ' + (index + 1));
					});
				});
			}
		});

		// Move Step Up/Down
		$(document).on('click', '.move-step-up', function() {
			const card = $(this).closest('.step-card');
			card.prev('.step-card').before(card);
			reindexSteps();
		});

		// Load templates into selectors
		function loadTemplateOptions() {
			$.get(apiUrl + '/templates', function(templates) {
				const selectors = $('.template-selector');
				selectors.each(function() {
					const sel = $(this);
					if (sel.find('option').length > 1) return;
					templates.forEach(t => {
						sel.append(`<option value="${t.id}" data-subject="${escapeHtml(t.subject)}" data-body="${escapeHtml(t.body)}">${escapeHtml(t.name)}</option>`);
					});
				});
			});
		}

		$(document).on('change', '.template-selector', function() {
			const opt = $(this).find('option:selected');
			if (!opt.val()) return;
			const card = $(this).closest('.step-card');
			card.find('.step-subject').val(opt.data('subject'));
			card.find('.step-body').val(opt.data('body'));
		});

		$(document).on('click', '.move-step-down', function() {
			const card = $(this).closest('.step-card');
			card.next('.step-card').after(card);
			reindexSteps();
		});

		function reindexSteps() {
			$('.step-card').each(function(index) {
				$(this).find('h4').text('Step ' + (index + 1));
			});
		}

		// Step Preview
		$(document).on('click', '.step-preview-btn', function() {
			const card = $(this).closest('.step-card');
			const subject = card.find('.step-subject').val() || '';
			const body = card.find('.step-body').val() || '';

			const sampleData = {
				'{{first_name}}': 'John',
				'{{business_name}}': 'Acme Corp',
				'{{website}}': 'https://acme.com',
				'{{city}}': 'Chicago',
				'{{audit_flag}}': 'I noticed your site doesn\'t have SSL...'
			};

			let previewSubj = subject;
			let previewBody = body;

			for (const [token, val] of Object.entries(sampleData)) {
				previewSubj = previewSubj.replaceAll(token, `<strong>${val}</strong>`);
				previewBody = previewBody.replaceAll(token, `<strong>${val}</strong>`);
			}

			alert('PREVIEW:\n\nSubject: ' + previewSubj.replace(/<\/?[^>]+(>|$)/g, "") + '\n\n' + previewBody.replace(/<\/?[^>]+(>|$)/g, ""));
		});

		// AI Subject Line
		$(document).on('click', '.ai-subject-btn', function() {
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
									<strong>${escapeHtml(lead.business_name)}</strong>
									<span class="score-pill score-${getScoreColor(score)}">${score}%</span>
								</div>
								<p class="kanban-item-url">${escapeHtml(lead.website_url) || ''}</p>
								<p class="kanban-item-email">${escapeHtml(lead.email) || 'No email'}</p>
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

		// Inline Status Update
		$(document).on('change', '.inline-status-update', function() {
			const leadId = $(this).data('id');
			const newStatus = $(this).val();
			updateLeadStatus(leadId, newStatus);
			alert('Status updated to ' + newStatus);
		});

		// Add Tag to Lead
		$(document).on('change', '#addTagSelect', function() {
			const tagId = $(this).val();
			const leadId = window.currentLead ? window.currentLead.id : (window.selectedInboxLeadId || null);
			if (!tagId || !leadId) return;

			$.ajax({
				url: apiUrl + '/leads/' + leadId + '/tags',
				method: 'POST',
				data: { tags: [tagId] }, // In production, this would append
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Tag added successfully.');
					if (window.currentLead) {
						loadLeadSidebar(leadId);
					}
				}
			});
		});

		// Inline Assignee Update
		$(document).on('change', '.inline-assignee-update', function() {
			const leadId = $(this).data('id');
			const assigneeId = $(this).val();
			$.ajax({
				url: apiUrl + '/leads/' + leadId,
				method: 'POST',
				data: { assigned_to: assigneeId },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Lead reassigned successfully.');
				}
			});
		});

		// Opt-out Lead
		$(document).on('click', '.opt-out-lead', function() {
			const email = $(this).data('email');
			if (!email) return;
			if (!confirm('Add ' + email + ' to suppression list?')) return;

			$.ajax({
				url: apiUrl + '/compliance/opt-out',
				method: 'POST',
				data: { email: email },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Lead ' + email + ' added to suppression list.');
					fetchLeads();
				}
			});
		});

		function loadScraperQueue() {
			$.ajax({
				url: apiUrl + '/scraper/queue',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function(data) {
					const tbody = $('#scraperQueueBody');
					tbody.empty();
					if (data.length === 0) {
						tbody.append('<tr><td colspan="2">Queue is empty.</td></tr>');
					}
					data.forEach(q => {
						tbody.append(`<tr><td>${q.status}</td><td>${q.count}</td></tr>`);
					});
				}
			});
		}

		if ($('#scraperQueueBody').length) {
			loadScraperQueue();
		}

		$('#triggerScraperBtn').on('click', function() {
			const btn = $(this);
			btn.text('Processing...').prop('disabled', true);
			$.ajax({
				url: apiUrl + '/scraper/queue',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Batch processing triggered!');
					btn.text('Process 5 Jobs Now').prop('disabled', false);
					loadScraperQueue();
				}
			});
		});

		// Manual Audit Trigger
		$(document).on('click', '.manual-audit', function() {
			const leadId = $(this).data('id');
			const btn = $(this);
			btn.text('Auditing...').prop('disabled', true);
			$.ajax({
				url: apiUrl + '/leads/' + leadId + '/audit',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Audit completed and AI insights updated!');
					btn.text('Audit').prop('disabled', false);
					fetchLeads();
				}
			});
		});

		// Send Test Email
		$('#sendTestEmail').on('click', function() {
			const email = $('#testEmailAddr').val();
			if (!email) return;

			const btn = $(this);
			btn.text('Sending...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/settings/test-email',
				method: 'POST',
				data: { email: email },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Test email sent successfully! Please check your inbox.');
					btn.text('Send Test Email').prop('disabled', false);
				},
				error: function(err) {
					alert('Failed to send test email: ' + err.responseJSON.message);
					btn.text('Send Test Email').prop('disabled', false);
				}
			});
		});

		// Activate Demo License
		$('#activateDemoLicense').on('click', function() {
			const btn = $(this);
			btn.text('Activating...').prop('disabled', true);

			$.ajax({
				url: apiUrl + '/license/activate-demo',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				},
				success: function() {
					alert('Demo Pro License activated! Reloading...');
					location.reload();
				},
				error: function() {
					alert('Activation failed.');
					btn.text('✨ Activate Demo Pro License').prop('disabled', false);
				}
			});
		});

		// Test AI Connection
		$('.test-ai-connection').on('click', function() {
			const provider = $(this).data('provider');
			const btn = $(this);
			btn.text('Testing...').prop('disabled', true);
			const startTime = Date.now();

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
					const latency = Date.now() - startTime;
					alert(provider + ' connection successful in ' + latency + 'ms. Response: ' + response.result);
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
