<div class="wrap leadflow-tasks">
	<h1 class="wp-heading-inline">Global Task Dashboard</h1>
	<p class="description">Manage all pending manual actions and follow-ups across all your leads. Tasks created by campaigns will automatically appear here.</p>
	<hr class="wp-header-end">

	<div class="leadflow-filters">
		<select id="taskStatusFilter">
			<option value="pending">Pending</option>
			<option value="completed">Completed</option>
		</select>
		<button class="button" id="applyTaskFilters">Filter Tasks</button>
	</div>

	<div class="chart-box">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Due Date</th>
					<th>Lead / Business</th>
					<th>Task Type</th>
					<th>Description</th>
					<th>Assigned To</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="globalTasksBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>
</div>

<script>
jQuery(function($) {
	const apiUrl = leadflowData.apiUrl;
	const nonce = leadflowData.nonce;

	function fetchGlobalTasks() {
		const status = $('#taskStatusFilter').val();
		$.ajax({
			url: apiUrl + '/tasks',
			method: 'GET',
			data: { status: status },
			beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
			success: function(tasks) {
				const tbody = $('#globalTasksBody');
				tbody.empty();
				if (tasks.length === 0) {
					tbody.append('<tr><td colspan="6">No tasks found.</td></tr>');
					return;
				}
				tasks.forEach(t => {
					tbody.append(`
						<tr>
							<td>${t.due_date}</td>
							<td><strong>${t.business_name}</strong></td>
							<td><span class="status-badge status-contacted">${t.task_type}</span></td>
							<td>${t.description}</td>
							<td>${t.assignee_name}</td>
							<td>
								<button class="button button-small toggle-task-global" data-id="${t.id}" data-status="${t.status}">${t.status === 'pending' ? 'Complete' : 'Re-open'}</button>
								<button class="button button-small view-lead" data-id="${t.lead_id}">Go to Lead</button>
							</td>
						</tr>
					`);
				});
			}
		});
	}

	fetchGlobalTasks();

	$('#applyTaskFilters').on('click', fetchGlobalTasks);

	$(document).on('click', '.toggle-task-global', function() {
		const id = $(this).data('id');
		const newStatus = $(this).data('status') === 'pending' ? 'completed' : 'pending';
		$.ajax({
			url: apiUrl + '/tasks/' + id,
			method: 'POST',
			data: { status: newStatus },
			beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
			success: function() { fetchGlobalTasks(); }
		});
	});
});
</script>
