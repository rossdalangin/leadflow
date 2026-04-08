<div class="wrap leadflow-settings">
	<h1>Plugin Configuration</h1>
	<p class="description">Configure your API keys, email servers, and AI preferences to power your lead generation engine.</p>

	<h2 class="nav-tab-wrapper">
		<a href="#general" class="nav-tab nav-tab-active"><span class="dashicons dashicons-admin-generic"></span> General</a>
		<a href="#smtp" class="nav-tab"><span class="dashicons dashicons-email-alt"></span> SMTP / Email</a>
		<a href="#ai" class="nav-tab"><span class="dashicons dashicons-cloud"></span> AI Provider</a>
		<a href="#license" class="nav-tab"><span class="dashicons dashicons-shield"></span> License</a>
		<a href="#whitelabel" class="nav-tab"><span class="dashicons dashicons-admin-appearance"></span> White-label</a>
		<a href="#team" class="nav-tab"><span class="dashicons dashicons-groups"></span> Team Quotas</a>
		<a href="#webhooks" class="nav-tab"><span class="dashicons dashicons-rest-api"></span> Webhooks</a>
		<a href="#status" class="nav-tab"><span class="dashicons dashicons-performance"></span> System Status</a>
		<a href="#logs" class="nav-tab"><span class="dashicons dashicons-list-view"></span> Logs</a>
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
					<td><input type="text" name="leadflow_google_places_api_key" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_google_places_api_key' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Scraping Logic</th>
					<td>
						<label><input type="checkbox" name="leadflow_robots_check" value="1" <?php checked( 1, get_option( 'leadflow_robots_check' ), true ); ?>> Respect robots.txt</label><br>
						<label><input type="checkbox" name="leadflow_no_ssl_audit" value="1" <?php checked( 1, get_option( 'leadflow_no_ssl_audit' ), true ); ?>> Check for SSL certificate</label><br>
						<label><input type="checkbox" name="leadflow_auto_archive_negative" value="1" <?php checked( 1, get_option( 'leadflow_auto_archive_negative', 1 ), true ); ?>> Auto-move negative sentiment replies to Closed Lost</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Scraping Ethics (Crawl Delay)</th>
					<td><input type="number" name="leadflow_crawl_delay" value="<?php echo esc_attr( get_option( 'leadflow_crawl_delay', 2 ) ); ?>" class="small-text"> seconds</td>
				</tr>
				<tr>
					<th scope="row">Custom Scoring Rules (Pro)</th>
					<td>
						<div id="scoringRulesContainer">
							<?php
							$rules = get_option( 'leadflow_scoring_rules', array() );
							foreach ( $rules as $index => $rule ) :
							?>
								<div class="scoring-rule-row" style="margin-bottom:10px; display:flex; gap:10px;">
									<select name="leadflow_scoring_rules[<?php echo $index; ?>][key]">
										<option value="has_ssl" <?php selected( 'has_ssl', $rule['key'] ); ?>>Has SSL</option>
										<option value="cms" <?php selected( 'cms', $rule['key'] ); ?>>CMS</option>
										<option value="is_ecommerce" <?php selected( 'is_ecommerce', $rule['key'] ); ?>>eCommerce</option>
									</select>
									<input type="text" name="leadflow_scoring_rules[<?php echo $index; ?>][value]" value="<?php echo esc_attr( $rule['value'] ); ?>" placeholder="Value">
									<input type="number" name="leadflow_scoring_rules[<?php echo $index; ?>][points]" value="<?php echo esc_attr( $rule['points'] ); ?>" style="width:60px;"> pts
								</div>
							<?php endforeach; ?>
						</div>
						<p class="description">Add points for specific technical signals found during audit.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">AI Outreach Language</th>
					<td>
						<select name="leadflow_outreach_language">
							<option value="English" <?php selected( 'English', get_option( 'leadflow_outreach_language', 'English' ) ); ?>>English</option>
							<option value="Spanish" <?php selected( 'Spanish', get_option( 'leadflow_outreach_language', 'English' ) ); ?>>Spanish</option>
							<option value="French" <?php selected( 'French', get_option( 'leadflow_outreach_language', 'English' ) ); ?>>French</option>
							<option value="German" <?php selected( 'German', get_option( 'leadflow_outreach_language', 'English' ) ); ?>>German</option>
						</select>
						<p class="description">The AI will automatically write or translate your outreach into this language.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Lead Scoring Weights</th>
					<td>
						<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; max-width:400px;">
							<label>Business Name Weight</label><input type="number" name="leadflow_weight_name" value="<?php echo esc_attr( get_option( 'leadflow_weight_name', 20 ) ); ?>" class="small-text">
							<label>Website Weight</label><input type="number" name="leadflow_weight_url" value="<?php echo esc_attr( get_option( 'leadflow_weight_url', 20 ) ); ?>" class="small-text">
							<label>Email Weight</label><input type="number" name="leadflow_weight_email" value="<?php echo esc_attr( get_option( 'leadflow_weight_email', 30 ) ); ?>" class="small-text">
							<label>Phone Weight</label><input type="number" name="leadflow_weight_phone" value="<?php echo esc_attr( get_option( 'leadflow_weight_phone', 15 ) ); ?>" class="small-text">
							<label>Social Weight</label><input type="number" name="leadflow_weight_social" value="<?php echo esc_attr( get_option( 'leadflow_weight_social', 15 ) ); ?>" class="small-text">
						</div>
						<p class="description">Total should ideally equal 100 for percentage-based scoring.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Hunter.io API Key</th>
					<td><input type="text" name="leadflow_hunter_api_key" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_hunter_api_key' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Clearbit API Key</th>
					<td><input type="text" name="leadflow_clearbit_api_key" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_clearbit_api_key' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">LinkedIn API (Client ID)</th>
					<td><input type="text" name="leadflow_linkedin_client_id" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_linkedin_client_id' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Facebook App ID (Groups)</th>
					<td><input type="text" name="leadflow_facebook_app_id" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_facebook_app_id' ) ); ?>" class="regular-text"></td>
				</tr>
			</table>
		</div>

		<div id="smtp" class="settings-section" style="display:none;">
			<h2>Email Sending Configuration</h2>
			<p class="description">Configure how LeadFlow Pro sends outreach emails. We recommend using a dedicated workspace or professional SMTP provider.</p>

			<table class="form-table">
				<tr>
					<th scope="row">Email Provider</th>
					<td>
						<select name="leadflow_email_provider">
							<option value="smtp" <?php selected( 'smtp', get_option( 'leadflow_email_provider', 'smtp' ) ); ?>>Standard SMTP</option>
							<option value="gmail" <?php selected( 'gmail', get_option( 'leadflow_email_provider', 'smtp' ) ); ?>>Gmail API (Pro)</option>
						</select>
					</td>
				</tr>
				<tr class="gmail-only" style="display:none;">
					<th scope="row">Gmail Client ID</th>
					<td><input type="text" name="leadflow_gmail_client_id" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_client_id' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr class="gmail-only" style="display:none;">
					<th scope="row">Gmail Client Secret</th>
					<td><input type="password" name="leadflow_gmail_client_secret" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_client_secret' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr class="gmail-only" style="display:none;">
					<th scope="row">Authorized Redirect URI</th>
					<td>
						<code><?php echo esc_url( admin_url( 'admin.php?page=leadflow-settings&gmail_callback=1' ) ); ?></code>
						<p class="description">Copy this into your Google Cloud Console redirect URIs.</p>
					</td>
				</tr>
				<tr class="gmail-only" style="display:none;">
					<th scope="row">Authentication</th>
					<td>
						<?php if ( LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_token' ) ) : ?>
							<span class="status-badge status-replied">Authenticated</span>
							<button type="button" class="button" id="reauthGmail">Re-authenticate</button>
							<button type="button" class="button" id="revokeGmail" style="color:#d63638;">Revoke Connection</button>
						<?php else : ?>
							<button type="button" class="button button-primary" id="authGmail">Connect Gmail Account</button>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h3 class="smtp-only">SMTP Settings</h3>
			<table class="form-table smtp-only">
				<tr>
					<th scope="row">Email Signature</th>
					<td><textarea name="leadflow_email_signature" rows="4" class="large-text" placeholder="Kind regards,&#10;John Doe&#10;CEO at Acme Corp"><?php echo esc_textarea( get_option( 'leadflow_email_signature' ) ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row">From Name</th>
					<td><input type="text" name="leadflow_smtp_from_name" value="<?php echo esc_attr( get_option( 'leadflow_smtp_from_name' ) ); ?>" class="regular-text" placeholder="John Doe"></td>
				</tr>
				<tr>
					<th scope="row">From Email</th>
					<td><input type="email" name="leadflow_smtp_from_email" value="<?php echo esc_attr( get_option( 'leadflow_smtp_from_email' ) ); ?>" class="regular-text" placeholder="john@example.com"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Host</th>
					<td><input type="text" name="leadflow_smtp_host" value="<?php echo esc_attr( get_option( 'leadflow_smtp_host' ) ); ?>" class="regular-text" placeholder="smtp.mailtrap.io"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Port</th>
					<td><input type="number" name="leadflow_smtp_port" value="<?php echo esc_attr( get_option( 'leadflow_smtp_port', 587 ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Username</th>
					<td><input type="text" name="leadflow_smtp_user" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_smtp_user' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">SMTP Password</th>
					<td><input type="password" name="leadflow_smtp_pass" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_smtp_pass' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Encryption</th>
					<td>
						<select name="leadflow_smtp_encryption">
							<option value="tls" <?php selected( 'tls', get_option( 'leadflow_smtp_encryption', 'tls' ) ); ?>>TLS</option>
							<option value="ssl" <?php selected( 'ssl', get_option( 'leadflow_smtp_encryption', 'tls' ) ); ?>>SSL</option>
							<option value="none" <?php selected( 'none', get_option( 'leadflow_smtp_encryption', 'tls' ) ); ?>>None</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Test Delivery</th>
					<td>
						<input type="email" id="testEmailAddr" class="regular-text" placeholder="your-email@example.com">
						<button type="button" class="button" id="sendTestEmail">Send Test Email</button>
					</td>
				</tr>
				<tr>
					<th scope="row">Domain Deliverability</th>
					<td>
						<div id="domainHealthReport" style="padding:15px; background:#f9f9f9; border-radius:8px; border:1px solid #ddd;">
							<button type="button" class="button" id="checkDomainHealth">Check SPF/DKIM/DMARC</button>
							<div id="domainHealthResults" style="margin-top:10px; display:none;"></div>
						</div>
					</td>
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
				<tr>
					<th scope="row">Test Inbox Connection</th>
					<td><button type="button" class="button" id="testImapBtn">Test IMAP Connection</button></td>
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

		<div id="team" class="settings-section" style="display:none;">
			<h2>Agency Team Quotas (Pro)</h2>
			<p class="description">Set lead limits for individual team members to manage workload and resource allocation.</p>
			<table class="form-table">
				<?php
				$users = get_users(array('role__in' => array('administrator', 'editor')));
				foreach ($users as $user) : ?>
					<tr>
						<th scope="row"><?php echo esc_html($user->display_name); ?></th>
						<td>
							<input type="number" name="leadflow_quota_user_<?php echo $user->ID; ?>" value="<?php echo esc_attr(get_option('leadflow_quota_user_' . $user->ID, 0)); ?>" class="small-text"> leads
							<p class="description">Set to 0 for unlimited (Pro global limits still apply).</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div id="whitelabel" class="settings-section" style="display:none;">
			<h2>Agency White-labeling (Pro)</h2>
			<p class="description">Rebrand the plugin interface for your clients. Changes will apply to the main menu and dashboard titles.</p>
			<table class="form-table">
				<tr>
					<th scope="row">Custom Plugin Name</th>
					<td><input type="text" name="leadflow_custom_name" value="<?php echo esc_attr( get_option( 'leadflow_custom_name', 'LeadFlow Pro' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Custom Brand Color</th>
					<td><input type="color" name="leadflow_custom_color" value="<?php echo esc_attr( get_option( 'leadflow_custom_color', '#6366f1' ) ); ?>"></td>
				</tr>
			</table>
		</div>

		<div id="webhooks" class="settings-section" style="display:none;">
			<h2>Advanced Webhook Manager (Pro)</h2>
			<p class="description">Send real-time data to external apps (Zapier, Make, custom APIs) when key events happen.</p>

			<div id="webhookList">
				<?php
				$webhooks = get_option('leadflow_webhooks', array());
				foreach ($webhooks as $i => $wh) : ?>
					<div class="webhook-row chart-box" style="margin-bottom:15px; padding:20px;">
						<p><label>Webhook URL</label><br><input type="url" name="leadflow_webhooks[<?php echo $i; ?>][url]" value="<?php echo esc_url($wh['url']); ?>" class="large-text"></p>
						<p><label>Trigger Events</label><br>
							<?php $evts = isset($wh['events']) ? $wh['events'] : array(); ?>
							<label><input type="checkbox" name="leadflow_webhooks[<?php echo $i; ?>][events][]" value="lead_created" <?php checked(in_array('lead_created', $evts)); ?>> Lead Created</label>
							<label style="margin-left:15px;"><input type="checkbox" name="leadflow_webhooks[<?php echo $i; ?>][events][]" value="lead_qualified" <?php checked(in_array('lead_qualified', $evts)); ?>> Lead Qualified</label>
							<label style="margin-left:15px;"><input type="checkbox" name="leadflow_webhooks[<?php echo $i; ?>][events][]" value="lead_replied" <?php checked(in_array('lead_replied', $evts)); ?>> Lead Replied</label>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" onclick="alert('Multiple webhooks is a Pro feature.')">+ Add New Webhook</button>
		</div>

		<div id="logs" class="settings-section" style="display:none;">
			<h2>System Logs</h2>
			<p class="description">Review the latest background activity, enrichment hits, and API errors.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Time</th><th>Module</th><th>Level</th><th>Message</th></tr></thead>
				<tbody id="systemLogsBody"></tbody>
			</table>
			<p><button type="button" class="button" id="refreshLogsBtn">Refresh Logs</button></p>
		</div>

		<div id="status" class="settings-section" style="display:none;">
			<h2>System Health & Connectivity</h2>
			<table class="form-table">
				<tr>
					<th scope="row">WP-Cron Status</th>
					<td><?php echo ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? '❌ Disabled' : '✅ Active'; ?></td>
				</tr>
				<tr>
					<th scope="row">PHP IMAP Extension</th>
					<td><?php echo function_exists( 'imap_open' ) ? '✅ Installed' : '❌ Not Found (Needed for Inbox)'; ?></td>
				</tr>
				<tr>
					<th scope="row">OpenSSL (Encryption)</th>
					<td><?php echo extension_loaded( 'openssl' ) ? '✅ Enabled' : '❌ Disabled'; ?></td>
				</tr>
				<tr>
					<th scope="row">Database Version</th>
					<td><?php echo esc_html( get_option( 'leadflow_db_version', '1.0.0' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row">OpenAI Connectivity</th>
					<td><?php echo LeadFlow_AI::test_connectivity('openai') ? '✅ Connected' : '❌ Failed (Check API Key)'; ?></td>
				</tr>
				<tr>
					<th scope="row">Gemini Connectivity</th>
					<td><?php echo LeadFlow_AI::test_connectivity('gemini') ? '✅ Connected' : '❌ Failed (Check API Key)'; ?></td>
				</tr>
				<tr>
					<th scope="row">Google Places API</th>
					<td><?php echo LeadFlow_Discovery::test_google_connectivity() ? '✅ Connected' : '❌ Failed (Check API Key)'; ?></td>
				</tr>
			</table>

			<h3>Scraper Queue Status</h3>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Status</th><th>Count</th></tr></thead>
				<tbody id="scraperQueueBody"></tbody>
			</table>
			<p><button type="button" class="button" id="triggerScraperBtn">Process 5 Jobs Now</button></p>
		</div>

		<div id="license" class="settings-section" style="display:none;">
			<h2>License Key & Pro Features</h2>
			<p class="description">Enter your license key to unlock unlimited leads, advanced AI tools, Kanban views, and more.</p>
			<table class="form-table">
				<tr>
					<th scope="row">License Key</th>
					<td>
						<input type="text" name="leadflow_license_key" value="<?php echo esc_attr( LeadFlow_Security::get_decrypted_option( 'leadflow_license_key' ) ); ?>" class="regular-text">
						<p class="description">Enter your license key to enable Pro features.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Status</th>
					<td>
						<span class="license-status <?php echo LeadFlow_License::is_pro() ? 'active' : 'inactive'; ?>">
							<?php echo LeadFlow_License::is_pro() ? 'Active (Pro)' : 'Inactive (Free)'; ?>
						</span>
						<?php if ( ! LeadFlow_License::is_pro() ) : ?>
							<p><button type="button" class="button" id="activateDemoLicense">✨ Activate Demo Pro License</button></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
