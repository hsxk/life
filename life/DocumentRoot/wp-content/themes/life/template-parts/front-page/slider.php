<?php wp_enqueue_script('life_js'，WP_CONTENT_URL.'/themes/life/assets/js/slider.js',array());?>
<?php
$args = array(
		'posts_per_page' => 6,
		'orderby'=>'post_date',
		'post_status'=>'publish'
);
$the_query = new WP_Query( $args );
?>
<section>
	<div id="sliderTop" style="width: 375px;">
		<div class="sliderTop-main">
			 <div class="sliderTop-articles pickup clearfix" style="width: 7500px; margin-left: -375px;">
		 		<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
		 			<article class="article" style="width: 375px;">
						<a href="<?php the_permalink(); ?>">
							<figure>
								<?php the_post_thumbnail();?>
							</figure>
							<div>
								<time datetime="<?php echo get_the_date('Y-m-d');?>"><?php the_date('Y年n月');?></time>
								<h2 class="post-title" style="font-size: 16px;"><?php the_title();?></h2>
							</div>
						</a>
					</article>
				<?php endwhile;?>
			</div>
		</div>
	</div>
</section>
<?php wp_reset_postdata();?>
