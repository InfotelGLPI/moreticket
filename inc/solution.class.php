<?php
/*
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
 * Class PluginMoreticketSolution
 */
class PluginMoreticketSolution extends CommonITILObject
{

    static $rightname = "plugin_moreticket";

    public static function getTaskClass()
    {
        // TODO: Implement getTaskClass() method.
    }

    public static function getDefaultValues($entity = 0)
    {
        // TODO: Implement getDefaultValues() method.
    }

    public static function getItemLinkClass(): string
    {
        // TODO: Implement getItemLinkClass() method.
    }

    public static function getContentTemplatesParametersClass(): string
    {
        // TODO: Implement getContentTemplatesParametersClass() method.
    }

    static function showFormSolution($params)
    {

        if (isset($params['item'])) {
            $item = $params['item'];
            $options = $params['options'];

            if ($item->getType() == 'ITILSolution') {

                $ticket = $options['item'];
                $config = new PluginMoreticketConfig();
                $use_duration_solution = $config->useDurationSolution();
                if ($use_duration_solution == 1) {
                    echo "<div class='row'><div class='col-12 col-md-9'>";
                    echo __('Duration');
                    if ($config->isMandatorysolution()) {
                        echo "&nbsp;<span style='color:red'>*</span>&nbsp;";
                    }
                    $rand = mt_rand();
                    echo "<span id='duration_solution_" . $rand . $ticket->fields['id'] . "'>";
                    $toadd = [];
                    for ($i = 9; $i <= 100; $i++) {
                        $toadd[] = $i * HOUR_TIMESTAMP;
                    }
                    Dropdown::showTimeStamp("duration_solution", ['min' => 0,
                        'max' => 8 * HOUR_TIMESTAMP,
//                                                             'addfirstminutes' => true,
                        'inhours' => true,
                        'toadd' => $toadd]);
                    echo "</span>";
                    echo "</div></div>";
                }
            }
        }
    }

    /**
     * @param \Ticket $item
     *
     * @return bool
     */
    static function beforeAdd(ITILSolution $solution)
    {
        global $CFG_GLPI;

        if (!is_array($solution->input) || !count($solution->input)) {
            // Already cancel by another plugin
            return false;
        }
        $config = new PluginMoreticketConfig();
        if ($config->useDurationSolution()) {
            if ($solution->input['itemtype'] == 'Ticket') {
                if (isset($solution->input['duration_solution']) && $solution->input['duration_solution'] > 0) {

                    //               $solution->input['content'] = html_entity_decode($solution->input['content']);
                    //               $solution->input['content'] = strip_tags($solution->input['content']);
                    $ticket = new Ticket();
                    $tickets_id = $solution->input['items_id'];
                    if ($ticket->getFromDB($tickets_id)) {
                        if ($ticket->getField('actiontime') == 0) {
                            $ticket->update(['id' => $tickets_id,
                                'actiontime' => $solution->input['duration_solution']]);
                        }
                    }

                    $user = new User();
                    $user->getFromDB(Session::getLoginUserID());

                    $tickettask = new TicketTask();
                    $tickettask->add(['tickets_id' => $tickets_id,
                        'date_creation' => date('Y-m-d H:i:s'),
                        'date' => date('Y-m-d H:i:s',
                            strtotime('- 10 seconds', strtotime(date('Y-m-d H:i:s')))),
                        'users_id' => Session::getLoginUserID(),
                        'users_id_tech' => Session::getLoginUserID(),
                        'content' => $solution->input['content'],
                        'state' => Planning::DONE,
                        'is_private' => $user->getField('task_private'),
                        'actiontime' => $solution->input['duration_solution']]);
                } else if ($config->isMandatorysolution()) {
                    if (Plugin::isPluginActive('servicecatalog')
                        && Session::getCurrentInterface() != "central") {
                        return true;
                    }
                    $ticket = new Ticket();
                    $tickets_id = $solution->input['items_id'];
                    $ticket->getFromDB($tickets_id);
                    $dur = (isset($ticket->fields['actiontime']) ? $ticket->fields['actiontime'] : 0);
                    if ($dur == 0) {
                        Session::addMessageAfterRedirect(_n('Mandatory field', 'Mandatory fields', 2) . " : " . __('Duration'), false, ERROR);
                        $solution->input = [];
                    }
                    return false;
                }
            }
        }
        return true;
    }
}
