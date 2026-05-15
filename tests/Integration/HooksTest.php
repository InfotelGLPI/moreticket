<?php

namespace GlpiPlugin\Moreticket\Tests\Integration;

use CommonITILActor;
use CommonITILObject;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Moreticket\Config;
use GlpiPlugin\Moreticket\Solution as MtSolution;
use GlpiPlugin\Moreticket\Ticket as MtTicket;
use GlpiPlugin\Moreticket\TicketFollowup as MtTicketFollowup;
use GlpiPlugin\Moreticket\TicketTask as MtTicketTask;
use GlpiPlugin\Moreticket\WaitingTicket;

/**
 * Vérifie que les callbacks des hooks ITEM_ADD, PRE_ITEM_ADD, ITEM_UPDATE
 * et POST_PREPAREADD se comportent correctement.
 *
 * Chaque test s'exécute dans une transaction annulée par DbTestCase::tearDown,
 * donc aucun nettoyage manuel n'est nécessaire.
 */
class HooksTest extends DbTestCase
{
    // ------------------------------------------------------------------ helpers

    private function setConfig(array $fields): void
    {
        global $DB;
        $DB->update('glpi_plugin_moreticket_configs', $fields, ['id' => 1]);

        // Invalider le singleton Config::$_instance
        $ref = new \ReflectionProperty(Config::class, '_instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }

    private function createTicket(): \Ticket
    {
        $ticket = new \Ticket();
        $id = $ticket->add([
            'name'        => 'Test ticket hooks',
            'content'     => 'Test content',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $id);
        $ticket->getFromDB($id);
        return $ticket;
    }

    // ============================================================
    // PRE_ITEM_ADD — Ticket::beforeAdd
    // ============================================================

    public function testPreItemAddTicketReturnsFalseWhenInputIsEmpty(): void
    {
        $ticket        = new \Ticket();
        $ticket->input = [];

        $this->assertFalse(MtTicket::beforeAdd($ticket));
    }

    public function testPreItemAddTicketReturnsFalseWhenInputIsNull(): void
    {
        $ticket        = new \Ticket();
        $ticket->input = null;

        $this->assertFalse(MtTicket::beforeAdd($ticket));
    }

    // PRE_ITEM_ADD — Solution::beforeAdd

    public function testPreItemAddSolutionReturnsFalseWhenInputIsEmpty(): void
    {
        $solution        = new \ITILSolution();
        $solution->input = [];

        $this->assertFalse(MtSolution::beforeAdd($solution));
    }

    // ============================================================
    // POST_PREPAREADD — TicketTask::beforeAdd
    // ============================================================

    public function testPostPrepareAddTicketTaskReturnsFalseWhenInputIsEmpty(): void
    {
        $task        = new \TicketTask();
        $task->input = [];

        $this->assertFalse(MtTicketTask::beforeAdd($task));
    }

    public function testPostPrepareAddTicketTaskCreatesWaitingTicketWhenPendingAndWaitingEnabled(): void
    {
        $this->login();
        $this->setConfig([
            'use_waiting'             => 1,
            'waitingreason_mandatory' => 0,
            'date_report_mandatory'   => 0,
        ]);

        $ticket = $this->createTicket();

        $task        = new \TicketTask();
        $task->input = [
            'tickets_id' => $ticket->getID(),
            'pending'    => 1,
            'content'    => 'Task content',
            '_job'       => $ticket,
        ];

        $this->assertFalse(
            WaitingTicket::getWaitingTicketFromDB($ticket->getID()),
            'Aucun WaitingTicket ne doit exister avant le hook.'
        );

        MtTicketTask::beforeAdd($task);

        $result = WaitingTicket::getWaitingTicketFromDB($ticket->getID());
        $this->assertIsArray($result);
        $this->assertSame($ticket->getID(), (int) $result['tickets_id']);
    }

    public function testPostPrepareAddTicketTaskSkipsWhenWaitingIsDisabled(): void
    {
        $this->login();
        $this->setConfig(['use_waiting' => 0]);

        $ticket = $this->createTicket();

        $task        = new \TicketTask();
        $task->input = [
            'tickets_id' => $ticket->getID(),
            'pending'    => 1,
            'content'    => 'Task content',
            '_job'       => $ticket,
        ];

        MtTicketTask::beforeAdd($task);

        $this->assertFalse(WaitingTicket::getWaitingTicketFromDB($ticket->getID()));
    }

    public function testPostPrepareAddTicketTaskSkipsWhenPendingIsFalse(): void
    {
        $this->login();
        $this->setConfig(['use_waiting' => 1]);

        $ticket = $this->createTicket();

        $task        = new \TicketTask();
        $task->input = [
            'tickets_id' => $ticket->getID(),
            'pending'    => 0,
            'content'    => 'Task content',
        ];

        MtTicketTask::beforeAdd($task);

        $this->assertFalse(WaitingTicket::getWaitingTicketFromDB($ticket->getID()));
    }

    // ============================================================
    // POST_PREPAREADD — TicketFollowup::beforeAdd
    // ============================================================

    public function testPostPrepareAddFollowupReturnsFalseWhenInputIsEmpty(): void
    {
        $followup        = new \ITILFollowup();
        $followup->input = [];

        $this->assertFalse(MtTicketFollowup::beforeAdd($followup));
    }

    public function testPostPrepareAddFollowupCreatesWaitingTicketWhenPendingAndWaitingEnabled(): void
    {
        $this->login();
        $this->setConfig([
            'use_waiting'             => 1,
            'waitingreason_mandatory' => 0,
            'date_report_mandatory'   => 0,
        ]);

        $ticket = $this->createTicket();

        $followup        = new \ITILFollowup();
        $followup->input = [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'pending'  => 1,
            'content'  => 'Followup content',
            '_job'     => $ticket,
        ];

        $this->assertFalse(
            WaitingTicket::getWaitingTicketFromDB($ticket->getID()),
            'Aucun WaitingTicket ne doit exister avant le hook.'
        );

        MtTicketFollowup::beforeAdd($followup);

        $result = WaitingTicket::getWaitingTicketFromDB($ticket->getID());
        $this->assertIsArray($result);
        $this->assertSame($ticket->getID(), (int) $result['tickets_id']);
    }

    // ============================================================
    // ITEM_ADD — Ticket::afterAdd
    // ============================================================

    public function testItemAddTicketReturnsFalseWhenInputIsEmpty(): void
    {
        $ticket        = new \Ticket();
        $ticket->input = [];

        $this->assertFalse(MtTicket::afterAdd($ticket));
    }

    public function testItemAddTicketClearsCloseSessionData(): void
    {
        $this->login();
        $this->setConfig(['use_waiting' => 0, 'use_solution' => 0]);

        $ticket        = $this->createTicket();
        $ticket->input = ['id' => $ticket->getID(), 'name' => 'Test'];

        $_SESSION['glpi_plugin_moreticket_close'] = ['solutiontypes_id' => 1];

        MtTicket::afterAdd($ticket);

        $this->assertArrayNotHasKey('glpi_plugin_moreticket_close', $_SESSION);
    }

    // ITEM_ADD — TicketTask::afterAddTask

    public function testItemAddTaskSetsTicketToWaitingWhenTechAddsTask(): void
    {
        $this->login();
        $this->setConfig(['update_after_tech_add_task' => 1]);

        $ticket = $this->createTicket();
        $userId = (int) \Session::getLoginUserID();

        $ticketUser = new \Ticket_User();
        $ticketUser->add([
            'tickets_id' => $ticket->getID(),
            'users_id'   => $userId,
            'type'       => CommonITILActor::ASSIGN,
        ]);

        // Recharger le ticket après l'assignation (GLPI peut changer son statut)
        $ticket->getFromDB($ticket->getID());

        $this->assertContains(
            (int) $ticket->fields['status'],
            \Ticket::getProcessStatusArray(),
            'Le ticket doit être dans un statut "en cours" pour que le hook soit actif.'
        );

        $task         = new \TicketTask();
        $task->fields = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $userId,
        ];

        MtTicketTask::afterAddTask($task);

        $ticket->getFromDB($ticket->getID());
        $this->assertSame(\Ticket::WAITING, (int) $ticket->fields['status']);
    }

    // ============================================================
    // ITEM_UPDATE — Ticket::beforeUpdate
    // ============================================================

    public function testPreItemUpdateTicketReturnsFalseWhenInputIsEmpty(): void
    {
        $ticket        = new \Ticket();
        $ticket->input = [];

        $this->assertFalse(MtTicket::beforeUpdate($ticket));
    }

    public function testPreItemUpdateTicketReturnsFalseWhenInputIsNull(): void
    {
        $ticket        = new \Ticket();
        $ticket->input = null;

        $this->assertFalse(MtTicket::beforeUpdate($ticket));
    }

    // ITEM_UPDATE — Ticket::afterUpdate

    public function testItemUpdateTicketClearsCloseSessionData(): void
    {
        $this->login();
        $this->setConfig(['use_waiting' => 0, 'use_solution' => 0]);

        $ticket        = $this->createTicket();
        $ticket->input = ['id' => $ticket->getID(), 'name' => 'Updated'];

        $_SESSION['glpi_plugin_moreticket_close'] = ['solutiontypes_id' => 1];

        MtTicket::afterUpdate($ticket);

        $this->assertArrayNotHasKey('glpi_plugin_moreticket_close', $_SESSION);
    }

    public function testItemUpdateTicketClearsWaitingSessionData(): void
    {
        $this->login();
        $this->setConfig(['use_waiting' => 0]);

        $ticket        = $this->createTicket();
        $ticket->input = ['id' => $ticket->getID(), 'name' => 'Updated'];

        $_SESSION['glpi_plugin_moreticket_waiting'] = ['reason' => 'test reason'];

        MtTicket::afterUpdate($ticket);

        $this->assertArrayNotHasKey('glpi_plugin_moreticket_waiting', $_SESSION);
    }
}
