<?php

namespace App;

class Branding
{
    private static ?self $instance = null;

    public static array $config = [
        'link' => 'https://think.studio',
        'logo_url' => 'https://raw.githubusercontent.com/dev-think-one/branding/main/assets/studio/logo.png',
        'logo_full_url' => 'https://raw.githubusercontent.com/dev-think-one/branding/main/assets/studio/logo-full.png',
        'logo_full_svg_url' => 'https://raw.githubusercontent.com/dev-think-one/branding/main/assets/studio/logo-full.svg',
        'screenshot_url' => 'https://raw.githubusercontent.com/dev-think-one/branding/main/assets/studio/screenshot.png',
        'badge_bg' => 'white',
    ];

    private function __construct()
    {
    }

    public static function instance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function init(): static
    {
        $instance = static::instance();

        $instance->apply();

        return $instance;
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return static::$config;
        }

        if (isset(static::$config[$key])) {
            return static::$config[$key];
        }

        return $default;
    }

    protected function apply(): void
    {
        /* Login screen */
        \App\add_action('login_head', function () {
            echo $this->customLogoStyles();
        });
        \App\add_filter('login_message', [$this, 'loginMessage']);
        \App\add_filter('login_headerurl', function () {
            return static::config('link');
        });

        /* Admin dashboard */
        \App\add_filter('admin_footer_text', function () {
            echo $this->adminFooterText();
        });
        \App\add_filter('admin_head', function () {
            echo $this->adminStyles();
        });

        /* Site front */
        \App\add_filter('wp_head', function () {
            echo $this->siteHeads();
        });
        if (\App\get_option('branding_reading_setting_field')) {
            \App\add_filter('wp_footer', function () {
                echo $this->maintainerBadge();
            });
        }

        \App\add_action('admin_init', [$this, 'frontendSettings']);
    }

    public function requestIpAddr(): ?string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'] ?? null)) {
            // IP from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null)) {
            // IP pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        return $ip;
    }

    public function loginMessage($message): string
    {
        $text = sprintf(__('System detects your IP as: "%s"'), $this->requestIpAddr());

        return ($message ? $message . "<br>" : '') . "<p style='text-align: center;'>" . $text . "</p>";
    }

    public function customLogoStyles(): string
    {
        return '<style>h1 a { background-image:url("' . static::config('logo_url') . '") !important; background-position: bottom !important; }</style>';
    }

    public function adminFooterText(): string
    {
        return sprintf(
            __('CMS maintained by <a href="%s"><img style="%s" src="%s" alt=""></a>'),
            static::config('link'),
            'width: 119px; transform: translateY(2px)',
            static::config('logo_full_url')
        );
    }

    public function adminStyles(): string
    {
        $styles = '<style>li#wp-admin-bar-wp-logo{ display:none !important; }</style>';

        if ($screenshotUrl = static::config('screenshot_url')) {
            $styles .= '<style>
                .theme-browser .theme .theme-screenshot,
                .theme-overlay .screenshot {
                    background-size: cover;
                    background-image: url(' . $screenshotUrl . ') !important;
                }
            </style>';
        }

        return $styles;
    }

    public function siteHeads(): string
    {
        $comment = sprintf(
            __('Site powered by Think Studio  %s'),
            static::config('link'),
            'width: 119px; transform: translateY(2px)',
            static::config('logo_full_url')
        );

        return '<!-- ' . $comment . ' -->';
    }

    public function maintainerBadge(): string
    {
        $html = '
        <a target="_blank"
           href="'.static::config('link').'"
           style="display: block; position: fixed; bottom: 36px; right: -44px; z-index: 99999; background-color: '.static::config('badge_bg').'; padding: 5px 30px; transform: rotate(-45deg);"
        >
            <img src="'.static::config('logo_full_svg_url').'"  width="120" height="12">
        </a>
        ';

        return $html;
    }

    public function frontendSettings(): void
    {
        \App\register_setting('reading', 'branding_reading_setting_field');

        \App\add_settings_section(
            'frontend_settings_section',
            _('Maintainer branding'),
            null,
            'reading',
            []
        );

        \App\add_settings_field(
            'branding_reading_setting_field',
            'Display maintainer badge',
            [$this, 'frontendSettingEnableBadge'],
            'reading',
            'frontend_settings_section'
        );
    }

    public function frontendSettingEnableBadge(): void
    {
        echo '<input type="checkbox" value="1" name="branding_reading_setting_field" ' . \App\checked(get_option('branding_reading_setting_field'), true, false) . '" />';
    }
}
