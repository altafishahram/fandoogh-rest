<?php
namespace RMM;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {

    private static $instance = null;
    private $modules = [];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_i18n();
        $this->init_price_formatting();
        $this->init_modules();

        // Self-healing database check
        add_action('admin_init', ['\\RMM\\Core\\Installer', 'maybe_upgrade']);
    }

    private function init_i18n() {
        load_plugin_textdomain(
            'fondogh-online-menu',
            false,
            dirname(RMM_PLUGIN_BASENAME) . '/languages'
        );
    }

    private function init_price_formatting() {
        // Strip trailing zero decimals only for zero-decimal currencies (Rials / Tomans)
        add_filter('woocommerce_price_trim_zeros', function ($trim) {
            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
            return in_array($currency, ['IRT', 'IRR', 'IRHT', 'IRHR', 'TOMAN', 'RIAL'], true);
        });

        add_filter('wc_price_args', function ($args) {
            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
            if (in_array($currency, ['IRT', 'IRR', 'IRHT', 'IRHR', 'TOMAN', 'RIAL'], true)) {
                $args['decimals'] = 0;
            }
            return $args;
        });
    }

    private function init_modules() {
        // Core Modules
        $this->modules['settings']    = new Core\Settings();
        $this->modules['assets']      = new Core\Assets();
        $this->modules['svg']         = new Core\SvgSupport();

        // Branches Modules
        $this->modules['branch_tax']  = new Branches\BranchTaxonomy();
        $this->modules['inventory']   = new Branches\InventoryRepository();
        $this->modules['branch_ctx']  = new Branches\BranchContext();

        // Operation Modes
        $this->modules['mode_mgr']    = new OperationModes\ModeManager();
        $this->modules['table_qr']    = new OperationModes\TableQRGenerator();

        // Phase 2 Modules (Conditional check so Phase 1 runs cleanly even before Phase 2 files are created)
        if (class_exists('RMM\Menu\FoodMeta')) {
            $this->modules['food_meta'] = new Menu\FoodMeta();
        }
        if (class_exists('RMM\Menu\ModifiersEngine')) {
            $this->modules['modifiers'] = new Menu\ModifiersEngine();
        }
        if (class_exists('RMM\Menu\HappyHour')) {
            $this->modules['happy_hour'] = new Menu\HappyHour();
        }
        if (class_exists('RMM\I18n\MultiLangManager')) {
            $this->modules['multilang'] = new I18n\MultiLangManager();
        }

        // Phase 3 Modules (Frontend Dashboard)
        if (class_exists('RMM\FrontendDashboard\PortalRouter')) {
            $this->modules['portal_router'] = new FrontendDashboard\PortalRouter();
        }
        if (class_exists('RMM\FrontendDashboard\AuthController')) {
            $this->modules['auth_ctrl'] = new FrontendDashboard\AuthController();
        }
        if (class_exists('RMM\FrontendDashboard\RestApiController')) {
            $this->modules['rest_api'] = new FrontendDashboard\RestApiController();
        }

        // Phase 4 Modules (Elementor)
        if (class_exists('RMM\Elementor\ElementorIntegration')) {
            $this->modules['elementor'] = new Elementor\ElementorIntegration();
        }

        // Phase 5 Modules (Template Library)
        if (class_exists('RMM\Elementor\TemplateLibrary\TemplateManager')) {
            $this->modules['templates'] = new Elementor\TemplateLibrary\TemplateManager();
        }

        // Phase 6 Module (Design System Studio & Engine)
        if (class_exists('RMM\DesignSystem\DesignSystem')) {
            $this->modules['design_system'] = new DesignSystem\DesignSystem();
        }
    }

    /**
     * Access a loaded module by key
     *
     * @param string $key
     * @return object|null
     */
    public function get_module($key) {
        return isset($this->modules[$key]) ? $this->modules[$key] : null;
    }
}
