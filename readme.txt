=== Source Global CDN ===
Contributors: ahdark
Donate link: https://ahdark.com/donate
Tags: cdn, accelerate, 加速, 静态文件
Requires at least: 4.8
Tested up to: 5.8.3
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

自动将WordPress核心内静态文件转移使用Source Global CDN进行托管，减轻站点静态文件加载负担。

== Description ==

该服务会自动将WordPress核心静态文件的引用更改为至Source Global CDN的静态文件引用，从我们的服务获取静态文件有助于减小站点负担和提高加载速度。

关于Source Global CDN，你可以前往 https://www.sourcegcdn.com 获取详细信息。

The service automatically changes references to WordPress core static files to static file references to Source Global CDN, fetching static files from our service can help reduce site load and increase loading speed.

About _Source Global CDN_, you can visit https://www.sourcegcdn.com for more information.

== Frequently Asked Questions ==

= 它会入侵我的站点吗 =

显然不会，他只会改变页面引用的静态文件链接。

= 它能做什么 =

它可以让你的服务器少承受数十个静态文件的负担，同时加快页面加载速度。

== Changelog ==

= 1.0 =
* 加入了加速Gravatar的功能
* 加入了加载`wp-admin`和`wp-includes`目录下静态文件的功能
