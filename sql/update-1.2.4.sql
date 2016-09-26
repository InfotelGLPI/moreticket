ALTER TABLE glpi_plugin_moreticket_configs ADD `urgency_justification` tinyint(1) NOT NULL default '0';
ALTER TABLE glpi_plugin_moreticket_configs ADD `urgency_ids` text COLLATE utf8_unicode_ci default NULL;

CREATE TABLE `glpi_plugin_moreticket_urgencytickets` (
  `id` int(11) NOT NULL auto_increment, -- id ...
  `tickets_id` int(11) NOT NULL, -- id du ticket GLPI
  `justification` varchar(255) default NULL, -- justification
  PRIMARY KEY  (`id`), -- index
  FOREIGN KEY (`tickets_id`) REFERENCES glpi_tickets(id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
