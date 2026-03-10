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

use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Profile
 */
class Profile extends \Profile
{
    public static $rightname = "profile";

    /**
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(__('More ticket', 'moreticket'));
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-clock-pause";
    }


    /**
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if (!$item instanceof \Profile || !self::canView()) {
            return false;
        }

        $profile = new \Profile();
        $profile->getFromDB($item->getID());

        $rights = self::getAllRights($profile->fields['interface']);

        $twig = TemplateRenderer::getInstance();
        $twig->display('@moreticket/profile.html.twig', [
            'id' => $item->getID(),
            'profile' => $profile,
            'title' => self::getTypeName(Session::getPluralNumber()),
            'rights' => $rights,
        ]);

        return true;
    }


    /**
     * @param $ID
     */
    public static function createFirstAccess($ID)
    {
        //85
        self::addDefaultProfileInfos(
            $ID,
            [
                'plugin_moreticket' => 127,
                'plugin_moreticket_justification' => 1,
                'plugin_moreticket_hide_task_duration' => 1,
            ],
            true
        );
    }

    /**
     * @param      $profiles_id
     * @param      $rights
     * @param bool $drop_existing
     *
     * @internal param $profile
     */
    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $dbu = new DbUtils();
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable(
                'glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right]
            ) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable(
                'glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right]
            )) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name'] = $right;
                $myright['rights'] = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }


    /**
     * @param bool $all
     *
     * @return array
     */
    public static function getAllRights($interface = 'central')
    {
        if ($interface == 'central') {
            $rights[] = [
                'itemtype' => Config::class,
                'label' => __('More ticket', 'moreticket'),
                'field' => 'plugin_moreticket',
                'rights' => \Profile::getRightsFor(Config::class),
            ];
            $rights[] = [
                'itemtype' => UrgencyTicket::class,
                'label' => __('Adding a justification of urgency', 'moreticket'),
                'field' => 'plugin_moreticket_justification',
                'rights' => [
                    READ => __('Read'),
                ],
            ];
            $rights[] = [
                'itemtype' => Ticket::class,
                'label' => __('Hide task duration in tickets', 'moreticket'),
                'field' => 'plugin_moreticket_hide_task_duration',
                'rights' => [
                    READ => __('Read'),
                ],
            ];

        } else {
            $rights[] = [
                'itemtype' => UrgencyTicket::class,
                'label' => __('Adding a justification of urgency', 'moreticket'),
                'field' => 'plugin_moreticket_justification',
                'rights' => [
                    READ => __('Read'),
                ],
            ];
            $rights[] = [
                'itemtype' => Ticket::class,
                'label' => __('Hide task duration in tickets', 'moreticket'),
                'field' => 'plugin_moreticket_hide_task_duration',
                'rights' => [
                    READ => __('Read'),
                ],
            ];
        }

        return $rights;
    }

    /**
     * Init profiles
     *
     * @param $old_right
     *
     * @return int
     */

    public static function translateARight($old_right)
    {
        switch ($old_right) {
            case '':
                return 0;
            case 'r':
                return READ;
            case 'w':
                return ALLSTANDARDRIGHT + READNOTE + UPDATENOTE;
            case '0':
            case '1':
                return $old_right;

            default:
                return 0;
        }
    }

    /**
     * @param $profiles_id the profile ID
     *
     * @return bool
     * @since 0.85
     * Migration rights from old system to the new one for one profile
     *
     */
    public static function migrateOneProfile($profiles_id)
    {
        global $DB;
        //Cannot launch migration if there's nothing to migrate...
        if (!$DB->tableExists('glpi_plugin_moreticket_profiles')) {
            return true;
        }

        $it = $DB->request([
            'FROM' => 'glpi_plugin_moreticket_profiles',
            'WHERE' => ['profiles_id' => $profiles_id],
        ]);
        foreach ($it as $profile_data) {
            // plugin_moreticket_justification
            $matching = [
                'moreticket' => 'plugin_moreticket',
                'justification' => 'plugin_moreticket_justification',
            ];
            $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
            foreach ($matching as $old => $new) {
                if (!isset($current_rights[$old])) {
                    $DB->update('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name' => $new,
                        'profiles_id' => $profiles_id,
                    ]);
                }
            }

            // plugin_moreticket_hide_task_duration
            $matching = [
                'moreticket' => 'plugin_moreticket',
                'justification' => 'plugin_moreticket_hide_task_duration',
            ];
            $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
            foreach ($matching as $old => $new) {
                if (!isset($current_rights[$old])) {
                    $DB->update('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name' => $new,
                        'profiles_id' => $profiles_id,
                    ]);
                }
            }
        }
    }

    /**
     * Initialize profiles, and migrate it necessary
     */
    public static function initProfile()
    {
        global $DB;
        $profile = new self();
        $dbu = new DbUtils();
        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights($_SESSION['glpiactiveprofile']['interface']) as $data) {
            if ($dbu->countElementsInTable(
                "glpi_profilerights",
                ["name" => $data['field']]
            ) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        //Migration old rights in new ones
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_profiles',
        ]);
        foreach ($it as $prof) {
            self::migrateOneProfile($prof['id']);
        }

        $it = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
                'name' => ['LIKE', '%plugin_moreticket%'],
            ],
        ]);
        foreach ($it as $prof) {
            if (isset($_SESSION['glpiactiveprofile'])) {
                $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
            }
        }
    }


    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights($_SESSION['glpiactiveprofile']['interface']) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }
}
