# Профайлер для Битрикса, оформленный в виде модуля

На базе [бандла](https://github.com/ProklUng/web.profilier.bundle), но самодостаточен, ни в чем снаружи - кроме зависимостей из 
composer.json - не нуждается.

Представляет собой досуговую адаптацию фирменного [бандла](https://github.com/symfony/web-profiler-bundle) на фоне решения
 частной задачи.

***INTERNAL***

В процессе разработки.

## Установка

composer.json основного проекта:

```json
  "extra": {
    "installer-paths": {
      "./bitrix/modules/{$name}/": ["type:bitrix-d7-module", "type:bitrix-module"],
      "./bitrix/components/{$name}/": ["type:bitrix-d7-component", "type:bitrix-component"],
      "./bitrix/templates/{$name}/": ["type:bitrix-d7-template", "type:bitrix-theme"]
    }
  }
```

И:

```json
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/proklung/bitrix.webprofilier.module"
        },
        {
          "type": "git",
          "url": "https://github.com/proklung/bitrix.containerable.boilerplate"
        },
        {
        "type": "git",
        "url": "https://github.com/proklung/web.profilier.bundle"
        }
    ]
```

Composer 1.0 & 2.0:


```bash
$ composer require proklung/bitrix-webprofilier-module
```

Установите модуль `proklung.profilier` в административном интерфейсе сайта `bitrix/admin/partner_modules.php`

Поставить галочку `Активность` на странице `/bitrix/admin/_profiler_bitrix.php` (или в админке по пути `Контент` -> `Битрикс профайлер`).
Загрузить модуль в `init.php`:

```php
use Bitrix\Main\Loader;

if (Loader::includeModule('proklung.profilier')) {
    \Proklung\Profilier\DI\Services::init();
}
```

## Конфигурирование

### Дополнительные data collectors

Файл `/bitrix/.settings.php`:

```php
    'proklung.profilier' => [
        'value' => [
           'parameters' => [
                'container.dumper.inline_factories' => false,
                'compile_container_envs' => ['prod'],
                 // Директория, куда ляжет кэш модуля
                'cache_path' => '/bitrix/cache',
            ],
            'twig' => [
                'cache_dir' => null,
                'config' => [],
                'paths' => [$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/proklung.profilier/templates',
                            __DIR__ . '/../vendor/proklung/web-profilier-bundle/src/Bundle/Resources/view',
                    ],
            ],
            // Бандлы
            'bundles' => [],
             // Дополнительные data collectors
            'collectors' => [
                // Название сервиса    
                'fooCollector' => [
                    // Класс, объект или замыкание, возвращающее готовый объект
                    'className' => 'Proklung\Profilier\DataCollector\ModuleDataCollector',
                    'id' => 'module_collector',
                    'template' => '/collectors/module.html.twig',
                    'priority' => 336
                ]
            ]
        ],
    ],


Параметр `cache_path` - путь, куда ляжет скомпилированный контейнер. Если не задано, то по умолчанию `/bitrix/cache/s1/proklung.profilier`.

Предполагается, что в системе так или иначе установлена переменная среды `DEBUG` в массиве `$_ENV`. Если нет, то по умолчанию
 полагается, что среда "отладочная".
 
Параметр (массив) `compile_container_envs` указывает окружения, при которых необходимо кэшировать контейнер.

Пока простая логика: `$_ENV["DEBUG"] === true` => окружение `dev`, иначе `prod`. 
```
### Эксплуатация

Работает только, если пользователь залогинен админом. Не рекомендуется использовать на проде.

В админке появляются две кнопки на административной панели (открывать в новом окне):

1) ***Профайлер текущего запроса*** - ссылка на профайлер с данными на текущую открытую страницу.
2) ***Профайлер последнего запроса*** - ссылка на профайлер с данными на последний запрос. Отличие от первой кнопки в том,
   что можно сделать в другой вкладке открыть страницу, возвращающую, скажем json, и посмотреть ее профайл.
3) Еще ссылки: `/bitrix/admin/_profilier_module.php`