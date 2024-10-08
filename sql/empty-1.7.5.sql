-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_waitingtickets'
-- Champs supplémentaire à gèrer pour les tickets en attente de GLPI
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_waitingtickets`;
CREATE TABLE `glpi_plugin_moreticket_waitingtickets`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT, -- id ...
    `tickets_id`                        int unsigned NOT NULL,                -- id du ticket GLPI
    `reason`                            VARCHAR(255) DEFAULT NULL,            -- raison de l'attente
    `date_suspension`                   timestamp NULL DEFAULT NULL,          -- date de suspension
    `date_report`                       timestamp NULL DEFAULT NULL,          -- date de report
    `date_end_suspension`               timestamp NULL DEFAULT NULL,          -- date de sortie de suspension
    `plugin_moreticket_waitingtypes_id` int unsigned DEFAULT NULL,            -- id du type d'attente
    `status`                            int unsigned NOT NULL DEFAULT '2',    -- ancien statut
    PRIMARY KEY (`id`),                                                       -- index
    KEY                                 `date_suspension` (`date_suspension`),
    KEY (`tickets_id`),
    KEY (`plugin_moreticket_waitingtypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_waitingtypes'
-- Liste des types d'attente pour un ticket 'en attente'
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_waitingtypes`;
CREATE TABLE `glpi_plugin_moreticket_waitingtypes`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- nom du type d'attente
    `comment`                           TEXT COLLATE utf8mb4_unicode_ci,
    `plugin_moreticket_waitingtypes_id` int unsigned NOT NULL DEFAULT '0',
    `completename`                      TEXT COLLATE utf8mb4_unicode_ci,
    `level`                             int unsigned NOT NULL DEFAULT '0',
    `ancestors_cache`                   LONGTEXT COLLATE utf8mb4_unicode_ci,
    `sons_cache`                        LONGTEXT COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY                                 `name` (`name`),
    KEY                                 `unicity` (`plugin_moreticket_waitingtypes_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_configs'
-- Plugin configuration
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_configs`;
CREATE TABLE `glpi_plugin_moreticket_configs`
(
    `id`                             int unsigned NOT NULL AUTO_INCREMENT,
    `use_waiting`                    tinyint NOT NULL                DEFAULT '0',
    `use_solution`                   tinyint NOT NULL                DEFAULT '0',
    `close_informations`             tinyint NOT NULL                DEFAULT '0',
    `solution_status`                TEXT COLLATE utf8mb4_unicode_ci,
    `waitingtype_mandatory`          tinyint NOT NULL                DEFAULT '0',
    `date_report_mandatory`          tinyint NOT NULL                DEFAULT '0',
    `solutiontype_mandatory`         tinyint NOT NULL                DEFAULT '0',
    `close_followup`                 tinyint NOT NULL                DEFAULT '0',
    `waitingreason_mandatory`        tinyint NOT NULL                DEFAULT '0',
    `urgency_justification`          tinyint NOT NULL                DEFAULT '0',
    `urgency_ids`                    TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `use_duration_solution`          tinyint NOT NULL                DEFAULT '0',
    `is_mandatory_solution`          tinyint NOT NULL                DEFAULT '0',
    `use_question`                   tinyint NOT NULL                DEFAULT '0',
    `add_save_button`                tinyint NOT NULL                DEFAULT '0',
    `day_sending`                    INT(1) NOT NULL DEFAULT '0',
    `day_closing`                    INT(1) NOT NULL DEFAULT '0',
    `update_after_document`          tinyint NOT NULL                DEFAULT '0',
    `update_after_approval`          tinyint NOT NULL                DEFAULT '0',
    `followup_text`                  TEXT,
    `closing_with_problem`           INT(1) NOT NULL DEFAULT '1',
    `add_followup_stop_waiting`      INT(1) NOT NULL DEFAULT '0',
    `update_after_tech_add_task`     tinyint NOT NULL DEFAULT '0',
    `update_after_tech_add_followup` tinyint NOT NULL DEFAULT '0',
    `waiting_by_default_task`        tinyint NOT NULL DEFAULT '0',
    `waiting_by_default_followup`    tinyint NOT NULL DEFAULT '0',

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_moreticket_configs` (`id`, `use_waiting`, `use_solution`, `close_informations`,
                                              `date_report_mandatory`, `waitingtype_mandatory`,
                                              `solutiontype_mandatory`, `solution_status`, `close_followup`,
                                              `waitingreason_mandatory`, `urgency_justification`)
VALUES (1, 1, 1, 1, 1, 1, 1, '{"5":1}', 0, 1, 0);

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_closetickets'
-- informations pour un ticket 'clos'
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_closetickets`;
CREATE TABLE `glpi_plugin_moreticket_closetickets`
(
    `id`            int unsigned NOT NULL AUTO_INCREMENT,
    `tickets_id`    int unsigned NOT NULL, -- id du ticket GLPI
    `date`          VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `comment`       TEXT COLLATE utf8mb4_unicode_ci,
    `requesters_id` int unsigned NOT NULL DEFAULT '0',
    `documents_id`  int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY (`tickets_id`),
    KEY (`documents_id`),
    KEY (`requesters_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_urgencytickets'
-- zone de justification pour l'urgence
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_urgencytickets`;
CREATE TABLE `glpi_plugin_moreticket_urgencytickets`
(
    `id`            int unsigned NOT NULL AUTO_INCREMENT, -- id ...
    `tickets_id`    int unsigned NOT NULL,                -- id du ticket GLPI
    `justification` VARCHAR(255) DEFAULT NULL,            -- justification
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_moreticket_urgencytickets'
-- zone de justification pour l'urgence
--
DROP TABLE IF EXISTS `glpi_plugin_moreticket_notificationtickets`;
CREATE TABLE `glpi_plugin_moreticket_notificationtickets`
(
    `id`                   int unsigned NOT NULL AUTO_INCREMENT, -- id ...
    `tickets_id`           int unsigned NOT NULL,                -- id du ticket GLPI
    `users_id_lastupdater` int unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
