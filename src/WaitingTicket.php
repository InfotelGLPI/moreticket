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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

use Ajax;
use CommonDBTM;
use CommonGLPI;
use CommonITILObject;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryExpression;
use Html;
use ITILFollowup;
use Session;

/**
 * Class WaitingTicket
 */
class WaitingTicket extends CommonDBTM
{
    use ParentTicketRights;

    public static $types     = ['Ticket'];
    public $dohistory = true;
    public static $rightname = "plugin_moreticket";

    /**
     * Have I the global right to "create" the Object
     * May be overloaded if needed (ex KnowbaseItem)
     *
     * @return
     **/
    public static function canCreate(): bool
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
        return false;
    }

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
        return _n('Waiting ticket', 'Waiting tickets', $nb, 'moreticket');
    }

    public static function getIcon()
    {
        return "ti ti-clock-pause";
    }
    /**
     * Display moreticket-item's tab for each users
     *
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $config = new Config();

        if (!$withtemplate) {
            if ($item->getType() == 'Ticket' && $config->useWaiting() == true) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        self::getTypeName(2),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["tickets_id" => $item->getID()],
                        ),
                    );
                }
                return self::createTabEntry(self::getTypeName(2));
            }
        }
        return '';
    }

    /**
     * Display tab's content for each users
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (in_array($item->getType(), WaitingTicket::getTypes(true))) {
            self::showForTicket($item);
        }
        return true;
    }

    /**
     * Check the mandatory values of forms
     *
     * @param       $values
     * @param bool  $add
     * @param int   $tickets_id ticket the form belongs to, 0 on the creation form
     *
     * @return bool
     */
    public static function checkMandatory($values, $add = false, $tickets_id = 0)
    {
        $checkKo   = [];
        $dateError = false;

        $config = new Config();

        $mandatory_fields = [];

        if ($config->mandatoryReportDate() == true) {
            $mandatory_fields['date_report'] = __('Postponement date', 'moreticket');
        }

        if ($config->mandatoryWaitingReason() == true) {
            $mandatory_fields['reason'] = __('Reason', 'moreticket');
        }

        $msg = [];

        foreach ($mandatory_fields as $key => $value) {
            if (!array_key_exists($key, $values)) {
                $msg[]     = $value;
                $checkKo[] = 1;
            }
        }

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if ($key != 'date_report' && empty($value)) {
                    $msg[]     = $mandatory_fields[$key];
                    $checkKo[] = 1;
                } elseif ($key == 'date_report' && $value == 'NULL') {
                    $msg[]     = $mandatory_fields[$key];
                    $checkKo[] = 1;
                } elseif ($key == 'date_report' && strtotime($value) <= time()) {
                    $dateError = Html::convDateTime($value);
                    $checkKo[] = 1;
                }
            }
        }

        if (in_array(1, $checkKo)) {
            // Keep what was typed, for the form of this ticket alone, and only now that the
            // submit is refused: on success the values are already in the waiting row, and a
            // draft outliving a success is a draft handed to the next ticket opened.
            SessionDraft::remember(
                SessionDraft::WAITING,
                $values,
                ['reason', 'date_report', 'plugin_moreticket_waitingtypes_id'],
                $tickets_id,
            );

            if (!$add) {
                $errorMessage = __('Waiting ticket cannot be saved', 'moreticket') . "<br>";
            } else {
                $errorMessage = __('Ticket cannot be saved', 'moreticket') . "<br>";
            }

            if ($dateError) {
                $errorMessage .= __("Postponement date is inferior of today's date", 'moreticket') . "<br>";
            }

            if (count($msg)) {
                $errorMessage .= _n('Mandatory field', 'Mandatory fields', 2) . " : " . implode(', ', $msg);
            }

            Session::addMessageAfterRedirect($errorMessage, false, ERROR);

            return false;
        }

        return true;
    }

    /**
     * Print the waiting ticket form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return
     * */
    public function showForm($ID, $options = [])
    {
        // validation des droits
        if (!$this->canView()) {
            return false;
        }

        $ID = (int) $ID;

        if ($ID > 0) {
            if (self::getWaitingTicketFromDB($ID) === false) {
                $this->getEmpty();
            } else {
                $this->fields = self::getWaitingTicketFromDB($ID);
            }
        } else {
            // Create item
            $this->getEmpty();
        }

        // Give back what a refused submit left behind -- but only if it was typed for this
        // very ticket. The session key is common to the whole user session, so a reason
        // entered on another ticket used to pre-fill this form, and be recorded here.
        foreach (SessionDraft::restore(SessionDraft::WAITING, $ID) as $key => $value) {
            $this->fields[$key] = $value;
        }

        $config = new Config();

        if ($this->fields['date_report'] == 'NULL') {
            $this->fields['date_report'] = date("Y-m-d H:i:s");
        }

        // The block is shown under canView(), that is READ, while recording a reason is
        // canCreate(), that is UPDATE (see the class contract at the top of this file).
        // Rendering the inputs to a reader promised a write the sink refuses.
        $canedit    = self::canCreate();
        $date_field = '';

        if ($canedit) {
            // The date field echoes its markup directly: capture it into an HTML slot.
            ob_start();
            Html::showDateTimeField("date_report", ['value'      => $this->fields['date_report'],
                'maybeempty' => false]);
            $date_field = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@moreticket/waitingticket_form.html.twig', [
            'block_id'         => 'moreticket_waiting_ticket',
            'with_break'       => false,
            'row_class'        => '',
            'canedit'          => $canedit,
            'reason_mandatory' => $config->mandatoryWaitingReason() == true,
            'reason_value'     => $this->fields['reason'],
            'reason_input'     => $canedit ? Html::input('reason', ['value' => $this->fields['reason'], 'size' => 20]) : '',
            'date_mandatory'   => $config->mandatoryReportDate() == true,
            'date_value'       => Html::convDateTime($this->fields['date_report']),
            'date_field'       => $date_field,
            'position_script'  => '',
        ]);
    }

    /**
     * add waiting block form, with the plugin's waiting reason and postponement date
     * @param int $tickets_id
     * @param string $itilObject getType()
     * @return void
     */
    public function addFormWaitingBlock($tickets_id, $itilObject)
    {
        // validation des droits
        if (!$this->canView()) {
            return false;
        }

        $tickets_id = (int) $tickets_id;

        if ($tickets_id > 0) {
            if (self::getWaitingTicketFromDB($tickets_id) === false) {
                $this->getEmpty();
            } else {
                $this->fields = self::getWaitingTicketFromDB($tickets_id);
            }
        } else {
            // Create item
            $this->getEmpty();
        }

        // Same as showForm(): a draft belongs to the ticket it was typed for. This block is
        // injected in the followup and task forms of the timeline, which are exactly the
        // places a technician reaches straight after leaving another ticket.
        foreach (SessionDraft::restore(SessionDraft::WAITING, $tickets_id) as $key => $value) {
            $this->fields[$key] = $value;
        }

        switch ($itilObject) {
            case ITILFollowup::class:
                $blockId       = 'moreticket_waiting_ticket_followup';
                $blockSelector = '#moreticket_waiting_ticket_followup';
                $position      = 'first';
                break;
            case \TicketTask::class:
                $blockId       = 'moreticket_waiting_ticket_task';
                $blockSelector = '#moreticket_waiting_ticket_task';
                $position      = 'last';
                break;
        }
        $config = new Config();

        if ($this->fields['date_report'] == 'NULL') {
            $this->fields['date_report'] = date("Y-m-d H:i:s");
        }

        // The block is shown under canView(), that is READ, while recording a reason is
        // canCreate(), that is UPDATE (see the class contract at the top of this file).
        // Rendering the inputs to a reader promised a write the sink refuses.
        $canedit    = self::canCreate();
        $date_field = '';

        if ($canedit) {
            // The date field echoes its markup directly: capture it into an HTML slot.
            ob_start();
            Html::showDateTimeField("date_report", ['value'      => $this->fields['date_report'],
                'maybeempty' => false]);
            $date_field = ob_get_clean();
        }

        // Position the block with javascript and toggle its display on the pending switch.
        $position_script = Html::scriptBlock(
            "$(document).ready(function() {
           let switch_pending = $('input[type=\"checkbox\"][name=\"pending\"]:$position');

                    if (switch_pending != undefined) {
                        $('input[type=\"checkbox\"][name=\"pending\"]:$position').closest('label').closest('span').parent().parent().parent().after($('$blockSelector'));

                        $('$blockSelector').css({'display': 'none'});

                        if (switch_pending.is(':checked') === true) {
                            $('$blockSelector').css({
                                    'display': 'block',
                                    'clear': 'both',
                                    'text-align': 'center'
                                });
                        } else {
                            $('$blockSelector').css({'display': 'none'});
                        }

                        switch_pending.change(function () {
                            if (switch_pending.is(':checked') === true) {
                                $('$blockSelector').css({
                                        'display': 'block',
                                        'clear': 'both',
                                        'text-align': 'center'
                                });
                            } else {
                                $('$blockSelector').css({'display': 'none'});
                            }
                        });
                    }
        });",
        );

        TemplateRenderer::getInstance()->display('@moreticket/waitingticket_form.html.twig', [
            'block_id'         => $blockId,
            'with_break'       => true,
            'row_class'        => 'tab_bg_1',
            'canedit'          => $canedit,
            'reason_mandatory' => $config->mandatoryWaitingReason() == true,
            'reason_value'     => $this->fields['reason'],
            'reason_input'     => $canedit ? Html::input('reason', ['value' => $this->fields['reason'], 'size' => 20]) : '',
            'date_mandatory'   => $config->mandatoryReportDate() == true,
            'date_value'       => Html::convDateTime($this->fields['date_report']),
            'date_field'       => $date_field,
            'position_script'  => $position_script,
        ]);
    }

    /**
     * Print the wainting ticket form
     *
     * @param $item
     *
     * @return Nothing
     * @internal param int $ID ID of the item
     * @internal param array $options - target filename : where to go when done.*     - target filename : where to go
     *    when done.
     *     - withtemplate boolean : template or basic item
     *
     */
    public static function showForTicket($item)
    {
        // validation des droits
        if (!Session::haveRight('plugin_moreticket', READ)) {
            return false;
        }

        if (isset($_REQUEST["start"])) {
            $start = $_REQUEST["start"];
        } else {
            $start = 0;
        }

        // Total Number of events
        $dbu    = new DbUtils();
        $number = $dbu->countElementsInTable(
            "glpi_plugin_moreticket_waitingtickets",
            ["tickets_id" => $item->getField('id')],
        );

        $entries = [];
        if ($number >= 1) {
            foreach (self::getWaitingTicketFromDB(
                $item->getField('id'),
                ['start' => $start,
                    'limit' => $_SESSION['glpilist_limit']],
            ) as $waitingTicket) {
                if ($waitingTicket['date_report'] == "0000-00-00 00:00:00") {
                    $date_report = _x('periodicity', 'None');
                } else {
                    $date_report = Html::convDateTime($waitingTicket['date_report']);
                }
                $entries[] = [
                    'date_suspension'     => Html::convDateTime($waitingTicket['date_suspension']),
                    'reason'              => $waitingTicket['reason'],
                    'date_report'         => $date_report,
                    'date_end_suspension' => Html::convDateTime($waitingTicket['date_end_suspension']),
                ];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'            => true,
            'nofilter'          => true,
            'nopager'           => false,
            'columns'           => [
                'date_suspension'     => __('Suspension date', 'moreticket'),
                'reason'              => __('Reason', 'moreticket'),
                'date_report'         => __('Postponement date', 'moreticket'),
                'date_end_suspension' => __('Suspension end date', 'moreticket'),
            ],
            'formatters'        => [],
            'entries'           => $entries,
            'total_number'      => $number,
            'filtered_number'   => $number,
            'showmassiveactions' => false,
        ]);
    }

    /**
     * Get last waitingTicket
     *
     * @param       $tickets_id
     * @param array $options
     *
     * @return array|bool|mixed
     */
    public static function getWaitingTicketFromDB($tickets_id, $options = [])
    {
        global $DB;

        if (sizeof($options) == 0) {
            $iterator = $DB->request(
                ['FROM' => 'glpi_plugin_moreticket_waitingtickets',
                    'WHERE' => ['tickets_id' => $tickets_id,
                        'date_suspension' => new QuerySubQuery([
                            'SELECT' => ['MAX' => 'date_suspension'],
                            'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                            'WHERE' => ['tickets_id' => $tickets_id],
                        ]),
                        ['OR' => [
                            new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") = 0"),
                            new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") IS NULL"),
                        ],
                        ],
                    ],
                ],
            );

            $data_WaitingType = [];
            foreach ($iterator as $row) {
                $data_WaitingType[$row['id']] = $row;
                $iterator->next();
            }
        } else {
            $criteria = [
                'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                'WHERE' => ['tickets_id' => $tickets_id],
                'ORDERBY' => ['date_suspension DESC'],
            ];

            // START and LIMIT are two distinct keys of the iterator, and handleLimits() tests
            // is_numeric() on LIMIT: the array form emitted no clause at all, so the pager was
            // displayed but every one of its pages returned the whole table.
            if (isset($options['limit'])) {
                $criteria['START'] = (int) ($options['start'] ?? 0);
                $criteria['LIMIT'] = (int) $options['limit'];
            }

            $iterator = $DB->request($criteria);

            $data_WaitingType = [];
            foreach ($iterator as $row) {
                $data_WaitingType[$row['id']] = $row;
                $iterator->next();
            }
        }

        if (sizeof($data_WaitingType) > 0) {
            if (sizeof($options) == 0) {
                $data_WaitingType = reset($data_WaitingType);
            }

            return $data_WaitingType;
        }

        return false;
    }

    /**
     * Add a waiting ticket to the element's ticket, or update the latest waiting ticket if there already is one
     * @param $item TicketTask or ITILFollowup
     * @return void
     */
    public static function addWaitingTicket($item)
    {
        $waiting_ticket = new self();

        // Resolve the parent ticket before the control rather than after it, so a refused
        // submit stores its draft under the ticket the form belonged to.
        $tickets_id = (int) ($item->getType() === 'ITILFollowup'
            ? ($item->input['items_id'] ?? 0)
            : ($item->input['tickets_id'] ?? 0));

        if (self::checkMandatory($item->input, false, $tickets_id)) {
            if (isset($item->input['date_report'])
                && ($item->input['date_report'] == "0000-00-00 00:00:00"
                    || empty($item->input['date_report']))) {
                $item->input['date_report'] = 'NULL';
            }

            $status = (in_array(
                $item->input['_job']->fields['status'],
                [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
            )) ? CommonITILObject::ASSIGNED : $item->input['_job']->fields['status'];

            // Then we add tickets informations
            $input = [
                'reason'                            => (isset($item->input['reason'])) ? $item->input['reason'] : "",
                'tickets_id'                        => $tickets_id,
                'date_report'                       => (isset($item->input['date_report'])) ? $item->input['date_report'] : "NULL",
                'date_suspension'                   => date("Y-m-d H:i:s"),
                'date_end_suspension'               => 'NULL',
                'status'                            => $status,
                'plugin_moreticket_waitingtypes_id' => self::sanitizeWaitingType($item->input['plugin_moreticket_waitingtypes_id'] ?? 0),
            ];

            // based on WaitingTicket::preUpdateWaitingTicket
            if ($status == CommonITILObject::WAITING) {
                unset($input['status']);
            }

            // add() and update() control no right of their own, it is up to the caller. This
            // sink is reached from the timeline forms of ITILFollowup and TicketTask, whose
            // core rights say nothing about the plugin's: READ alone was enough to write a
            // reason, while the contract of the class is canCreate(), that is UPDATE. The
            // mandatory control above deliberately stays outside the test (see setup.php):
            // what is gated is the recording of the value, not the obligation to provide one.
            if (!self::canCreate()) {
                return;
            }

            $waitingTicketData = WaitingTicket::getWaitingTicketFromDB($tickets_id);

            if (!$waitingTicketData) {
                if ($waiting_ticket->add($input)) {
                    SessionDraft::forget(SessionDraft::WAITING);
                }
            } else {
                $waiting_ticket->getFromDB($waitingTicketData['id']);
                // based on WaitingTicket::preUpdateWaitingTicket
                unset($input['status']);
                unset($input['date_suspension']);
                unset($input['date_end_suspension']);
                $input['id'] = $waitingTicketData['id'];
                $waiting_ticket->update($input);
            }
        }
    }

    /**
     * @param $item
     */
    public static function preUpdateWaitingTicket($item)
    {

        $config = new Config();
        if ($config->useWaiting()) {
            $waiting_ticket = new self();

            // Then we add tickets informations
            if (isset($item->fields['id'])
                && isset($item->fields['status'])
                && isset($item->input['status'])
            ) {
                // ADD

                if ($item->fields['status'] != CommonITILObject::WAITING
                    && $item->input['status'] == CommonITILObject::WAITING
                    && self::getWaitingTicketFromDB($item->fields['id']) === false) {
                    if (self::checkMandatory($item->input, false, (int) $item->fields['id'])) {
                        if (isset($item->input['date_report'])
                            && ($item->input['date_report'] == "0000-00-00 00:00:00"
                                || empty($item->input['date_report']))) {
                            $item->input['date_report'] = 'NULL';
                        }

                        $status = (in_array(
                            $item->fields['status'],
                            [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                        ))
                            ? CommonITILObject::ASSIGNED : $item->fields['status'];

                        // Then we add tickets informations
                        $input = ['reason'                            => (isset($item->input['reason'])) ? $item->input['reason'] : "",
                            'tickets_id'                        => $item->fields['id'],
                            'date_report'                       => (isset($item->input['date_report'])) ? $item->input['date_report'] : "NULL",
                            'date_suspension'                   => date("Y-m-d H:i:s"),
                            'date_end_suspension'               => 'NULL',
                            'status'                            => $status,
                            'plugin_moreticket_waitingtypes_id' => self::sanitizeWaitingType($item->input['plugin_moreticket_waitingtypes_id'] ?? 0)];
                        // Same test as addWaitingTicket(): reading a reason is canView(),
                        // recording one is canCreate().
                        if (self::canCreate() && $waiting_ticket->add($input)) {
                            SessionDraft::forget(SessionDraft::WAITING);
                        }
                    } else {
                        unset($item->input['status']);
                    }

                    // UPDATE
                } elseif ($item->fields['status'] == CommonITILObject::WAITING
                           && $item->input['status'] == CommonITILObject::WAITING) {
                    $waiting_ticket_data = self::getWaitingTicketFromDB($item->fields['id']);
                    if (($waiting_ticket_data === false)) {
                        if (self::checkMandatory($item->input, false, (int) $item->fields['id'])) {
                            if (isset($item->input['date_report'])
                                && $item->input['date_report'] == "0000-00-00 00:00:00") {
                                $item->input['date_report'] = 'NULL';
                            }
                            $input = ['reason'                            => (isset($item->input['reason'])) ? $item->input['reason'] : "",
                                'tickets_id'                        => $item->fields['id'],
                                'date_report'                       => (isset($item->input['date_report']) && !empty($item->input['date_report'])) ? $item->input['date_report'] : "NULL",
                                'date_suspension'                   => date("Y-m-d H:i:s"),
                                'date_end_suspension'               => 'NULL',
                                'plugin_moreticket_waitingtypes_id' => self::sanitizeWaitingType($item->input['plugin_moreticket_waitingtypes_id'] ?? 0)];

                            // Then we add tickets informations
                            if (self::canCreate() && $waiting_ticket->add($input)) {
                                SessionDraft::forget(SessionDraft::WAITING);
                            }
                        } else {
                            unset($item->input['status']);
                        }
                    } else {
                        // Rewrite only what the post actually carries. This branch is reached
                        // by any update that reposts an unchanged status, while the waiting
                        // block is injected in the timeline forms alone: reading the three
                        // keys unconditionally replaced a reason and a postponement date
                        // entered earlier with empty values, silently and with nothing in the
                        // ticket history to show for it.
                        $update = [];

                        foreach (['reason', 'date_report', 'plugin_moreticket_waitingtypes_id'] as $field) {
                            if (array_key_exists($field, $item->input)) {
                                $update[$field] = $field === 'plugin_moreticket_waitingtypes_id'
                                    ? self::sanitizeWaitingType($item->input[$field])
                                    : $item->input[$field];
                            }
                        }

                        if (count($update) > 0 && self::canCreate()) {
                            $update['id'] = $waiting_ticket_data['id'];
                            $waiting_ticket->update($update);
                        }

                        // The two ADD branches above drop the draft once the row is written;
                        // this one never did, so a reason refused earlier stayed in session
                        // and was offered to the next form displayed.
                        SessionDraft::forget(SessionDraft::WAITING);
                    }
                }
            }
        }
    }

    /**
     * @param $item
     */
    public static function postUpdateWaitingTicket($item)
    {
        $waiting_ticket = new self();
        // Then we add tickets informations
        if (isset($item->fields['id'])) {
            if (isset($item->oldvalues['status'])
                && $item->oldvalues['status'] == CommonITILObject::WAITING) {
                if (isset($item->input['status'])
                    && $item->input['status'] != CommonITILObject::WAITING) {
                    // Get all waiting with date_suspension < today
                    $condition = ['tickets_id' => $item->fields['id'],
                        [
                            'OR' => [
                                ['date_end_suspension' => null],
                            ],
                        ]] + [new QueryExpression(
                            'UNIX_TIMESTAMP(date_suspension) <= UNIX_TIMESTAMP(NOW())',
                        ),
                        ];

                    $lastWaiting = $waiting_ticket->find($condition);

                    foreach ($lastWaiting as $field) {
                        $waiting_ticket->update(['id'                  => $field['id'],
                            'date_end_suspension' => date("Y-m-d H:i:s")]);
                    }
                    SessionDraft::forget(SessionDraft::WAITING);
                }
            }
        }
    }

    // Hook done on before add ticket - checkMandatory

    /**
     * Keep a posted waiting type id only when it names an existing row.
     *
     * The column carries no foreign key (sql/empty-1.7.5.sql declares a plain KEY), no form
     * of the plugin produces the field and WaitingType is not registered as an administrable
     * dropdown, so whatever reaches this key comes from the caller and from nobody else.
     * Search option 3452 joins the waiting type table on it: an unchecked value turns into a
     * dangling reference displayed in ticket lists and dashboards.
     *
     * @param mixed $value Value read from $item->input
     *
     * @return int The id when it exists, 0 otherwise
     */
    private static function sanitizeWaitingType($value): int
    {
        $waitingtypes_id = (int) $value;

        if ($waitingtypes_id <= 0) {
            return 0;
        }

        $waiting_type = new WaitingType();

        return $waiting_type->getFromDB($waitingtypes_id) ? $waitingtypes_id : 0;
    }

    /**
     * @param $item
     *
     * @return bool
     */
    public static function preAddWaitingTicket($item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if (isset($config->fields['use_waiting'])
            && $config->useWaiting()) {
            // Then we add tickets informations
            if (isset($item->input['id'])
                && isset($item->input['status'])
                && $item->input['status'] == CommonITILObject::WAITING
                && !self::checkMandatory($item->input, true)) {
                $_SESSION['saveInput'][$item->getType()] = $item->input;
                $item->input                             = [];
            }
        }
        return true;
    }

    // Hook done on after add ticket - add waitingtickets

    /**
     * @param $item
     *
     * @return bool
     */
    public static function postAddWaitingTicket($item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if (isset($config->fields['use_waiting']) && $config->useWaiting()) {
            $waiting_ticket = new self();
            // Then we add tickets informations
            if (isset($item->fields['id'])
                && ($item->input['status'] ?? null) == CommonITILObject::WAITING) {
                if (self::checkMandatory($item->input, false, (int) $item->fields['id'])) {
                    if (empty($item->input['date_report'])) {
                        $item->input['date_report'] = 'NULL';
                    }
                    // The reason and the waiting type are only posted when the block is part
                    // of the form: with both left optional in the configuration checkMandatory()
                    // passes without them, and reading them raw wrote NULL behind a PHP warning.
                    // The write itself answers to canCreate(), like every other sink here.
                    if (self::canCreate()
                        && $waiting_ticket->add(['reason'                            => $item->input['reason'] ?? '',
                            'tickets_id'                        => $item->fields['id'],
                            'date_report'                       => $item->input['date_report'],
                            'date_suspension'                   => date("Y-m-d H:i:s"),
                            'date_end_suspension'               => 'NULL',
                            'plugin_moreticket_waitingtypes_id' => self::sanitizeWaitingType($item->input['plugin_moreticket_waitingtypes_id'] ?? 0)])) {
                        SessionDraft::forget(SessionDraft::WAITING);
                    }
                } else {
                    $item->input['id']                       = $item->fields['id'];
                    $_SESSION['saveInput'][$item->getType()] = $item->input;
                    unset($item->input['status']);
                }
            }
        }
        return true;
    }

    /**
     * Type than could be linked to a typo
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     * */
    public static function getTypes($all = false)
    {
        if ($all) {
            return self::$types;
        }

        // Only allowed types
        $types = self::$types;
        $dbu   = new DbUtils();

        foreach ($types as $key => $type) {
            if (!($item = $dbu->getItemForItemtype($type))) {
                continue;
            }

            if (!$item->canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    /**
     * Cron action
     *
     * @param  $task for log
     *
     * @return int
     * @global $DB
     * @global $CFG_GLPI
     */
    public static function cronMoreticketWaitingTicket($task = null)
    {
        global $DB;

        if ($task->fields["state"] == \CronTask::STATE_DISABLE) {
            return 0;
        }

        $cron_status = 0;
        $today       = date('Y-m-d H:i:s');

        $waiting_ticket = new self();
        $ticket         = new \Ticket();
        $followup       = new ITILFollowup();
        $log            = new \Log();
        $config         = new Config();
        $content        = __("Waiting ticket exceedeed", 'moreticket');

        $query_ticket_waiting = [
            'SELECT' => 'id AS tickets_id',
            'FROM' => 'glpi_tickets',
            'WHERE' => ['is_deleted' => 0,
                'status' => \Ticket::WAITING],
        ];
        foreach ($DB->request($query_ticket_waiting) as $data) {
            // Update ticket only if last waiting has empty end of suspension
            $waiting = $waiting_ticket->getWaitingTicketFromDB($data['tickets_id']);
            if ($waiting
                && !empty($waiting['date_report'])
                && $waiting['date_report'] <= $today
            ) {
                $ticket->update(['id'     => $data['tickets_id'],
                    'status' => $waiting['status']]);
                $waiting_ticket->update(['id'                  => $waiting['id'],
                    'date_end_suspension' => date("Y-m-d H:i:s")]);
                if ($config->addFollowupStopWaiting()) {
                    $followup->add([
                        'itemtype' => \Ticket::getType(),
                        'items_id' => $ticket->getID(),
                        'content'    => $content,
                    ]);
                }
                $cron_status = 1;
                $task->addVolume(1);
                if (Session::isCron()) {
                    $log->history($data['tickets_id'], 'Ticket', [12, \Ticket::WAITING, $waiting['status']]);
                }
            }
        }
        return $cron_status;
    }

    // Cron action

    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {
        switch ($name) {
            case 'MoreticketWaitingTicket':
                return [
                    'description' => __("End of standby ticket", 'moreticket')];   // Optional
                break;
        }
        return [];
    }


    /**
     * Delete all elements that could have been added prior to bugfix when adding task/followup
     * @return void
     */
    public static function deleteDuplicates()
    {
        global $DB;
        $waitingTicket = new WaitingTicket();

        // latest element added to each ticket with at least one duplicate

        $iterator = $DB->request(
            [
                'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                'WHERE' => [
                    'date_suspension' => new QuerySubQuery([
                        'SELECT' => ['MAX' => 'date_suspension'],
                        'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                        'GROUPBY' => ['tickets_id'],
                    ]),
                    [
                        'OR' => [
                            new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") = 0"),
                            new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") IS NULL"),
                        ],
                    ],
                    'tickets_id' => new QuerySubQuery([
                        'SELECT' => ['tickets_id'],
                        'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                        'WHERE' => [
                            'OR' => [
                                new QueryExpression(
                                    "UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") = 0",
                                ),
                                new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") IS NULL"),
                            ],
                        ],
                        'GROUPBY' => ['tickets_id'],
                        'HAVING' => [new QueryExpression("COUNT(tickets_id) > 1")],
                    ]),
                ],
            ],
        );

        $duplicates = 0;
        foreach ($iterator as $row) {
            $tickets_id = $row['tickets_id'];

            // get the most recent where status != WAITING

            $iteratorStatus = $DB->request(
                [
                    'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                    'WHERE' => [
                        'date_suspension' => new QuerySubQuery([
                            'SELECT' => ['MAX' => 'date_suspension'],
                            'FROM' => 'glpi_plugin_moreticket_waitingtickets',
                            'WHERE' => [
                                'tickets_id' => $tickets_id,
                                'status' => ['<>', CommonITILObject::WAITING],
                            ],
                        ]),
                        [
                            'OR' => [
                                new QueryExpression(
                                    "UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") = 0",
                                ),
                                new QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date_end_suspension") . ") IS NULL"),
                            ],
                        ],
                        'tickets_id' => $tickets_id,
                        'status' => ['<>', CommonITILObject::WAITING],
                    ],
                ],
            );

            $status = CommonITILObject::ASSIGNED;
            foreach ($iteratorStatus as $rowStatus) {
                $status = $rowStatus['status'];
            }

            // update the one most recently added to set its status at the status of the one found before
            $waitingTicket->getFromDB($row['id']);
            if ($waitingTicket->fields['status'] == CommonITILObject::WAITING) {
                $DB->update(
                    'glpi_plugin_moreticket_waitingtickets',
                    ['status' => $status],
                    ['id' => $row['id']],
                );
            }

            // delete all those not marked as ended except for the one that was updated
            $DB->delete(
                'glpi_plugin_moreticket_waitingtickets',
                [
                    'tickets_id' => $tickets_id,
                    'date_end_suspension' => null,
                    'id' => ['!=', $row['id']],
                ],
            );
            $duplicates++;
        }

        if ($duplicates) {
            Session::addMessageAfterRedirect(sprintf(__('%s duplicates deleted', 'moreticket'), $duplicates));
        }
    }
}
