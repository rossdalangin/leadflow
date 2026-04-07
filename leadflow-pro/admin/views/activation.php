<div class="wrap leadflow-activation">
	<div class="activation-card" style="max-width: 600px; margin: 100px auto; text-align: center; background: #fff; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
		<h1 style="font-size: 2.5rem; margin-bottom: 10px;">🚀 Welcome to LeadFlow Pro</h1>
		<p style="font-size: 1.2rem; color: #64748b; margin-bottom: 30px;">Stop manual prospecting and start closing. Please activate your license to unlock the full power of LeadFlow Pro.</p>

		<form id="activationForm" style="text-align: left;">
			<div style="margin-bottom: 20px;">
				<label style="display: block; font-weight: 700; margin-bottom: 8px;">License Key</label>
				<input type="text" id="activationKey" placeholder="LF-XXXX-XXXX-XXXX" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 1.1rem;" required>
			</div>
			<button type="submit" id="activateBtn" class="button button-primary" style="width: 100%; padding: 12px; font-size: 1.1rem; height: auto;">Activate Plugin</button>
		</form>

		<p style="margin-top: 30px; font-size: 0.9rem; color: #94a3b8;">
			Don't have a license? <a href="https://leadflowpro.com/pricing" target="_blank" style="color: #6366f1; font-weight: 600;">Get one here</a>
		</p>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#activationForm').on('submit', function(e) {
			e.preventDefault();
			const key = $('#activationKey').val();
			const btn = $('#activateBtn');

			btn.text('Activating...').prop('disabled', true);

			$.ajax({
				url: leadflowData.apiUrl + '/license/activate',
				method: 'POST',
				data: { license_key: key },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', leadflowData.nonce);
				},
				success: function(response) {
					alert('Activation successful! Welcome aboard.');
					location.reload();
				},
				error: function(err) {
					alert('Activation failed: ' + (err.responseJSON ? err.responseJSON.message : 'Invalid key'));
					btn.text('Activate Plugin').prop('disabled', false);
				}
			});
		});
	});
	</script>
</div>
