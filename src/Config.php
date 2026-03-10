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

namespace GlpiPlugin\Moreticket;

use CommonDBTM;
use CommonITILObject;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Config
 */
class Config extends CommonDBTM
{

    static $rightname = "plugin_moreticket";
    private static $_instance = null;
   /**
    * @param bool $update
    *
    * @return null|Config
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
    * Config constructor.
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
    * @return
    */
    static function getTypeName($nb = 0)
    {
        return __("Setup");
    }


    /**
     * Singleton for the unique config record
     */
    static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            if (!self::$_instance->getFromDB(1)) {
                self::$_instance->getEmpty();
            }
        }
        return self::$_instance;
    }
    public function showConfigForm()
    {
        $this->getFromDB(1);
        $config = self::getInstance();
        $dbu = new DbUtils();
        $all_statuses = \Ticket::getAllStatusArray();
        $filtered_statuses = [];

        foreach ([\Ticket::CLOSED, \Ticket::SOLVED] as $status_id) {
            if (isset($all_statuses[$status_id])) {
                $filtered_statuses[$status_id] = $all_statuses[$status_id];
            }
        }
        $checked_statuses = $this->getSolutionStatus($this->fields["solution_status"]);

        $data = [
            'fields' => $this->fields,
            'useWaiting' => $this->useWaiting(),
            'useSolution' => $this->useSolution(),
            'useDurationSolution' => $this->useDurationSolution(),
            'useUrgency' => $this->useUrgency(),
            'urgency_ids' => self::getValuesUrgency(),
            'solution_status_checked'=> $checked_statuses,
            'all_solution_statuses'  => $filtered_statuses,
            'form_url' => $this->getFormURL(),
            'urgency_selected' => $dbu->importArrayFromDB($this->fields["urgency_ids"]),
            'id'                => 1,
            'item'              => $config,
            'config'            => $config->fields,
            'action'            => plugin_moreticket_geturl() . 'front/config.form.php',
        ];

        TemplateRenderer::getInstance()->display('@moreticket/config.html.twig', $data);

        return true;
    }

   /**
    * @param $input
    *
    * @return array|mixed
    */
    public function getSolutionStatus($input)
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
    public function useWaiting()
    {
        return $this->fields['use_waiting'];
    }

   /**
    * @return mixed
    */
    public function mandatoryReportDate()
    {
        return $this->fields['date_report_mandatory'];
    }

   /**
    * @return mixed
    */
    public function mandatoryWaitingType()
    {
        return $this->fields['waitingtype_mandatory'];
    }

   /**
    * @return mixed
    */
    public function mandatoryWaitingReason()
    {
        return $this->fields['waitingreason_mandatory'];
    }

   /**
    * @return mixed
    */
    public function useSolution()
    {
        return $this->fields['use_solution'];
    }

   /**
    * @return mixed
    */
    public function mandatorySolutionType()
    {
        return $this->fields['solutiontype_mandatory'];
    }

   /**
    * @return mixed
    */
    public function solutionStatus()
    {
        return $this->fields["solution_status"];
    }

   /**
    * @return mixed
    */
    public function closeInformations()
    {
        return $this->fields["close_informations"];
    }

   /**
    * @return mixed
    */
    public function closeFollowup()
    {
        return $this->fields["close_followup"];
    }

   /**
    * @return mixed
    */
    public function useUrgency()
    {
        return $this->fields['urgency_justification'];
    }

   /**
    * @return array
    */
    public function getUrgency_ids()
    {
        $dbu = new DbUtils();
        return $dbu->importArrayFromDB($this->fields['urgency_ids']);
    }

   /**
    * @return mixed
    */
    public function useDurationSolution()
    {
        if (isset($this->fields['use_duration_solution'])) {
            return $this->fields['use_duration_solution'];
        }
        return false;
    }

   /**
    * @return mixed
    */
    public function isMandatorysolution()
    {
        return $this->fields['is_mandatory_solution'];
    }

    public function useQuestion()
    {
        return $this->fields['use_question'];
    }

   /**
    * @return array
    */
    public static function getValuesUrgency()
    {
        global $CFG_GLPI;

        $URGENCY_MASK_FIELD = 'urgency_mask';
        $values             = [];

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

    public function addFollowupStopWaiting()
    {
        return $this->fields['add_followup_stop_waiting'];
    }
}
