<?php

namespace FriendsOfRedaxo\QuickNavigation\ApiFunction;

use FriendsOfRedaxo\QuickNavigation\QuickNavigation;
use rex_api_function;
use rex_response;

/**
 * API function for rendering the quick navigation menu.
 */
class MenuRender extends rex_api_function
{
    /**
     * Executes the API function.
     * @return rex_api_result The result of the api-function
     */
    public function execute()
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
