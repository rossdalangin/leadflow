<div class="wrap leadflow-discovery">
	<h1>Lead Discovery Engine</h1>
	<p>Find targeted business leads by keyword and location using Google Places.</p>

	<div class="discovery-search-box chart-box">
		<form id="discoverySearchForm">
			<div class="search-inputs">
				<p>
					<label>Keyword (e.g. Dentist, Plumber)</label><br>
					<input type="text" id="discoveryKeyword" placeholder="Enter keyword..." required class="regular-text">
				</p>
				<p>
					<label>Location (City, Country)</label><br>
					<input type="text" id="discoveryLocation" placeholder="Enter location..." required class="regular-text">
				</p>
			</div>
			<p><button type="submit" class="button button-primary" id="startDiscoveryBtn">Start Discovery</button></p>
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
			<button class="button" id="importSelectedLeads">Import Selected as CRM Leads</button>
		</div>
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
