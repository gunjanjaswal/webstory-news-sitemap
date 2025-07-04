<?php
/**
 * Plugin Name: Web Story News Sitemap
 * Description: Generates a Google News sitemap for web stories
 * Version: 1.1
 * Author: Gunjan Jaswaal
 * Author URI: https://www.gunjanjaswal.me
 * Author Email: hello@gunjanjaswal.me
 * License: GPL2
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define constants
define('WEBSTORY_NEWS_SITEMAP_VERSION', '1.1');
define('WEBSTORY_NEWS_SITEMAP_PATH', plugin_dir_path(__FILE__));
define('WEBSTORY_NEWS_SITEMAP_URL', plugin_dir_url(__FILE__));

/**
 * Add a direct access option that bypasses rewrite rules
 */
function webstory_news_direct_access() {
	if (isset($_GET['webstory_news_direct']) && $_GET['webstory_news_direct'] === 'sitemap') {
		// Create an instance of the plugin class
		$sitemap = new WebStory_News_Sitemap();
		
		// Generate the sitemap
		$sitemap->generate_sitemap();
		exit;
	}
}
add_action('init', 'webstory_news_direct_access', 999);

// Direct file access for local development
// This allows accessing the sitemap directly via this file
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
	// Only allow direct access in development environments
	if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
		strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || 
		strpos($_SERVER['HTTP_HOST'], '.test') !== false || 
		strpos($_SERVER['HTTP_HOST'], '.local') !== false) {
		
		// Create an instance of the plugin class
		$sitemap = new WebStory_News_Sitemap();
		
		// Generate the sitemap
		$sitemap->generate_sitemap();
		exit;
	}
}

class WebStory_News_Sitemap {

	function __construct() {
		// Add query vars filter
		add_filter('query_vars', array($this, 'add_query_vars'));
		
		// Add init action for rewrite rules
		add_action('init', array($this, 'add_rewrite_rules'));
		
		// Add parse request action
		add_action('parse_request', array($this, 'handle_request'));
		
		// Add early template_redirect hook to catch the request before Yoast
		add_action('template_redirect', array($this, 'early_catch_request'), -100);
	}
	
	/**
	 * Early catch for the sitemap request before Yoast can handle it
	 */
	function early_catch_request() {
		// Get the request URI
		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		
		// Check if this is our sitemap URL
		if (preg_match('/\/webstory-news-sitemap\.xml$/', $request_uri)) {
			// Generate our sitemap
			$this->generate_sitemap();
			exit;
		}
	}
	
	/**
	 * Add query vars for the sitemap
	 */
	function add_query_vars($vars) {
		$vars[] = 'webstory_news_sitemap';
		return $vars;
	}
	
	/**
	 * Add rewrite rules for the sitemap
	 */
	function add_rewrite_rules() {
		// Skip default WordPress sitemap
		if (!defined('WPCOM_SKIP_DEFAULT_SITEMAP')) {
			define('WPCOM_SKIP_DEFAULT_SITEMAP', true);
		}
		
		// Add rewrite tag
		add_rewrite_tag('%webstory_news_sitemap%', 'true');
		
		// Add multiple rewrite rules to increase compatibility
		// Standard rule
		add_rewrite_rule(
			'webstory-news-sitemap\.xml$',
			'index.php?webstory_news_sitemap=true',
			'top'
		);
		
		// Alternative rule with leading slash
		add_rewrite_rule(
			'^/webstory-news-sitemap\.xml$',
			'index.php?webstory_news_sitemap=true',
			'top'
		);
		
		// Rule for subdirectory WordPress installations
		add_rewrite_rule(
			'.*/webstory-news-sitemap\.xml$',
			'index.php?webstory_news_sitemap=true',
			'top'
		);
		
		// Handle Yoast SEO compatibility
		$this->handle_yoast_compatibility();
	}
	
	/**
	 * Handle compatibility with Yoast SEO plugin
	 */
	function handle_yoast_compatibility() {
		// Check if Yoast SEO is active
		if (!defined('WPSEO_VERSION')) {
			return;
		}
		
		// Add a higher priority action to ensure our rules take precedence
		add_action('init', array($this, 'ensure_priority'), 999);
		
		// We'll only use the early_catch_request method which is more targeted
		// and doesn't interfere with Yoast's normal operation
	}

	

	/**
	 * Ensure our rewrite rules take priority
	 */
	function ensure_priority() {
		// Re-add our rewrite rules to ensure they take precedence
		add_rewrite_rule(
			'webstory-news-sitemap\.xml$',
			'index.php?webstory_news_sitemap=true',
			'top'
		);
	}
	
	/**
	 * Handle the sitemap request
	 */
	function handle_request($wp) {
		if (isset($wp->query_vars['webstory_news_sitemap']) && $wp->query_vars['webstory_news_sitemap'] === 'true') {
			$this->generate_sitemap();
			exit;
		}
	}
	
