<?php

/*
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2015-2026 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Moreticket;

use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Document;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use ITILSolution;
use Log;
use Session;
use SolutionTemplate;
use Toolbox;

use GlpiPlugin\Moreticket\Config;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class CloseTicket
 */
class CloseTicket extends CommonDBTM
{
    public static $types = ['Ticket'];
    public $dohistory = true;
    public static $rightname = "plugin_moreticket";


    public static function getIcon()
    {
        return "ti ti-browser-x";
    }

    public static function canCreate(): bool
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
        return false;
    }

    public function canCreateItem(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    /**
     * Normalize input server-side before insert.
     *
     * The closing record's author (requesters_id) is forced to the authenticated
     * user and never taken from client input: the form ships it as a hidden field,
     * so a technician with UPDATE on the ticket could otherwise attribute the
     * closure to a colleague and pollute the audit trail. The closing date is
     * defaulted to the server time when the POST omits it instead of trusting a
     * raw client value.
     *
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForAdd($input)
    {
        $input['requesters_id'] = Session::getLoginUserID();

        if (empty($input['date'])) {
            $input['date'] = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        }

        return $input;
    }

    /**
     * Display moreticket-item's tab for each users
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $config = new Config();

        if (!$withtemplate) {
            if ($item->getType() == \Ticket::class
                && $item->fields['status'] == \Ticket::CLOSED
                && $config->closeInformations()) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        __('Close ticket informations', 'moreticket'),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["tickets_id" => $item->getID()]
                        )
                    );
                }
                return __('Close ticket informations', 'moreticket');
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
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $config = new Config();

        if ($item->getType() == \Ticket::class
            && ($item->fields['status'] == \Ticket::CLOSED)
            && $config->closeInformations()) {
            self::showForTicket($item);
        }

        return true;
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
        return __('Close ticket informations', 'moreticket');
    }

    // Check the mandatory values of forms

    /**
     * @param $values
     *
     * @return bool
     */
    public static function checkMandatory($values)
    {
        $checkKo = [];

        $config = new Config();

        $mandatory_fields = ['solution' => __('Solution description', 'moreticket')];

        if ($config->mandatorySolutionType() == true) {
            $mandatory_fields['solutiontypes_id'] = _n('Solution type', 'Solution types', 1);
        }

        $msg = [];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    $msg[] = $mandatory_fields[$key];
                    $checkKo[] = 1;
                }
            }
            $_SESSION['glpi_plugin_moreticket_close'][$key] = $value;
        }

        if (in_array(1, $checkKo)) {
            Session::addMessageAfterRedirect(
                __('Ticket cannot be closed', 'moreticket') . "<br>" . _n(
                    'Mandatory field',
                    'Mandatory fields',
                    2
                ) . " : " . implode(', ', $msg),
                false,
                ERROR
            );
            return false;
        }
        return true;
    }

    /**
     * @param Ticket $item
     *
     * @return bool
     */
    public static function showForTicket(\Ticket $item)
    {
        if (!self::canView()) {
            return false;
        }

        $canedit = ($item->canUpdate() && self::canUpdate());

        // The date field and the comment textarea echo their markup directly:
        // capture them into HTML slots for the template.
        ob_start();
        Html::showDateTimeField("date", ['value' => date('Y-m-d H:i:s')]);
        $date_field = ob_get_clean();

        ob_start();
        Html::textarea([
            'name' => 'comment',
            'cols' => 80,
            'rows' => 8,
            'enable_richtext' => false,
        ]);
        $comment_field = ob_get_clean();

        TemplateRenderer::getInstance()->display('@moreticket/closeticket_form.html.twig', [
            'form_action'      => Toolbox::getItemTypeFormURL(__CLASS__),
            'canedit'          => $canedit,
            'writer_name'      => getUserName(Session::getLoginUserID()),
            'requesters_hidden' => Html::hidden('requesters_id', ['value' => Session::getLoginUserID()]),
            'date_field'       => $date_field,
            'comment_field'    => $comment_field,
            'file_field'       => Html::file(['display' => false]),
            'max_upload'       => Document::getMaxUploadSize(),
            'tickets_hidden'   => Html::hidden('tickets_id', ['value' => $item->fields['id']]),
            'items_hidden'     => Html::hidden('items_id', ['value' => $item->fields['id']]),
            'itemtype_hidden'  => Html::hidden('itemtype', ['value' => 'Ticket']),
        ]);

        // List
        self::showList($item, $canedit);
    }

    /**
     * Provides search options configuration. Do not rely directly
     * on this, @return array a *not indexed* array of search options
     *
     * @since 9.3
     *
     * This should be overloaded in Class
     *
     * @see CommonDBTM::searchOptions instead.
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id' => '10',
            'table' => $this->getTable(),
            'field' => 'date',
            'name' => __('Date'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id' => '12',
            'table' => 'glpi_users',
            'field' => 'name',
            'name' => __('Writer'),
            'datatype' => 'dropdown',
            'linkfield' => 'requesters_id',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Print the wainting ticket form
     *
     * @param $item
     * @param $canedit
     *
     * @return
     * @internal param int $ID ID of the item
     * @internal param array $options - target filename : where to go when done.*     - target filename : where to go
     *    when done.
     *     - withtemplate boolean : template or basic item
     */
    public static function showList($item, $canedit)
    {
        // validation des droits
        if (!self::canView()) {
            return false;
        }

        $rand = mt_rand();
        $dbu  = new DbUtils();
        $doc  = new Document();

        $data = $dbu->getAllDataFromTable(
            "glpi_plugin_moreticket_closetickets",
            ['tickets_id' => $item->getField('id')] + ['ORDER' => 'date DESC'],
            false
        );

        $entries = [];
        foreach ($data as $closeTicket) {
            $document_link = '';
            if ($doc->getFromDB($closeTicket['documents_id'])) {
                $document_link = $doc->getLink();
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $closeTicket['id'],
                'date'     => $closeTicket['date'],
                'comment'  => $closeTicket['comment'],
                'writer'   => $dbu->getUserName($closeTicket['requesters_id']),
                'document' => $document_link,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'             => true,
            'nofilter'           => true,
            'columns'            => [
                'date'     => __('Date'),
                'comment'  => __('Comments'),
                'writer'   => __('Writer'),
                'document' => __('Document'),
            ],
            'formatters'         => [
                'date'     => 'datetime',
                'document' => 'raw_html',
            ],
            'entries'            => $entries,
            'total_number'       => count($entries),
            'filtered_number'    => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
                'specific_actions' => [
                    'purge' => _x('button', 'Delete permanently'),
                ],
            ],
        ]);
    }

    /**
     * Get close ticket informations
     *
     * @param  $tickets_id
     * @param  $options
     *
     * @return array
     */
    public static function getCloseTicketFromDB($tickets_id, $options = [])
    {
        $dbu = new DbUtils();
        $data = $dbu->getAllDataFromTable(
            "glpi_plugin_moreticket_closetickets",
            ['tickets_id' => $tickets_id]
            + ['ORDER' => 'date DESC']
            + ['START' => (int) $options['start']]
            + ['LIMIT' => (int) $options['limit']],
            false
        );

        return $data;
    }

    /**
     * Print the wainting ticket form
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
        global $CFG_GLPI;

        // validation des droits
        if (!$this->canView()) {
            return false;
        }

        $ticket = new \Ticket();

        if ($ID > 0) {
            if (!$ticket->getFromDB($ID)) {
                $ticket->getEmpty();
            }
        } else {
            // Create item
            $ticket->getEmpty();
        }

        // If values are saved in session we retrieve it
        if (isset($_SESSION['glpi_plugin_moreticket_close'])) {
            foreach ($_SESSION['glpi_plugin_moreticket_close'] as $key => $value) {
                if (!is_array($value)) {
                    $ticket->fields[$key] = str_replace(['\r\n', '\r', '\n'], '', $value);
                }
            }
        }

        unset($_SESSION['glpi_plugin_moreticket_close']);

        $config     = new Config();
        $rand       = mt_rand();
        $content_id = "solution$rand";

        // Solution template dropdown (echoes directly).
        ob_start();
        SolutionTemplate::dropdown([
            'name' => "solution_template",
            'value' => 0,
            'rand' => $rand,
            'on_change' => "solutiontemplate_update{$rand}(this.value)",
        ]);
        $solution_template_dropdown = ob_get_clean();

        $JS = "function solutiontemplate_update{$rand}(value) {
                  $.ajax({
                     url: '{$CFG_GLPI['root_doc']}/ajax/solution.php',
                     type: 'POST',
                     data: {
                        solutiontemplates_id: value
                     }
                  }).done(function(data) {
                     tinymce.get('{$content_id}').setContent(data.content);

                     var solutiontypes_id = isNaN(parseInt(data.solutiontypes_id))
                        ? 0
                        : parseInt(data.solutiontypes_id);
                     $('#dropdown_solutiontypes_id{$rand}').trigger('setValue', solutiontypes_id);
                  });
               }";

        // Solution type dropdown (echoes directly).
        ob_start();
        Dropdown::show(
            'SolutionType',
            [
                'value' => $ticket->getField('solutiontypes_id'),
                'rand' => $rand,
                'entity' => $ticket->getEntityID(),
            ]
        );
        $solution_type_dropdown = ob_get_clean();

        // Solution description rich-text editor (both helpers echo directly).
        if (!isset($ticket->fields['solution'])) {
            $ticket->fields['solution'] = '';
        }
        $editor_rand = mt_rand();
        ob_start();
        Html::initEditorSystem("solution" . $editor_rand);
        Html::textarea([
            'name' => 'solution',
            'value' => $ticket->fields['solution'],
            'rand' => $editor_rand,
            'editor_id' => $content_id,
            'enable_fileupload' => false,
            'enable_richtext' => true,
            // Uploaded images processing is not able to handle multiple use of same uploaded file, so until this is fixed,
            // it is preferable to disable image pasting in rich text inside massive actions.
            'enable_images' => false,
            'cols' => 12,
            'rows' => 80,
        ]);
        $solution_editor = ob_get_clean();

        $use_duration_solution = $config->useDurationSolution();

        if (!isset($ticket->fields['duration_solution'])) {
            $ticket->fields['duration_solution'] = '';
        }

        $duration_dropdown = '';
        $duration_span_id  = '';
        if ($use_duration_solution == 1) {
            $duration_rand    = mt_rand();
            $duration_span_id = "duration_solution_" . $duration_rand . $ticket->fields['id'];
            $toadd = [];
            for ($i = 9; $i <= 100; $i++) {
                $toadd[] = $i * HOUR_TIMESTAMP;
            }
            ob_start();
            Dropdown::showTimeStamp("duration_solution", [
                'min' => 0,
                'max' => 8 * HOUR_TIMESTAMP,
                'value' => $ticket->fields['duration_solution'],
                'inhours' => true,
                'toadd' => $toadd,
            ]);
            $duration_dropdown = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@moreticket/closeticket_solution_form.html.twig', [
            'solution_template_label'    => _n('Solution template', 'Solution templates', 1),
            'solution_template_dropdown' => $solution_template_dropdown,
            'render_twig_hidden'         => Html::hidden("_render_twig", ['value' => true]),
            'script_block'               => Html::scriptBlock($JS),
            'solution_type_label'        => _n('Solution type', 'Solution types', 1),
            'solution_type_mandatory'    => $config->mandatorySolutionType() == true,
            'solution_type_dropdown'     => $solution_type_dropdown,
            'solution_desc_label'        => __('Solution description', 'moreticket'),
            'solution_editor'            => $solution_editor,
            'use_duration_solution'      => $use_duration_solution == 1,
            'duration_mandatory'         => $config->isMandatorysolution(),
            'duration_span_id'           => $duration_span_id,
            'duration_dropdown'          => $duration_dropdown,
        ]);
    }

    // Hook done on before add ticket - checkMandatory

    /**
     * @param $item
     *
     * @return bool
     */
    public static function preAddCloseTicket($item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if (isset($config->fields['use_solution'])
            && $config->useSolution()
            && $config->solutionStatus()) {
            // Get allowed status
            $array = json_decode($config->solutionStatus(), true);
            if (is_array($array)) {
                $solution_status = array_keys($array);

                // Then we add tickets informations
                if (isset($item->input['status'])
                    && in_array($item->input['status'], $solution_status)) {
                    if (self::checkMandatory($item->input)) {
                        // Add followup on immediate ticket closing
                        if (!isset($item->input['id']) || $item->input['id'] == 0) {
                            $item->input['statusold'] = $item->input['status'];
                            $item->input['status'] = 0;
                        }

                        $item->input['solution'] = str_replace(['\r', '\n', '\r\n'], '', $item->input['solution']);
                    } else {
                        $_SESSION['saveInput'][$item->getType()] = $item->input;
                        $item->input = [];
                    }
                }
                return true;
            }
        }

        return false;
    }

    public static function postAddCloseTicket(\Ticket $item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if (isset($config->fields['use_solution'])
            && $config->useSolution()
            && $config->solutionStatus()) {
            // Get allowed status
            $array = json_decode($config->solutionStatus(), true);
            if (is_array($array)) {
                // Then we add tickets informations
                if (isset($item->fields['id'])
                    && isset($item->input['statusold'])) {
                    $input = [];
                    $input['itemtype'] = 'Ticket';
                    $input['items_id'] = $item->getID();
                    $input['content'] = $item->input['solution'];
                    $input['date_creation'] = $item->input['date'];
                    $input['status'] = 3;
                    $input['solutiontypes_id'] = $item->input['solutiontypes_id'];

                    $input['duration_solution'] = $item->input['duration_solution'];

                    $itilsolution = new ITILSolution();
                    $id = $itilsolution->add($input);
                    if ($id > 0) {
                        $item->update([
                            'id' => $item->fields['id'],
                            'status' => $item->input['statusold'],
                        ]);
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function post_addItem()
    {

        $changes[0] = '0';
        $changes[1] = '';
        $changes[2] = sprintf(
            __('%1$s added closing informations', 'moreticket'),
            getUserName(Session::getLoginUserID())
        );
        Log::history($this->fields['tickets_id'], 'Ticket', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);

        parent::post_addItem();
    }


    /**
     * @param int $history
     *
     * @return void
     */
    public function post_updateItem($history = 1)
    {

        $changes[0] = '0';
        $changes[1] = '';
        $changes[2] = sprintf(
            __('%1$s updated closing informations', 'moreticket'),
            getUserName(Session::getLoginUserID())
        );
        Log::history($this->fields['tickets_id'], 'Ticket', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);

        parent::post_updateItem();
    }


    /**
     * @param int $history
     *
     * @return void
     */
    public function post_purgeItem($history = 1)
    {

        $changes[0] = '0';
        $changes[1] = '';
        $changes[2] = sprintf(
            __('%1$s deleted closing informations', 'moreticket'),
            getUserName(Session::getLoginUserID())
        );
        Log::history($this->fields['tickets_id'], 'Ticket', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);

        parent::post_updateItem();
    }

    /**
     * Cleaning the information entered in the ticket for adding solution
     * but not useful so delete to not add solution.
     *
     * @param \Ticket $ticket
     */
    public static function cleanCloseTicket(\Ticket $ticket)
    {
        $fields = ['solutiontemplates_id', 'solution', 'solutiontypes_id'];
        foreach ($fields as $field) {
            if (isset($ticket->input[$field])) {
                unset($ticket->input[$field]);
            }
        }
    }

    /**
     * Get the standard massive actions which are forbidden
     *
     * @return array of massive actions
     **@since version 0.84
     *
     */
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'amend_comment';
        return $forbidden;
    }
}
