<?php

namespace GlpiPlugin\Moreticket\Tests\Integration;

use CommonGLPI;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Moreticket\Profile;
use Profile as GlpiProfile;

class ProfileTest extends DbTestCase
{
    public function testGetTabNameForItemReturnsEmptyForNonProfile(): void
    {
        $item = new CommonGLPI();
        $profile = new Profile();

        $result = $profile->getTabNameForItem($item);

        $this->assertSame('', $result);
    }

    public function testGetTabNameForItemReturnsNonEmptyForProfile(): void
    {
        $glpiProfile = new GlpiProfile();
        $profile = new Profile();

        $result = $profile->getTabNameForItem($glpiProfile);

        $this->assertNotEmpty($result);
    }

    public function testGetAllRightsCentralReturnsThreeRights(): void
    {
        $rights = Profile::getAllRights('central');

        $this->assertIsArray($rights);
        $this->assertCount(3, $rights);
    }

    public function testGetAllRightsHelpdeskReturnsTwoRights(): void
    {
        $rights = Profile::getAllRights('helpdesk');

        $this->assertIsArray($rights);
        $this->assertCount(2, $rights);
    }

    public function testGetAllRightsCentralContainsExpectedFields(): void
    {
        $rights = Profile::getAllRights('central');

        $fields = array_column($rights, 'field');

        $this->assertContains('plugin_moreticket', $fields);
        $this->assertContains('plugin_moreticket_justification', $fields);
        $this->assertContains('plugin_moreticket_hide_task_duration', $fields);
    }

    public function testGetAllRightsHelpdeskDoesNotContainMainRight(): void
    {
        $rights = Profile::getAllRights('helpdesk');

        $fields = array_column($rights, 'field');

        $this->assertNotContains('plugin_moreticket', $fields);
        $this->assertContains('plugin_moreticket_justification', $fields);
        $this->assertContains('plugin_moreticket_hide_task_duration', $fields);
    }

    public function testCreateFirstAccessInsertsProfileRights(): void
    {
        global $DB;

        $this->login();

        $profileId = getItemByTypeName('Profile', 'Self-Service', true);
        $this->assertGreaterThan(0, $profileId);

        // Supprimer les droits existants pour ce profil
        $DB->delete('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => ['LIKE', '%plugin_moreticket%'],
        ]);

        Profile::createFirstAccess($profileId);

        $count = countElementsInTable('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => ['LIKE', '%plugin_moreticket%'],
        ]);

        $this->assertGreaterThanOrEqual(3, $count);
    }

    public function testAddDefaultProfileInfosInsertsRightsForProfile(): void
    {
        global $DB;

        $this->login();

        $profileId = getItemByTypeName('Profile', 'Technician', true);
        $this->assertGreaterThan(0, $profileId);

        // Nettoyer
        $DB->delete('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => 'plugin_moreticket',
        ]);

        Profile::addDefaultProfileInfos($profileId, ['plugin_moreticket' => READ]);

        $count = countElementsInTable('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => 'plugin_moreticket',
        ]);

        $this->assertSame(1, $count);
    }

    public function testAddDefaultProfileInfosDoesNotDuplicateRights(): void
    {
        global $DB;

        $this->login();

        $profileId = getItemByTypeName('Profile', 'Technician', true);
        $this->assertGreaterThan(0, $profileId);

        $DB->delete('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => 'plugin_moreticket_justification',
        ]);

        // Premier appel
        Profile::addDefaultProfileInfos($profileId, ['plugin_moreticket_justification' => READ]);
        // Deuxième appel (ne doit pas dupliquer)
        Profile::addDefaultProfileInfos($profileId, ['plugin_moreticket_justification' => READ]);

        $count = countElementsInTable('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => 'plugin_moreticket_justification',
        ]);

        $this->assertSame(1, $count);
    }
}
