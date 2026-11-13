{$meta_title = $btr->sviat__monobank__module_title scope=global}

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_page">{$btr->sviat__monobank__module_title|escape}</div>
    </div>
</div>

{if $message_success}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">{$btr->general_settings_saved|default:'Settings saved'|escape}</div>
                </div>
            </div>
        </div>
    </div>
{/if}

<form method="post">
    <input type="hidden" name="session_id" value="{$smarty.session.id|escape}">

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="alert alert--icon alert--info">
                <div class="alert__content">
                    <div class="alert__title">{$btr->sviat__monobank__settings_note_title|escape}</div>
                    <p>{$btr->sviat__monobank__settings_note_text|escape}</p>
                    <a href="{url controller=PaymentMethodsAdmin}">{$btr->sviat__monobank__settings_note_link|escape}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-12">
            <div class="boxed mb-2">
                <div class="heading_box mb-2">{$btr->sviat__monobank__payment_box|escape}</div>

                <div class="form-group">
                    <div class="heading_label">
                        <span>{$btr->sviat__monobank__basket_lang|escape}</span>
                        <i class="fn_tooltips" title="{$btr->sviat__monobank__basket_lang_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                    </div>
                    <select name="basket_lang" class="selectpicker form-control">
                        <option value="0">{$btr->sviat__monobank__basket_lang_order|escape}</option>
                        {foreach $languages as $language}
                            <option value="{$language->id}"{if $settings->sviat__monobank__basket_lang == $language->id} selected{/if}>{$language->name|escape}</option>
                        {/foreach}
                    </select>
                </div>

                <div class="form-group">
                    <div class="activity_of_switch activity_of_switch--left">
                        <div class="activity_of_switch_item">
                            <div class="okay_switch clearfix">
                                <label class="switch_label">
                                    {$btr->sviat__monobank__hold|escape}
                                    <i class="fn_tooltips" title="{$btr->sviat__monobank__hold_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                                </label>
                                <label class="switch switch-default">
                                    <input class="switch-input" name="hold_mode" value="1" type="checkbox"{if $settings->sviat__monobank__hold_mode} checked{/if}>
                                    <span class="switch-label"></span>
                                    <span class="switch-handle"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="boxed mb-2">
                <div class="heading_box mb-1">{$btr->sviat__monobank__receipt_box|escape}</div>
                <p class="text_grey mb-2">{$btr->sviat__monobank__receipt_note|escape}</p>
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__sku|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__sku_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <select name="sku" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{$btr->sviat__monobank__not_sent|escape}</option>
                                {foreach $features as $feature}
                                    <option value="{$feature->id}"{if $settings->sviat__monobank__sku == $feature->id} selected{/if}>{$feature->name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__barcode|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__barcode_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <select name="barcode" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{$btr->sviat__monobank__not_sent|escape}</option>
                                {foreach $features as $feature}
                                    <option value="{$feature->id}"{if $settings->sviat__monobank__barcode == $feature->id} selected{/if}>{$feature->name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__uktzed|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__uktzed_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <select name="uktzed" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{$btr->sviat__monobank__not_sent|escape}</option>
                                {foreach $features as $feature}
                                    <option value="{$feature->id}"{if $settings->sviat__monobank__uktzed == $feature->id} selected{/if}>{$feature->name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__header|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__header_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <select name="header" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{$btr->sviat__monobank__not_sent|escape}</option>
                                {foreach $features as $feature}
                                    <option value="{$feature->id}"{if $settings->sviat__monobank__header == $feature->id} selected{/if}>{$feature->name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__footer|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__footer_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <select name="footer" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{$btr->sviat__monobank__not_sent|escape}</option>
                                {foreach $features as $feature}
                                    <option value="{$feature->id}"{if $settings->sviat__monobank__footer == $feature->id} selected{/if}>{$feature->name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <div class="heading_label">
                                <span>{$btr->sviat__monobank__tax|escape}</span>
                                <i class="fn_tooltips" title="{$btr->sviat__monobank__tax_hint|escape}">{include file='svg_icon.tpl' svgId='icon_tooltips'}</i>
                            </div>
                            <input type="number" name="tax" min="0" class="form-control" value="{$settings->sviat__monobank__tax|default:0|escape}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-12 col-md-12 clearfix">
            <button type="submit" class="btn btn_small btn_blue float-md-right">
                {include file='svg_icon.tpl' svgId='checked'}
                <span>{$btr->general_apply|escape}</span>
            </button>
        </div>
    </div>
</form>
