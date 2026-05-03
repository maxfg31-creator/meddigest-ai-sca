<?php
/**
 * Template loading helper.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

final class TemplateLoader
{
    /**
     * Render a plugin template.
     *
     * @param string $template Relative template path.
     * @param array  $vars     Template variables.
     */
    public function render($template, array $vars = [])
    {
        $template = ltrim($template, '/');
        $path     = MEDDIGEST_AI_SCA_DIR . 'templates/' . $template;

        if (!is_readable($path)) {
            return '';
        }

        ob_start();
        extract($vars, EXTR_SKIP);
        include $path;

        return (string) ob_get_clean();
    }
}

