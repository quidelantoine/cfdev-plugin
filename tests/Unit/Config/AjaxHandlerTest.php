<?php

namespace Weblitzer\CFDev\Tests\Unit\Config;

use Weblitzer\CFDev\Config\Ajax\AjaxHandler;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class AjaxHandlerTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterAddsAjaxSaveHook(): void
    {
        \Brain\Monkey\Actions\expectAdded('wp_ajax_cfdev_field_ajax_save')->once();
        (new AjaxHandler())->register();
        $this->addToAssertionCount(1);
    }

    public function testRegisterDoesNotAllowUnauthenticated(): void
    {
        // wp_ajax_nopriv_* would expose the endpoint to unauthenticated users
        \Brain\Monkey\Actions\expectAdded('wp_ajax_nopriv_cfdev_field_ajax_save')->never();
        (new AjaxHandler())->register();
        $this->addToAssertionCount(1);
    }
}
