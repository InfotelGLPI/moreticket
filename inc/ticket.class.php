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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class PluginMoreticketTicket
 */
class PluginMoreticketTicket extends CommonITILObject
{

    public static $rightname = "plugin_moreticket";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string|translated
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Ticket', 'Tickets', $nb);
    }

    /**
     * @param Ticket $ticket
     */
    public static function emptyTicket(Ticket $ticket)
    {
        if (!empty($_POST)) {
            self::setSessions($_POST);
        } elseif (!empty($_REQUEST)) {
            self::setSessions($_REQUEST);
        }
    }

    /**
     * @param Ticket $ticket
     *
     * @return bool
     */
    public static function beforeAdd(Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        $clean_close_ticket = true;

        if (Session::haveRight("plugin_moreticket", UPDATE)) {
            PluginMoreticketWaitingTicket::preAddWaitingTicket($ticket);
            if (PluginMoreticketCloseTicket::preAddCloseTicket($ticket)) {
                $clean_close_ticket = false;
            }
        }

        if (Session::haveRight("plugin_moreticket_justification", READ)) {
            PluginMoreticketUrgencyTicket::preAddUrgencyTicket($ticket);
        }

        //cleaning the information entered in the ticket for adding solution but not useful so delete to not add solution.
        if ($clean_close_ticket) {
            PluginMoreticketCloseTicket::cleanCloseTicket($ticket);
        }
    }


    /**
     * @param Ticket $ticket
     *
     * @return bool
     */
    public static function afterAdd(Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        PluginMoreticketNotificationTicket::afterAddTicket($ticket);

        if (Session::haveRight("plugin_moreticket", UPDATE)) {
            PluginMoreticketWaitingTicket::postAddWaitingTicket($ticket);
            PluginMoreticketCloseTicket::postAddCloseTicket($ticket);
            if (isset($_SESSION['glpi_plugin_moreticket_close'])) {
                unset($_SESSION['glpi_plugin_moreticket_close']);
            }

//         if (isset($_SESSION['glpi_plugin_moreticket_waiting'])) {
//            unset($_SESSION['glpi_plugin_moreticket_waiting']);
//         }
        }

        if (Session::haveRight("plugin_moreticket_justification", READ)) {
            PluginMoreticketUrgencyTicket::postAddUrgencyTicket($ticket);

            if (isset($_SESSION['glpi_plugin_moreticket_urgency'])) {
                unset($_SESSION['glpi_plugin_moreticket_urgency']);
            }
        }
    }


    /**
     * @param Ticket $ticket
     *
     * @return bool
     */
    public static function beforeUpdate(Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        if (Session::haveRight("plugin_moreticket", UPDATE)) {
            PluginMoreticketWaitingTicket::preUpdateWaitingTicket($ticket);
        }

        if (Session::haveRight("plugin_moreticket_justification", READ)) {
            PluginMoreticketUrgencyTicket::preUpdateUrgencyTicket($ticket);
        }
    }

    /**
     * @param Ticket $ticket
     */
    public static function afterUpdate(Ticket $ticket)
    {
        PluginMoreticketNotificationTicket::afterUpdateTicket($ticket);

        if (Session::haveRight("plugin_moreticket", UPDATE)) {
            PluginMoreticketWaitingTicket::postUpdateWaitingTicket($ticket);

            if (isset($_SESSION['glpi_plugin_moreticket_close'])) {
                unset($_SESSION['glpi_plugin_moreticket_close']);
            }

            if (isset($_SESSION['glpi_plugin_moreticket_waiting'])) {
                unset($_SESSION['glpi_plugin_moreticket_waiting']);
            }
        }

        if (Session::haveRight("plugin_moreticket_justification", READ)) {
            PluginMoreticketUrgencyTicket::postUpdateUrgencyTicket($ticket);

            if (isset($_SESSION['glpi_plugin_moreticket_urgency'])) {
                unset($_SESSION['glpi_plugin_moreticket_urgency']);
            }
        }
    }


    /**
     * @param $input
     */
    public static function setSessions($input)
    {

        foreach ($input as $key => $values) {
            switch ($key) {
//            case 'plugin_moreticket_waitingtypes_id':
//            case 'date_report':
//            case 'reason':
//               $_SESSION['glpi_plugin_moreticket_waiting'][$key] = $values;
//               break;
                case 'solutiontypes_id':
                case 'solution':
                case 'solutiontemplates_id':
                case 'duration_solution':
                    $_SESSION['glpi_plugin_moreticket_close'][$key] = $values;
                    break;
                case 'justification':
                    $_SESSION['glpi_plugin_moreticket_urgency'][$key] = $values;
                    break;
            }
        }
        //      if (isset($_SESSION['glpi_plugin_moreticket_close'])) {
        //         print_r($_SESSION['glpi_plugin_moreticket_close']);
        //      }
    }

    public static function getDefaultValues($entity = 0)
    {
        // TODO: Implement getDefaultValues() method.
    }

    public static function getItemLinkClass(): string
    {
        return false;
    }

    //   static function displaySaveButton($params) {
    //
    //
    //      $config = new PluginMoreticketConfig();
    //      if($config->fields["add_save_button"] == 1) {
    //
    //
    //         if (isset($params['item'])) {
    //            $item    = $params['item'];
    //            $options = $params['options'];
    //
    //
    //            if ($item->getType() == 'Ticket') {
    //
    //
    //               $canupdate     = !$item->getID()
    //                                || (Session::getCurrentInterface() == "central"
    //                                    && $item->canUpdateItem());
    //               $can_requester = $item->canRequesterUpdateItem();
    //               $canpriority   = Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY);
    //               $canassign     = $item->canAssign();
    //               $canassigntome = $item->canAssignTome();
    //
    //
    //               $display_save_btn = (!array_key_exists('locked', $options) || !$options['locked'])
    //                                   && ($canupdate || $can_requester || $canpriority || $canassign || $canassigntome);
    //
    //
    //               if ($display_save_btn
    //                   && !$options['template_preview']) {
    //                  if ($item->getID()) {
    //
    //
    //                     if ($display_save_btn) {
    //                        $colsize1 = '13';
    //                        $colsize2 = '29';
    //                        echo "<tr class='tab_bg_1'>";
    //                        echo "<th width='$colsize1%'>";
    //                        echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
    //                        echo "</th>";
    //                        echo "<td width='$colsize2%'></td>";
    //                        echo "<td width='$colsize1%'></td>";
    //                        echo "<td width='$colsize2%'></td>";
    //                        echo "</tr>";
    //                     }
    //                  }
    //               }
    //            }
    //         }
    //      }
    //   }

    /**
     * @param Ticket $ticket
     */
    public static function afterAddDocument(Document $document)
    {
        $config = new PluginMoreticketConfig();
        if ($config->getField('update_after_document') == 1) {
            if (isset($document->input['itemtype'])) {
                if ($document->input['itemtype'] == Ticket::getType()) {
                    $ticket = new Ticket();
                    $ticket->getFromDB($document->input['items_id']);
                    if (in_array($ticket->fields["status"], Ticket::getReopenableStatusArray())) {
                        if (($ticket->countUsers(CommonITILActor::ASSIGN) > 0)
                            || ($ticket->countGroups(CommonITILActor::ASSIGN) > 0)
                            || ($ticket->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
                            $update['status'] = CommonITILObject::ASSIGNED;
                        } else {
                            $update['status'] = CommonITILObject::INCOMING;
                        }

                        $update['id'] = $ticket->fields['id'];

                        // Use update method for history
                        $ticket->update($update);
                        $reopened = true;
                    }
                }
            }
        }
        $doc = $document;
    }

    public static function afterAddTask(TicketTask $task)
    {
        global $DB;
        $config = new PluginMoreticketConfig();
        if ($config->fields['update_after_tech_add_task']) {
            $ticket = new Ticket();
            $user = new User();
            $user->getFromDB($task->fields['users_id']);
            $condition = [
                'tickets_id' => $task->fields['tickets_id'],
                'users_id' => $task->fields['users_id'],
                'type' => CommonITILActor::ASSIGN
            ];
            $ticket->getFromDB($task->fields['tickets_id']);
            if (countElementsInTable('glpi_tickets_users', $condition) > 0 &&
                in_array($ticket->fields['status'], Ticket::getProcessStatusArray())) {
                $DB->update(
                    Ticket::getTable(),
                    [
                        'status' => Ticket::WAITING
                    ],
                    [
                        'id' => $ticket->getID()
                    ]
                );
            }
        }
    }

    public static function afterAddFollowupTech(ITILFollowup $followup)
    {
        global $DB;
        $config = new PluginMoreticketConfig();
        $ticket = new Ticket();
        if ($config->fields['update_after_tech_add_followup'] && $followup->fields['itemtype'] == Ticket::getType()) {
            $user = new User();
            $ticket->getFromDB($followup->fields['items_id']);
            $user->getFromDB($followup->fields['users_id']);
            $condition = [
                'tickets_id' => $followup->fields['items_id'],
                'users_id' => $followup->fields['users_id'],
                'type' => CommonITILActor::ASSIGN
            ];
            if (countElementsInTable('glpi_tickets_users', $condition) > 0 &&
                in_array($ticket->fields['status'], Ticket::getProcessStatusArray())) {
                $DB->update(
                    Ticket::getTable(),
                    [
                        'status' => Ticket::WAITING
                    ],
                    [
                        'id' => $ticket->getID()
                    ]
                );
            }
        }
    }

    public static function afterUpdateValidation(TicketValidation $validation)
    {
        Toolbox::logInfo('test');
        $config = new PluginMoreticketConfig();
        if ($config->getField('update_after_approval') == 1) {
            //         if($validation->itemtype == Ticket::getType()) {
            $ticket = new Ticket();
            $ticket->getFromDB($validation->fields['tickets_id']);
            $validation_status = CommonITILValidation::WAITING;

            // Percent of validation
            $validation_percent = $ticket->fields['validation_percent'];

            $statuses = [
                CommonITILValidation::ACCEPTED => 0,
                CommonITILValidation::WAITING => 0,
                CommonITILValidation::REFUSED => 0
            ];
            $validations = getAllDataFromTable(
                TicketValidation::getTable(),
                [
                    'tickets_id' => $ticket->getID()
                ]
            );

            if ($total = count($validations)) {
                foreach ($validations as $validation) {
                    $statuses[$validation['status']]++;
                }
            }

            if ($validation_percent > 0) {
                if (($statuses[CommonITILValidation::ACCEPTED] * 100 / $total) >= $validation_percent) {
                    $validation_status = CommonITILValidation::ACCEPTED;
                } elseif (($statuses[CommonITILValidation::REFUSED] * 100 / $total) >= $validation_percent) {
                    $validation_status = CommonITILValidation::REFUSED;
                }
            } else {
                if ($statuses[CommonITILValidation::ACCEPTED]) {
                    $validation_status = CommonITILValidation::ACCEPTED;
                } elseif ($statuses[CommonITILValidation::REFUSED]) {
                    $validation_status = CommonITILValidation::REFUSED;
                }
            }

            $global_validation = $validation_status;
            if (in_array(
                $ticket->fields["status"],
                Ticket::getReopenableStatusArray()
            ) && $global_validation != CommonITILValidation::WAITING) {
                if (($ticket->countUsers(CommonITILActor::ASSIGN) > 0)
                    || ($ticket->countGroups(CommonITILActor::ASSIGN) > 0)
                    || ($ticket->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
                    $update['status'] = CommonITILObject::ASSIGNED;
                } else {
                    $update['status'] = CommonITILObject::INCOMING;
                }
                $update["_reopen"] = true;
                $update['id'] = $ticket->fields['id'];

                Toolbox::logInfo($update);
                // Use update method for history
                $ticket->update($update);
                $reopened = true;
            }
            //         }
        }
        $doc = $validation;
    }


    public static function getTaskClass()
    {
        // TODO: Implement getTaskClass() method.
    }

    public static function getContentTemplatesParametersClass(): string
    {
        // TODO: Implement getContentTemplatesParametersClass() method.
    }

    public static function getContentTemplatesParametersClassInstance(): \Glpi\ContentTemplates\Parameters\CommonITILObjectParameters
    {
        // TODO: Implement getContentTemplatesParametersClassInstance() method.
    }
}
