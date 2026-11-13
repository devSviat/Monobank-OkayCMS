<?php

$lang['sviat__monobank__module_title'] = 'Monobank: интернет-эквайринг';
$lang['sviat__monobank__api_token']    = 'Токен из кабинета web.monobank.ua';

$lang['sviat__monobank__settings_note_title'] = 'Токен вводится в способе оплаты';
$lang['sviat__monobank__settings_note_text']  = 'Добавьте способ оплаты с модулем Monobank и вставьте в него токен из web.monobank.ua. В кабинете Monobank больше ничего настраивать не нужно.';
$lang['sviat__monobank__settings_note_link']  = 'Перейти к способам оплаты';

$lang['sviat__monobank__payment_box'] = 'Оплата';
$lang['sviat__monobank__receipt_box'] = 'Фискальный чек';
$lang['sviat__monobank__receipt_note'] = 'Эти поля нужны, только если в web.monobank.ua подключен пРРО или Checkbox. Значения берутся из свойств товара.';
$lang['sviat__monobank__not_sent']    = 'Не передавать';

$lang['sviat__monobank__hold']      = 'Блокировать средства вместо списания (холд)';
$lang['sviat__monobank__hold_hint'] = 'Деньги блокируются на карте покупателя, а заказ сразу отмечается оплаченным. Списать или снять блокировку нужно в web.monobank.ua в течение 9 дней, иначе деньги вернутся покупателю. Работает только с гривной.';

$lang['sviat__monobank__basket_lang']       = 'Язык названий товаров в счёте';
$lang['sviat__monobank__basket_lang_hint']  = 'На этом языке покупатель видит корзину на странице оплаты, и на нём же названия товаров и доставки печатаются в фискальном чеке.';
$lang['sviat__monobank__basket_lang_order'] = 'Язык, на котором оформлен заказ';

$lang['sviat__monobank__sku']      = 'Код товара';
$lang['sviat__monobank__sku_hint'] = 'Свойство с кодом товара для чека. Если не выбрать, в чек пойдёт артикул варианта, а без артикула номер варианта.';

$lang['sviat__monobank__barcode']      = 'Штрихкод';
$lang['sviat__monobank__barcode_hint'] = 'Свойство со штрихкодом товара.';

$lang['sviat__monobank__uktzed']      = 'Код УКТ ВЭД';
$lang['sviat__monobank__uktzed_hint'] = 'Нужен для подакцизных товаров: алкоголя, табака, топлива. Для остальных товаров можно не выбирать.';

$lang['sviat__monobank__header']      = 'Текст перед названием';
$lang['sviat__monobank__header_hint'] = 'Свойство, текст которого чек печатает перед названием товара.';

$lang['sviat__monobank__footer']      = 'Текст после названия';
$lang['sviat__monobank__footer_hint'] = 'Свойство, текст которого чек печатает после названия товара.';

$lang['sviat__monobank__tax']      = 'Налоговая ставка Checkbox';
$lang['sviat__monobank__tax_hint'] = 'Номер ставки, выбранной на портале Checkbox при регистрации кассы. Только для связки с Checkbox: пРРО monopay ставки пока не применяет. 0 означает не передавать.';
