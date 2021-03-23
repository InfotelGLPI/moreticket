CREATE TABLE `glpi_plugin_moreticket_notificationtickets` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT, -- id ...
  `tickets_id`    INT(11) NOT NULL, -- id du ticket GLPI
  `users_id_lastupdater`    INT(11) NOT NULL,
  PRIMARY KEY (`id`), -- index
  FOREIGN KEY (`tickets_id`) REFERENCES glpi_tickets (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
