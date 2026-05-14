<?php

/**
 * Bootstrap pour les tests d'intégration (avec base de données GLPI complète).
 */

// 3 niveaux au-dessus de tests/ → glpi/ (en CI : glpi/plugins/moreticket/tests/)
$glpiRoot = dirname(__DIR__, 3);

// Enregistrer les namespaces PSR-4 du plugin avant le bootstrap GLPI
$loader = require $glpiRoot . '/vendor/autoload.php';
$loader->addPsr4('GlpiPlugin\\Moreticket\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Moreticket\\Tests\\', dirname(__DIR__) . '/tests/');

// Bootstrap GLPI complet : initialise la DB, le cache et les fixtures de test
require $glpiRoot . '/tests/bootstrap.php';
