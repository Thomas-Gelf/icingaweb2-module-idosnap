CREATE TABLE idosnap_schema_migration (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(schema_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE idosnap_snapshot (
  uuid VARBINARY(16) NOT NULL,
  label VARCHAR(128) NOT NULL,
  ts_created BIGINT NOT NULL,
  PRIMARY KEY (uuid),
  UNIQUE INDEX label (label),
  INDEX sort_ts (ts_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE idosnap_host_status (
  snapshot_uuid VARBINARY(16) NOT NULL,
  ido_object_id BIGINT(20) NOT NULL,
  hostname VARCHAR(255) NOT NULL,
  severity INT UNSIGNED NOT NULL,
  PRIMARY KEY (snapshot_uuid, ido_object_id),
  INDEX search_host (hostname(128)),
  INDEX sort_severity (snapshot_uuid, severity),
  CONSTRAINT hoststatus_snapshot
    FOREIGN KEY snapshot (snapshot_uuid)
      REFERENCES idosnap_snapshot (uuid)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE idosnap_service_status (
  snapshot_uuid VARBINARY(16) NOT NULL,
  ido_object_id BIGINT(20) NOT NULL,
  ido_host_object_id BIGINT(20) NOT NULL,
  service VARCHAR(255) NOT NULL,
  severity INT UNSIGNED NOT NULL,
  PRIMARY KEY (snapshot_uuid, ido_object_id),
  INDEX sort_severity (snapshot_uuid, severity),
  INDEX search_service (service(128)),
  INDEX search_service_on_host (snapshot_uuid, ido_host_object_id),
  CONSTRAINT servicestatus_host
    FOREIGN KEY host (snapshot_uuid, ido_host_object_id)
      REFERENCES idosnap_host_status (snapshot_uuid, ido_object_id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
  CONSTRAINT servicestatus_snapshot
    FOREIGN KEY snapshot (snapshot_uuid)
      REFERENCES idosnap_snapshot (uuid)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO idosnap_schema_migration
  (schema_version, migration_time)
  VALUES (1, NOW());
