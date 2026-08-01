-- HLK review packages
-- Apply manually on shared Vis DB

CREATE TABLE review_package (
  id INT AUTO_INCREMENT NOT NULL,
  guid CHAR(36) NOT NULL,
  contractor_id INT NOT NULL,
  doc_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  attributes JSON NOT NULL,
  created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  submitted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  UNIQUE INDEX UNIQ_REVIEW_PACKAGE_GUID (guid),
  INDEX review_package_contractor_idx (contractor_id),
  INDEX review_package_doc_idx (doc_id),
  INDEX review_package_status_idx (status),
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE TABLE review_package_file (
  id INT AUTO_INCREMENT NOT NULL,
  package_id INT NOT NULL,
  display_name VARCHAR(255) NOT NULL,
  filename VARCHAR(512) NOT NULL,
  slot_key VARCHAR(64) DEFAULT NULL,
  status VARCHAR(20) NOT NULL,
  submitted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  INDEX IDX_REVIEW_PACKAGE_FILE_PACKAGE (package_id),
  PRIMARY KEY (id),
  CONSTRAINT FK_REVIEW_PACKAGE_FILE_PACKAGE FOREIGN KEY (package_id) REFERENCES review_package (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE TABLE review_package_log (
  id INT AUTO_INCREMENT NOT NULL,
  package_id INT NOT NULL,
  event VARCHAR(64) NOT NULL,
  message LONGTEXT NOT NULL,
  meta JSON DEFAULT NULL,
  created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  INDEX review_package_log_package_idx (package_id),
  PRIMARY KEY (id),
  CONSTRAINT FK_REVIEW_PACKAGE_LOG_PACKAGE FOREIGN KEY (package_id) REFERENCES review_package (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
