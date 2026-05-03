<?php
/**
 * Plugin Name: MedDigest AI SCA
 * Description: Adds AI SCA credit packs, wallet state, and practice entry points to the existing MedDigest WordPress site.
 * Version: 0.1.0
 * Author: MedDigest
 * Text Domain: meddigest-ai-sca
 * Domain Path: /languages
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('MEDDIGEST_AI_SCA_VERSION')) {
    define('MEDDIGEST_AI_SCA_VERSION', '0.1.0');
}

if (!defined('MEDDIGEST_AI_SCA_FILE')) {
    define('MEDDIGEST_AI_SCA_FILE', __FILE__);
}

if (!defined('MEDDIGEST_AI_SCA_DIR')) {
    define('MEDDIGEST_AI_SCA_DIR', plugin_dir_path(__FILE__));
}

if (!defined('MEDDIGEST_AI_SCA_URL')) {
    define('MEDDIGEST_AI_SCA_URL', plugin_dir_url(__FILE__));
}

if (!defined('MEDDIGEST_AI_SCA_REST_NAMESPACE')) {
    define('MEDDIGEST_AI_SCA_REST_NAMESPACE', 'meddigest-ai/v1');
}

spl_autoload_register(
    static function ($class_name) {
        $prefix = 'MedDigest\\AiSca\\';

        if (0 !== strpos($class_name, $prefix)) {
            return;
        }

        $relative_class = substr($class_name, strlen($prefix));
        $relative_path  = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        $file_path      = MEDDIGEST_AI_SCA_DIR . 'includes/' . $relative_path;

        if (is_readable($file_path)) {
            require_once $file_path;
        }
    }
);

register_activation_hook(MEDDIGEST_AI_SCA_FILE, ['MedDigest\\AiSca\\Activation', 'activate']);
register_deactivation_hook(MEDDIGEST_AI_SCA_FILE, ['MedDigest\\AiSca\\Deactivation', 'deactivate']);

add_action(
    'plugins_loaded',
    static function () {
        MedDigest\AiSca\Plugin::instance()->init();
    }
);

