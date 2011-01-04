<div class="pagination clearfix">
	<span><b>{TOTAL_RECORDS}</b> records | 
	<b>({TOTAL_PAGES})</b> pages</span>
	<ul>
		<!-- BEGIN first -->
			<li>
			<a href="{FIRST_LINK}">First</a>
			</li>
		<!-- END first -->
		<!-- BEGIN pages -->
			<!-- BEGIN current_page -->
				<li>
					<p>{PAGE_NUMBER}</p>
				</li>
			<!-- END current_page -->
			<!-- BEGIN other_page -->
				<li>
					<a href="{PAGE_LINK}">{PAGE_NUMBER}</a>
				</li>
			<!-- END other_page -->
		<!-- END pages -->
		<!-- BEGIN last -->
			<li>
				<a href="{LAST_LINK}">Last</a>
			</li>
		<!-- END last -->
	</ul>
</div>
