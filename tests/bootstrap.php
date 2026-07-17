<?php

/*
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2015-2026 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// 3 niveaux au-dessus de tests/ → glpi/ (en CI : glpi/plugins/moreticket/tests/)
$glpiRoot = dirname(__DIR__, 3);

// Charge vendor/autoload.php de GLPI, qui inclut src/autoload/constants.php
// (constantes READ, ALLSTANDARDRIGHT, READNOTE, UPDATENOTE, etc.)
$loader = require $glpiRoot . '/vendor/autoload.php';

// Définir GLPI_ROOT pour satisfaire les gardes "if (!defined('GLPI_ROOT')) die()"
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', $glpiRoot);
}

// Enregistrer les namespaces PSR-4 du plugin
$loader->addPsr4('GlpiPlugin\\Moreticket\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Moreticket\\Tests\\', dirname(__DIR__) . '/tests/');
