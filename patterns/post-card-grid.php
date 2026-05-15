<?php
/**
 * Title: Post Card Grid
 * Slug: drslon-blog/post-card-grid
 * Categories: posts
 * Inserter: yes
 * Description: Compact post grid for landing sections and archives.
 */
?>
<!-- wp:query {"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"displayLayout":{"type":"grid","columnCount":3},"layout":{"type":"constrained"}} -->
<div class="wp-block-query">
	<!-- wp:post-template -->
		<!-- wp:group {"className":"drslon-archive-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group drslon-archive-card">
			<!-- wp:post-featured-image {"isLink":true} /-->

			<!-- wp:group {"className":"drslon-archive-card__body","layout":{"type":"constrained"}} -->
			<div class="wp-block-group drslon-archive-card__body">
				<!-- wp:template-part {"slug":"post-meta"} /-->
				<!-- wp:post-title {"isLink":true,"level":3} /-->
				<!-- wp:post-excerpt {"excerptLength":18,"moreText":""} /-->
				<!-- wp:read-more {"content":"Читать далее","fontSize":"sm"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
