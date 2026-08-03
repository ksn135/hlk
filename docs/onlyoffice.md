# OnlyOffice в HLK

Редактор: `/onlyoffice/editor/{id}/{mode}`. Document Server — общий, путь `/oo/`.

## Env (prod `.env.local` / `.env.local.php`)

```env
ONLYOFFICE_URL=/oo
ONLYOFFICE_APP_URL=https://lk.vodorodfilm.com
ONLYOFFICE_DS_APP_URL=https://lk.vodorodfilm.com
ONLYOFFICE_INTERNAL_URL=http://192.168.3.140:80
ONLYOFFICE_JWT_SECRET=<тот же, что на Document Server>
HLK_FILES_DIR=/var/www/hlk/shared/public/files
```

`HLK_FILES_DIR` должен указывать на **web-доступный** каталог `public/files` (shared), чтобы DS мог качать  
`https://lk.vodorodfilm.com/files/review_packages/{guid}/…`.

## Apache

В VirtualHost `lk.vodorodfilm.com` подключить [`deploy/apache/onlyoffice-proxy.conf`](../deploy/apache/onlyoffice-proxy.conf)  
(обязателен `X-Forwarded-Host …/oo`).

## Как устроена загрузка документа

1. В конфиге редактора `document.url` = `{APP}/files/review_packages/…` (статика Apache).
2. `callbackUrl` = `{APP}/onlyoffice/callback/{jwt}` (Symfony, `PUBLIC_ACCESS`).
3. После сохранения Symfony скачивает cache с DS через `ONLYOFFICE_INTERNAL_URL`.

## «Загрузка не удалась»

1. Проверить, что файл открывается с сервера DS / с любой машины:
   ```bash
   curl -I "https://lk.vodorodfilm.com/files/review_packages/<guid>/<file.docx>"
   ```
   Ожидается `200`, не `404`/`302` на login.
2. JWT-секрет совпадает с DS.
3. В DevTools → исходник editor: `document.url` и `callbackUrl` на хосте **lk**, не **vis**.
4. Прокси `/oo/` с `X-Forwarded-Host` = `lk.vodorodfilm.com/oo`.
