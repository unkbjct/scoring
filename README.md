## Installation

склонировать проект

```sh
git clone https://github.com/FinzorDev/bitrixnspk.git
```

Установить зависимости:

```sh
composer install
```

создать файл .env в корне проекта и записать ключ rest bitrix, и ключ scoring

Сгенерировать ключ проекта:

```sh
php artisan key:generate
```

запустить проект

## about

ендпоинт скрипта [site.ru]/public/api/points

Метод: POST

принимает два параметра:

-   lead_id - обязательное поле;

#### success result

при успешном выполнении скрипта будет возвращен следующий ответ:

```json
{
    "success": true,
}
```

#### error result:

-   При ошибке будет возвращен следующий ответ;

```json
{
  "success": false,
  "errorMessage": "[Описание причины возникновения ошибки]",
}
```
