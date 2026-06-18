<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AlertService;
use App\Services\AuditLogger;

final class TicketController
{
    public function index(): void
    {
        $r = Auth::reseller();
        $tickets = Db::all(
            'SELECT t.*, (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id=t.id) AS msgs
             FROM tickets t WHERE t.reseller_id=:id ORDER BY t.updated_at DESC',
            [':id' => $r['id']]
        );
        View::render('reseller/tickets/index', ['title' => 'تیکت‌ها', 'tickets' => $tickets], 'reseller');
    }

    public function create(): void
    {
        View::render('reseller/tickets/create', ['title' => 'تیکت جدید'], 'reseller');
    }

    public function store(Request $request): void
    {
        $r = Auth::reseller();
        $subject = trim((string) $request->post('subject'));
        $body = trim((string) $request->post('body'));
        $priority = in_array($request->post('priority'), ['low', 'normal', 'high'], true) ? (string) $request->post('priority') : 'normal';

        if ($subject === '' || $body === '') {
            flash('error', 'موضوع و متن پیام الزامی است.');
            flash_old($request->all());
            Response::redirect('/panel/tickets/create');
        }

        $ticketId = Db::insert(
            'INSERT INTO tickets (reseller_id, subject, status, priority, created_at, updated_at)
             VALUES (:rid, :s, "open", :p, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [':rid' => $r['id'], ':s' => mb_substr($subject, 0, 200), ':p' => $priority]
        );
        $this->addMessage($ticketId, 'reseller', (int) $r['id'], $body);

        AlertService::raise('ticket', 'تیکت جدید از «' . ($r['display_name'] ?: $r['username']) . '»: ' . mb_substr($subject, 0, 80), 'info', 'ticket:' . $ticketId);
        AuditLogger::log('ticket.create', 'ticket', $ticketId, ['subject' => $subject]);
        clear_old();
        flash('success', 'تیکت ثبت شد.');
        Response::redirect('/panel/tickets/' . $ticketId);
    }

    public function show(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $ticket = $this->own((int) $args['id'], $r);
        $messages = Db::all('SELECT * FROM ticket_messages WHERE ticket_id=:id ORDER BY id', [':id' => $ticket['id']]);
        View::render('reseller/tickets/show', ['title' => 'تیکت #' . $ticket['id'], 'ticket' => $ticket, 'messages' => $messages], 'reseller');
    }

    public function reply(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $ticket = $this->own((int) $args['id'], $r);
        if ($ticket['status'] === 'closed') {
            flash('error', 'این تیکت بسته شده است.');
            Response::redirect('/panel/tickets/' . $ticket['id']);
        }
        $body = trim((string) $request->post('body'));
        if ($body === '') {
            flash('error', 'متن پاسخ خالی است.');
            Response::redirect('/panel/tickets/' . $ticket['id']);
        }
        $this->addMessage((int) $ticket['id'], 'reseller', (int) $r['id'], $body);
        Db::exec('UPDATE tickets SET status="open", updated_at=UTC_TIMESTAMP() WHERE id=:id', [':id' => $ticket['id']]);
        AlertService::raise('ticket', 'پاسخ جدید در تیکت #' . $ticket['id'] . ' از نماینده', 'info', 'ticket:' . $ticket['id']);
        flash('success', 'پاسخ ارسال شد.');
        Response::redirect('/panel/tickets/' . $ticket['id']);
    }

    private function addMessage(int $ticketId, string $type, int $sid, string $body): void
    {
        Db::exec(
            'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body, created_at)
             VALUES (:t, :st, :sid, :b, UTC_TIMESTAMP())',
            [':t' => $ticketId, ':st' => $type, ':sid' => $sid, ':b' => $body]
        );
    }

    private function own(int $id, array $r): array
    {
        $t = Db::one('SELECT * FROM tickets WHERE id=:id', [':id' => $id]);
        if (!$t || (int) $t['reseller_id'] !== (int) $r['id']) {
            Response::abort(404, 'تیکت یافت نشد');
        }
        return $t;
    }
}
