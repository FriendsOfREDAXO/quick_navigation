<?php
/**
 * @package redaxo\structure
 *
 * @internal
 */
class rex_api_quicknavigation_render extends rex_api_function
{
    public function execute()
    {
        rex_response::sendContent(QuickNavigation::get(), 'text/html');
        exit();
    }

    public function requiresCsrfProtection()
    {
        return true;
    }
}
