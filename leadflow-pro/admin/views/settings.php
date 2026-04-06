<div class="wrap leadflow-settings">
	<h1>LeadFlow Pro Settings</h1>
	<h2 class="nav-tab-wrapper">
		<a href="#general" class="nav-tab nav-tab-active">General</a>
		<a href="#smtp" class="nav-tab">SMTP / Email</a>
		<a href="#ai" class="nav-tab">AI Provider</a>
		<a href="#license" class="nav-tab">License</a>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'leadflow-settings-group' ); ?>
		<?php do_settings_sections( 'leadflow-settings-group' ); ?>

		<div id="general" class="settings-section active">
			<h2>General Settings</h2>
			<table class="form-table">
				<tr>
					<th scope="row">Discovery Engine (Google Places API Key)</th>
					<td><input type="text" name="leadflow_google_places_api_key" value="<?php echo esc_attr( get_option( 'leadflow_google_places_api_key' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Scraping Logic</th>
					<td>
						<label><input type="checkbox" name="leadflow_robots_check" value="1" <?php checked( 1, get_option( 'leadflow_robots_check' ), true ); ?>> Respect robots.txt</label><br>
						<label><input type="checkbox" name="leadflow_no_ssl_audit" value="1" <?php checked( 1, get_option( 'leadflow_no_ssl_audit' ), true ); ?>> Check for SSL certificate</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Scraping Ethics (Crawl Delay)</th>
					<td><input type="number" name="leadflow_crawl_delay" value="<?php echo esc_attr( get_option( 'leadflow_crawl_delay', 2 ) ); ?>" class="small-text"> seconds</td>
				</tr>
			</table>
		</div>

		<div id="smtp" class="settings-section" style="display:none;">
			<h2>SMTP Configuration</h2>
			<table class="form-table">
				<tr>
					<th scope="row">From Name</th>
					<td><input type="text" name="leadflow_smtp_from_name" value="<?php echo esc_attr( get_option( 'leadflow_smtp_from_name' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">From Email</th>
					<td><input type="email" name="leadflow_smtp_from_email" value="<?php echo esc_attr( get_option( 'leadflow_smtp_from_email' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Host</th>
					<td><input type="text" name="leadflow_smtp_host" value="<?php echo esc_attr( get_option( 'leadflow_smtp_host' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Port</th>
					<td><input type="number" name="leadflow_smtp_port" value="<?php echo esc_attr( get_option( 'leadflow_smtp_port' ) ); ?>" class="regular-text"></td>
				</tr>
			</table>
		</div>

		<div id="ai" class="settings-section" style="display:none;">
			<?php include_once LEADFLOW_PRO_PATH . 'admin/views/settings-ai.php'; ?>
			<table class="form-table">
				<tr>
					<th scope="row">Monthly Token Budget (AI)</th>
					<td><input type="number" name="leadflow_token_budget" value="<?php echo esc_attr( get_option( 'leadflow_token_budget', 50000 ) ); ?>" class="regular-text"> tokens</td>
				</tr>
			</table>
		</div>

		<div id="license" class="settings-section" style="display:none;">
			<h2>License Key</h2>
			<table class="form-table">
				<tr>
					<th scope="row">License Key</th>
					<td>
						<input type="text" name="leadflow_license_key" value="<?php echo esc_attr( get_option( 'leadflow_license_key' ) ); ?>" class="regular-text">
						<p class="description">Enter your license key to enable Pro features.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Status</th>
					<td>
						<span class="license-status <?php echo LeadFlow_License::is_pro() ? 'active' : 'inactive'; ?>">
							<?php echo LeadFlow_License::is_pro() ? 'Active (Pro)' : 'Inactive (Free)'; ?>
						</span>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
