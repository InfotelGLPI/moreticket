ALTER TABLE `glpi_plugin_moreticket_configs` ADD `update_after_tech_add_task` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_moreticket_configs` ADD `update_after_tech_add_followup` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_moreticket_configs` ADD `waiting_by_default_followup` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_moreticket_configs` ADD `waiting_by_default_task` tinyint NOT NULL DEFAULT '0';
