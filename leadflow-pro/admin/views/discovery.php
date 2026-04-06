<div class="wrap leadflow-discovery">
	<h1 class="wp-heading-inline">Lead Discovery Engine</h1>
	<p class="description">Source new business opportunities from across the web. Select a data source and enter your target keywords.</p>

	<div class="leadflow-tabs" style="margin-top:20px;">
		<button class="tab-btn active" data-source="google">Google Places</button>
		<button class="tab-btn" data-source="linkedin">LinkedIn Profiles</button>
		<button class="tab-btn" data-source="facebook">Facebook Groups</button>
	</div>

	<div class="discovery-search-box chart-box">
		<form id="discoverySearchForm">
			<input type="hidden" id="discoverySource" value="google">
			<p class="description" style="color: #fff; opacity: 0.9; margin-bottom: 20px;">
				<span class="dashicons dashicons-info"></span>
				Select a source above. Google Places is best for local businesses, while LinkedIn and Facebook are great for finding B2B profiles and group members.
			</p>
			<div class="search-inputs">
				<p>
					<label id="discoveryLabel">Keyword (e.g. Dentist, Plumber)</label><br>
					<input type="text" id="discoveryKeyword" placeholder="Enter keyword..." required class="regular-text">
				</p>
				<p id="locationInputWrapper">
					<label>Location (City, Country)</label><br>
					<input type="text" id="discoveryLocation" placeholder="Enter location..." class="regular-text">
				</p>
			</div>
			<p><button type="submit" class="button button-primary" id="startDiscoveryBtn">Start Lead Discovery</button></p>
		</form>
	</div>

	<div id="discoveryResults" style="display:none;">
		<h3>Discovery Results</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="manage-column column-cb check-column"><input type="checkbox"></th>
					<th>Business Name</th>
					<th>Website</th>
					<th>Phone</th>
					<th>Source</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="discoveryResultsBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
		<div class="bulk-actions" style="margin-top: 15px;">
			<button class="button button-primary" id="importSelectedLeads">Import Selected as CRM Leads</button>
		</div>
	</div>

	<div class="pro-tip chart-box" style="margin-top: 30px; border-left: 4px solid var(--leadflow-accent);">
		<p><strong>💡 Pro Tip:</strong> LinkedIn discovery is best for finding B2B decision makers, while Google Places is unbeatable for local service businesses like Dentists or Plumbers.</p>
	</div>

	<div class="csv-import-box chart-box" style="margin-top: 30px;">
		<h3>Bulk Import via CSV</h3>
		<p>Upload a CSV file to import leads directly into the CRM.</p>
		<form id="csvImportForm" enctype="multipart/form-data">
			<input type="file" name="leads_csv" accept=".csv" required>
			<button type="submit" class="button">Upload and Import</button>
		</form>
	</div>
</div>
