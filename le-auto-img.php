<?php
/**
 * Plugin Name: LeAutoImg
 * Plugin URI:  https://www.laojiang.me/7242.html
 * Description: 实现无图文章中插入随机的预设值图片功能。公众号：<span style="color: red;">老蒋朋友圈</span>
 * Version: 1.0.0
 * Author: 老蒋和他的小伙伴
 * Author URI: https://www.laojiang.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: https://www.laojiang.me/
 */

if (!defined('ABSPATH')) {
    exit;
}

class LeAutoImg {
    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('le_auto_img_options', array(
            'enabled' => true,
            'images' => array('', ''),
            'alignment' => 'center',
            'custom_css' => '',
            'paragraph_position' => '1',
        ));

        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_filter('the_content', array($this, 'auto_add_image'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_plugin_page() {
        add_options_page(
            'LeAutoImg Settings',
            'LeAutoImg',
            'manage_options',
            'le-auto-img',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h2>LeAutoImg 设置</h2>
            <p>实现无图文章中插入随机的预设值图片功能。插件介绍：<a href="https://www.laojiang.me/7242.html" target="_blank">点击这里</a>。公众号：<span style="color: red;">老蒋朋友圈</span></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('le_auto_img_options_group');
                do_settings_sections('le-auto-img-admin');
                submit_button();
                ?>
            </form>
            <p><img width="150" height="150" src="<?php echo plugins_url('/images/wechat.png', __FILE__); ?>" alt="扫码关注公众号" /></p>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'le_auto_img_options_group',
            'le_auto_img_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'le_auto_img_setting_section',
            '常规设置',
            array($this, 'section_info'),
            'le-auto-img-admin'
        );

        add_settings_field(
            'enabled',
            '启用插件',
            array($this, 'enabled_callback'),
            'le-auto-img-admin',
            'le_auto_img_setting_section'
        );

        add_settings_field(
            'images',
            '预设图片',
            array($this, 'images_callback'),
            'le-auto-img-admin',
            'le_auto_img_setting_section'
        );

        add_settings_field(
            'paragraph_position',
            '插入段落',
            array($this, 'paragraph_position_callback'),
            'le-auto-img-admin',
            'le_auto_img_setting_section'
        );

        add_settings_field(
            'alignment',
            '图片对齐',
            array($this, 'alignment_callback'),
            'le-auto-img-admin',
            'le_auto_img_setting_section'
        );

        add_settings_field(
            'custom_css',
            '自定义CSS',
            array($this, 'custom_css_callback'),
            'le-auto-img-admin',
            'le_auto_img_setting_section'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        
        $new_input['enabled'] = isset($input['enabled']) ? true : false;
        $new_input['images'] = isset($input['images']) ? array_filter($input['images']) : array();
        $new_input['alignment'] = isset($input['alignment']) ? sanitize_text_field($input['alignment']) : 'center';
        $new_input['custom_css'] = isset($input['custom_css']) ? sanitize_textarea_field($input['custom_css']) : '';
        $new_input['paragraph_position'] = isset($input['paragraph_position']) ? sanitize_text_field($input['paragraph_position']) : '1';

        return $new_input;
    }

    public function section_info() {
        echo '配置你的自动图片设置：';
    }

    public function enabled_callback() {
        printf(
            '<input type="checkbox" name="le_auto_img_options[enabled]" %s />',
            (isset($this->options['enabled']) && $this->options['enabled']) ? 'checked' : ''
        );
    }

    public function images_callback() {
        $images = isset($this->options['images']) ? $this->options['images'] : array('', '');
        echo '<div id="image-container">';
        foreach ($images as $index => $image) {
            $this->render_image_field($index, $image);
        }
        echo '</div>';
        echo '<button type="button" id="add-image-field" class="button">添加更多图片</button>';
    }

    private function render_image_field($index, $value) {
        ?>
        <div class="image-field">
            <input type="text" 
                   name="le_auto_img_options[images][]" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text image-url" />
            <button type="button" class="button upload-image">上传图片</button>
            <button type="button" class="button remove-image">删除</button>
            <div class="image-preview"></div>
        </div>
        <?php
    }

    public function alignment_callback() {
        ?>
        <select name="le_auto_img_options[alignment]">
            <option value="left" <?php selected($this->options['alignment'], 'left'); ?>>左对齐</option>
            <option value="center" <?php selected($this->options['alignment'], 'center'); ?>>居中</option>
        </select>
        <?php
    }

    public function custom_css_callback() {
        printf(
            '<textarea name="le_auto_img_options[custom_css]" rows="5" cols="50">%s</textarea>',
            isset($this->options['custom_css']) ? esc_textarea($this->options['custom_css']) : ''
        );
    }

    public function paragraph_position_callback() {
        $position = isset($this->options['paragraph_position']) ? $this->options['paragraph_position'] : '1';
        ?>
        <select name="le_auto_img_options[paragraph_position]">
            <option value="1" <?php selected($position, '1'); ?>>第一段后</option>
            <option value="2" <?php selected($position, '2'); ?>>第二段后</option>
            <option value="3" <?php selected($position, '3'); ?>>第三段后</option>
            <option value="4" <?php selected($position, '4'); ?>>第四段后</option>
            <option value="5" <?php selected($position, '5'); ?>>第五段后</option>
        </select>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_le-auto-img' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'le-auto-img-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_style(
            'le-auto-img-admin',
            plugins_url('css/admin.css', __FILE__)
        );
    }

    public function auto_add_image($content) {
        if (!$this->options['enabled']) {
            return $content;
        }

        // Check if post already has images
        if (preg_match('/<img/', $content)) {
            return $content;
        }

        // Get available images
        $images = array_filter($this->options['images']);
        if (empty($images)) {
            return $content;
        }

        // Get random image
        $random_image = $images[array_rand($images)];
        
        // Get post title
        $post_title = get_the_title();
        
        // Create image HTML
        $alignment_class = $this->options['alignment'] === 'center' ? 'aligncenter' : 'alignleft';
        $image_html = sprintf(
            '<img src="%s" alt="%s" title="%s" class="le-auto-img %s" style="%s"/>',
            esc_url($random_image),
            esc_attr($post_title),
            esc_attr($post_title),
            esc_attr($alignment_class),
            esc_attr($this->options['custom_css'])
        );

        // Get paragraph position
        $position = isset($this->options['paragraph_position']) ? intval($this->options['paragraph_position']) : 1;
        
        // Split content into paragraphs
        $parts = explode('</p>', $content);
        
        // If there are enough paragraphs, insert after specified position
        if (count($parts) >= $position) {
            $parts[$position - 1] .= $image_html;
            return implode('</p>', $parts);
        }
        
        // If not enough paragraphs, add to the end
        return $content . $image_html;
    }
}

// Initialize plugin
add_action('plugins_loaded', array('LeAutoImg', 'get_instance'));

// Register activation hook
register_activation_hook(__FILE__, 'le_auto_img_activate');
function le_auto_img_activate() {
    // Add default options if they don't exist
    if (!get_option('le_auto_img_options')) {
        add_option('le_auto_img_options', array(
            'enabled' => true,
            'images' => array('', ''),
            'alignment' => 'center',
            'custom_css' => '',
            'paragraph_position' => '1',
        ));
    }
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'le_auto_img_deactivate');
function le_auto_img_deactivate() {
    // Cleanup if needed
}

// Register uninstall hook
register_uninstall_hook(__FILE__, 'le_auto_img_uninstall');
function le_auto_img_uninstall() {
    delete_option('le_auto_img_options');
} 