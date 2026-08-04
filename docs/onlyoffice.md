# OnlyOffice в HLK

Редактор: `/onlyoffice/editor/{id}/{mode}`. Document Server — общий, путь `/oo/`.

## Env (prod `.env.local` / `.env.local.php`)

```env
ONLYOFFICE_URL=/oo
ONLYOFFICE_APP_URL=https://lk.vodorodfilm.com
ONLYOFFICE_DS_APP_URL=https://lk.vodorodfilm.com
ONLYOFFICE_INTERNAL_URL=http://192.168.3.140:80
ONLYOFFICE_JWT_SECRET=<тот же, что на Document Server>
HLK_FILES_DIR=/var/www/vis/shared/public/files
```

Важно:

| Переменная | Назначение |
|------------|------------|
| `HLK_FILES_DIR` | Каталог на диске, куда Vis кладёт `review_packages/…` (обычно **shared Vis**, не отдельный hlk) |
| `ONLYOFFICE_APP_URL` | База для **callback** (`/onlyoffice/callback/…`) — всегда **lk** |
| `ONLYOFFICE_DS_APP_URL` | База для **document.url** (`/files/…`) — хост, с которого DS реально скачает файл |

## Общий файловый сторадж (частая причина «Загрузка не удалась»)

Vis при создании пакета пишет в свой `public/files`  
(` /var/www/vis/shared/public/files/review_packages/{guid}/… `).

Deployer у HLK держит **отдельный** ` /var/www/hlk/shared/public/files `.

Если PHP читает Vis-каталог (`HLK_FILES_DIR` → vis), редактор открывается, но  
`https://lk…/files/review_packages/…` смотрит в **пустой** hlk-shared → DS получает **404** → «Загрузка не удалась».

Нужно одно из:

1. **Симлинк** (предпочтительно):
   ```bash
   # на сервере vis
   rm -rf /var/www/hlk/shared/public/files
   ln -s /var/www/vis/shared/public/files /var/www/hlk/shared/public/files
   ```
   и `ONLYOFFICE_DS_APP_URL=https://lk.vodorodfilm.com`

2. **Alias в Apache** VHost `lk` на каталог Vis:
   ```apache
   Alias /files /var/www/vis/shared/public/files
   <Directory /var/www/vis/shared/public/files>
       Require all granted
   </Directory>
   ```

3. **Временно** отдавать файл с Vis (после деплоя с раздельными URL):
   ```env
   ONLYOFFICE_APP_URL=https://lk.vodorodfilm.com
   ONLYOFFICE_DS_APP_URL=https://vis.vodorodfilm.com
   HLK_FILES_DIR=/var/www/vis/shared/public/files
   ```
   Тогда `document.url` = vis `/files/…`, `callbackUrl` = lk `/onlyoffice/callback/…`.

## Apache `/oo/`

В VirtualHost `lk.vodorodfilm.com` — [`deploy/apache/onlyoffice-proxy.conf`](../deploy/apache/onlyoffice-proxy.conf)  
(обязателен `X-Forwarded-Host …/oo`).

## «Загрузка не удалась» — чеклист

1. **`/etc/hosts` на Document Server** (`192.168.3.140`): должна быть запись `lk.vodorodfilm.com` → IP веб-сервера (как для `vis`). Браузер резолвит DNS сам; DS качает `document.url` со своего хоста — без записи редактор открывается, файл — нет.
   ```bash
   # именно на 192.168.3.140:
   curl -sI "https://lk.vodorodfilm.com/files/review_packages/<guid>/<file.docx>"
   ```
   Нужен **200**, не «Could not resolve host» / timeout.

2. JWT: `ONLYOFFICE_JWT_SECRET` = тот же, что у Vis / в `local.json` DS.

3. `document.url` / `callbackUrl` на хосте **lk**; общий `/files` с Vis.

4. Прокси `/oo/` с `X-Forwarded-Host` = `lk.vodorodfilm.com/oo`.
