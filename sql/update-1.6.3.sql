CREATE TABLE `glpi_plugin_moreticket_notificationtickets` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT, -- id ...
  `tickets_id`    INT(11) NOT NULL, -- id du ticket GLPI
  `users_id_lastupdater`    INT(11) NOT NULL,
  PRIMARY KEY (`id`), -- index
  FOREIGN KEY (`tickets_id`) REFERENCES glpi_tickets (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


ALTER TABLE glpi_plugin_moreticket_configs ADD `day_sending` INT(1) NOT NULL          DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `day_closing` INT(1) NOT NULL          DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `update_after_document` INT(1) NOT NULL          DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `update_after_approval` INT(1) NOT NULL          DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `followup_text` TEXT;

