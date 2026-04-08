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
			<p>
				<button type="submit" class="button button-primary" id="startDiscoveryBtn">Start Lead Discovery</button>
				<button type="button" class="button" id="saveSearchBtn">Save Search Parameters</button>
			</p>
		</form>
	</div>

	<div id="savedSearchesSection" class="chart-box" style="margin-top: 20px;">
		<h3>Saved Searches & Auto-Discovery</h3>
		<p class="description">Enable "Auto-Discover" to have LeadFlow Pro automatically run these searches daily and import new leads.</p>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Name</th><th>Source</th><th>Keyword</th><th>Location</th><th>Auto-Discover</th><th>Last Run</th><th>Actions</th></tr></thead>
			<tbody id="savedSearchesBody"></tbody>
		</table>
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
			<button class="button" id="exportDiscoveryResults">Export Results to CSV</button>
		</div>
	</div>

	<div class="pro-tip chart-box" style="margin-top: 30px; border-left: 4px solid var(--leadflow-accent);">
		<p><strong>💡 Pro Tip:</strong> LinkedIn discovery is best for finding B2B decision makers, while Google Places is unbeatable for local service businesses like Dentists or Plumbers.</p>
	</div>

	<div class="csv-import-box chart-box" style="margin-top: 30px;">
		<h3>Bulk Import via CSV</h3>
		<p>Upload a CSV file to import leads directly into the CRM. <a href="<?php echo esc_url( rest_url( 'leadflow/v1/discovery/sample-csv' ) ); ?>?_wpnonce=<?php echo wp_create_nonce('wp_rest'); ?>" target="_blank">Download Sample Template</a></p>
		<form id="csvImportForm" enctype="multipart/form-data">
			<input type="file" name="leads_csv" accept=".csv" required>
			<button type="submit" class="button">Upload and Import</button>
		</form>
	</div>

	<div class="lead-magnet-box chart-box" style="margin-top: 30px;">
		<h3>Lead Magnet Form Generator</h3>
		<p>Generate a "Free Website Audit" form to embed on your site. Anyone who fills it out will be automatically added to your CRM.</p>
		<div style="background:#f1f1f1; padding:15px; border-radius:8px; font-family:monospace; margin-bottom:15px;">
			&lt;div id="leadflow-audit-form"&gt;&lt;/div&gt;<br>
			&lt;script src="<?php echo LEADFLOW_PRO_URL . 'assets/lead-magnet.js'; ?>"&gt;&lt;/script&gt;
		</div>
		<button class="button" onclick="jQuery('#leadMagnetSettings').slideToggle()">Configure Form</button>

		<div id="leadMagnetSettings" style="display:none; margin-top:20px; background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px;">
			<form method="post" action="options.php">
				<?php settings_fields( 'leadflow-magnet-group' ); ?>
				<p><label>Success Message</label><br>
				<input type="text" name="leadflow_magnet_success" value="<?php echo esc_attr( get_option( 'leadflow_magnet_success', 'Success! Your audit is being generated and will be emailed to you shortly.' ) ); ?>" class="large-text"></p>
				<p><label>Redirect URL (Optional)</label><br>
				<input type="url" name="leadflow_magnet_redirect" value="<?php echo esc_url( get_option( 'leadflow_magnet_redirect' ) ); ?>" class="large-text" placeholder="https://..."></p>
				<?php submit_button('Save Form Settings'); ?>
			</form>
		</div>
	</div>
</div>
