<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;

final class TicketController
{
    public function index(Request $request): void
    {
        $status = in_array($request->get('status'), ['open', 'answered', 'closed'], true) ? (string) $request->get('status') : '';
        $where = $status !== '' ? 'WHERE t.status = :st' : '';
        $params = $status !== '' ? [':st' => $status] : [];

        $tickets = Db::all(
            "SELECT t.*, r.username, r.display_name,
                    (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id=t.id) AS msgs
             FROM tickets t JOIN resellers r ON r.id=t.reseller_id
             {$where} ORDER BY (t.status='open') DESC, t.updated_at DESC LIMIT 300",
            $params
        );
        View::render('owner/tickets/index', [
            'title' => 'تیکت‌ها',
            'tickets' => $tickets,
            'status' => $status,
            'openCount' => self::openCount(),
        ]);
    }

    public function show(Request $request, array $args): void
    {
        $ticket = $this->find((int) $args['id']);
        $messages = Db::all('SELECT * FROM ticket_messages WHERE ticket_id=:id ORDER BY id', [':id' => $ticket['id']]);
        $reseller = Db::one('SELECT id, username, display_name FROM resellers WHERE id=:id', [':id' => $ticket['reseller_id']]);
        View::render('owner/tickets/show', ['title' => 'تیکت #' . $ticket['id'], 'ticket' => $ticket, 'messages' => $messages, 'reseller' => $reseller]);
    }

    public function reply(Request $request, array $args): void
    {
        $ticket = $this->find((int) $args['id']);
        $body = trim((string) $request->post('body'));
        if ($body === '') {
            flash('error', 'متن پاسخ خالی است.');
            Response::redirect('/owner/tickets/' . $ticket['id']);
        }
        Db::exec(
            'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body, created_at)
             VALUES (:t, "owner", :sid, :b, UTC_TIMESTAMP())',
            [':t' => $ticket['id'], ':sid' => Auth::id(), ':b' => $body]
        );
        Db::exec('UPDATE tickets SET status="answered", updated_at=UTC_TIMESTAMP() WHERE id=:id', [':id' => $ticket['id']]);
        AuditLogger::log('ticket.reply', 'ticket', (int) $ticket['id']);
        flash('success', 'پاسخ ارسال شد.');
        Response::redirect('/owner/tickets/' . $ticket['id']);
    }

    public function close(Request $request, array $args): void
    {
        $ticket = $this->find((int) $args['id']);
        Db::exec('UPDATE tickets SET status="closed", updated_at=UTC_TIMESTAMP() WHERE id=:id', [':id' => $ticket['id']]);
        AuditLogger::log('ticket.close', 'ticket', (int) $ticket['id']);
        flash('success', 'تیکت بسته شد.');
        Response::redirect('/owner/tickets/' . $ticket['id']);
    }

    public static function openCount(): int
    {
        return (int) Db::scalar("SELECT COUNT(*) FROM tickets WHERE status='open'");
    }

    private function find(int $id): array
    {
        $t = Db::one('SELECT * FROM tickets WHERE id=:id', [':id' => $id]);
        if (!$t) {
            Response::abort(404, 'تیکت یافت نشد');
        }
        return $t;
    }
}
