<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketDemoSeeder extends Seeder
{
    private const MARKER = '[TicketDemoSeeder]';

    public function run(): void
    {
        SupportTicket::where('description', 'like', '%'.self::MARKER.'%')->delete();

        $b2c = User::where('email', 'customer@amazepays.in')->first();
        $b2b = User::where('email', 'b2bclient@amazepays.in')->first();
        $admin = User::where('email', 'admin@amazepays.in')->first();

        if (! $b2c) {
            $this->command->warn('TicketDemoSeeder: B2C user customer@amazepays.in not found. Run UserSeeder first.');

            return;
        }

        $rows = [
            [
                'user_id' => $b2c->id,
                'subject' => 'Voucher code not received',
                'description' => 'I completed payment but have not received the voucher email after 2 hours. Order reference in profile.'.self::MARKER,
                'category' => 'voucher_not_received',
                'priority' => 'urgent',
                'status' => 'open',
                'assigned_to' => null,
            ],
            [
                'user_id' => $b2c->id,
                'subject' => 'UPI payment shows pending',
                'description' => 'My UPI payment is still showing as pending in the app.'.self::MARKER,
                'category' => 'payment_issue',
                'priority' => 'medium',
                'status' => 'in_progress',
                'assigned_to' => $admin?->id,
            ],
            [
                'user_id' => $b2c->id,
                'subject' => 'Wrong amount charged',
                'description' => 'I was charged ₹50 more than the checkout total.'.self::MARKER,
                'category' => 'order_issue',
                'priority' => 'low',
                'status' => 'resolved',
                'assigned_to' => $admin?->id,
                'resolved_by' => $admin?->id,
                'resolved_at' => now()->subDay(),
                'resolution_note' => 'Refund of ₹50 credited to wallet. [demo]',
            ],
        ];

        if ($b2b) {
            $rows[] = [
                'user_id' => $b2b->id,
                'subject' => 'Bulk order invoice',
                'description' => 'Need GST invoice for last week bulk purchase.'.self::MARKER,
                'category' => 'other',
                'priority' => 'high',
                'status' => 'open',
                'assigned_to' => null,
            ];
            $rows[] = [
                'user_id' => $b2b->id,
                'subject' => 'Refund status',
                'description' => 'Ticket closed after finance approved refund.'.self::MARKER,
                'category' => 'refund',
                'priority' => 'medium',
                'status' => 'closed',
                'assigned_to' => $admin?->id,
            ];
        }

        $rows[] = [
            'user_id' => $b2c->id,
            'subject' => 'General question about offers',
            'description' => 'Can I stack two promo codes on one order?'.self::MARKER,
            'category' => 'other',
            'priority' => 'low',
            'status' => 'open',
            'assigned_to' => null,
        ];

        foreach ($rows as $data) {
            $ticket = SupportTicket::create($data);
            $this->seedAudit('ticket.created', ['ticket_id' => $ticket->id, 'subject' => $ticket->subject]);
        }

        $inProgress = SupportTicket::where('description', 'like', '%'.self::MARKER.'%')
            ->where('status', 'in_progress')
            ->first();
        if ($inProgress && $admin) {
            TicketMessage::create([
                'ticket_id' => $inProgress->id,
                'user_id' => $admin->id,
                'message' => 'Hi — we are checking with the payment gateway. You should see an update within 24 hours.',
                'is_admin_reply' => true,
            ]);
            $this->seedAudit('ticket.replied', ['ticket_id' => $inProgress->id]);
        }

        $resolved = SupportTicket::where('description', 'like', '%'.self::MARKER.'%')
            ->where('status', 'resolved')
            ->first();
        if ($resolved && $admin) {
            TicketMessage::create([
                'ticket_id' => $resolved->id,
                'user_id' => $admin->id,
                'message' => 'We have processed a ₹50 adjustment to your wallet.',
                'is_admin_reply' => true,
            ]);
            TicketMessage::create([
                'ticket_id' => $resolved->id,
                'user_id' => $b2c->id,
                'message' => 'Thank you, I can see the credit.',
                'is_admin_reply' => false,
            ]);
            $this->seedAudit('ticket.replied', ['ticket_id' => $resolved->id]);
            $this->seedAudit('ticket.resolved', ['ticket_id' => $resolved->id]);
        }

        $b2bOpen = $b2b
            ? SupportTicket::where('description', 'like', '%'.self::MARKER.'%')
                ->where('user_id', $b2b->id)
                ->where('status', 'open')
                ->first()
            : null;
        if ($b2bOpen && $admin) {
            TicketMessage::create([
                'ticket_id' => $b2bOpen->id,
                'user_id' => $admin->id,
                'message' => 'Please share your company GSTIN and we will email the invoice within 1 business day.',
                'is_admin_reply' => true,
            ]);
            $this->seedAudit('ticket.replied', ['ticket_id' => $b2bOpen->id]);
        }

        $this->command->info('Seeded '.count($rows).' demo support tickets (B2C + B2B). See /panel/tickets.');
    }

    private function seedAudit(string $action, array $newValues): void
    {
        AuditLog::create([
            'tenant_id' => null,
            'user_id' => null,
            'action' => $action,
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TicketDemoSeeder',
            'url' => 'http://127.0.0.1:8000/panel/tickets',
        ]);
    }
}
