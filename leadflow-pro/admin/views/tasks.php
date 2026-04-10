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
