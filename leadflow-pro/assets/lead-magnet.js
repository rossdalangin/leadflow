(function($) {
	'use strict';
	$(function() {
		$('#leadflowAuditForm').on('submit', function(e) {
			e.preventDefault();
			const form = $(this);
			const btn = form.find('button');
			const success = $('#leadflowFormSuccess');
			const data = form.serializeArray().reduce(function(obj, item) {
				obj[item.name] = item.value;
				return obj;
			}, {});

			btn.text('Analyzing...').prop('disabled', true);

			$.ajax({
				url: leadflowMagnet.apiUrl + '/leads',
				method: 'POST',
				data: {
					...data,
					lead_source: 'Lead Magnet Form'
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', leadflowMagnet.nonce);
				},
				success: function() {
					if (leadflowMagnet.redirectUrl) {
						window.location.href = leadflowMagnet.redirectUrl;
					} else {
						form.fadeOut(function() {
							success.fadeIn();
						});
					}
				},
				error: function(err) {
					alert('Error: ' + (err.responseJSON ? err.responseJSON.message : 'Something went wrong.'));
					btn.text('Generate My Free Audit').prop('disabled', false);
				}
			});
		});
	});
})(jQuery);
