-- HLK / Vis shared DB: contractor LK fields
-- Apply manually (do NOT run doctrine:schema:update --force)

ALTER TABLE contractor
  ADD COLUMN login VARCHAR(255) DEFAULT NULL COMMENT 'Логин ЛК',
  ADD COLUMN password VARCHAR(255) DEFAULT NULL COMMENT 'Хеш пароля ЛК',
  ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL COMMENT 'Пароль ЛК (открытый для менеджеров)',
  ADD COLUMN allow_lk_access TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Доступ в ЛК';


CREATE UNIQUE INDEX contractor_login_unique ON contractor (login);
CREATE INDEX allow_lk_access_idx ON contractor (allow_lk_access);

-- Заполнить login из ИНН там, где это безопасно (нет дублей ИНН среди неудалённых).
-- При коллизиях править вручную.
UPDATE contractor c
SET c.login = c.inn
WHERE c.login IS NULL
  AND c.inn IS NOT NULL
  AND c.inn <> ''
  AND c.deleted_at IS NULL
  AND (
    SELECT COUNT(*) FROM (
      SELECT inn FROM contractor
      WHERE deleted_at IS NULL AND inn IS NOT NULL AND inn <> ''
      GROUP BY inn HAVING COUNT(*) > 1
    ) dups WHERE dups.inn = c.inn
  ) = 0;

-- Тестовый контрагент (подставьте хеш после seed-команды HLK или задайте через Vis UI)
-- UPDATE contractor SET allow_lk_access = 1, login = inn WHERE id = 2473;
