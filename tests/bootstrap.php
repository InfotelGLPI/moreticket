<?php

/**
 * Bootstrap pour les tests unitaires (sans base de données).
 * Charge l'autoloader GLPI + les constantes, sans initialiser la DB.
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
