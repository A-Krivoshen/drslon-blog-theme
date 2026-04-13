<?php
/**
 * Title: Post Card Grid
 * Slug: drslon-blog/post-card-grid
 * Categories: posts
 * Inserter: yes
 */
?>
<!-- wp:query {"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"displayLayout":{"type":"grid","columnCount":3},"layout":{"type":"constrained"}} -->
<div class="wp-block-query">
	<!-- wp:post-template -->
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}},"border":{"width":"1px","color":"var:preset|color|border","radius":"16px"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:16px;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
			<!-- wp:post-title {"isLink":true,"level":3} /-->
			<!-- wp:post-excerpt /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
