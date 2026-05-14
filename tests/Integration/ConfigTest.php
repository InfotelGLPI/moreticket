<?php

namespace GlpiPlugin\Moreticket\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Moreticket\Config;

class ConfigTest extends DbTestCase
{
    public function testGetInstanceReturnsSingleton(): void
    {
        $a = Config::getInstance();
        $b = Config::getInstance();

        $this->assertSame($a, $b);
    }

    public function testConfigRecordExistsAfterPluginInstall(): void
    {
        $config = new Config();

        $this->assertTrue($config->getFromDB(1));
        $this->assertSame(1, (int) $config->getID());
    }

    public function testGetSolutionStatusReturnsEmptyArrayOnEmptyInput(): void
    {
        $config = new Config();

        $this->assertSame([], $config->getSolutionStatus(''));
        $this->assertSame([], $config->getSolutionStatus(null));
    }

    public function testGetSolutionStatusParsesJsonCorrectly(): void
    {
        $config = new Config();

        $json = json_encode([4 => 1, 5 => 1]);
        $result = $config->getSolutionStatus($json);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(4, $result);
        $this->assertArrayHasKey(5, $result);
    }

    public function testPrepareInputForUpdateOnlyAllowsPermittedFields(): void
    {
        $config = new Config();

        $input = [
            'id'                     => 1,
            'use_waiting'            => 1,
            'unexpected_field'       => 'should_be_removed',
            'another_unknown_field'  => 'removed_too',
            'use_solution'           => 0,
        ];

        $filtered = $config->prepareInputForUpdate($input);

        $this->assertArrayHasKey('id', $filtered);
        $this->assertArrayHasKey('use_waiting', $filtered);
        $this->assertArrayHasKey('use_solution', $filtered);
        $this->assertArrayNotHasKey('unexpected_field', $filtered);
        $this->assertArrayNotHasKey('another_unknown_field', $filtered);
    }

    public function testUseDurationSolutionReturnsFalseWhenFieldAbsent(): void
    {
        $config = new Config();
        $config->fields = [];

        $this->assertFalse($config->useDurationSolution());
    }

    public function testUseDurationSolutionReturnsFieldValueWhenPresent(): void
    {
        $config = new Config();
        $config->fields['use_duration_solution'] = 1;

        $this->assertSame(1, $config->useDurationSolution());
    }

    public function testGetValuesUrgencyReturnsArrayWithUrgencyMask(): void
    {
        global $CFG_GLPI;

        $CFG_GLPI['urgency_mask'] = (1 << 5) | (1 << 4) | (1 << 3) | (1 << 2) | (1 << 1);

        $values = Config::getValuesUrgency();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    public function testGetValuesUrgencyAlwaysContainsUrgency3(): void
    {
        global $CFG_GLPI;

        $CFG_GLPI['urgency_mask'] = 0;

        $values = Config::getValuesUrgency();

        // L'urgence 3 (Medium) est toujours présente quoi que soit le masque
        $this->assertArrayHasKey(3, $values);
    }
}
