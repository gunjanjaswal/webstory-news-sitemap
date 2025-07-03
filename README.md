# Web Story News Sitemap for WordPress

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A lightweight WordPress plugin that generates a Google News sitemap specifically for Web Stories. This plugin creates a dedicated XML sitemap that follows Google News guidelines for Web Stories, making them more discoverable in Google News and Google Discover.

## Features

- Generates a Google News sitemap specifically for the `web-story` post type
- Creates a clean URL endpoint at `/webstory-news-sitemap.xml`
- Includes proper Google News XML tags including:
  - `<news:publication>`
  - `<news:publication_date>`
  - `<news:title>`
  - `<image:loc>` for featured images
- Formats dates in IST timezone (Asia/Kolkata)
- Uses CDATA sections for titles and image URLs to handle special characters
- Includes `<lastmod>` tags with the last modified date
- Compatible with Yoast SEO and other SEO plugins
- Works with LiteSpeed servers
- Follows Google News guidelines (includes only posts from the last 2 days)

## Installation

1. Upload the `webstory-news-sitemap` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Your sitemap will be available at `https://your-site.com/webstory-news-sitemap.xml`

## Requirements

- WordPress 5.0 or higher
- Web Stories for WordPress plugin
- PHP 7.0 or higher

## Compatibility

This plugin is designed to work alongside other SEO plugins like Yoast SEO. It uses advanced techniques to ensure its sitemap functionality doesn't conflict with other sitemap generators.

## Troubleshooting

If you're having trouble accessing the sitemap at `/webstory-news-sitemap.xml`, try these steps:

1. Go to Settings > Permalinks and click "Save Changes" to flush the rewrite rules
2. If using Yoast SEO, try toggling the XML Sitemaps feature off and on again
3. For LiteSpeed servers, you may need to add this to your .htaccess file:
```
# BEGIN Web Story News Sitemap
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^webstory-news-sitemap\.xml$ /index.php?webstory_news_sitemap=true [L]
</IfModule>
# END Web Story News Sitemap
```

## Google News Guidelines

This plugin follows Google News guidelines for sitemaps:

- Only includes posts from the last 2 days
- Limits the sitemap to 80 posts
- Uses proper Google News XML namespace and tags
- Includes publication name and language

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Gunjan Jaswal (hello@gunjanjaswal.me)

## Support

For support, please open an issue on the GitHub repository or contact the developer directly.
