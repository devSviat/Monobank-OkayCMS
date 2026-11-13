<?php

$lang['sviat__monobank__module_title'] = 'Monobank: інтернет-еквайринг';
$lang['sviat__monobank__api_token']    = 'Токен з кабінету web.monobank.ua';

$lang['sviat__monobank__settings_note_title'] = 'Токен вводиться у способі оплати';
$lang['sviat__monobank__settings_note_text']  = 'Додайте спосіб оплати з модулем Monobank і вставте в нього токен з web.monobank.ua. У кабінеті Monobank більше нічого налаштовувати не треба.';
$lang['sviat__monobank__settings_note_link']  = 'Перейти до способів оплати';

$lang['sviat__monobank__payment_box'] = 'Оплата';
$lang['sviat__monobank__receipt_box'] = 'Фіскальний чек';
$lang['sviat__monobank__receipt_note'] = 'Ці поля потрібні, лише якщо у web.monobank.ua підключено пРРО або Checkbox. Значення беруться з властивостей товару.';
$lang['sviat__monobank__not_sent']    = 'Не передавати';

$lang['sviat__monobank__hold']      = 'Блокувати кошти замість списання (холд)';
$lang['sviat__monobank__hold_hint'] = 'Гроші блокуються на картці покупця, а замовлення одразу позначається оплаченим. Списати чи скасувати блокування треба у web.monobank.ua протягом 9 днів, інакше гроші повернуться покупцю. Працює лише з гривнею.';

$lang['sviat__monobank__basket_lang']       = 'Мова назв товарів у рахунку';
$lang['sviat__monobank__basket_lang_hint']  = 'Цією мовою покупець бачить кошик на сторінці оплати, і нею ж назви товарів і доставки друкуються у фіскальному чеку.';
$lang['sviat__monobank__basket_lang_order'] = 'Мова, якою оформлено замовлення';

$lang['sviat__monobank__sku']      = 'Код товару';
$lang['sviat__monobank__sku_hint'] = 'Властивість з кодом товару для чека. Якщо не вибрати, в чек піде артикул варіанта, а без артикула номер варіанта.';

$lang['sviat__monobank__barcode']      = 'Штрих-код';
$lang['sviat__monobank__barcode_hint'] = 'Властивість зі штрих-кодом товару.';

$lang['sviat__monobank__uktzed']      = 'Код УКТ ЗЕД';
$lang['sviat__monobank__uktzed_hint'] = 'Потрібен для підакцизних товарів: алкоголю, тютюну, пального. Для інших товарів можна не вибирати.';

$lang['sviat__monobank__header']      = 'Текст перед назвою';
$lang['sviat__monobank__header_hint'] = 'Властивість, текст якої чек друкує перед назвою товару.';

$lang['sviat__monobank__footer']      = 'Текст після назви';
$lang['sviat__monobank__footer_hint'] = 'Властивість, текст якої чек друкує після назви товару.';

$lang['sviat__monobank__tax']      = 'Податкова ставка Checkbox';
$lang['sviat__monobank__tax_hint'] = 'Номер ставки, яку ви обрали на порталі Checkbox під час реєстрації каси. Лише для звʼязки з Checkbox: пРРО monopay ставки поки не застосовує. 0 означає не передавати.';
