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

namespace GlpiPlugin\Moreticket;

use CommonITILActor;
use CommonITILObject;
use CommonITILValidation;
use Document;
use Glpi\ContentTemplates\Parameters\CommonITILObjectParameters;
use ITILFollowup;
use TicketValidation;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class Ticket
 */
class Ticket extends CommonITILObject
{
    public static $rightname = "plugin_moreticket";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Ticket', 'Tickets', $nb);
    }

    /**
     * @param Ticket $ticket
     */
    public static function emptyTicket(\Ticket $ticket)
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
    public static function beforeAdd(\Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        $clean_close_ticket = true;

        // No right test here any more, for the reason given in setup.php: refusing a ticket
        // that comes without its mandatory waiting reason or urgency justification is a
        // control, and a control the caller escapes by not holding a right is not one. Each
        // function below returns on its own when its feature is disabled.
        WaitingTicket::preAddWaitingTicket($ticket);
        if (CloseTicket::preAddCloseTicket($ticket)) {
            $clean_close_ticket = false;
        }

        UrgencyTicket::preAddUrgencyTicket($ticket);

        //cleaning the information entered in the ticket for adding solution but not useful so delete to not add solution.
        if ($clean_close_ticket) {
            CloseTicket::cleanCloseTicket($ticket);
        }
    }


    /**
     * @param Ticket $ticket
     *
     * @return bool
     */
    public static function afterAdd(\Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        NotificationTicket::afterAddTicket($ticket);

        // Same reasoning as beforeAdd(): what the pre-hook accepted has to be recorded, or
        // the control above would have asked for a justification only to throw it away.
        WaitingTicket::postAddWaitingTicket($ticket);
        CloseTicket::postAddCloseTicket($ticket);

        SessionDraft::forget(SessionDraft::CLOSING);

        UrgencyTicket::postAddUrgencyTicket($ticket);

        // On the creation path GLPI restores the form from $_SESSION['saveInput'], which the
        // pre-hooks fill: the plugin's own draft has nothing left to say here.
        SessionDraft::forget(SessionDraft::URGENCY);
    }


    /**
     * @param Ticket $ticket
     *
     * @return bool
     */
    public static function beforeUpdate(\Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        // Automatic switch to WAITING when a technician adds a task or a followup. It now
        // goes through Ticket::update() so the change is journalised and notified, which
        // means it also lands here. A waiting reason and a postponement date are simply not
        // part of that transition: checkMandatory() would refuse it and drop the status
        // without a word. Skip the child hooks for it -- the outcome stays exactly what the
        // direct table write produced, only the write layer is no longer bypassed.
        if (!empty($ticket->input['_moreticket_auto_waiting'])) {
            return true;
        }

        WaitingTicket::preUpdateWaitingTicket($ticket);

        UrgencyTicket::preUpdateUrgencyTicket($ticket);

        // Nothing above cancels the update: the two controls refuse a change by dropping the
        // offending field from the input, never by stopping the write.
        return true;
    }

    /**
     * @param Ticket $ticket
     */
    public static function afterUpdate(\Ticket $ticket)
    {
        NotificationTicket::afterUpdateTicket($ticket);

        // postUpdateWaitingTicket() closes the suspension period when the ticket leaves
        // WAITING. Behind a right test, a ticket taken out of waiting by someone without the
        // plugin right kept an open suspension for ever, and the waiting duration reported
        // afterwards was wrong for everybody.
        WaitingTicket::postUpdateWaitingTicket($ticket);

        SessionDraft::forget(SessionDraft::CLOSING);
        SessionDraft::forget(SessionDraft::WAITING);

        UrgencyTicket::postUpdateUrgencyTicket($ticket);

        SessionDraft::forget(SessionDraft::URGENCY);
    }


    /**
     * @param $input
     */
    public static function setSessions($input)
    {
        // Called on the blank ticket form: whatever is restored here was typed for a ticket
        // that does not exist yet, so both drafts are stamped with id 0 and will only ever be
        // handed back to another creation form -- never to an existing ticket.
        SessionDraft::remember(
            SessionDraft::CLOSING,
            $input,
            ['solutiontypes_id', 'solution', 'solutiontemplates_id', 'duration_solution'],
            0,
        );

        SessionDraft::remember(SessionDraft::URGENCY, $input, ['justification'], 0);
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
    //      $config = new Config();
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
    //               $canpriority   = Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY);
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
        $config = new Config();
        if ($config->getField('update_after_document') == 1) {
            if (isset($document->input['itemtype'])) {
                if ($document->input['itemtype'] == \Ticket::getType()) {
                    $ticket = new \Ticket();
                    $ticket->getFromDB($document->input['items_id']);
                    if (in_array($ticket->fields["status"], \Ticket::getReopenableStatusArray())) {
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


    public static function afterAddFollowupTech(ITILFollowup $followup)
    {
        $config = new Config();
        $ticket = new \Ticket();
        if ($config->fields['update_after_tech_add_followup'] && $followup->fields['itemtype'] == \Ticket::getType()) {
            $user = new User();
            $ticket->getFromDB($followup->fields['items_id']);
            $user->getFromDB($followup->fields['users_id']);
            $condition = [
                'tickets_id' => $followup->fields['items_id'],
                'users_id' => $followup->fields['users_id'],
                'type' => CommonITILActor::ASSIGN,
            ];
            if (countElementsInTable('glpi_tickets_users', $condition) > 0
                && in_array($ticket->fields['status'], \Ticket::getProcessStatusArray())) {
                // Go through the write layer instead of the table: this is a status change
                // like any other and it owes the ticket its history entry, a fresh date_mod,
                // the notifications and the item_update hooks. The flag tells
                // Ticket::beforeUpdate that no waiting reason comes with this transition.
                $ticket->update([
                    'id'                       => $ticket->getID(),
                    'status'                   => \Ticket::WAITING,
                    '_moreticket_auto_waiting' => true,
                ]);
            }
        }
    }

    public static function afterUpdateValidation(TicketValidation $validation)
    {

        $config = new Config();
        if ($config->getField('update_after_approval') == 1) {
            //         if($validation->itemtype == \getType()) {
            $ticket = new \Ticket();
            $ticket->getFromDB($validation->fields['tickets_id']);
            $validation_status = CommonITILValidation::WAITING;

            // Percent of validation
            $validation_percent = $ticket->fields['validation_percent'];

            $statuses = [
                CommonITILValidation::ACCEPTED => 0,
                CommonITILValidation::WAITING => 0,
                CommonITILValidation::REFUSED => 0,
            ];
            $validations = getAllDataFromTable(
                TicketValidation::getTable(),
                [
                    'tickets_id' => $ticket->getID(),
                ],
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
                \Ticket::getReopenableStatusArray(),
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
        // Minimal implementation: default to the core Ticket content-template parameters.
        return \Glpi\ContentTemplates\Parameters\TicketParameters::class;
    }

    public static function getContentTemplatesParametersClassInstance(): CommonITILObjectParameters
    {
        // Minimal implementation: default to the core Ticket content-template parameters.
        return new \Glpi\ContentTemplates\Parameters\TicketParameters();
    }
}
