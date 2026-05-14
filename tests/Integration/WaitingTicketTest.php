<?php

namespace GlpiPlugin\Moreticket\Tests\Integration;

use CommonITILObject;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Moreticket\Config;
use GlpiPlugin\Moreticket\WaitingTicket;

class WaitingTicketTest extends DbTestCase
{
    public function testGetTypesAllReturnsTicketType(): void
    {
        $types = WaitingTicket::getTypes(true);

        $this->assertIsArray($types);
        $this->assertContains('Ticket', $types);
    }

    public function testCronInfoReturnsDescriptionForKnownName(): void
    {
        $info = WaitingTicket::cronInfo('MoreticketWaitingTicket');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('description', $info);
        $this->assertNotEmpty($info['description']);
    }

    public function testCronInfoReturnsEmptyArrayForUnknownName(): void
    {
        $info = WaitingTicket::cronInfo('nonexistent');

        $this->assertIsArray($info);
        $this->assertEmpty($info);
    }

    public function testCheckMandatoryReturnsTrueWhenNoFieldsAreMandatory(): void
    {
        $this->login();

        // S'assurer que la config ne rend rien obligatoire
        $config = new Config();
        $config->fields['date_report_mandatory']   = 0;
        $config->fields['waitingreason_mandatory'] = 0;

        // Patch temporaire : forcer Config::getConfig() à retourner notre instance
        // On passe des valeurs vides qui ne doivent pas déclencher d'erreur
        $values = ['reason' => 'test', 'date_report' => date('Y-m-d H:i:s', strtotime('+1 day'))];

        // Sans champ obligatoire, checkMandatory doit retourner true
        // On crée une config "vide" de contraintes en remplaçant les champs
        $result = $this->invokeCheckMandatoryWithConfig($values, false, false);

        $this->assertTrue($result);
    }

    public function testCheckMandatoryReturnsFalseWhenReasonMandatoryAndMissing(): void
    {
        $this->login();

        $result = $this->invokeCheckMandatoryWithConfig([], true, false);

        $this->assertFalse($result);
    }

    public function testCheckMandatoryReturnsFalseWhenDateMandatoryAndMissing(): void
    {
        $this->login();

        $result = $this->invokeCheckMandatoryWithConfig(['reason' => 'test'], false, true);

        $this->assertFalse($result);
    }

    public function testCheckMandatoryReturnsFalseWhenDateReportIsInPast(): void
    {
        $this->login();

        $values = [
            'reason'      => 'test reason',
            'date_report' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ];

        $result = $this->invokeCheckMandatoryWithConfig($values, false, true);

        $this->assertFalse($result);
    }

    public function testGetWaitingTicketFromDBReturnsFalseWhenNoRecord(): void
    {
        $result = WaitingTicket::getWaitingTicketFromDB(999999);

        $this->assertFalse($result);
    }

    public function testGetWaitingTicketFromDBReturnsDataWhenRecordExists(): void
    {
        global $DB;

        $this->login();

        // Créer un ticket de test
        $ticket = new \Ticket();
        $ticketId = $ticket->add([
            'name'       => 'Test waiting ticket',
            'content'    => 'Test content',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $ticketId);

        // Insérer un enregistrement de mise en attente directement
        $DB->insert('glpi_plugin_moreticket_waitingtickets', [
            'tickets_id'                        => $ticketId,
            'reason'                            => 'Waiting reason',
            'date_suspension'                   => date('Y-m-d H:i:s'),
            'date_end_suspension'               => null,
            'date_report'                       => date('Y-m-d H:i:s', strtotime('+1 week')),
            'status'                            => CommonITILObject::ASSIGNED,
            'plugin_moreticket_waitingtypes_id' => 0,
        ]);

        $result = WaitingTicket::getWaitingTicketFromDB($ticketId);

        $this->assertIsArray($result);
        $this->assertSame($ticketId, (int) $result['tickets_id']);
        $this->assertSame('Waiting reason', $result['reason']);
    }

    /**
     * Invoque WaitingTicket::checkMandatory avec une config contrôlée.
     * Insère et supprime la ligne de config pour maîtriser les contraintes.
     */
    private function invokeCheckMandatoryWithConfig(
        array $values,
        bool $reasonMandatory,
        bool $dateMandatory
    ): bool {
        global $DB;

        // Sauvegarder et remplacer la config
        $current = $DB->request(['FROM' => 'glpi_plugin_moreticket_configs', 'LIMIT' => 1]);
        $original = null;
        foreach ($current as $row) {
            $original = $row;
        }

        if ($original !== null) {
            $DB->update('glpi_plugin_moreticket_configs', [
                'waitingreason_mandatory' => (int) $reasonMandatory,
                'date_report_mandatory'   => (int) $dateMandatory,
            ], ['id' => $original['id']]);
        }

        // Vider le cache du singleton Config
        $ref = new \ReflectionProperty(Config::class, '_instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $result = WaitingTicket::checkMandatory($values);

        // Restaurer la config originale
        if ($original !== null) {
            $DB->update('glpi_plugin_moreticket_configs', [
                'waitingreason_mandatory' => $original['waitingreason_mandatory'],
                'date_report_mandatory'   => $original['date_report_mandatory'],
            ], ['id' => $original['id']]);
        }

        // Réinitialiser le singleton
        $ref->setValue(null, null);

        return $result;
    }
}
