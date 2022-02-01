<?php
/**
 * Plugin Name: Source Global CDN
 * Plugin URI: https://www.sourcegcdn.com/public/wordpress/56.html
 * Description: Automatically transfer the static files in the WordPress core and use Source Global CDN for hosting, reducing the load of static files on the site.
 * Author: Source Global CDN
 * Author URI: https://www.sourcegcdn.com
 * Version: 1.0.2
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

if (!class_exists('SOURCE_GLOBAL_CDN')) {
    class SOURCE_GLOBAL_CDN
    {
        private $page_url;

        public function __construct()
        {
            $this->page_url = network_admin_url(is_multisite() ? 'settings.php?page=source-global-cdn' : 'options-general.php?page=source-global-cdn');
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
                    add_submenu_page(
                        is_multisite() ? 'settings.php' : 'options-general.php',
                        __("Source Global CDN", "source-global-cdn"),
                        __("Source Global CDN Settings", "source-global-cdn"),
                        is_multisite() ? 'manage_network_options' : 'manage_options',
                        'source-global-cdn',
                        [$this, 'options_page_html']
                    );
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
                function get_avatar_from_sdn($avatar)
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
                        'sdn.ahdark.com',
                        $avatar
                    );
                }

                add_filter('get_avatar', 'get_avatar_from_sdn');
                add_filter('um_user_avatar_url_filter', 'get_avatar_from_sdn', 1);
                add_filter('bp_gravatar_url', 'get_avatar_from_sdn', 1);
                add_filter('get_avatar_url', 'get_avatar_from_sdn', 1);
            }
        }

        public function field_source_admin_cb()
        {
            $this->field_cb('source_admin', __("Switch the static files that the WordPress core depends on to the resources of <code>wp.sourcegcdn.com/core/</code>, which greatly speeds up the access speed.", "source-global-cdn"));
        }

        public function field_sdn_gravatar_cb()
        {
            $this->field_cb('sdn_gravatar', __("Use <code>sdn.ahdark.com</code> to speed up your Gravatar while ensuring normal access to the China Mainland.", "source-global-cdn"));
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
                <?php _e("For detailed updates and project information and introduction, please go to <a href=\"https://www.sourcegcdn.com\" target=\"_blank\" rel=\"noopener\">www.sourcegcdn.com</a>.", "source-global-cdn"); ?>
            </p>
            <?php
        }

        private function field_cb($option_name, $description)
        {
            $option_value = get_option($option_name);
            ?>
            <label>
                <input type="radio" value="1"
                       name="<?php echo esc_attr($option_name); ?>" <?php checked($option_value, '1'); ?>>Enable
            </label>
            <label>
                <input type="radio" value="2"
                       name="<?php echo esc_attr($option_name); ?>" <?php checked($option_value, '2'); ?>>Disable
            </label>
            <p class="description">
                <?php echo esc_attr($description); ?>
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
