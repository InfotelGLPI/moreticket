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
  ADD `solution_status` TEXT COLLATE utf8_unicode_ci;
ALTER TABLE glpi_plugin_moreticket_configs
  ADD `close_informations` TINYINT NOT NULL DEFAULT '0';

-- --------------------------------------------------------
-- 
-- Structure de la table 'glpi_plugin_moreticket_closetickets'
-- informations pour un ticket 'clos'
-- 
DROP TABLE IF EXISTS `glpi_plugin_moreticket_closetickets`;
CREATE TABLE `glpi_plugin_moreticket_closetickets` (
  `id`            INT(11) NOT NULL        AUTO_INCREMENT,
  `tickets_id`    INT(11) NOT NULL, -- id du ticket GLPI
  `date`          VARCHAR(255)
                  COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment`       TEXT COLLATE utf8_unicode_ci,
  `requesters_id` INT(11) NOT NULL        DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`tickets_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;