<?php

namespace Okay\Modules\Sviat\Monobank\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Entities\FeaturesEntity;

class SettingsAdmin extends IndexAdmin
{
    private const array FEATURE_SETTINGS = ['sku', 'barcode', 'uktzed', 'header', 'footer'];

    public function fetch(FeaturesEntity $featuresEntity): void
    {
        if ($this->request->method('post')) {
            $this->settings->set('sviat__monobank__hold_mode', $this->request->post('hold_mode', 'int'));
            $this->settings->set('sviat__monobank__tax', $this->request->post('tax', 'int'));
            $this->settings->set('sviat__monobank__basket_lang', $this->request->post('basket_lang', 'int'));
            foreach (self::FEATURE_SETTINGS as $key) {
                $this->settings->set('sviat__monobank__' . $key, $this->request->post($key, 'int'));
            }
            $this->design->assign('message_success', true);
        }

        $this->design->assign('features', $featuresEntity->cols(['id', 'name'])->find());
        $this->response->setContent($this->design->fetch('settings.tpl'));
    }
}
