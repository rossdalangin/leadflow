<div class="wrap leadflow-campaigns">
	<h1 class="wp-heading-inline">Outreach Automation</h1>
	<p class="description">Design multi-step outreach sequences. Campaigns automatically pause when a lead replies, ensuring a natural conversation flow.</p>
	<a href="#" class="page-title-action" id="createCampaignBtn">Create New Campaign</a>
	<hr class="wp-header-end">

	<div class="leadflow-campaign-list">
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
					<p class="description">Define the steps in your sequence. Each step can be an email or a manual social task.</p>
					<div class="step-card">
						<h4>Step 1</h4>
						<p><label>Step Type</label><br>
							<select name="step[1][type]" class="step-type">
								<option value="email">Email</option>
								<option value="linkedin">LinkedIn Connection/Message</option>
								<option value="facebook">Facebook Group Outreach</option>
							</select>
						</p>
						<div class="email-fields">
							<p><label>Subject</label><br><input type="text" name="step[1][subject]" class="step-subject" value="Quick question about {{business_name}}"></p>
							<button type="button" class="button ai-subject-btn">✨ AI: Generate Subject</button>
						</div>
						<p><label>Message Body</label><br><textarea name="step[1][body]" class="step-body" rows="5">Hi {{business_name}} team, I saw your website {{website}} and noticed something...</textarea></p>
						<button type="button" class="button ai-writer-btn">✨ AI: Write this for me</button>
					</div>
				</div>
				<button type="button" class="button" id="addStepBtn">Add Step</button>
				<p><button type="submit" class="button button-primary">Save & Activate</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>
</div>
