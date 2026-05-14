<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2013-2016 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Moreticket\CloseTicket;
use GlpiPlugin\Moreticket\Config;
use GlpiPlugin\Moreticket\Profile;
use GlpiPlugin\Moreticket\Solution;
use GlpiPlugin\Moreticket\WaitingTicket;
use GlpiPlugin\Moreticket\WaitingType;

/**
 * @return bool
 */
function plugin_moreticket_install()
{
    global $DB;

    $update = true;
    if (!$DB->tableExists("glpi_plugin_moreticket_configs")) {
        // table sql creation
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/empty-1.7.5.sql");
        $update = false;
    }

    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "solution_status")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.1.1.sql");
    }

    if ($DB->fieldExists("glpi_plugin_moreticket_waitingtypes", "is_helpdeskvisible")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.1.2.sql");
    }

    if (!$DB->fieldExists("glpi_plugin_moreticket_closetickets", "documents_id")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.1.3.sql");
    }

    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "date_report_mandatory")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.2.0.sql");
    }

    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "close_followup")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.2.2.sql");
    }

    //version 1.2.3
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "waitingreason_mandatory")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.2.3.sql");
    }

    //version 1.2.4
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "urgency_justification")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.2.4.sql");
    }

    //version 1.2.5
    if (!$DB->fieldExists("glpi_plugin_moreticket_waitingtickets", "status")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.2.5.sql");
    }

    //version 1.3.2
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "use_duration_solution")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.3.2.sql");
    }

    //version 1.3.4
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "is_mandatory_solution")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.3.4.sql");
    }

    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "use_question")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.5.1.sql");
    }
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", "add_save_button")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.6.2.sql");
    }
    if (!$DB->tableExists("glpi_plugin_moreticket_notificationtickets")) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.6.3.sql");
    }
    if (!$DB->fieldExists("glpi_plugin_moreticket_configs", 'update_after_tech_add_task')) {
        WaitingTicket::deleteDuplicates();
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.7.5.sql");
    }

    if ($update) {
        $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.7.0.sql");
    }

    $DB->runFile(PLUGIN_MORETICKET_DIR . "/sql/update-1.8.1.sql");

    CronTask::Register('GlpiPlugin\Moreticket\WaitingTicket', 'MoreticketWaitingTicket', DAY_TIMESTAMP, ['state' => 1]);

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    $migration = new Migration(PLUGIN_MORETICKET_VERSION);
    $migration->dropTable('glpi_plugin_moreticket_profiles');
    return true;
}

// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 */
function plugin_moreticket_uninstall()
{
    global $DB;

    // Plugin tables deletion
    $tables = [
        "glpi_plugin_moreticket_configs",
        "glpi_plugin_moreticket_waitingtickets",
        "glpi_plugin_moreticket_waitingtypes",
        "glpi_plugin_moreticket_closetickets",
        "glpi_plugin_moreticket_urgencytickets",
        "glpi_plugin_moreticket_notificationtickets"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    CronTask::Unregister('Moreticket');

    return true;
}

// Hook done on purge item case
/**
 * @param $item
 */
function plugin_pre_item_purge_moreticket($item)
{
    switch (get_class($item)) {
        case 'Ticket':
            $temp = new WaitingTicket();
            $temp->deleteByCriteria(['tickets_id' => $item->getField('id')]);
            break;
    }
}


////// SEARCH FUNCTIONS ///////() {

// Define search option for types of the plugins
/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_moreticket_getAddSearchOptions($itemtype)
{
    $sopt = [];

    if ($itemtype == "Ticket") {
        if (Session::haveRight("plugin_moreticket", READ)) {
            $config = new Config();

            //         $sopt[3450]['table']         = 'glpi_plugin_moreticket_waitingtickets';
            //         $sopt[3450]['field']         = 'reason';
            //         $sopt[3450]['name']          = __('Reason', 'moreticket');
            //         $sopt[3450]['datatype']      = "text";
            //         $sopt[3450]['joinparams']    = ['jointype' => 'child',
            //                                              'condition' => "AND `NEWTABLE`.`date_end_suspension` IS NULL"];
            //         $sopt[3450]['massiveaction'] = false;
            //
            //         $sopt[3451]['table']         = 'glpi_plugin_moreticket_waitingtickets';
            //         $sopt[3451]['field']         = 'date_report';
            //         $sopt[3451]['name']          = __('Postponement date', 'moreticket');
            //         $sopt[3451]['datatype']      = "datetime";
            //         $sopt[3451]['joinparams']    = ['jointype' => 'child',
            //                                              'condition' => "AND `NEWTABLE`.`date_end_suspension` IS NULL"];
            //         $sopt[3451]['massiveaction'] = false;
            //
            //Used by Mydashboard
             $sopt[3452]['table']         = 'glpi_plugin_moreticket_waitingtypes';
             $sopt[3452]['field']         = 'name';
             $sopt[3452]['name']          = WaitingType::getTypeName(1);
             $sopt[3452]['datatype']      = "dropdown";
             $condition                   = "AND (`NEWTABLE`.`date_end_suspension` IS NULL)";
             $sopt[3452]['joinparams']    = ['beforejoin'
                                                  => ['table'      => 'glpi_plugin_moreticket_waitingtickets',
                                                           'joinparams' => ['jointype'  => 'child',
                                                                                 'condition' => $condition]]];
            //         $sopt[3452]['massiveaction'] = false;

            if ($config->closeInformations()) {
                $sopt[3453]['table'] = 'glpi_plugin_moreticket_closetickets';
                $sopt[3453]['field'] = 'date';
                $sopt[3453]['name'] = __('Close ticket informations', 'moreticket') . " : " . __('Date');
                $sopt[3453]['datatype'] = "datetime";
                $sopt[3453]['joinparams'] = ['jointype' => 'child'];
                $sopt[3453]['massiveaction'] = false;

                $sopt[3454]['table'] = 'glpi_plugin_moreticket_closetickets';
                $sopt[3454]['field'] = 'comment';
                $sopt[3454]['name'] = __('Close ticket informations', 'moreticket') . " : " . __('Comments');
                $sopt[3454]['datatype'] = "text";
                $sopt[3454]['joinparams'] = ['jointype' => 'child'];
                $sopt[3454]['massiveaction'] = false;

                $sopt[3455]['table'] = 'glpi_plugin_moreticket_closetickets';
                $sopt[3455]['field'] = 'requesters_id';
                $sopt[3455]['name'] = __('Close ticket informations', 'moreticket') . " : " . __('Writer');
                $sopt[3455]['datatype'] = "dropdown";
                $sopt[3455]['joinparams'] = ['jointype' => 'child'];
                $sopt[3455]['massiveaction'] = false;

                $sopt[3486]['table'] = 'glpi_documents';
                $sopt[3486]['field'] = 'name';
                $sopt[3486]['name'] = __('Close ticket informations', 'moreticket') . " : " . _n(
                        'Document',
                        'Documents',
                        Session::getPluralNumber()
                    );
                $sopt[3486]['forcegroupby'] = true;
                $sopt[3486]['usehaving'] = true;
                $sopt[3486]['datatype'] = 'dropdown';
                $sopt[3486]['massiveaction'] = false;
                $sopt[3486]['joinparams'] = [
                    'beforejoin' => [
                        'table' => 'glpi_documents_items',
                        'joinparams' => [
                            'jointype' => 'itemtype_item',
                            'specific_itemtype' => CloseTicket::class,
                            'beforejoin' => [
                                'table' => 'glpi_plugin_moreticket_closetickets',
                                'joinparams' => []
                            ]
                        ]
                    ]
                ];
            }

            $sopt[3487]['table'] = 'glpi_plugin_moreticket_notificationtickets';
            $sopt[3487]['field'] = 'users_id_lastupdater';
            $sopt[3487]['name'] = __('Updated by a user', 'moreticket');
            $sopt[3487]['massiveaction'] = false;
            $sopt[3487]['datatype'] = 'specific';
            $sopt[3487]['joinparams'] = ['jointype' => 'child'];
            $sopt[3487]['additionalfields'] = ['tickets_id'];
        }
    }
    return $sopt;
}

function plugin_moreticket_pre_item_form($params)
{
    $item = $params['item'] ?? null;
    if ($item === null) {
        return;
    }
    $config = new Config();

    switch ($item->getType()) {
        case 'ITILSolution':
            if ($config->isMandatorysolution() == true) {
                echo "<div class='alert alert-warning'>";

                echo "<div class='d-flex'>";

                echo "<div class='me-2'>";
                echo "<i style='font-size:2em;' class='ti ti-alert-triangle'></i>";
                echo "</div>";

                echo "<div>";
                echo "<h4 class='alert-title'>" . __("Warning", 'moreticket') . "</h4>";
                echo "<div class='text-muted'>" . __("Duration is mandatory", 'moreticket') . "</div>";
                echo "</div>";

                echo "</div>";

                echo "</div>";
            }
            break;
    }
}


function plugin_moreticket_post_item_form($params)
{
    global $DB;
    $item = $params['item'];
    $config = new Config();
    $waitingTicket = new WaitingTicket();
    switch ($item->getType()) {
        case 'ITILSolution':
            if ($config->useDurationSolution() == true) {
                Solution::showFormSolution($params);

                if ($config->isMandatorysolution()) {
                    if (isset($params['item'])) {
                        $item = $params['item'];
                        if ($item->getType() == 'ITILSolution') {
                            echo Html::scriptBlock(
                                "$(document).ready(function(){
                        $('.itilsolution').children().find(':submit').hide();
                     });"
                            );
                        }
                    }
                }
            }
            break;
        case 'TicketTask':
            // block with plugin's waiting reason + postponement date
            if ($config->useWaiting() && WaitingTicket::canView()) {
                $waitingTicket->addFormWaitingBlock($item->fields['tickets_id'], $item->getType());
            }

            // automatically click task's set ticket to waiting status switch
            if ($config->fields['waiting_by_default_task'] && Session::haveRight('ticket', \Ticket::OWN)) {
                $actionButtonLayout = $DB->request([
                    'SELECT' => 'timeline_action_btn_layout',
                    'FROM' => 'glpi_users',
                    'WHERE' => [
                        'id' => Session::getLoginUserID()
                    ]
                ])->current()['timeline_action_btn_layout'];
                if ($actionButtonLayout === null) {
                    $actionButtonLayout = $DB->request([
                        'SELECT' => 'value',
                        'FROM' => 'glpi_configs',
                        'WHERE' => [
                            'name' => 'timeline_action_btn_layout'
                        ]
                    ])->current()['value'];
                }
                $element = 'a';
                if ($actionButtonLayout == 1) {
                    $element = 'button';
                }
                echo "<script>
                        $(document).ready(function() {
                            let buttonTask = document.getElementById('itil-footer').querySelector('" . $element . "[data-bs-target=\"#new-TicketTask-block\"]');
                            console.log(buttonTask);
                            buttonTask.addEventListener('click', (e) => {
                                let inputs = document.getElementById('new-itilobject-form').querySelectorAll('[id^=\"enable-pending-reasons\"]');
                                if (!inputs[1].checked) inputs[1].click();
                            })
                        })
                 </script>";
            }
            break;
        case 'ITILFollowup':
            if ($item->fields['itemtype'] == 'Ticket') {
                // block with plugin's waiting reason + postponement date
                if ($config->useWaiting() && WaitingTicket::canView()) {
                    $waitingTicket->addFormWaitingBlock($item->fields['items_id'], $item->getType());
                }

                // automatically click follow up set ticket to waiting status switch
                if (strpos($_SERVER['REQUEST_URI'], "ticket.form.php") !== false) {
                    if ($config->fields['waiting_by_default_followup'] && Session::haveRight('ticket', \Ticket::OWN)) {
                        echo "<script>
                        $(document).ready(function() {
                            let buttonFollowup = document.getElementById('itil-footer').querySelector(\"button[data-bs-target='#new-ITILFollowup-block']\");
                            console.log(buttonFollowup);
                            buttonFollowup.addEventListener('click', e => {
                                let input = document.getElementById('new-itilobject-form').querySelector('[id^=\"enable-pending-reasons\"]');
                                if (!input.checked) input.click();
                            })
                        });
                 </script>";
                    }
                }
            }
            break;
    }
}
