<?php

$lang['sviat__monobank__module_title'] = 'Monobank internet acquiring';
$lang['sviat__monobank__api_token']    = 'Token from web.monobank.ua';

$lang['sviat__monobank__settings_note_title'] = 'The token goes into the payment method';
$lang['sviat__monobank__settings_note_text']  = 'Add a payment method that uses the Monobank module and paste the token from web.monobank.ua into it. Nothing else needs setting up in the Monobank account.';
$lang['sviat__monobank__settings_note_link']  = 'Go to payment methods';

$lang['sviat__monobank__payment_box'] = 'Payment';
$lang['sviat__monobank__receipt_box'] = 'Fiscal receipt';
$lang['sviat__monobank__receipt_note'] = 'You only need these fields if a cash register (PRRO) or Checkbox is connected in web.monobank.ua. Values come from product features.';
$lang['sviat__monobank__not_sent']    = 'Do not send';

$lang['sviat__monobank__hold']      = 'Block the funds instead of charging (hold)';
$lang['sviat__monobank__hold_hint'] = 'The money is blocked on the buyer\'s card and the order is marked paid right away. Charge or release the hold in web.monobank.ua within 9 days, otherwise the money goes back to the buyer. UAH only.';

$lang['sviat__monobank__basket_lang']       = 'Language of product names in the invoice';
$lang['sviat__monobank__basket_lang_hint']  = 'The buyer sees the basket on the payment page in this language, and the fiscal receipt prints product and delivery names in it.';
$lang['sviat__monobank__basket_lang_order'] = 'Language the order was placed in';

$lang['sviat__monobank__sku']      = 'Product code';
$lang['sviat__monobank__sku_hint'] = 'Feature with the product code for the receipt. If not set, the variant SKU is sent, or the variant number when there is no SKU.';

$lang['sviat__monobank__barcode']      = 'Barcode';
$lang['sviat__monobank__barcode_hint'] = 'Feature with the product barcode.';

$lang['sviat__monobank__uktzed']      = 'UKTZED code';
$lang['sviat__monobank__uktzed_hint'] = 'Needed for excise goods: alcohol, tobacco, fuel. Other products can leave it unset.';

$lang['sviat__monobank__header']      = 'Text before the name';
$lang['sviat__monobank__header_hint'] = 'Feature whose text the receipt prints before the product name.';

$lang['sviat__monobank__footer']      = 'Text after the name';
$lang['sviat__monobank__footer_hint'] = 'Feature whose text the receipt prints after the product name.';

$lang['sviat__monobank__tax']      = 'Checkbox tax rate';
$lang['sviat__monobank__tax_hint'] = 'Number of the rate you chose on the Checkbox portal when registering the cash register. Checkbox link only: monopay PRRO does not apply rates yet. 0 means do not send.';
