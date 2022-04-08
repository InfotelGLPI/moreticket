CREATE TABLE `glpi_plugin_moreticket_notificationtickets` (
  `id`            int unsigned NOT NULL AUTO_INCREMENT, -- id ...
  `tickets_id`    int unsigned NOT NULL, -- id du ticket GLPI
  `users_id_lastupdater`    int unsigned NOT NULL,
  PRIMARY KEY (`id`), -- index
  KEY (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


ALTER TABLE glpi_plugin_moreticket_configs ADD `day_sending` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `day_closing` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `update_after_document` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `update_after_approval` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `followup_text` TEXT;
ALTER TABLE glpi_plugin_moreticket_configs ADD `closing_with_problem` int unsigned NOT NULL DEFAULT '1';
ALTER TABLE glpi_plugin_moreticket_configs ADD `add_followup_stop_waiting` int unsigned NOT NULL DEFAULT '0';

