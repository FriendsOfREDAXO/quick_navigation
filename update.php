<?php

/**
 * API function for rendering the quick navigation menu. Only used for update from prior versions.
 */
class rex_api_quicknavigation_render extends rex_api_function
{
    /**
     * Executes the API function and sends the quick navigation HTML as the response.
     *
     * @return void
     */
    public function execute(): void
    {
        rex_response::sendContent(QuickNavigation::get(), 'text/html');
        exit;
    }

    /**
     * Indicates that this API function does not require CSRF protection.
     *
     * @return bool
     */
    public function requiresCsrfProtection()
    {
        return false;
    }
}
