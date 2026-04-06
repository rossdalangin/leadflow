<div class="leadflow-ai-settings">
	<h2>AI Provider Configuration</h2>
	<table class="form-table">
		<tr>
			<th scope="row">AI Provider</th>
			<td>
				<label><input type="radio" name="leadflow_ai_provider" value="openai" <?php checked( 'openai', LeadFlow_AI::get_active_provider() ); ?>> ChatGPT (OpenAI)</label><br>
				<label><input type="radio" name="leadflow_ai_provider" value="gemini" <?php checked( 'gemini', LeadFlow_AI::get_active_provider() ); ?>> Google Gemini (1.5 Pro)</label>
			</td>
		</tr>
		<tr>
			<th scope="row">OpenAI API Key</th>
			<td>
				<input type="password" name="leadflow_openai_api_key" value="<?php echo esc_attr( get_option( 'leadflow_openai_api_key' ) ); ?>" class="regular-text">
				<button type="button" class="button test-ai-connection" data-provider="openai">Test Connection</button>
			</td>
		</tr>
		<tr>
			<th scope="row">Gemini API Key</th>
			<td>
				<input type="password" name="leadflow_gemini_api_key" value="<?php echo esc_attr( get_option( 'leadflow_gemini_api_key' ) ); ?>" class="regular-text">
				<button type="button" class="button test-ai-connection" data-provider="gemini">Test Connection</button>
			</td>
		</tr>
		<tr>
			<th scope="row">AI Features</th>
			<td>
				<label><input type="checkbox" name="leadflow_ai_features[]" value="email_writer" checked disabled> Email writer</label><br>
				<label><input type="checkbox" name="leadflow_ai_features[]" value="lead_scorer" checked disabled> Lead scoring</label><br>
				<label><input type="checkbox" name="leadflow_ai_features[]" value="sentiment_analysis" checked disabled> Reply sentiment analysis</label>
			</td>
		</tr>
	</table>

	<h3>Usage this month</h3>
	<?php
	global $wpdb;
	$prefix = $wpdb->prefix . 'leadflow_';
	$openai_used = $wpdb->get_var( "SELECT SUM(tokens_used) FROM {$prefix}ai_usage WHERE provider = 'openai'" ) ?: 0;
	$gemini_used = $wpdb->get_var( "SELECT SUM(tokens_used) FROM {$prefix}ai_usage WHERE provider = 'gemini'" ) ?: 0;
	$openai_budget = get_option( 'leadflow_token_budget_openai', 50000 );
	$gemini_budget = get_option( 'leadflow_token_budget_gemini', 50000 );
	?>
	<div class="ai-usage-grid">
		<div class="ai-usage-card">
			<h4>OpenAI</h4>
			<p class="usage-value"><?php echo number_format($openai_used); ?> tokens</p>
			<p class="usage-limit">Budget: <?php echo number_format($openai_budget); ?></p>
		</div>
		<div class="ai-usage-card">
			<h4>Gemini</h4>
			<p class="usage-value"><?php echo number_format($gemini_used); ?> tokens</p>
			<p class="usage-limit">Budget: <?php echo number_format($gemini_budget); ?></p>
		</div>
	</div>

	<h3>AI Feature Gates (Pro Only)</h3>
	<div class="ai-upsell-box">
		<p>✨ Pro users can switch between providers and have unlimited AI requests.</p>
		<a href="<?php echo admin_url( 'admin.php?page=leadflow-settings#license' ); ?>" class="button">View Upgrade Options</a>
	</div>
</div>
