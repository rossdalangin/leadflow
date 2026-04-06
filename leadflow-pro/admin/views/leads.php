<div class="wrap leadflow-leads">
	<h1 class="wp-heading-inline">Lead CRM</h1>
	<p class="description">Manage your leads through the sales funnel. Use the Table view for bulk management or Kanban for pipeline visualization.</p>
	<a href="#" class="page-title-action" id="addLeadBtn">Add New Lead</a>
	<a href="<?php echo esc_url( rest_url( 'leadflow/v1/leads/export-csv' ) ); ?>" class="page-title-action" style="margin-left:5px;">Export CSV (Pro)</a>
	<hr class="wp-header-end">

	<div class="leadflow-tabs">
		<button class="tab-btn active" data-view="table">Table View</button>
		<button class="tab-btn <?php echo LeadFlow_License::is_pro() ? '' : 'pro-only'; ?>" data-view="kanban">Kanban Pipeline <?php echo LeadFlow_License::is_pro() ? '' : '<span class="dashicons dashicons-lock"></span>'; ?></button>
	</div>

	<div id="leadViewContainer">
		<div class="leadflow-filters">
			<select name="lead_status" id="leadStatusFilter">
				<option value="">All Statuses</option>
				<option value="New">New</option>
				<option value="Contacted">Contacted</option>
				<option value="Replied">Replied</option>
				<option value="Qualified">Qualified</option>
			</select>
			<input type="text" id="leadSearch" placeholder="Search leads...">
			<button class="button" id="applyFilters">Apply Filters</button>
		</div>

		<div id="leadTableView">
			<?php
			global $wpdb;
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leadflow_leads" );
			if ( 0 == $count ) :
			?>
				<div class="notice notice-info inline" style="margin: 20px 0; padding: 20px; border-radius: 12px; background: #fff; border-left: 4px solid var(--leadflow-primary);">
					<h3 style="margin-top:0;">🚀 Welcome to LeadFlow Pro!</h3>
					<p>Your CRM is currently empty. You can start by discovering new leads or import some sample data to see how the system works.</p>
					<a href="<?php echo admin_url( 'admin.php?page=leadflow-discovery' ); ?>" class="button button-primary">Discover New Leads</a>
					<a href="<?php echo admin_url( 'admin.php?page=leadflow-leads&leadflow_seed=1' ); ?>" class="button" style="margin-left:10px;">Import Sample Data</a>
				</div>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="manage-column column-cb check-column"><input type="checkbox" id="selectAllLeads"></th>
						<th>Business Name</th>
						<th>Score</th>
						<th>Website</th>
						<th>Email</th>
						<th>Assigned To</th>
						<th>Status</th>
						<th>Last Action</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody id="leadTableBody">
					<!-- Populated by JS -->
				</tbody>
			</table>
			<div class="leadflow-bulk-actions" style="margin-top:20px;">
				<select id="bulkStatusUpdate">
					<option value="">Bulk Status Update...</option>
					<option value="New">New</option>
					<option value="Contacted">Contacted</option>
					<option value="Replied">Replied</option>
					<option value="Qualified">Qualified</option>
				</select>
				<button class="button" id="applyBulkStatus">Apply Status</button>
				<button class="button" id="bulkDeleteLeads" style="color:#d63638; margin-left:10px;">Bulk Delete</button>
			</div>
		</div>

		<div id="leadKanbanView" style="display:none;">
			<?php if ( LeadFlow_License::is_pro() ) : ?>
				<div class="kanban-board" id="kanbanBoard">
					<div class="kanban-column" data-status="New"><h3>New</h3><div class="kanban-items"></div></div>
					<div class="kanban-column" data-status="Contacted"><h3>Contacted</h3><div class="kanban-items"></div></div>
					<div class="kanban-column" data-status="Replied"><h3>Replied</h3><div class="kanban-items"></div></div>
					<div class="kanban-column" data-status="Qualified"><h3>Qualified</h3><div class="kanban-items"></div></div>
				</div>
			<?php else : ?>
				<div class="leadflow-upsell-overlay">
					<h3>Upgrade to Pro for Kanban View</h3>
					<p>Visualize your pipeline and drag-and-drop leads through the conversion funnel.</p>
					<a href="<?php echo admin_url( 'admin.php?page=leadflow-settings#license' ); ?>" class="button button-primary">Upgrade Now</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Lead Detail Modal -->
	<div id="leadDetailModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content" style="max-width: 900px; height: 80vh; display: flex; flex-direction: column;">
			<div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
				<h2 id="detailLeadName">Lead Details</h2>
				<button class="button close-modal">Close</button>
			</div>
			<div class="modal-body" style="flex:1; overflow-y:auto; display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
				<div class="lead-activity-section">
					<h3>Activity Log & Conversation</h3>
					<div id="detailLeadThread" class="inbox-thread" style="height:400px; border:1px solid var(--leadflow-border); border-radius:12px;"></div>
				</div>
				<div class="lead-info-section">
					<h3>Audit Insights</h3>
					<div id="detailLeadSidebar" class="inbox-sidebar" style="background:transparent; border:none; padding:0;"></div>
					<div id="detailAiTools" style="margin-top:20px;">
						<button class="button button-small ai-score-btn">✨ AI: Score</button>
						<button class="button button-small ai-summarize-btn">✨ AI: Hook</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Lead Modal -->
	<div id="addLeadModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content">
			<h2>Add New Lead</h2>
			<form id="addLeadForm">
				<p><label>Contact First Name</label><br><input type="text" name="first_name" placeholder="e.g. John"></p>
				<p><label>Business Name</label><br><input type="text" name="business_name" required placeholder="e.g. Acme Corp"></p>
				<p><label>Website URL</label><br><input type="url" name="website_url" placeholder="https://..."></p>
				<p><label>Email</label><br><input type="email" name="email" placeholder="john@example.com"></p>
				<p><button type="submit" class="button button-primary">Save Lead</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>
</div>
