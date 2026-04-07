<div class="wrap leadflow-campaigns">
	<h1 class="wp-heading-inline">Outreach Automation</h1>
	<p class="description">Design multi-step outreach sequences. Campaigns automatically pause when a lead replies, ensuring a natural conversation flow.</p>
	<a href="#" class="page-title-action" id="createCampaignBtn">Create New Campaign</a>
	<hr class="wp-header-end">

	<div class="leadflow-tabs" style="margin-top:20px;">
		<button class="tab-btn active" data-view="campaign-list">Active Campaigns</button>
		<button class="tab-btn" data-view="sending-queue">Sending Queue</button>
		<button class="tab-btn" data-view="suppression-list">Suppression List</button>
	</div>

	<div class="leadflow-campaign-list" id="campaignListView">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Campaign Name</th>
					<th>Status Filter</th>
					<th>Status</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="campaignListBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>

	<div class="leadflow-suppression-list" id="suppressionListView" style="display:none;">
		<div class="chart-box">
			<h3>Global Suppression List</h3>
			<p>Emails in this list will never be contacted by any campaign.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Email</th><th>Reason</th><th>Added</th><th>Actions</th></tr></thead>
				<tbody id="suppressionListBody"></tbody>
			</table>
			<div style="margin-top:20px;">
				<input type="email" id="suppressEmail" placeholder="email@example.com">
				<button class="button" id="addSuppressionBtn">Add to Suppression List</button>
			</div>
		</div>
	</div>

	<div class="leadflow-sending-queue" id="sendingQueueView" style="display:none;">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Scheduled For</th>
					<th>Lead</th>
					<th>Campaign</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="sendingQueueBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>

	<!-- Campaign Builder Modal -->
	<div id="campaignBuilderModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content campaign-builder">
			<h2>Campaign Builder</h2>
			<form id="campaignBuilderForm">
				<input type="hidden" name="id" id="campaignId">
				<p><label>Campaign Name</label><br><input type="text" name="name" id="campaignName" required></p>
				<div style="display:flex; gap:20px;">
					<p><label>Start Hour (0-23)</label><br><input type="number" name="start_hour" id="campaignStartHour" min="0" max="23" value="9"></p>
					<p><label>End Hour (0-23)</label><br><input type="number" name="end_hour" id="campaignEndHour" min="0" max="23" value="17"></p>
					<p><label><br><input type="checkbox" name="skip_weekends" id="campaignSkipWeekends" checked value="1"> Skip Weekends</label></p>
				</div>
				<p><label>Status Filter (Leads to include)</label><br>
					<select name="status_filter" id="campaignStatusFilter">
						<option value="New">New</option>
						<option value="Contacted">Contacted</option>
						<option value="Replied">Replied</option>
						<option value="Qualified">Qualified</option>
						<option value="Proposal Sent">Proposal Sent</option>
					</select>
				</p>
				<hr>
				<div class="sequence-steps" id="sequenceSteps">
					<h3>Sequence Steps</h3>
					<p class="description">Define the steps in your sequence. Each step can be an email or a manual social task.</p>
					<!-- Steps will be dynamically added here -->
				</div>
				<button type="button" class="button" id="addStepBtn">Add Step</button>
				<p><button type="submit" class="button button-primary">Save & Activate</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>
</div>
