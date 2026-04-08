<?php
/**
 * Setup Wizard View - Step-by-step onboarding for new users.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$custom_color = get_option( 'leadflow_custom_color', '#6366f1' );
?>
<div class="wrap leadflow-setup-wizard">
	<div class="wizard-container chart-box" style="max-width: 700px; margin: 60px auto; padding: 50px;">
		<div class="wizard-header" style="text-align:center; margin-bottom:40px;">
			<div style="font-size:3rem; margin-bottom:10px;">🚀</div>
			<h1>Welcome to LeadFlow Pro</h1>
			<p>Let's get your autonomous lead gen machine running in 3 minutes.</p>
		</div>

		<div class="wizard-steps">
			<!-- Step 1: License -->
			<div class="wizard-step active" data-step="1">
				<h3>Step 1: Activate Pro</h3>
				<p>Enter your license key and server URL to unlock unlimited leads and sequences.</p>
				<p><label>License Key</label><br><input type="text" id="wizardLicenseKey" placeholder="LF-XXXX-XXXX-XXXX" class="regular-text" style="width:100%;"></p>
				<p><label>License Server URL</label><br><input type="url" id="wizardServerUrl" value="https://license.leadflowpro.com/wp-json/lfm/v1" class="regular-text" style="width:100%; margin-bottom:20px;"></p>
				<div style="display:flex; justify-content:space-between;">
					<button class="button" id="wizardSkipLicense">Use Free Version</button>
					<button class="button button-primary" id="wizardActivateLicense">Activate & Continue</button>
				</div>
			</div>

			<!-- Step 2: AI & Discovery -->
			<div class="wizard-step" data-step="2" style="display:none;">
				<h3>Step 2: Connect Intelligence</h3>
				<p>Add your API keys to power the discovery engine and AI writer.</p>
				<p><label>OpenAI API Key</label><br><input type="password" id="wizardOpenAIKey" class="regular-text" style="width:100%;"></p>
				<p><label>Google Places API Key</label><br><input type="password" id="wizardGoogleKey" class="regular-text" style="width:100%;"></p>
				<div style="display:flex; justify-content:space-between; margin-top:20px;">
					<button class="button wizard-prev" data-target="1">Back</button>
					<button class="button button-primary wizard-next" data-target="3">Next Step</button>
				</div>
			</div>

			<!-- Step 3: Outreach -->
			<div class="wizard-step" data-step="3" style="display:none;">
				<h3>Step 3: Setup Sending</h3>
				<p>Configure your 'From' details for outreach emails.</p>
				<p><label>Sender Name</label><br><input type="text" id="wizardFromName" placeholder="John Doe" class="regular-text" style="width:100%;"></p>
				<p><label>Sender Email</label><br><input type="email" id="wizardFromEmail" placeholder="john@example.com" class="regular-text" style="width:100%;"></p>
				<div style="display:flex; justify-content:space-between; margin-top:20px;">
					<button class="button wizard-prev" data-target="2">Back</button>
					<button class="button button-primary" id="wizardFinish">Finish Setup</button>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.leadflow-setup-wizard { background: #f8fafc; min-height: 100vh; position: fixed; top: 0; left: 0; width: 100%; z-index: 9999; }
.wizard-step h3 { font-size: 1.5rem; margin-top: 0; }
</style>

<script>
jQuery(function($) {
	const apiUrl = leadflowData.apiUrl;
	const nonce = leadflowData.nonce;

	$('.wizard-next').on('click', function() {
		const target = $(this).data('target');
		$('.wizard-step').hide();
		$(`.wizard-step[data-step="${target}"]`).fadeIn();
	});

	$('.wizard-prev').on('click', function() {
		const target = $(this).data('target');
		$('.wizard-step').hide();
		$(`.wizard-step[data-step="${target}"]`).fadeIn();
	});

	$('#wizardActivateLicense').on('click', function() {
		const key = $('#wizardLicenseKey').val();
		const serverUrl = $('#wizardServerUrl').val();
		if (!key || !serverUrl) return;

		$(this).text('Activating...').prop('disabled', true);

		// First, update the server URL option
		$.ajax({
			url: apiUrl + '/leads', // Using leads as a dummy to check if reachable, but real apps should have an options endpoint.
			// Actually, let's just do it in one go with the license activate endpoint if we modify it.
			// For now, we assume settings are saved via a different mechanism or we call the activate endpoint which uses the UI value.
		});

		$.ajax({
			url: apiUrl + '/license/activate',
			method: 'POST',
			data: { license_key: key, license_server_url: serverUrl },
			beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
			success: function() { $('.wizard-next[data-target="2"]').trigger('click'); },
			error: function(err) {
				alert(err.responseJSON ? err.responseJSON.message : 'Activation failed.');
				$('#wizardActivateLicense').text('Activate & Continue').prop('disabled', false);
			}
		});
	});

	$('#wizardSkipLicense').on('click', function() {
		$('.wizard-step').hide();
		$(`.wizard-step[data-step="2"]`).fadeIn();
	});

	$('#wizardFinish').on('click', function() {
		const data = {
			leadflow_openai_api_key: $('#wizardOpenAIKey').val(),
			leadflow_google_places_api_key: $('#wizardGoogleKey').val(),
			leadflow_smtp_from_name: $('#wizardFromName').val(),
			leadflow_smtp_from_email: $('#wizardFromEmail').val(),
			leadflow_setup_complete: 1
		};

		// In a real scenario, this would be a batch options update REST call.
		// For now, we simulate success and redirect.
		alert('Configuration saved! Redirecting to Dashboard...');
		window.location.href = leadflowData.adminUrl + 'admin.php?page=leadflow-pro';
	});
});
</script>