	/**
	 * Generate the Google News sitemap for web stories
	 */
	function generate_sitemap() {
		// Set the content type
		header('Content-Type: application/xml; charset=UTF-8');
		
		// Only include web stories published in the last two days (Google News requirement)
		$args = array(
			'post_type'      => 'web-story',
			'post_status'    => 'publish',
			'date_query'     => array(
				array(
					'after'     => '2 days ago',
					'inclusive' => true,
				),
			),
			'posts_per_page' => 80 // Google allows up to 1000 URLs in a news sitemap
		);
		
		$query = new WP_Query($args);
		
		// Start XML output
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' . "\n";
		echo '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" ' . "\n";
		echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
		
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				
				$post_id = get_the_ID();
				$permalink = get_permalink();
				
				// Get post date in IST timezone (UTC+5:30)
				$post_date = get_post_time('Y-m-d H:i:s', false, $post_id);
				$date_obj = new DateTime($post_date, new DateTimeZone('UTC'));
				$date_obj->setTimezone(new DateTimeZone('Asia/Kolkata')); // IST timezone
				$publication_date = $date_obj->format('c'); // ISO 8601 format
				
				$title = get_the_title();
				
				// Start URL item
				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url($permalink) . "</loc>\n";
				
				// Add news specific tags
				echo "\t\t<news:news>\n";
				echo "\t\t\t<news:publication>\n";
				echo "\t\t\t\t<news:name>" . esc_html(get_bloginfo('name')) . "</news:name>\n";
				echo "\t\t\t\t<news:language>" . substr(get_bloginfo('language'), 0, 2) . "</news:language>\n";
				echo "\t\t\t</news:publication>\n";
				echo "\t\t\t<news:publication_date>" . $publication_date . "</news:publication_date>\n";
				echo "\t\t\t<news:title><![CDATA[" . $title . "]]></news:title>\n";
				echo "\t\t</news:news>\n";
				
				// Add featured image if available
				if (has_post_thumbnail($post_id)) {
					$image_id = get_post_thumbnail_id($post_id);
					$image_url = wp_get_attachment_url($image_id);
					$image_caption = wp_get_attachment_caption($image_id) ?: get_the_title($image_id);
					
					echo "\t\t<image:image>\n";
					echo "\t\t\t<image:loc><![CDATA[" . $image_url . "]]></image:loc>\n";
					echo "\t\t</image:image>\n";
				}
				
				// Add lastmod tag with the post's modified date in IST timezone
				$modified_date = get_post_modified_time('Y-m-d H:i:s', false, $post_id);
				$modified_date_obj = new DateTime($modified_date, new DateTimeZone('UTC'));
				$modified_date_obj->setTimezone(new DateTimeZone('Asia/Kolkata')); // IST timezone
				$lastmod_date = $modified_date_obj->format('c'); // ISO 8601 format
				echo "\t\t<lastmod>" . $lastmod_date . "</lastmod>\n";
				
				// End URL item
				echo "\t</url>\n";
			}
		}
		
		// Reset post data
		wp_reset_postdata();
		
		// End XML output
		echo '</urlset>';
		exit;
	}
}

// Initialize the plugin
$webstory_news_sitemap = new WebStory_News_Sitemap();

/**
 * Function to flush rewrite rules on plugin activation
 */
function webstory_news_sitemap_activate() {
	// Create an instance of the plugin class
	$plugin = new WebStory_News_Sitemap();
	
	// Add rewrite rules
	$plugin->add_rewrite_rules();
	
	// Flush rewrite rules
	flush_rewrite_rules();
	
	// Add direct rule to .htaccess for LiteSpeed servers
	webstory_news_sitemap_add_htaccess_rule();
}
register_activation_hook(__FILE__, 'webstory_news_sitemap_activate');

/**
 * Add direct rewrite rule to .htaccess for LiteSpeed servers
 */
function webstory_news_sitemap_add_htaccess_rule() {
	// Only proceed if we can modify the .htaccess file
	if (!is_writable(ABSPATH . '.htaccess')) {
		return;
	}
	
	// Get current .htaccess content
	$htaccess_content = file_get_contents(ABSPATH . '.htaccess');
	
	// Check if our rule already exists
	if (strpos($htaccess_content, '# BEGIN Web Story News Sitemap') !== false) {
		return; // Rule already exists
	}
	
	// Create our custom rule
	$sitemap_rule = "\n# BEGIN Web Story News Sitemap\n";
	$sitemap_rule .= "<IfModule mod_rewrite.c>\n";
	$sitemap_rule .= "RewriteEngine On\n";
	$sitemap_rule .= "RewriteRule ^webstory-news-sitemap\.xml$ /index.php?webstory_news_sitemap=true [L]\n";
	$sitemap_rule .= "</IfModule>\n";
	$sitemap_rule .= "# END Web Story News Sitemap\n";
	
	// Add our rule after WordPress rules
	$pattern = '/(# BEGIN WordPress[\s\S]+?# END WordPress)/i';
	$replacement = '$1' . $sitemap_rule;
	$new_htaccess = preg_replace($pattern, $replacement, $htaccess_content);
	
	// If no WordPress rules found, just append to the end
	if ($new_htaccess === $htaccess_content) {
		$new_htaccess .= $sitemap_rule;
	}
	
	// Write the new content back to .htaccess
	file_put_contents(ABSPATH . '.htaccess', $new_htaccess);
}

/**
 * Function to flush rewrite rules on plugin deactivation
 */
function webstory_news_sitemap_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();
	
	// Remove our custom rules from .htaccess
	webstory_news_sitemap_remove_htaccess_rule();
}
register_deactivation_hook(__FILE__, 'webstory_news_sitemap_deactivate');

/**
 * Remove our custom rules from .htaccess
 */
function webstory_news_sitemap_remove_htaccess_rule() {
	// Only proceed if we can modify the .htaccess file
	if (!is_writable(ABSPATH . '.htaccess')) {
		return;
	}
	
	// Get current .htaccess content
	$htaccess_content = file_get_contents(ABSPATH . '.htaccess');
	
	// Remove our custom rules
	$pattern = '/\n# BEGIN Web Story News Sitemap[\s\S]+?# END Web Story News Sitemap\n/i';
	$new_htaccess = preg_replace($pattern, '', $htaccess_content);
	
	// Write the new content back to .htaccess
	file_put_contents(ABSPATH . '.htaccess', $new_htaccess);
}

