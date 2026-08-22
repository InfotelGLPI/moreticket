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

ALTER TABLE glpi_plugin_moreticket_configs
  ADD `urgency_justification` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE glpi_plugin_moreticket_configs
  ADD `urgency_ids` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;

CREATE TABLE `glpi_plugin_moreticket_urgencytickets` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT, -- id ...
  `tickets_id`    INT(11) NOT NULL, -- id du ticket GLPI
  `justification` VARCHAR(255)     DEFAULT NULL, -- justification
  PRIMARY KEY (`id`), -- index
  FOREIGN KEY (`tickets_id`) REFERENCES glpi_tickets (id)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
