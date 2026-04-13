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
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"},"blockGap":"10px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
			<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"md"} /-->
			<!-- wp:template-part {"slug":"post-meta"} /-->
			<!-- wp:post-excerpt {"moreText":"Открыть"} /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
