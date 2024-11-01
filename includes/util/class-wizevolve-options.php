<?php

if (!class_exists('WizEvolve_Options')) {

    class WizEvolve_Options {
        private $plugin_name;
        private $plugin_slug;
        private $plugin_menu_name;
        private $plugin_description;
        private $plugin_short_description;
        private $plugin_image;
        private $plugin_account_url;
        private $settings_section_count;
        private static $menu_slug = 'wizevolve';

        public function __construct(string $plugin_function, string $plugin_name, string $plugin_slug, string $plugin_description, string $plugin_short_description, string $plugin_image, string $plugin_menu_name = '') {
            $this->plugin_function = $plugin_function;
            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->plugin_menu_name = empty($plugin_menu_name) ? $this->plugin_name : $plugin_menu_name;
            $this->plugin_description = $plugin_description;
            $this->plugin_short_description = $plugin_short_description;
            $this->plugin_image = $plugin_image;
            $this->settings_section_count = 0;
            $this->plugin_account_url = admin_url('admin.php?page=' . $this->plugin_slug . '-account');

            add_filter('wizevolve_plugins', function($plugins) use ($plugin_function, $plugin_name, $plugin_slug, $plugin_short_description, $plugin_image) {
                $plugins[] = [
                    'name' => $plugin_name,
                    'slug' => $plugin_slug,
                    'description' => $plugin_short_description,
                    'image' => $plugin_image,
                    'function' => $plugin_function,
                ];

                return $plugins;
            });

            add_action('admin_head', [$this, 'admin_css']);

            require_once( ABSPATH . 'wp-admin/includes/template.php' );

            add_action('admin_menu', [$this, 'add_menu']);
            add_action('admin_init', [$this, 'settings_init']);
        }

        function admin_css() {
            echo '<style>
                .wizevolve-card
                {
                    padding-left: 160px;
                }

                .wizevolve-card img
                {
                    position: absolute;
                    height: 80%;
                    width: 130px;
                    top: 0;
                    bottom: 0;
                    margin: auto;
                    left: 10px;
                    object-fit: contain;
                }
            </style>';
        }

        public function add_menu(): void
        {
            if (empty($GLOBALS['admin_page_hooks'][self::$menu_slug])) {
                add_menu_page(
                    esc_html__('WizEvolve', 'wizevolve-min-max-quantities'),
                    esc_html__('WizEvolve', 'wizevolve-min-max-quantities'),
                    'manage_options',
                    self::$menu_slug,
                    [$this, 'main_page'],
                    'dashicons-admin-generic'
                );
            }

            add_submenu_page(
                self::$menu_slug,
                $this->plugin_name,
                $this->plugin_menu_name,
                'manage_options',
                $this->plugin_slug,
                [$this, 'settings_page']
            );
        }

        public function main_page(): void
        {
            $plugins = apply_filters('wizevolve_plugins', []);

            echo '<h1>' . esc_html__('WizEvolve', 'wizevolve-min-max-quantities') . '</h1>';
            echo '<p>' . esc_html__('Welcome to the WizEvolve Dashboard. Here, you can see all your activated WizEvolve plugins. To configure a plugin, use the navigation to go to its respective settings page. This dashboard provides an overview and quick access to each plugin\'s individual settings. Use it to manage your plugins effectively.', 'wizevolve-min-max-quantities') . '</p>';


            foreach ($plugins as $plugin) {
                $name = esc_html($plugin['name']);
                $description = esc_html($plugin['description']);
                $image = esc_url($plugin['image']);

                $settings_text = esc_html__('Settings', 'wizevolve-min-max-quantities');
                $settings_url = esc_url(admin_url('admin.php?page=' . esc_attr($plugin['slug'])));
                $setting_html = "<a href=\"${settings_url}\">${settings_text}</a>";

                $account_url = esc_url($plugin['function']()->is_activation_mode() ? admin_url('admin.php?page=' . esc_attr($plugin['slug'])) : admin_url('admin.php?page=' . $plugin['slug'] . '-account'));
                $account_text = $plugin['function']()->is_activation_mode() ? esc_html__('Activate', 'wizevolve-min-max-quantities') : esc_html__('Account', 'wizevolve-min-max-quantities');
                $account_html = $plugin['function']()->is_activation_mode()  || $plugin['function']()->is_registered() ? "<a href=\"${account_url}\">${account_text}</a>" : '';

                $upgrade_url = esc_url($plugin['function']()->get_upgrade_url());
                $upgrade_text = esc_html__('Upgrade Now!', 'wizevolve-min-max-quantities');
                $upgrade_html = !$plugin['function']()->is_activation_mode() && $plugin['function']()->is_not_paying() ? "<a href=\"${upgrade_url}\">${upgrade_text}</a>" : '';


                echo '<div class="card wizevolve-card">
                    <img src="' . esc_url($image) . '" />
                    <h2 class="title">' . wp_kses($name, 'post') . '</h2>
                    <p>
                        ' . esc_html($description) . '
                    </p>
                    <p>
                        ' . wp_kses($setting_html, 'post') . '
                        ' . wp_kses($account_html, 'post') . '
                        ' . wp_kses($upgrade_html, 'post') . '
                    </p>
                </div>';
            }

        }

        public function settings_page(): void
        {
            ?>
            <form action="options.php" method="post">

                <?php
                echo '<h1>' . esc_html($this->plugin_name) . '</h1>';

                echo '<p>' . esc_html($this->plugin_description) . '</p>';

                settings_fields($this->plugin_slug);
                do_settings_sections($this->plugin_slug);
                submit_button('Save Settings');

                ?>
            </form>
            <?php
        }

        public function settings_init(): void
        {
            register_setting($this->plugin_slug, $this->plugin_slug);
        }

        public function start_section($title, $content): void
        {
            $this->settings_section_count++;
            add_settings_section(
                $this->plugin_slug . $this->settings_section_count,
                esc_attr($title),
                function() use ($content){
                    echo "<p>" . esc_html($content) . "</p>";
                },
                $this->plugin_slug
            );
        }

        private function print_description($description, $premium_only): void
        {
            $plugin_function = $this->plugin_function;
            if(!empty($description)) {
                ?>
                <p class="description">
                    <?php
                    if($premium_only && !$plugin_function()->can_use_premium_code()) {
                        echo '<strong>' . esc_html__('Premium Feature', 'wizevolve-min-max-quantities') . '</strong> <a href="' . esc_url($plugin_function()->get_upgrade_url()) . '">' . esc_html__("Upgrade Now!", 'wizevolve-min-max-quantities') . '</a><br />';
                    }
                    echo esc_html($description);
                    ?>
                </p>
                <?php
            } elseif($premium_only && !$plugin_function()->can_use_premium_code()) {
                ?>
                <p class="description">
                    <?php echo  '<strong>' . esc_html__('Premium Feature', 'wizevolve-min-max-quantities') . '</strong> <a href="' . esc_url($plugin_function()->get_upgrade_url()) . '">' . esc_html__("Upgrade Now!", 'wizevolve-min-max-quantities') . '</a>'; ?>
                </p>
                <?php
            }
        }

        public function add_select_option($id, $title, $choices, $description = '', $default = '', $premium_only = false): void
        {
            add_settings_field(
                $id,
                $title,
                function($args) use ($premium_only) {
                    $plugin_function = $this->plugin_function;
                    $options = get_option($this->plugin_slug);
                    $selected_value = $options[$args['label_for']] ?? $args['default'];
                    ?>
                    <select id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($this->plugin_slug . '[' . esc_attr($args['label_for']) . ']'); ?>" <?php echo $premium_only && !$plugin_function()->can_use_premium_code() ? 'disabled' : ''; ?>>
                        <?php
                        foreach ($args['choices'] as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '"' . selected($selected_value, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <?php

                    $this->print_description($args['description'], $premium_only);
                },
                $this->plugin_slug,
                $this->plugin_slug . $this->settings_section_count,
                [
                    'label_for' => $id,
                    'choices'   => $choices,
                    'description' => $description,
                    'default' => $default,
                ]
            );
        }

        public function add_html_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            add_settings_field(
                $id,
                $title,
                function($args) use ($premium_only) {
                    $plugin_function = $this->plugin_function;
                    $options = get_option($this->plugin_slug);
                    $html_content = $options[$args['label_for']] ?? $args['default'];
                    ?>
                    <textarea class="large-text code" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($this->plugin_slug . '[' . esc_attr($args['label_for']) . ']'); ?>" <?php echo $premium_only && !$plugin_function()->can_use_premium_code() ? 'disabled' : ''; ?>><?php echo wp_kses(html_entity_decode($html_content), 'post'); ?></textarea>
                    <?php

                    $this->print_description($args['description'], $premium_only);
                },
                $this->plugin_slug,
                $this->plugin_slug . $this->settings_section_count,
                [
                    'label_for' => $id,
                    'description' => $description,
                    'default' => $default,
                ]
            );
        }

        public function add_textarea_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            add_settings_field(
                $id,
                $title,
                function($args) use ($premium_only) {
                    $options = get_option($this->plugin_slug);
                    $plugin_function = $this->plugin_function;
                    $textarea_content = $options[$args['label_for']] ?? $args['default'];
                    ?>
                    <textarea class="large-text" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($this->plugin_slug . '[' . esc_attr($args['label_for']) . ']'); ?>" <?php echo $premium_only && !$plugin_function()->can_use_premium_code() ? 'disabled' : ''; ?>><?php echo esc_textarea($textarea_content); ?></textarea>
                    <?php

                    $this->print_description($args['description'], $premium_only);
                },
                $this->plugin_slug,
                $this->plugin_slug . $this->settings_section_count,
                [
                    'label_for' => $id,
                    'description' => $description,
                    'default' => $default,
                ]
            );
        }


        // Note: No default options possible. You cannot have a default of `on`, because when
        // you save an empty checkbox, the value is removed from the array. Thus a default of `on`
        // will result in a always on, even when the checkbox is saved as off.
        public function add_checkbox_option($id, $title, $label = '', $description = '', $premium_only = false): void
        {
            add_settings_field(
                $id,
                $title,
                function($args) use ($premium_only) {
                    $options = get_option($this->plugin_slug);
                    $plugin_function = $this->plugin_function;
                    $checkbox_status = $options[$args['label_for']] ?? '';
                    ?>
                    <label for="<?php echo esc_attr($args['label_for']); ?>">
                        <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($this->plugin_slug . '[' . esc_attr($args['label_for']) . ']'); ?>" <?php checked($checkbox_status, 'on'); ?>  <?php echo $premium_only && !$plugin_function()->can_use_premium_code() ? 'disabled' : ''; ?> />

                        <?php echo esc_html($args['label']); ?>
                    </label>

                    <?php

                    $this->print_description($args['description'], $premium_only);
                },
                $this->plugin_slug,
                $this->plugin_slug . $this->settings_section_count,
                [
                    'label_for' => $id,
                    'description' => $description,
                    'label' => $label,
                ]
            );
        }

        private function add_input_option($id, $title, $type, $description = '', $default = '', $premium_only = false): void
        {
            add_settings_field(
                $id,
                $title,
                function($args) use ($premium_only){
                    $options = get_option($this->plugin_slug);
                    $plugin_function = $this->plugin_function;
                    $value = $options[$args['label_for']] ?? $args['default'];
                    ?>
                    <input class="regular-text" type="<?php echo esc_attr($args['type']); ?>" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($this->plugin_slug . '[' . esc_attr($args['label_for']) . ']'); ?>" value="<?php echo esc_attr($value); ?>" <?php echo $premium_only && !$plugin_function()->can_use_premium_code() ? 'disabled' : ''; ?>>
                    <?php

                    $this->print_description($args['description'], $premium_only);
                },
                $this->plugin_slug,
                $this->plugin_slug . $this->settings_section_count,
                [
                    'label_for' => $id,
                    'type' => $type,
                    'description' => $description,
                    'default' => $default,
                ]
            );
        }

        public function add_date_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'date', $description, $default, $premium_only);
        }

        public function add_email_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'email', $description, $default, $premium_only);
        }

        public function add_month_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'month', $description, $default, $premium_only);
        }

        public function add_number_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'number', $description, $default, $premium_only);
        }

        public function add_password_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'password', $description, $default, $premium_only);
        }

        public function add_range_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'range', $description, $default, $premium_only);
        }

        public function add_tel_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'tel', $description, $default, $premium_only);
        }

        public function add_text_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'text', $description, $default, $premium_only);
        }

        public function add_time_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'time', $description, $default, $premium_only);
        }

        public function add_url_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'url', $description, $default, $premium_only);
        }

        public function add_week_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'week', $description, $default, $premium_only);
        }

        public function add_color_option($id, $title, $description = '', $default = '', $premium_only = false): void
        {
            $this->add_input_option($id, $title, 'color', $description, $default, $premium_only);
        }
    }
}
