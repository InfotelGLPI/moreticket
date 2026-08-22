<?php

/**
 * -------------------------------------------------------------------------
 * moreticket plugin for GLPI
 * Copyright (C) 2015-2026 by the moreticket Development Team.
 *
 * https://github.com/InfotelGLPI/moreticket
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of moreticket.
 *
 * moreticket is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * moreticket is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Moreticket\Config;

Html::header_nocache();

if (!Session::haveRight('plugin_moreticket', READ)) {
    throw new AccessDeniedHttpException();
}

header("Content-Type: application/json; charset=UTF-8");

global $CFG_GLPI;

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case "load":
            $config                = new Config();
            $use_waiting           = $config->useWaiting();
            $use_solution          = $config->useSolution();
            $use_question          = $config->useQuestion();
            $use_urgency           = $config->useUrgency();
            $solution_status       = $config->solutionStatus();
            $urgency_ids           = $config->getUrgency_ids();
            $use_duration_solution = $config->useDurationSolution();

            $params = ['root_doc'        => PLUGIN_MORETICKET_WEBDIR,
                'waiting'         => CommonITILObject::WAITING,
                'closed'          => CommonITILObject::CLOSED,
                'use_waiting'     => $use_waiting,
                'use_solution'    => $use_solution,
                'use_question'    => $use_question,
                'solution_status' => $solution_status,
                'use_urgency'     => $use_urgency,
                'urgency_ids'     => $urgency_ids,
                'div_kb'          => Session::haveRight('knowbase', UPDATE)];

            // HTTP_REFERER is a client-controlled, frequently absent header: read it
            // defensively (no PHP 8 "Undefined array key" warning) and treat it only
            // as a display hint — access is already gated by the rights checks below.
            $referer = $_SERVER['HTTP_REFERER'] ?? '';

            $inject_waiting = false;
            if (Session::haveRight("plugin_moreticket", UPDATE)
            && ($config->useWaiting() == true || $config->useSolution() == true)) {
                if (Session::getCurrentInterface() == "central"
                && (strpos($referer, "ticket.form.php") !== false)) {
                    $inject_waiting = true;
                }
            }

            $inject_urgency = false;
            if (Session::haveRight("plugin_moreticket_justification", READ)) {
                if ((strpos($referer, "ticket.form.php") !== false ||
                 strpos($referer, "newticket.form.php") !== false ||
                  strpos($referer, "helpdesk.public.php") !== false ||
                   strpos($referer, "tracking.injector.php") !== false)
                && ($config->useUrgency() == true)) {
                    $inject_urgency = true;
                }
            }

            echo json_encode([
                'params'         => $params,
                'inject_waiting' => $inject_waiting,
                'inject_urgency' => $inject_urgency,
            ], JSON_HEX_TAG);

            break;
    }
}
