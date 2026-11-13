# Тести модуля Monobank

Тести модульні: сайт не піднімають, у базу й до monobank не ходять. Замість сервісів ядра й клієнта API вони підставляють заглушки.

Що перевіряють:

- кошик рахунку і суми в копійках (`InvoiceRequestBuilderTest`, `MoneyConverterTest`);
- пошук замовлення лише за секретним кодом (`InvoiceOrderLookupTest`);
- підпис вебхука і кеш ключа (`WebhookVerifierTest`);
- оплачений вебхук: позначка, листи, закриття, історія, повтор, неспівпадіння суми й валюти (`PaidWebhookTest`);
- невдала оплата й повернення в історії (`PaymentOutcomeHistoryTest`).

## Запуск

В архів релізу тека `tests/` не входить, візьміть її з репозиторію. Тести мають лежати поруч із тестами ядра, за адресою, що дзеркалить адресу модуля:

```
{OkayCMS_root}/
├── Okay/Modules/Sviat/Monobank/     ← модуль
└── tests/Modules/Sviat/Monobank/    ← його тести
```

```bash
cd {OkayCMS_root}
mkdir -p tests/Modules/Sviat/Monobank
cp -r ~/Monobank-OkayCMS/tests/. tests/Modules/Sviat/Monobank/
vendor/bin/phpunit tests/Modules/Sviat/Monobank
```

Потрібен PHP 8.5 і dev-залежності OkayCMS (`composer install`).

## CI

На кожен PR у `main`:

1. `php -l` на коді модуля під PHP 8.5;
2. модуль кладеться в чисту OkayCMS 4.5.3 і окремо у [форк](https://github.com/devSviat/OkayCMS-Fork), і там запускається `phpunit`.

Після мерджу в `main`, якщо версії з `Init/module.json` ще немає серед тегів, CI ставить тег і публікує реліз з архівом модуля.
