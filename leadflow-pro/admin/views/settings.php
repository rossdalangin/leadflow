<div class="wrap leadflow-settings">
	<h1>Plugin Configuration</h1>
	<p class="description">Configure your API keys, email servers, and AI preferences to power your lead generation engine.</p>

	<h2 class="nav-tab-wrapper">
		<a href="#general" class="nav-tab nav-tab-active"><span class="dashicons dashicons-admin-generic"></span> General</a>
		<a href="#smtp" class="nav-tab"><span class="dashicons dashicons-email-alt"></span> SMTP / Email</a>
		<a href="#ai" class="nav-tab"><span class="dashicons dashicons-cloud"></span> AI Provider</a>
		<a href="#license" class="nav-tab"><span class="dashicons dashicons-shield"></span> License</a>
	</h2>

	<form method="post" action="options.php" class="leadflow-settings-form">
		<?php settings_fields( 'leadflow-settings-group' ); ?>
		<?php do_settings_sections( 'leadflow-settings-group' ); ?>

		<div id="general" class="settings-section active">
			<h2>General Settings</h2>
			<p class="description">Core functionality settings for the LeadFlow Pro engine.</p>
			<table class="form-table">
				<tr>
					<th scope="row">
						Discovery Engine (Google Places API Key)
						<p class="description" style="font-weight:normal; margin-top:5px;">
							<span class="dashicons dashicons-info" style="font-size:16px; width:16px; height:16px;"></span>
							Used to find businesses on Google Maps. Get your key from Google Cloud Console.
						</p>
					</th>
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
				<tr>
					<th scope="row">LinkedIn API (Client ID)</th>
					<td><input type="text" name="leadflow_linkedin_client_id" value="<?php echo esc_attr( get_option( 'leadflow_linkedin_client_id' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Facebook App ID (Groups)</th>
					<td><input type="text" name="leadflow_facebook_app_id" value="<?php echo esc_attr( get_option( 'leadflow_facebook_app_id' ) ); ?>" class="regular-text"></td>
				</tr>
			</table>
		</div>

		<div id="smtp" class="settings-section" style="display:none;">
			<h2>SMTP (Sending)</h2>
			<p class="description">Configure how LeadFlow Pro sends outreach emails. We recommend using a dedicated workspace or professional SMTP provider like SendGrid or Mailgun.</p>
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

			<h2>IMAP (Inbox Receiving)</h2>
			<p class="description">Used to pull replies from your inbox and update lead statuses automatically.</p>
			<table class="form-table">
				<tr>
					<th scope="row">IMAP Host</th>
					<td><input type="text" name="leadflow_imap_host" value="<?php echo esc_attr( get_option( 'leadflow_imap_host' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">IMAP Port</th>
					<td><input type="number" name="leadflow_imap_port" value="<?php echo esc_attr( get_option( 'leadflow_imap_port' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">IMAP User</th>
					<td><input type="text" name="leadflow_imap_user" value="<?php echo esc_attr( get_option( 'leadflow_imap_user' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">IMAP Password</th>
					<td><input type="password" name="leadflow_imap_pass" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_imap_pass' ) ); ?>" class="regular-text"></td>
				</tr>
			</table>
		</div>

		<div id="ai" class="settings-section" style="display:none;">
			<p class="description">Select your AI provider and set your monthly token budget. Pro users can choose between OpenAI GPT-4o and Gemini 1.5 Pro for maximum accuracy.</p>
			<?php include_once LEADFLOW_PRO_PATH . 'admin/views/settings-ai.php'; ?>
			<table class="form-table">
				<tr>
					<th scope="row">OpenAI Monthly Token Budget</th>
					<td><input type="number" name="leadflow_token_budget_openai" value="<?php echo esc_attr( get_option( 'leadflow_token_budget_openai', 50000 ) ); ?>" class="regular-text"> tokens</td>
				</tr>
				<tr>
					<th scope="row">Gemini Monthly Token Budget</th>
					<td><input type="number" name="leadflow_token_budget_gemini" value="<?php echo esc_attr( get_option( 'leadflow_token_budget_gemini', 50000 ) ); ?>" class="regular-text"> tokens</td>
				</tr>
			</table>
		</div>

		<div id="license" class="settings-section" style="display:none;">
			<h2>License Key & Pro Features</h2>
			<p class="description">Enter your license key to unlock unlimited leads, advanced AI tools, Kanban views, and more.</p>
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
