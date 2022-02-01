=== Source Global CDN ===
Contributors: ahdark
Donate link: https://ahdark.com/donate
Tags: cdn, accelerate, 加速, 静态文件
Requires at least: 5.6
Tested up to: 5.9
Stable tag: 1.0.2
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Automatically transfer the static files in the WordPress core and use Source Global CDN for hosting, reducing the load of static files on the site.

== Description ==

The service automatically changes references to WordPress core static files to static file references to Source Global CDN, fetching static files from our service can help reduce site load and increase loading speed.

About _Source Global CDN_, you can visit https://www.sourcegcdn.com for more information.

== Frequently Asked Questions ==

= Will it hack my site? =

Of course not, it just changes the static file links referenced by the page.

= What it can do? =

It can save your server from the burden of dozens of static files, while speeding up page load times.

You can go to <https://www.sourcegcdn.com/public/wordpress/56.html> for more details.

== Changelog ==

= 1.0 =
* 加入了加速Gravatar的功能
* 加入了加载`wp-admin`和`wp-includes`目录下静态文件的功能
