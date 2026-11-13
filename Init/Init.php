<?php

namespace Okay\Modules\Sviat\Monobank\Init;

use Okay\Core\Modules\AbstractInit;

class Init extends AbstractInit
{
    public function install(): void
    {
        $this->setModuleType(MODULE_TYPE_PAYMENT);
        $this->setBackendMainController('SettingsAdmin');
    }

    public function init(): void
    {
        // addBackendControllerPermission() registers the permission itself — no separate addPermission() needed.
        $this->registerBackendController('SettingsAdmin');
        $this->addBackendControllerPermission('SettingsAdmin', 'sviat__monobank');
    }
}
