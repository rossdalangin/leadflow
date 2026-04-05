<div class="wrap leadflow-leads">
	<h1 class="wp-heading-inline">Lead CRM</h1>
	<a href="#" class="page-title-action" id="addLeadBtn">Add New Lead</a>
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
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Business Name</th>
						<th>Website</th>
						<th>Email</th>
						<th>Status</th>
						<th>Last Action</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody id="leadTableBody">
					<!-- Populated by JS -->
				</tbody>
			</table>
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

	<!-- Add Lead Modal -->
	<div id="addLeadModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content">
			<h2>Add New Lead</h2>
			<form id="addLeadForm">
				<p><label>Business Name</label><br><input type="text" name="business_name" required></p>
				<p><label>Website URL</label><br><input type="url" name="website_url"></p>
				<p><label>Email</label><br><input type="email" name="email"></p>
				<p><button type="submit" class="button button-primary">Save Lead</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>
</div>
