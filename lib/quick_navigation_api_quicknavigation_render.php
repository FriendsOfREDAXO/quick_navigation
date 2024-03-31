<?php

/**
 * API function for rendering the quick navigation menu.
 */
class rex_api_quicknavigation_render extends rex_api_function
{
    /**
     * Executes the API function.
     * @return rex_api_result The result of the api-function
     */
    public function execute()
    {
        rex_response::sendContent(FriendsOfRedaxo\QuickNavigation\QuickNavigation::get(), 'text/html');
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
