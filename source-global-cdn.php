<?php
/**
 * Plugin Name: Source Global CDN
 * Plugin URI: https://www.sourcegcdn.com/public/wordpress/56.html
 * Description: Automatically transfer the static files in the WordPress core and use Source Global CDN for hosting, reducing the load of static files on the site.
 * Author: Source Global CDN
 * Author URI: https://www.sourcegcdn.com
 * Version: 2.0.3
 * Network: True
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: source-global-cdn
 * Domain Path: /languages
 *
 * {Plugin Name} is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * {Plugin Name} is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with {Plugin Name}. If not, see {License URI}.
 */

defined('ABSPATH') || exit;

add_action("init", function () {
    load_plugin_textdomain('source-global-cdn', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

if (!class_exists('SOURCE_GLOBAL_CDN')) {
    class SOURCE_GLOBAL_CDN
    {
        private $page_url;
        public $version = "2.0.3";

        public function __construct()
        {
            $this->page_url = network_admin_url(is_multisite() ? 'admin.php?page=sourcegcdn' : 'options-general.php?page=sourcegcdn');
        }

        public function init()
        {
            if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
                /**
                 * 插件列表项目中增加设置项
                 */
                add_filter(sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', plugin_basename(__FILE__)), function ($links) {
                    return array_merge(
                        ['<a href="' . $this->page_url . '">' . __("Setting") . '</a>'],
                        $links
                    );
                });

                /**
                 * 初始化设置项
                 */
                update_option("source_admin", get_option('source_admin') ?: '1');
                update_option("sdn_gravatar", get_option('sdn_gravatar') ?: '1');


                /**
                 * 禁用插件时删除配置
                 */
                register_deactivation_hook(__FILE__, function () {
                    delete_option("source_admin");
                    delete_option("sdn_gravatar");
                });


                /**
                 * 菜单注册
                 */
                add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', function () {
                    add_menu_page(
                        __("Source Global CDN", "source-global-cdn"),
                        __("Source GCDN", "source-global-cdn"),
                        is_multisite() ? 'manage_network_options' : 'manage_options',
                        'sourcegcdn',
                        [$this, 'options_page_html'],
                        plugin_dir_url(__FILE__) . 'assets/images/menu-icon.png'
                    );
//                    add_submenu_page(
//                        'sourcegcdn',
//                        __("Source Global CDN Options", "source-global-cdn"),
//                        __("Options", "source-global-cdn"),
//                        is_multisite() ? 'manage_network_options' : 'manage_options',
//                        'sourcegcdn-options',
//                        [$this, 'options_page_html'],
//                        5
//                    );
                    add_submenu_page(
                        'sourcegcdn',
                        __("About Source Global CDN", "source-global-cdn"),
                        __("About", "source-global-cdn"),
                        is_multisite() ? 'manage_network_options' : 'manage_options',
                        'sourcegcdn-about',
                        [$this, 'about_page_html'],
                        10
                    );
                });
            }

            if (get_option('sdn_gravatar') == 1 || get_option('source_admin') != 2) { // 启用插件
                add_action('wp_head', function () {
                    echo '<!-- ' . __("This site is accelerated by Source Global CDN, please go to https://www.sourcegcdn.com for project details.", "source-global-cdn") . ' -->';
                    if (get_option('sdn_gravatar') == 1) {
                        echo '<link rel="preconnect" href="//avatar.sourcegcdn.com"/>';
                    }
                    if (get_option('source_admin') != 2) {
                        echo '<link rel="preconnect" href="//wp.sourcegcdn.com"/>';
                    }
                }, 1);
                add_action('admin_head', function () {
                    echo '<!-- ' . __("This site is accelerated by Source Global CDN, please go to https://www.sourcegcdn.com for project details.", "source-global-cdn") . ' -->';
                    if (get_option('sdn_gravatar') == 1) {
                        echo '<link rel="preconnect" href="//avatar.sourcegcdn.com"/>';
                    }
                    if (get_option('source_admin') != 2) {
                        echo '<link rel="preconnect" href="//wp.sourcegcdn.com"/>';
                    }
                }, 1);
                add_action('wp_footer', function () {
                    echo '<script>console.log("' . __("This site is accelerated by Source Global CDN, please go to https://www.sourcegcdn.com for project details.", "source-global-cdn") . '");</script>';
                });
            }

            /**
             * 将WordPress核心所依赖的静态文件访问链接替换为公共资源节点
             */
            if (
                get_option('source_admin') != 2 &&
                !stristr($GLOBALS['wp_version'], 'alpha') &&
                !stristr($GLOBALS['wp_version'], 'beta') &&
                !stristr($GLOBALS['wp_version'], 'RC') &&
                !isset($GLOBALS['lp_version'])
            ) {
                $this->page_str_replace('preg_replace', [
                    '~' . home_url('/') . '(wp-admin|wp-includes)/(css|js)/~',
                    'https://wp.sourcegcdn.com/core/' . $GLOBALS['wp_version'] . '/$1/$2/'
                ], get_option('source_admin'));
            }

            if (is_admin() || wp_doing_cron()) {
                add_action('admin_init', function () {
                    /**
                     * source_admin用以标记用户是否启用管理后台加速功能
                     */
                    register_setting('wpsource', 'source_admin');

                    /**
                     * sdn_gravatar用以标记用户是否启用G家头像加速功能
                     */
                    register_setting('wpsource', 'sdn_gravatar');

                    add_settings_section(
                        'wpsource_section_main',
                        __("Manage", "source-global-cdn"),
                        '',
                        'wpsource'
                    );

                    add_settings_field(
                        'wpsource_field_select_source_admin',
                        __("Core acceleration", "source-global-cdn"),
                        [$this, 'field_source_admin_cb'],
                        'wpsource',
                        'wpsource_section_main'
                    );

                    add_settings_field(
                        'wpsource_field_select_sdn_gravatar',
                        __("Gravatar acceleration", "source-global-cdn"),
                        [$this, 'field_sdn_gravatar_cb'],
                        'wpsource',
                        'wpsource_section_main'
                    );
                });

            }

            if (get_option('sdn_gravatar') == 1) {
                /**
                 * 替换使用sdn.ahdark.com镜像源
                 */
                function get_avatar_from_mirror($avatar)
                {
                    return str_replace(
                        [
                            'www.gravatar.com',
                            '0.gravatar.com',
                            '1.gravatar.com',
                            '2.gravatar.com',
                            'secure.gravatar.com',
                            'cn.gravatar.com',
                            'gravatar.com',
                            "sdn.geekzu.com",
                            "cravatar.cn"
                        ],
                        'avatar.sourcegcdn.com',
                        $avatar
                    );
                }

                add_filter('get_avatar', 'get_avatar_from_mirror');
                add_filter('um_user_avatar_url_filter', 'get_avatar_from_mirror', 1);
                add_filter('bp_gravatar_url', 'get_avatar_from_mirror', 1);
                add_filter('get_avatar_url', 'get_avatar_from_mirror', 1);
            }
        }

        public function field_source_admin_cb()
        {
            $this->field_cb('source_admin', __("Switch the static files that the WordPress core depends on to the resources of <code>wp.sourcegcdn.com/core/</code>, which greatly speeds up the access speed. <br/>For details, please refer to <a href='https://www.sourcegcdn.com/public/wordpress/56.html' rel='noopener'>https://www.sourcegcdn.com/public/wordpress/56.html</a>.", "source-global-cdn"));
        }

        public function field_sdn_gravatar_cb()
        {
            $this->field_cb('sdn_gravatar', __("Use <code>avatar.sourcegcdn.com</code> to speed up your Gravatar while ensuring normal access to the China Mainland. <br/>For details, please refer to <a href='https://www.sourcegcdn.com/public/92.html' rel='noopener'>https://www.sourcegcdn.com/public/92.html</a>.", "source-global-cdn"));
        }

        public function options_page_html()
        {
            if (!current_user_can('activate_plugins'))
                wp_die(__("Insufficient privilege for the required operation"));

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('wpsource_update', 'key') !== false && current_user_can('manage_options')) {
                update_option("source_admin", sanitize_text_field($_POST['source_admin']));
                update_option("sdn_gravatar", sanitize_text_field($_POST['sdn_gravatar']));

                echo '<div class="notice notice-success settings-error is-dismissible"><p><strong>' . __("Saved.", "source-global-cdn") . '</strong></p></div>';
            }

            if (!current_user_can('manage_options')) {
                return;
            }

            settings_errors('wpsource_messages');
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="<?php echo esc_attr($this->page_url); ?>" method="post">
                    <?php
                    wp_nonce_field('wpsource_update', "key");
                    settings_fields('wpsource');
                    do_settings_sections('wpsource');
                    submit_button('Save');
                    ?>
                </form>
            </div>
            <p>
                <?php _e('For detailed updates and project information and introduction, please go to <a href="https://www.sourcegcdn.com" target="_blank" rel="noopener">www.sourcegcdn.com</a>.', "source-global-cdn"); ?>
            </p>
            <?php
        }

        public function about_page_html()
        {
            if (!current_user_can('activate_plugins'))
                wp_die(__("Insufficient privilege for the required operation"));

            if (!current_user_can('manage_options')) {
                return;
            }

            settings_errors('wpsource_messages');
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <h2><?php _e("Introduction", "source-global-cdn"); ?></h2>
                <p>
                    <?php _e("The plugin will automatically change the references to WordPress static files to Source Global CDN, which will speed up static file loading and reduce site load.", "source-global-cdn"); ?>
                </p>
                <h2><?php _e("About the Project", "source-global-cdn"); ?></h2>
                <p>
                    <?php _e("Source Global CDN is a public welfare project initiated by AHdark, which aims to promote the development of open source and provide free static file acceleration services for the whole world.", "source-global-cdn"); ?>
                    <br/>
                    <?php _e("<a href='https://www.sourcegcdn.com' rel='noopener'>https://www.sourcegcdn.com</a> is the official website of the Source Global CDN project, the update information and maintenance of this project Information will be released on this site, and more functions and services under this project will also be published.", "source-global-cdn"); ?>
                    <br/>
                    <?php _e("This plugin has been published to WordPress.org, but its maintenance is still going on in the GitHub repository. If you want, you can view <a href='https://github.com/SourceGlobalCDN/Source-Global-CDN' rel='noopener'>https://github.com/SourceGlobalCDN/Source-Global-CDN</a>", "source-global-cdn"); ?>
                </p>
            </div>
            <?php
        }

        private function field_cb($option_name, $description)
        {
            $option_value = get_option($option_name);
            ?>
            <label>
                <input type="radio" value="1"
                       name="<?php esc_attr_e($option_name); ?>" <?php checked($option_value, '1'); ?>>Enable
            </label>
            <label>
                <input type="radio" value="2"
                       name="<?php esc_attr_e($option_name); ?>" <?php checked($option_value, '2'); ?>>Disable
            </label>
            <p class="description">
                <?php echo $description; ?>
            </p>
            <?php
        }

        /**
         * @param $replace_func string 要调用的字符串关键字替换函数
         * @param $param array 传递给字符串替换函数的参数
         * @param $level int 替换级别
         */
        private function page_str_replace(string $replace_func, array $param, int $level)
        {
            if ($level == 2) {
                return;
            }

            add_action('init', function () use ($replace_func, $param) {
                ob_start(function ($buffer) use ($replace_func, $param) {
                    $param[] = $buffer;

                    return call_user_func_array($replace_func, $param);
                });
            });
        }
    }

    (new SOURCE_GLOBAL_CDN())->init();
}
