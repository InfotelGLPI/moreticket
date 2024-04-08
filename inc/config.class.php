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
 * Class PluginMoreticketConfig
 */
class PluginMoreticketConfig extends CommonDBTM
{

    static $rightname = "plugin_moreticket";

    /**
     * @param bool $update
     *
     * @return null|PluginMoreticketConfig
     */
    static function getConfig($update = false)
    {
        static $config = null;

        if (is_null($config)) {
            $config = new self();
        }
        if ($update) {
            $config->getFromDB(1);
        }
        return $config;
    }

    /**
     * PluginMoreticketConfig constructor.
     */
    function __construct()
    {
        global $DB;

        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * @param int $nb
     *
     * @return translated
     */
    static function getTypeName($nb = 0)
    {
        return __("Setup");
    }

    /**
     * Définition du nom de l'onglet
     **/
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case __CLASS__:
                $ong = [1 => __('Tools')];
                return $ong;
        }
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        // add tabs defined in getTabNameForItem
        $this->addStandardTab(__CLASS__, $ong, $options);
        return $ong;
    }

    // handle display of tabs defined in getTabNameForItem (default tab call showForm)
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 0, $withtemplate = 0)
    {
        if ($item instanceof self) {
            switch ($tabnum) {
                case 1 : //"My second tab""
                    $item->showToolsForm();
                    break;
            }
        }
        return true;
    }

    /**
     * @param string $interface
     *
     * @return array
     */
    function getRights($interface = 'central')
    {
        $values = parent::getRights();

        unset($values[CREATE], $values[DELETE], $values[PURGE]);
        return $values;
    }

    function showForm($ID, $options = [])
    {
        $this->getFromDB(1);
        echo "<div class='center'>";
        echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __("Setup") . "</th></tr>";
        echo "<tr><th colspan='2'>" . __("Ticket waiting", "moreticket") . "</th></tr>";
        echo Html::hidden('id', ['value' => 1]);

        echo "<tr class='tab_bg_1'>
            <td>" . __("Use waiting process", "moreticket") . "</td><td>";
        Dropdown::showYesNo(
            "use_waiting",
            $this->fields["use_waiting"],
            -1,
            ['on_change' => 'hide_show_waiting(this.value);']
        );
        echo "</td>";
        echo "</tr>";

        echo Html::scriptBlock(
            "
         function hide_show_waiting(val) {
            var display = (val == 0) ? 'none' : '';
            td = ($(\"td[id='show_waiting']\"));
            td.each(function (index, value) {
               td[index].style.display = display;
            });
         }"
        );

        $style = ($this->useWaiting()) ? "" : "style='display: none '";

        echo "<tr class='tab_bg_1'>
               <td id='show_waiting' $style>" . __("Postponement date is mandatory", "moreticket") . "</td>";
        echo "<td id='show_waiting' $style>";
        Dropdown::showYesNo("date_report_mandatory", $this->fields["date_report_mandatory"]);
        echo "</td>";
        echo "</tr>";

        //TODROP
//      echo "<tr class='tab_bg_1'>
//               <td id='show_waiting' $style>" . __("Waiting type is mandatory", "moreticket") . "</td>";
//      echo "<td id='show_waiting' $style>";
//      Dropdown::showYesNo("waitingtype_mandatory", $this->fields["waitingtype_mandatory"]);
//      echo "</td>";
//      echo "</tr>";

        echo "<tr class='tab_bg_1'>
               <td id='show_waiting' $style>" . __("Waiting reason is mandatory", "moreticket") . "</td>";
        echo "<td id='show_waiting' $style>";
        Dropdown::showYesNo("waitingreason_mandatory", $this->fields["waitingreason_mandatory"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
               <td id='show_waiting' $style>" . __("Add followup when waiting date is reached", "moreticket") . "</td>";
        echo "<td id='show_waiting' $style>";
        Dropdown::showYesNo("add_followup_stop_waiting", $this->fields["add_followup_stop_waiting"]);
        echo "</td>";
        echo "</tr>";

//       echo "<tr class='tab_bg_1'>
//            <td>" . __("Use the option ticket waiting in ticket followup", "moreticket") . "</td><td>";
//       Dropdown::showYesNo("use_question", $this->fields["use_question"]);
//       echo "</td>";
//       echo "</tr>";

        echo "<tr><th colspan='2'>" . __("Ticket resolution and close", "moreticket") . "</th></tr>";
        echo "<tr class='tab_bg_1'>
            <td>" . __("Use solution process", "moreticket") . "</td><td>";
        Dropdown::showYesNo(
            "use_solution",
            $this->fields["use_solution"],
            -1,
            ['on_change' => 'hide_show_solution(this.value);']
        );
        echo "</td>";
        echo "</tr>";

        echo Html::scriptBlock(
            "
         function hide_show_solution(val) {
                        var display = (val == 0) ? 'none' : '';
            td = ($(\"td[id='show_solution']\"));
            td.each(function (index, value) {
               td[index].style.display = display;
            });
         }"
        );

        $style = ($this->useSolution()) ? "" : "style='display: none '";

        echo "<tr class='tab_bg_1'>
               <td id='show_solution' $style>" . __("Solution type is mandatory", "moreticket") . "</td>";
        echo "<td id='show_solution' $style>";
        Dropdown::showYesNo("solutiontype_mandatory", $this->fields["solutiontype_mandatory"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Close ticket informations", "moreticket") . "</td><td>";
        Dropdown::showYesNo("close_informations", $this->fields["close_informations"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Status used to display solution block", "moreticket") . "</td><td>";

        $solution_status = $this->getSolutionStatus($this->fields["solution_status"]);

        foreach ([Ticket::CLOSED, Ticket::SOLVED] as $status) {
            $checked = isset($solution_status[$status]) ? 'checked' : '';
            echo "<input type='checkbox' name='solution_status[" . $status . "]' value='1' $checked>&nbsp;";
            echo Ticket::getStatus($status) . "<br>";
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Add a followup on immediate ticket closing", "moreticket") . "</td><td>";
        Dropdown::showYesNo("close_followup", $this->fields["close_followup"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Use the 'Duration' field in the add solution interface", "moreticket") . "</td><td>";
        Dropdown::showYesNo(
            "use_duration_solution",
            $this->fields["use_duration_solution"],
            -1,
            ['on_change' => 'hide_show_solution(this.value);']
        );
        echo "</td>";
        echo "</tr>";

        echo Html::scriptBlock(
            "
         function hide_show_solution(val) {
            var display = (val == 0) ? 'none' : '';
            document.getElementById('mandatory_solution').style.display = display;
         }"
        );

        $style = ($this->useDurationSolution()) ? "" : "style='display: none '";
        echo "<tr class='tab_bg_1' id='mandatory_solution' $style>
            <td>" . __("Mandatory 'Duration' field", "moreticket") . "</td><td>";
        Dropdown::showYesNo("is_mandatory_solution", $this->fields["is_mandatory_solution"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr><th colspan='2'>" . __("Ticket urgency", "moreticket") . "</th></tr>";
        echo "<tr class='tab_bg_1'>
            <td>" . __("Use a justification of the urgency field", "moreticket") . "</td><td>";
        Dropdown::showYesNo(
            "urgency_justification",
            $this->fields["urgency_justification"],
            -1,
            ['on_change' => 'hide_show_urgency(this.value);']
        );
        echo "</td>";
        echo "</tr>";

        echo Html::scriptBlock(
            "
         function hide_show_urgency(val) {
            var display = (val == 0) ? 'none' : '';
            document.getElementById('show_urgency_td1').style.display = display;
            document.getElementById('show_urgency_td2').style.display = display;
         }"
        );

        $style = ($this->useUrgency()) ? "" : "style='display: none '";
        echo "<tr class='tab_bg_1'>";
        echo "<td id='show_urgency_td1' $style>";
        echo __("Urgency impacted justification for the field", "moreticket");
        echo "</td>";

        $dbu = new DbUtils();

        echo "<td id='show_urgency_td2' $style>";
        $urgency_ids = self::getValuesUrgency();
        Dropdown::showFromArray(
            'urgency_ids',
            $urgency_ids,
            [
                'multiple' => true,
                'values' => $dbu->importArrayFromDB($this->fields["urgency_ids"])
            ]
        );
        echo "</td>";
        echo "</tr>";


        //      echo "<tr><th colspan='2'>" . __('Display save button',"moreticket") . "</th></tr>";
        //      echo "<tr class='tab_bg_1'>
        //            <td>" . __("Add a save button on top ticket form", "moreticket") . "</td><td>";
        //      Dropdown::showYesNo("add_save_button", $this->fields["add_save_button"]);
        //      echo "</td>";
        //      echo "</tr>";

        echo "<tr><th colspan='2'>" . __('Update ticket status', 'moreticket') . "</th></tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Update ticket status to processing after add document", "moreticket") . "</td><td>";
        Dropdown::showYesNo("update_after_document", $this->fields["update_after_document"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>
            <td>" . __("Update ticket status to processing after approval", "moreticket") . "</td><td>";
        Dropdown::showYesNo("update_after_approval", $this->fields["update_after_approval"]);
        echo "</td>";
        echo "</tr>";
//
//      echo "<tr><th colspan='2'>" . _n('Automatic action', 'Automatic actions', Session::getPluralNumber()) . "</th></tr>";
//      echo "<tr class='tab_bg_1'>
//            <td>" . __("Automatic sending a followup after x days of waiting", "moreticket") . "</td><td>";
//      Dropdown::showNumber("day_sending", ["value" => $this->fields["day_sending"]]);
//      echo "</td>";
//      echo "</tr>";
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>" . __("Automatic closing ticket after x days after followup", "moreticket") . "</td><td>";
//      Dropdown::showNumber("day_closing", ["value" => $this->fields["day_closing"]]);
//      echo "</td>";
//      echo "</tr>";
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>" . __("Content of followup", "moreticket") . "</td><td>";
//      Html::textarea(["name" => "followup_text",
//                      "value" => $this->fields["followup_text"],
//                      "enable_richtext" => true,
//                      "cols"       => 100,
//                      "rows"       => 5,]);
//      echo "</td>";
//      echo "</tr>";
//      echo "<tr class='tab_bg_1'>
//            <td>" . __("Close if a problem is linked", "moreticket") . "</td><td>";
//      Dropdown::showYesNo("closing_with_problem", $this->fields["closing_with_problem"]);
//      echo "</td>";
//      echo "</tr>";

        echo "<tr class='tab_bg_1' align='center'>";
        echo "<td colspan='2' align='center'>";
        echo Html::submit(_sx('button', 'Post'), ['name' => 'update', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    /**
     * @param $input
     *
     * @return array|mixed
     */
    function getSolutionStatus($input)
    {
        $solution_status = [];

        if (!empty($input)) {
            $solution_status = json_decode($input, true);
        }

        return $solution_status;
    }

    /**
     * @return mixed
     */
    function useWaiting()
    {
        return $this->fields['use_waiting'];
    }

    /**
     * @return mixed
     */
    function mandatoryReportDate()
    {
        return $this->fields['date_report_mandatory'];
    }

    /**
     * @return mixed
     */
    function mandatoryWaitingType()
    {
        return $this->fields['waitingtype_mandatory'];
    }

    /**
     * @return mixed
     */
    function mandatoryWaitingReason()
    {
        return $this->fields['waitingreason_mandatory'];
    }

    /**
     * @return mixed
     */
    function useSolution()
    {
        return $this->fields['use_solution'];
    }

    /**
     * @return mixed
     */
    function mandatorySolutionType()
    {
        return $this->fields['solutiontype_mandatory'];
    }

    /**
     * @return mixed
     */
    function solutionStatus()
    {
        return $this->fields["solution_status"];
    }

    /**
     * @return mixed
     */
    function closeInformations()
    {
        return $this->fields["close_informations"];
    }

    /**
     * @return mixed
     */
    function closeFollowup()
    {
        return $this->fields["close_followup"];
    }

    /**
     * @return mixed
     */
    function useUrgency()
    {
        return $this->fields['urgency_justification'];
    }

    /**
     * @return array
     */
    function getUrgency_ids()
    {
        $dbu = new DbUtils();
        return $dbu->importArrayFromDB($this->fields['urgency_ids']);
    }

    /**
     * @return mixed
     */
    function useDurationSolution()
    {
        if (isset($this->fields['use_duration_solution'])) {
            return $this->fields['use_duration_solution'];
        }
        return false;
    }

    /**
     * @return mixed
     */
    function isMandatorysolution()
    {
        return $this->fields['is_mandatory_solution'];
    }

    function useQuestion()
    {
        return $this->fields['use_question'];
    }

    /**
     * @return array
     */
    static function getValuesUrgency()
    {
        global $CFG_GLPI;

        $URGENCY_MASK_FIELD = 'urgency_mask';
        $values = [];

        if (isset($CFG_GLPI[$URGENCY_MASK_FIELD])) {
            if ($CFG_GLPI[$URGENCY_MASK_FIELD] & (1 << 5)) {
                $values[5] = CommonITILObject::getUrgencyName(5);
            }

            if ($CFG_GLPI[$URGENCY_MASK_FIELD] & (1 << 4)) {
                $values[4] = CommonITILObject::getUrgencyName(4);
            }

            $values[3] = CommonITILObject::getUrgencyName(3);

            if ($CFG_GLPI[$URGENCY_MASK_FIELD] & (1 << 2)) {
                $values[2] = CommonITILObject::getUrgencyName(2);
            }

            if ($CFG_GLPI[$URGENCY_MASK_FIELD] & (1 << 1)) {
                $values[1] = CommonITILObject::getUrgencyName(1);
            }
        }
        return $values;
    }

    function addTaskStopWaiting()
    {
        return $this->fields['add_followup_stop_waiting'];
    }

    /**
     * Get record of all tickets not marked as waiting ticket in core, but marked as waiting ticket in moreticket
     * @return array rows of glpi_plugin_moreticket_waitingtickets
     */
    static function getTicketsToMigrate()
    {
        $waitingTicket = new PluginMoreticketWaitingTicket();
        $ticket = new Ticket();
        // can't directly search for not waiting because tickets can be closed without a date of suspension end
        $coreStatusNotWaiting = $ticket->find(['status' => [
                CommonITILObject::INCOMING,
                CommonITILObject::ASSIGNED,
                CommonITILObject::PLANNED,
            ]
        ]);
        $idsNotWaiting = array_map(fn($e) => $e['id'], $coreStatusNotWaiting);

        return $waitingTicket->find([
            ['tickets_id' => $idsNotWaiting],
            ['date_end_suspension' => 'null']
        ]);
    }

    /**
     * Get record of ticket that have the core status Waiting but no pending reasons defined,
     * even though they also have the waiting status in moreticket and a waiting type
     * @return array
     */
    static function getTicketsWithPendingReasonsToAdd() {
        global $DB;
        $results = $DB->request([
            'SELECT' => [
                'glpi_tickets.id as tickets_id',
                'glpi_pendingreasons_items.pendingreasons_id as pendingreasons_id',
                'glpi_tickets.status as status',
                'glpi_plugin_moreticket_waitingtickets.plugin_moreticket_waitingtypes_id as plugin_moreticket_waitingtypes_id'
            ],
            'FROM' => 'glpi_tickets',
            'JOIN' => [
                'glpi_pendingreasons_items' => [
                    'FKEY' => [
                        'glpi_pendingreasons_items' => 'items_id',
                        'glpi_tickets' => 'id',
                        ['AND' =>[ 'glpi_pendingreasons_items.itemtype' => 'Ticket']]
                    ]
                ],
                'glpi_plugin_moreticket_waitingtickets' => [
                    'FKEY' => [
                        'glpi_plugin_moreticket_waitingtickets' => 'tickets_id',
                        'glpi_tickets' => 'id',
                        ['AND' => [[
                            'glpi_plugin_moreticket_waitingtickets.plugin_moreticket_waitingtypes_id' => ['<>', 0],
                            'glpi_plugin_moreticket_waitingtickets.date_end_suspension' => 'null',
                        ]]]
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_pendingreasons_items.items_id' => 'null',
                'glpi_tickets.status' => CommonITILObject::WAITING
            ]
        ], true);
        $data = [];
        foreach($results as $result){
            $data[] = $result;
        }
        return array_filter($data, fn($e) => $e['plugin_moreticket_waitingtypes_id']);
    }

    /**
     * Generate HTML for Tools tab
     * @return void
     */
    function showToolsForm()
    {
        $ticketsToMigrate = self::getTicketsToMigrate();
        $ticketsWithReasonsToAdd = self::getTicketsWithPendingReasonsToAdd();
        echo "<div class='center'>";
        echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>
                <th>" . __("Waiting tickets to migrate to core") . "</th>
                <th>".count($ticketsToMigrate)."</th>
              </tr>";

        echo "<tr>
                <th>" . __("Core waiting tickets without pending reasons but have waiting type defined in moreticket") . "</th>
                <th>".count($ticketsWithReasonsToAdd)."</th>
              </tr>";

        echo "<tr class='tab_bg_1' align='center'>";
        echo "<td colspan='2' align='center'>";
        echo Html::submit(
            __('Execute the migration', 'moreticket'),
            ['name' => 'tickets_migrate', 'class' => 'btn btn-primary']
        );
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    function executeMigration()
    {
        global $DB;
        $mappingFile = PLUGIN_MORETICKET_DIR . '/mapping_file.csv';
        if (!file_exists($mappingFile)) {
            die("The CSV file doesn't exist.");
        }
        $content = file_get_contents($mappingFile);
        $rows = explode("\n", $content);
        $data = [];
        foreach ($rows as $index => $row) {
            if (!empty($row) && $index > 0) {
                $value = str_getcsv($row, ';');
                // see if this is necessary
                $value[2] = trim(str_replace('"', '', $value[2]));
                $data[$value[2]] = [
                    'core' => $value[0],
                    'entity' => $value[1],
                    'moreticket' => $value[2]
                ];
            }
        }

        // add pending reasons ids (core values) to data
        $pendingReasonsName = array_map(fn($e) => $e['core'], $data);
        $pendingReasons = $DB->request([
            'SELECT' => ['name', 'id'],
            'FROM' => 'glpi_pendingreasons',
            //'WHERE' => [
            //    'name' => $pendingReasonsName
            //]
        ]);
        foreach ($pendingReasons as $pendingReason) {
            $data = array_map(function ($d) use ($pendingReason) {
                if ($d['core'] == $pendingReason['name']) {
                    $d['pendingreasons_id'] = $pendingReason['id'];
                }
                return $d;
            }, $data);
        }

        // add waiting types ids (moreticket 9.5 values) to data
        $waitingTypesName = array_map(fn($e) => $e['moreticket'], $data);
        $waitingTypes = $DB->request([
            'SELECT' => ['name', 'id'],
            'FROM' => 'glpi_plugin_moreticket_waitingtypes',
            //'WHERE' => [
            //    'name' => $waitingTypesName
            //]
        ]);
        foreach ($waitingTypes as $waitingType) {
            $data = array_map(function ($d) use ($waitingType) {
                if ($d['moreticket'] == $waitingType['name']) {
                    $d['plugin_moreticket_waitingtypes_id'] = $waitingType['id'];
                }
                return $d;
            }, $data);
        }

        $formattedData = [];
        foreach ($data as $d) {
            if (isset($d['plugin_moreticket_waitingtypes_id'])) {
                $formattedData[$d['plugin_moreticket_waitingtypes_id']] = $d;
            }
        }

        /*
        // add entities ids to data
        $entitiesName = array_map(fn($e) => $e['entity'], $data);
        $entities = $DB->request([
            'SELECT' => ['completename', 'id'],
            'FROM' => 'glpi_entities',
            'WHERE' => [
                'completename' => $entitiesName
            ]
        ]);
        foreach ($entities as $entity) {
            $data = array_map($data, function($d) use ($entity) {
                if ($d['entity'] == $entity['completename']) {
                    $d['entities_id'] = $entity['id'];
                }
                return $d;
            });
        }
        */
        $toMigrate = self::getTicketsToMigrate();
        foreach($toMigrate as $waitingTicket) {
            $ticket = new Ticket();
            // TODO : replace by getFromDBByCrit and add entities_id ? (and uncomment part above)
            if ($ticket->getFromDB($waitingTicket['tickets_id'])) {
                // add relation between pending reason and ticket
                $DB->insert(
                    'glpi_pendingreasons_items',
                    [
                        'pendingreasons_id' => $formattedData[$waitingTicket['plugin_moreticket_waitingtypes_id']]['pendingreasons_id'],
                        'items_id' => $waitingTicket['tickets_id'],
                        'itemtype' => 'Ticket',
                        'followup_frequency' => 0,
                        'followups_before_resolution' => 0,
                        'bump_count' => 0,
                        'last_bump_date' => date('Y-m-d H:i:s'),
                        'previous_status' => $ticket->fields['status']
                    ]
                );
                // update ticket status to Waiting
                $ticket->update([
                    'id' => $ticket->getID(),
                    'status' => CommonITILObject::WAITING
                ]);
            }
        }
        $addPendingReason = self::getTicketsWithPendingReasonsToAdd();
        foreach($addPendingReason as $waitingTicket) {
            $ticket = new Ticket();
            // TODO : replace by getFromDBByCrit and add entities_id ? (and uncomment part above)
            if ($ticket->getFromDB($waitingTicket['tickets_id'])) {
                // add relation between pending reason and ticket
                $DB->insert(
                    'glpi_pendingreasons_items',
                    [
                        'pendingreasons_id' => $formattedData[$waitingTicket['plugin_moreticket_waitingtypes_id']]['pendingreasons_id'],
                        'items_id' => $waitingTicket['tickets_id'],
                        'itemtype' => 'Ticket',
                        'followup_frequency' => 0,
                        'followups_before_resolution' => 0,
                        'bump_count' => 0,
                        'last_bump_date' => date('Y-m-d H:i:s'),
                        'previous_status' => $ticket->fields['status']
                    ]
                );
            }
        }

        Session::addMessageAfterRedirect(
            __("Migration done", 'moreticket')
        );
    }
}
