<div class="wrap leadflow-campaigns">
	<h1 class="wp-heading-inline">Outreach Campaigns</h1>
	<a href="#" class="page-title-action" id="createCampaignBtn">Create New Campaign</a>
	<hr class="wp-header-end">

	<div class="leadflow-campaign-list">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Campaign Name</th>
					<th>Status Filter</th>
					<th>Active Leads</th>
					<th>Avg. Open Rate</th>
					<th>Avg. Reply Rate</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="campaignListBody">
				<tr>
					<td>Web Design Outreach</td>
					<td>New</td>
					<td>145</td>
					<td>32%</td>
					<td>8.4%</td>
					<td><span class="status-active">Active</span></td>
					<td>
						<button class="button button-small" data-id="1">Edit</button>
						<button class="button button-small" data-id="1">Pause</button>
						<button class="button button-small" data-id="1">Delete</button>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Campaign Builder Modal -->
	<div id="campaignBuilderModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content campaign-builder">
			<h2>Campaign Builder</h2>
			<form id="campaignBuilderForm">
				<p><label>Campaign Name</label><br><input type="text" name="name" required></p>
				<p><label>Status Filter (Leads to include)</label><br>
					<select name="status_filter">
						<option value="New">New</option>
						<option value="Contacted">Contacted</option>
						<option value="Replied">Replied</option>
					</select>
				</p>
				<hr>
				<div class="sequence-steps" id="sequenceSteps">
					<h3>Sequence Steps</h3>
					<div class="step-card">
						<h4>Step 1: Initial Email</h4>
						<p><label>Subject</label><br><input type="text" name="step[1][subject]" class="step-subject" value="Quick question about {{business_name}}"></p>
						<button type="button" class="button ai-subject-btn">✨ AI: Generate Subject</button>
						<p><label>Body</label><br><textarea name="step[1][body]" class="step-body" rows="5">Hi {{business_name}} team, I saw your website {{website}} and noticed something...</textarea></p>
						<button type="button" class="button ai-writer-btn">✨ AI: Write this for me</button>
					</div>
				</div>
				<button type="button" class="button" id="addStepBtn">Add Step</button>
				<p><button type="submit" class="button button-primary">Save & Activate</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>
</div>
