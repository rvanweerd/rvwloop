<?php
/*
Plugin Name: rvw Loop
Plugin URI: http://vanweerd.com/how-to-arrange-your-wordpress-posts-in-columns-and-rows
Description: Configurable custom loop with columns/rows, choice of content or excerpt and more, using a shortcode
Version: 0.9.0
Author: Ronald van Weerd
Author URI: http://vanweerd.com
License: GPL2
*/

/*
 * Launch the plugin
 */
add_action( 'plugins_loaded', 'rvw_loop_init' );
function rvw_loop_init() {
    global $wp_scripts;
    add_filter('widget_text', 'do_shortcode');
    wp_enqueue_style('rvw_loop', WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__) , "" , plugin_basename(__FILE__)) . 'css/style.css');

	add_theme_support( 'post-thumbnails' );
	// set the thumbnail image sizes here (more info http://codex.wordpress.org/Function_Reference/add_image_size)
	add_image_size('loop', 120, 120, true);
	add_image_size('loop1', 150, 150, true);
	add_image_size('loop2', 250, 250, true);
}

/*
 * the Loop code, takes arguments fom shortcode
 */
add_action('init', 'rvw_loop');
function rvw_loop() {

	function ShowrvwLoop($atts) {
	extract(shortcode_atts(array(
		"categories" => null,
		"class" => "loop",
		"width" => null,
		"rows" => null,
		"columns" => 1,
		"num_posts" => get_option('posts_per_page'),
		"pagination" => "yes",
		"offset" => 0,
		"content_excerpt" => "excerpt",
		"num_words" => null
	), $atts));

	if ((!$width) && ($columns)) {
		$width = (100/$columns) . "%";
		} else {
		$width = "100%";
		$columns = 1;
	}

	if ((!$pagination == "yes") && ($rows)) {
		$num_posts = $rows * $columns;
	}

	if (!$rows) {
		$rows = 9999;
	}

	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	$query_args = array (
		'posts_per_page' => $num_posts,
		'cat' => $categories,
		'offset' => $offset,
		'paged' => $paged
	);

	global $wp_query;
	$temp = $wp_query;
	$wp_query= null;
	$wp_query = new WP_Query();

	$wp_query->query($query_args);

	$rt = 0;

	$return = '<div class="rvw_loop ' . $class . '">'. PHP_EOL;

	if ($wp_query->have_posts()) : while ($wp_query->have_posts())  : $wp_query->the_post();

		global $post;

		if ($ct && $ct%$columns==0) {
			$return .= '<div class="clearcol"></div>'. PHP_EOL;
			$rt++;
		}

		if ($rt >= $rows) { break; }

		if ($ct >= $num_posts) { break; }

		$return .= '<div class="col" style="width: ' . $width . ';">'. PHP_EOL;

		if ( has_post_thumbnail() ) {
			$return .= '    <a href="' . get_permalink() . '" rel="bookmark">' . get_the_post_thumbnail($post->ID, $class) . '</a>' . PHP_EOL;
		}
		$return .= '    <div class="title"><h2><a href="' . get_permalink() . '" rel="bookmark">' . get_the_title() . '</a></h2></div>' . PHP_EOL;
		$return .= '    <div class="date"><span class="label">Date: </span>' . get_the_time(get_option('date_format' )) . '</div>' . PHP_EOL;
		$return .= '    <div class="author"><span class="label">By: </span>' . get_the_author_link() . '</div>' . PHP_EOL;
		$return .= '    <div class="comments"><span class="label">Comments: </span>' . comments() . '</div>' . PHP_EOL;

		if ($content_excerpt == "content") {
			$return .= '    <div class="content">' . content($num_words) . '</div>'. PHP_EOL;
		} else {
			$return .= '    <div class="content">' . excerpt($num_words) . '</div>'. PHP_EOL;
		}
		$return .= '    <div class="categories"><span class="label">Categories: </span>' . get_the_term_list( $post->ID, 'category', '', ', ', '&nbsp;' ) . '</div>' . PHP_EOL;
		$return .= '    <div class="tags"><span class="label">Tags: </span>' . get_the_term_list( $post->ID, 'post_tag', '', ', ', '&nbsp;' ) . '</div>' . PHP_EOL;

		$return .= '</div>  <!-- end class col -->'. PHP_EOL;

		$ct++;

	endwhile;

	if ($pagination == "yes") :
		$return .= '<!-- Previous/Next page navigation -->'. PHP_EOL;
		$return .= '<div class="paging" style="width: ' . $width . ';">'. PHP_EOL;
		$return .= '<div class="alignleft">' . get_previous_posts_link('&laquo; Previous Page') . '</div>'. PHP_EOL;
		$return .= '<div class="alignright">' . get_next_posts_link('Next Page &raquo;') . '</div>'. PHP_EOL;
		$return .= '</div>'. PHP_EOL;
	endif;

	$wp_query = null; $wp_query = $temp;
	
	else : // do not delete

	$return .= '<div class="post">'. PHP_EOL;
	$return .= '    no items found'. PHP_EOL;
	$return .= '</div>'. PHP_EOL;

	endif; // do not delete

$return .= '</div> <!-- end class rvw_loop -->'. PHP_EOL;

return $return;

}

add_shortcode('rvw_loop', 'ShowrvwLoop');

}

function comments() {
	$num_comments = get_comments_number();
	if ( comments_open() ) {
		if($num_comments == 0) {
			$comments ="No Comments";
			}
			elseif($num_comments > 1) {
				$comments = $num_comments." Comments";
			}
			else {
				$comments ="1 Comment";
			}
		$write_comments = '<a href="' . get_comments_link() .'">'. $comments . '</a>';
	}
	else {
		$write_comments = 'Comments are off for this post';
	}
	return $write_comments;
}

function excerpt($limit) {
	if ($limit) {
	$excerpt = explode(' ', get_the_excerpt(), $limit);

	if (count($excerpt)>=$limit) {
		array_pop($excerpt);
		$excerpt = implode(" ",$excerpt).'...'; }
		else {
		$excerpt = implode(" ",$excerpt);
	}
	$excerpt = preg_replace('`[[^]]*]`','',$excerpt);
	} else {
	$excerpt = get_the_excerpt();
	}
	return $excerpt;
}

function content($limit) {
	if ($limit) {
	$content = explode(' ', get_the_content(), $limit);

	if (count($content)>=$limit) {
		array_pop($content);
		$content = implode(" ",$content).'...'; }
		else {
		$content = implode(" ",$content);
	}

	$content = preg_replace('/[.+]/','', $content);
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]>', $content);
	} else {
	$content = get_the_content();
	}
	return $content;

}

?>