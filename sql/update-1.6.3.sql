--
-- -------------------------------------------------------------------------
-- moreticket plugin for GLPI
-- Copyright (C) 2015-2026 by the moreticket Development Team.
--
-- https://github.com/InfotelGLPI/moreticket
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of moreticket.
--
-- moreticket is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- moreticket is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with moreticket. If not, see <http://www.gnu.org/licenses/>.
-- --------------------------------------------------------------------------
--

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

