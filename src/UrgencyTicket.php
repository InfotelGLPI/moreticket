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

use CommonDBTM;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\ServiceCatalog\Config as ServiceCatalogConfig;
use Html;
use Plugin;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class UrgencyTicket
 */
class UrgencyTicket extends CommonDBTM
{
    use ParentTicketRights;

    public static $types     = ['Ticket'];
    public $dohistory = true;
    public static $rightname = "plugin_moreticket_justification";

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
        $checkKo = [];

        $mandatory_fields                  = [];
        $mandatory_fields['justification'] = __('Justification', 'moreticket');

        $msg = [];

        foreach ($mandatory_fields as $key => $value) {
            if (!array_key_exists($key, $values) && empty($values[$key])) {
                $msg[]     = $value;
                $checkKo[] = 1;
            }
        }

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    $msg[]     = $mandatory_fields[$key];
                    $checkKo[] = 1;
                }
            }
        }

        if (in_array(1, $checkKo)) {
            // Only the justification, only on refusal, and only for this ticket: see
            // SessionDraft for what the previous unconditional copy of the whole post did.
            SessionDraft::remember(SessionDraft::URGENCY, $values, ['justification'], $tickets_id);

            if (!$add) {
                $errorMessage = __('Urgency ticket cannot be saved', 'moreticket') . "<br>";
            } else {
                $errorMessage = __('Ticket cannot be saved', 'moreticket') . "<br>";
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
     * Print the urgency ticket form
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
            if (self::getUrgencyTicketFromDB($ID) === false) {
                $this->getEmpty();
            } else {
                $this->fields = self::getUrgencyTicketFromDB($ID);
            }
        } else {
            // Create item
            $this->getEmpty();
        }

        // Give back what a refused submit left behind -- but only if it was typed for this
        // very ticket. A justification names a person and a situation: it has no business
        // being proposed on somebody else's ticket, let alone in another entity.
        foreach (SessionDraft::restore(SessionDraft::URGENCY, $ID) as $key => $value) {
            $this->fields[$key] = $value;
        }

        $align = "center";

        if (Plugin::isPluginActive('servicecatalog')) {
            $config      = new ServiceCatalogConfig();
            $use_as_step = $config->getFormDisplayAsStep();
            if ($use_as_step != 1) {
                $align = "left";
            }
        }

        // Html::textarea echoes its markup directly: capture it into an HTML slot.
        ob_start();
        Html::textarea(['name'            => 'justification',
            'value'           => $this->fields['justification'],
            'cols'            => 30,
            'rows'            => 5,
            'enable_richtext' => false]);
        $justification_field = ob_get_clean();

        TemplateRenderer::getInstance()->display('@moreticket/urgencyticket_form.html.twig', [
            'align'               => $align,
            'justification_field' => $justification_field,
        ]);
    }

    /**
     * Get last urgencyTicket
     *
     * @param       $tickets_id
     * @param array $options
     *
     * @return array|bool|mixed
     */
    public static function getUrgencyTicketFromDB($tickets_id, $options = [])
    {
        global $DB;
        $request_args = [
            'FROM'  => 'glpi_plugin_moreticket_urgencytickets',
            'WHERE' => ['tickets_id' => $tickets_id],
        ];
        if (sizeof($options) > 0) {
            $request_args['START'] = (int) $options['start'];
            $request_args['LIMIT'] = (int) $options['limit'];
        }
        $iterator = $DB->request($request_args);
        if (count($iterator) === 0) {
            return false;
        }
        $data = iterator_to_array($iterator);
        if (sizeof($options) == 0) {
            return reset($data);
        }
        return $data;
    }

    /**
     * @param $item
     */
    public static function preUpdateUrgencyTicket($item)
    {
        $config = new Config();
        if ($config->useUrgency()) {
            $urgency_ticket = new self();

            // Then we add tickets informations
            // justification is deliberately not part of the test: when the field is missing
            // from the post, checkMandatory() below is what has to say so and refuse the
            // urgency change. Requiring it upfront turned the whole control into something
            // the caller opts into, which is precisely what preAddUrgencyTicket() avoids.
            if (isset($item->fields['id'])
                && isset($item->fields['urgency'])
                && isset($item->input['urgency'])
            ) {
                $urgency_ids = $config->getUrgency_ids();
                if (!is_array($urgency_ids)) {
                    $urgency_ids = [$urgency_ids];
                }

                // The configured ids already come back as an array: wrapping them in another
                // one made this test compare a value against a nested array, so it was
                // structurally false and the branch never ran. Compare on a common type as
                // well, the posted urgency being a string and the ids integers.
                if (in_array((int) $item->input['urgency'], array_map('intval', $urgency_ids), true)) {
                    if (self::checkMandatory($item->input, false, (int) $item->fields['id'])) {
                        if ($urgency_ticket_data = self::getUrgencyTicketFromDB($item->fields['id'])) {
                            // UPDATE
                            $urgency_ticket->update(['id'            => $urgency_ticket_data['id'],
                                'justification' => $item->input['justification']]);
                        } else {
                            // ADD
                            // Then we add tickets informations
                            if ($urgency_ticket->add(
                                ['justification' => (isset($item->input['justification'])) ? $item->input['justification'] : "",
                                    'tickets_id'    => $item->fields['id']],
                            )
                            ) {
                                SessionDraft::forget(SessionDraft::URGENCY);
                            }
                        }
                    } else {
                        unset($item->input['urgency']);
                    }
                }
            }
        }
    }

    /**
     * @param $item
     */
    public static function postUpdateUrgencyTicket($item)
    {
        $config = new Config();

        if ($config->useUrgency()) {
            $urgency_ticket = new self();
            // Then we add tickets informations
            if (isset($item->fields['id'])) {
                if (isset($item->oldvalues['urgency']) && (isset($item->input['urgency']))
                    && $item->input['urgency'] != $item->oldvalues['urgency']
                ) {
                    $urgency_ticket_data = self::getUrgencyTicketFromDB($item->fields['id']);

                    $urgency_ids = $config->getUrgency_ids();

                    if (isset($urgency_ticket_data['id'])
                        && !in_array($item->input['urgency'], $urgency_ids)) {
                        $urgency_ticket->update(['id'            => $urgency_ticket_data['id'],
                            'justification' => ""]);
                    }

                    SessionDraft::forget(SessionDraft::URGENCY);
                }
            }
        }
    }

    /**
     * Hook done on before add ticket - checkMandatory
     *
     * @param $item
     *
     * @return bool
     */
    public static function preAddUrgencyTicket($item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if ($config->useUrgency()) {
            $urgency_ids = $config->getUrgency_ids();
            if (!is_array($urgency_ids)) {
                $urgency_ids = [$urgency_ids];
            }
            // Then we add tickets informations
            if (isset($item->input['urgency']) && in_array($item->input['urgency'], $urgency_ids)) {
                if (!self::checkMandatory($item->input, true)) {
                    $_SESSION['saveInput'][$item->getType()] = $item->input;
                    $item->input                             = [];
                }
            }
        }
        return true;
    }

    /**
     * Hook done on after add ticket - add urgencytickets
     *
     * @param $item
     *
     * @return bool
     */
    public static function postAddUrgencyTicket($item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();
        if ($config->useUrgency()) {
            $urgency_ticket = new self();
            $urgency_ids    = $config->getUrgency_ids();
            if (!is_array($urgency_ids)) {
                $urgency_ids = [$urgency_ids];
            }
            // Then we add tickets informations
            if (isset($item->input['urgency'])
                && in_array($item->input['urgency'], $urgency_ids)) {
                if (self::checkMandatory($item->input, false, (int) $item->fields['id'])) {
                    // Then we add tickets informations
                    if ($urgency_ticket->add(['justification' => $item->input['justification'],
                        'tickets_id'    => $item->fields['id']])
                    ) {
                        SessionDraft::forget(SessionDraft::URGENCY);
                    }
                } else {
                    $item->input['id']                       = $item->fields['id'];
                    $_SESSION['saveInput'][$item->getType()] = $item->input;
                    unset($item->input['urgency']);
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

        $dbu = new DbUtils();
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
}
