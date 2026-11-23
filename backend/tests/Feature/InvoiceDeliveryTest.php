<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\TestCase;

class InvoiceDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private ArrayTransport $outbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finance = User::factory()->create(['role' => 'FINANCE']);
        config(['mail.invoice_mailer' => 'smtp', 'mail.mailers.smtp.transport' => 'smtp']);
        $this->outbox = new ArrayTransport;
        Mail::mailer('smtp')->setSymfonyTransport($this->outbox);
    }

    private function orderFor(Customer $customer, bool $issueInvoice = true): SalesOrder
    {
        $number = 'ORD-SEKOLAH-'.$customer->id;
        $sku = 'LCD-'.$customer->id;
        Product::create(['sku' => $sku, 'name' => 'Monitor ruang komputer', 'category' => 'Elektronik', 'unit' => 'PCS', 'stock' => 5]);
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $base = '/api/orders/'.$number;
        $version = $this->putJson($base, [
            'version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'Pengadaan monitor sekolah',
                'notes' => 'Catatan internal negosiasi pemasok'],
            'items' => [['sku' => $sku, 'name' => 'Monitor ruang komputer', 'unit' => 'PCS', 'qty' => 2, 'price' => 2_000_000]],
        ])->assertOk()->json('data.version');
        $this->postJson($base.'/submit', ['version' => $version])->assertOk()->assertJsonPath('data.status', 'Siap Invoice');
        Sanctum::actingAs($this->finance);
        if ($issueInvoice) {
            $this->postJson($base.'/invoice', [
                'invoiceDate' => '2026-11-21', 'dueDate' => '2026-11-28', 'paymentType' => 'Penuh',
            ])->assertOk()->assertJsonPath('data.invoice.totalAmount', 4_440_000);
        }

        return SalesOrder::where('order_number', $number)->firstOrFail();
    }

    private function deliveryUrl(SalesOrder $order): string
    {
        return '/api/orders/'.$order->order_number.'/invoice/delivery';
    }

    private function emailPath(string $recipient): string
    {
        $message = $this->outbox->messages()->last()->getOriginalMessage();
        $this->assertCount(1, $message->getTo());
        $this->assertSame($recipient, $message->getTo()[0]->getAddress());
        $this->assertEmpty($message->getCc());
        $this->assertEmpty($message->getBcc());
        $body = $message->getTextBody().' '.$message->getHtmlBody();
        $this->assertSame(1, preg_match('~/invoice-access/[a-f0-9]{64}\b~', $body, $matches));

        return $matches[0];
    }

    private function failNextDelivery(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('send')->willThrowException(new TransportException('SMTP unavailable'));
        Mail::mailer('smtp')->setSymfonyTransport($transport);
    }

    private function duringNextDelivery(\Closure $work): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('send')->willReturnCallback(function ($message, $envelope) use ($work) {
            Mail::mailer('smtp')->setSymfonyTransport($this->outbox);
            $work();

            return $this->outbox->send($message, $envelope);
        });
        Mail::mailer('smtp')->setSymfonyTransport($transport);
    }

    public function test_mailtrap_sandbox_send_uses_registered_customer_and_failed_retry_preserves_link(): void
    {
        $order = $this->orderFor(Customer::factory()->create(['email' => 'school@example.test']));
        config(['mail.invoice_mailer' => 'mailtrap_sandbox', 'services.mailtrap_sandbox.token' => 'test-secret',
            'services.mailtrap_sandbox.inbox_id' => '123']);
        Http::preventStrayRequests();
        Http::fake(['https://sandbox.api.mailtrap.io/api/send/123' => Http::sequence()
            ->push(['success' => true, 'message_ids' => ['abc']])
            ->push([], 429)->push(['success' => false])->push([], 302)]);

        $endpoint = $this->deliveryUrl($order);
        $sent = $this->postJson($endpoint, ['recipient' => 'other@example.test'])
            ->assertOk()->assertJsonPath('data.latest.status', 'sent');
        [$request] = Http::recorded()->first();
        $this->assertTrue($request->hasHeader('Authorization', 'Bearer test-secret'));
        $this->assertSame([['email' => 'school@example.test']], $request['to']);
        $this->assertSame('Invoice '.$order->invoice['invoiceNumber'], $request['subject']);
        $this->assertSame(1, preg_match('~/invoice-access/[a-f0-9]{64}~', $request['text'], $matches));
        $sent->assertDontSee('test-secret')->assertDontSee(basename($matches[0]));
        foreach ([429, 200, 302] as $ignored) {
            $this->postJson($endpoint)->assertStatus(503)->assertJsonPath('data.latest.status', 'failed')
                ->assertJsonPath('data.active', true);
            $this->get($matches[0])->assertOk();
        }
        Http::assertSentCount(4);
        $this->assertCount(0, $this->outbox->messages());
        $this->assertSame($order->invoice, $order->fresh()->invoice);
    }

    #[TestWith(['', '9'])]
    #[TestWith(['test-secret', ''])]
    #[TestWith(['test-secret', '../other'])]
    public function test_invalid_sandbox_configuration_does_not_send(string $token, string $inbox): void
    {
        $order = $this->orderFor(Customer::factory()->create(['email' => 'school@example.test']));
        config(['mail.invoice_mailer' => 'mailtrap_sandbox', 'services.mailtrap_sandbox.token' => $token,
            'services.mailtrap_sandbox.inbox_id' => $inbox]);
        Http::fake();
        $this->postJson($this->deliveryUrl($order))->assertStatus(503)->assertJsonPath('data.active', false);
        Http::assertNothingSent();
        $this->assertCount(0, $this->outbox->messages());
    }

    #[TestWith(['qa@example.test', 'mailtrap_demo'])]
    #[TestWith(['other@example.test', 'mailtrap_sandbox'])]
    public function test_demo_mode_uses_the_saved_recipient_and_records_the_actual_channel(string $recipient, string $channel): void
    {
        config([
            'mail.invoice_mailer' => 'mailtrap_demo',
            'services.mailtrap_demo.recipient' => 'QA@example.test',
            'services.mailtrap_demo.token' => 'test-demo-token',
            'services.mailtrap_sandbox.token' => 'test-sandbox-token',
            'services.mailtrap_sandbox.inbox_id' => '123',
        ]);
        $order = $this->orderFor(Customer::factory()->create(['email' => $recipient]));
        $url = $this->deliveryUrl($order);
        $live = $channel === 'mailtrap_demo';
        $expected = $live ? 'https://send.api.mailtrap.io/api/send' : 'https://sandbox.api.mailtrap.io/api/send/123';
        Http::preventStrayRequests();
        Http::fake([$expected => Http::sequence()->push(['success' => true])->push([], 403)]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.channel', $channel);
        $this->postJson($url, ['channel' => 'mailtrap_demo', 'recipient' => 'qa@example.test'])
            ->assertOk()->assertJsonPath('data.latest.channel', $channel)->assertJsonPath('data.active', true);
        [$request] = Http::recorded()->first();
        $this->assertTrue($request->hasHeader('Authorization', 'Bearer '.($live ? 'test-demo-token' : 'test-sandbox-token')));
        $this->assertSame([['email' => $recipient]], $request['to']);
        $this->assertSame(($live ? '[Uji CAPS-26] ' : '').'Invoice '.$order->invoice['invoiceNumber'], $request['subject']);
        if ($live) {
            $this->assertSame('hello@demomailtrap.co', $request['from']['email']);
        }
        $this->assertSame(1, preg_match('~/invoice-access/[a-f0-9]{64}~', $request['text'], $matches));
        $this->postJson($url)->assertStatus(503)->assertJsonPath('data.active', true);
        $this->get($matches[0])->assertOk()->assertSee('4.440.000');
        Http::assertSentCount(2);
        config(['services.mailtrap_demo.recipient' => 'another@example.test']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.channel', 'mailtrap_sandbox')
            ->assertJsonPath('data.latest.channel', $channel);
        $this->deleteJson($url)->assertOk();
        $this->get($matches[0])->assertNotFound();
        $this->assertSame($order->invoice, $order->fresh()->invoice);
    }

    #[TestWith(['school@example.test'])]
    #[TestWith([null])]
    public function test_default_recipient_gets_one_automatic_notice_and_the_same_destination_on_resend(?string $customerEmail): void
    {
        config(['mail.invoice_mailer' => 'mailtrap_demo', 'services.mailtrap_demo.use_default' => true,
            'services.mailtrap_demo.recipient' => 'central@example.test', 'services.mailtrap_demo.token' => 'test-token']);
        Http::preventStrayRequests();
        Http::fake(['https://send.api.mailtrap.io/api/send' => Http::sequence()->push(['success' => true])->push(['success' => true])]);
        $customer = Customer::factory()->create(['email' => $customerEmail]);
        $order = $this->orderFor($customer, issueInvoice: false);
        $issue = '/api/orders/'.$order->order_number.'/invoice';
        $payload = ['invoiceDate' => '2026-11-21', 'dueDate' => '2026-11-28', 'recipient' => 'override@example.test'];
        $this->postJson($issue, $payload)->assertOk()->assertJsonPath('delivery.sent', true)
            ->assertJsonPath('delivery.recipient', 'central@example.test')->assertJsonPath('data.status', 'Menunggu Pembayaran');
        $this->postJson($issue, $payload)->assertConflict();
        Http::assertSentCount(1);
        $this->assertDatabaseHas('invoice_deliveries', ['sales_order_id' => $order->id, 'default_recipient' => true,
            'customer_id' => $customer->id, 'recipient' => 'central@example.test', 'status' => 'sent']);
        $endpoint = $this->deliveryUrl($order);
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.defaultRecipient', true)
            ->assertJsonPath('data.recipient', 'central@example.test')->assertJsonPath('data.active', true);
        [$first] = Http::recorded()->first();
        $this->assertSame(1, preg_match('~/invoice-access/[a-f0-9]{64}~', $first['text'], $path));
        $this->get($path[0])->assertOk()->assertSee('4.440.000');
        $this->postJson($endpoint, ['recipient' => 'override@example.test'])->assertOk()->assertJsonPath('data.latest.channel', 'mailtrap_demo');
        $this->get($path[0])->assertNotFound();
        Http::assertSentCount(2);
        foreach (Http::recorded() as [$request]) {
            $this->assertSame([['email' => 'central@example.test']], $request['to']);
            $this->assertArrayNotHasKey('cc', $request->data());
            $this->assertArrayNotHasKey('bcc', $request->data());
        }
        [$last] = Http::recorded()->last();
        $this->assertSame(1, preg_match('~/invoice-access/[a-f0-9]{64}~', $last['text'], $newPath));
        $this->get($newPath[0])->assertOk();
        $this->assertSame($customerEmail, $customer->fresh()->email);
        config(['services.mailtrap_demo.use_default' => false]);
        $this->get($newPath[0])->assertNotFound();
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.active', false)->assertJsonPath('data.recipient', $customerEmail);
    }

    public function test_finance_can_retry_failed_automatic_delivery_without_reissuing_the_invoice(): void
    {
        config(['mail.invoice_mailer' => 'mailtrap_demo', 'services.mailtrap_demo.use_default' => true,
            'services.mailtrap_demo.recipient' => 'central@example.test', 'services.mailtrap_demo.token' => 'test-token']);
        Http::preventStrayRequests();
        Http::fake(['https://send.api.mailtrap.io/api/send' => Http::sequence()->push([], 429)->push(['success' => true])]);
        $order = $this->orderFor(Customer::factory()->create(['email' => null]), issueInvoice: false);
        $issued = $this->postJson('/api/orders/'.$order->order_number.'/invoice', ['invoiceDate' => '2026-11-21', 'dueDate' => '2026-11-28'])
            ->assertOk()->assertJsonPath('delivery.sent', false)->assertJsonPath('data.status', 'Menunggu Pembayaran');
        $url = $this->deliveryUrl($order);
        $this->getJson($url)->assertOk()->assertJsonPath('data.latest.status', 'failed')->assertJsonPath('data.active', false);
        $this->postJson($url)->assertOk()->assertJsonPath('data.latest.recipient', 'central@example.test')->assertJsonPath('data.active', true);
        $this->assertSame($issued->json('data.invoice.invoiceNumber'), $order->fresh()->invoice['invoiceNumber']);
        Http::assertSentCount(2);
    }

    public function test_invalid_default_address_keeps_the_invoice_and_does_not_fall_back_to_the_customer(): void
    {
        config(['mail.invoice_mailer' => 'mailtrap_demo', 'services.mailtrap_demo.use_default' => true,
            'services.mailtrap_demo.recipient' => 'invalid']);
        Http::fake();
        $order = $this->orderFor(Customer::factory()->create(['email' => 'school@example.test']), issueInvoice: false);
        $this->postJson('/api/orders/'.$order->order_number.'/invoice', ['invoiceDate' => '2026-11-21', 'dueDate' => '2026-11-28'])
            ->assertOk()->assertJsonPath('delivery.sent', false)->assertJsonPath('data.status', 'Menunggu Pembayaran');
        $this->postJson($this->deliveryUrl($order))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_only_finance_can_read_send_or_revoke_delivery(): void
    {
        $order = $this->orderFor(Customer::factory()->create());
        $url = $this->deliveryUrl($order);
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $this->getJson($url)->assertUnauthorized();
        $this->postJson($url)->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();
        foreach (['MARKETING', 'WAREHOUSE', 'DIREKTUR', 'ADMINISTRASI', 'CLIENT', 'SUPPLIER'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson($url)->assertForbidden();
            $this->postJson($url)->assertForbidden();
            $this->deleteJson($url)->assertForbidden();
        }
        $this->assertCount(0, $this->outbox->messages());
        $this->assertDatabaseCount('invoice_deliveries', 0);
    }

    #[TestWith([null])]
    #[TestWith(['alamat-tidak-lengkap'])]
    public function test_customer_email_is_required_even_with_a_recipient_in_the_request(?string $email): void
    {
        $order = $this->orderFor(Customer::factory()->create(['email' => $email]));
        $this->postJson($this->deliveryUrl($order), ['recipient' => 'lain@example.test'])->assertUnprocessable();
        $this->assertCount(0, $this->outbox->messages());
        $this->assertSame($order->invoice, $order->fresh()->invoice);
    }

    public function test_finance_cannot_send_before_invoice_is_issued(): void
    {
        $order = $this->orderFor(Customer::factory()->create(), issueInvoice: false);
        $this->postJson($this->deliveryUrl($order))->assertConflict();
        $this->assertNull($order->fresh()->invoice);
        $this->assertCount(0, $this->outbox->messages());
        $this->assertDatabaseCount('invoice_deliveries', 0);
    }

    public function test_guest_sees_only_the_invoice_from_the_email_and_revoking_it_leaves_other_customers_accessible(): void
    {
        $school = Customer::factory()->create(['name' => 'SMP Jakarta Timur', 'email' => 'bendahara.smp@example.test']);
        $otherSchool = Customer::factory()->create(['name' => 'SMK Jakarta Barat', 'email' => 'bendahara.smk@example.test']);
        $order = $this->orderFor($school);
        $otherOrder = $this->orderFor($otherSchool);
        $url = $this->deliveryUrl($order);
        $this->getJson($url)->assertOk()->assertJsonPath('data.recipient', $school->email)
            ->assertJsonPath('data.latest', null)->assertJsonPath('data.active', false);
        $sent = $this->postJson($url, ['recipient' => $otherSchool->email])->assertOk()->assertJsonPath('data.latest.status', 'sent')
            ->assertJsonPath('data.latest.recipient', $school->email)->assertJsonPath('data.active', true);
        $this->assertNotEmpty($sent->json('data.latest.createdAt'));
        $this->assertNotEmpty($sent->json('data.expiresAt'));
        $path = $this->emailPath($school->email);
        $token = basename($path);
        $this->assertDatabaseHas('invoice_deliveries', [
            'sales_order_id' => $order->id, 'customer_id' => $school->id,
            'invoice_number' => $order->invoice['invoiceNumber'], 'token_hash' => hash('sha256', $token),
        ]);
        $this->assertStringNotContainsString($token, json_encode(DB::table('invoice_deliveries')->first()));
        $sent->assertDontSee($token);
        $this->getJson($url)->assertOk()->assertDontSee($token);
        $this->postJson($this->deliveryUrl($otherOrder))->assertOk();
        $otherPath = $this->emailPath($otherSchool->email);
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $public = $this->get($path)->assertOk()->assertSee($order->invoice['invoiceNumber'])
            ->assertSee($school->name)->assertDontSee($otherSchool->name)
            ->assertDontSee($otherOrder->invoice['invoiceNumber'])->assertDontSee('Catatan internal negosiasi pemasok')
            ->assertSee('Rp 4.440.000')->assertSee('Belum Dibayar')->assertSee('Virtual Account')->assertSee('QRIS')
            ->assertSee('Instruksi pembayaran belum tersedia.');
        $this->assertStringContainsString('no-store', $public->headers->get('Cache-Control'));
        $this->get('/invoice-access/'.$order->order_number)->assertNotFound();
        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs($this->finance);
        $this->deleteJson($url)->assertOk()->assertJsonPath('data.active', false);
        $this->get($path)->assertNotFound()->assertDontSee($school->name);
        $this->get($otherPath)->assertOk()->assertSee($otherSchool->name)->assertDontSee($school->name);
        $this->assertSame($order->invoice, $order->fresh()->invoice);
        $this->assertSame($order->status, $order->fresh()->status);
        $this->assertDatabaseMissing('spj_documents', ['status' => 'FINAL']);
    }

    public function test_failed_resend_keeps_the_previous_link_until_smtp_accepts_the_replacement(): void
    {
        $school = Customer::factory()->create();
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $this->postJson($url)->assertOk();
        $firstPath = $this->emailPath($school->email);
        $this->failNextDelivery();
        $this->postJson($url)->assertServiceUnavailable();
        $this->getJson($url)->assertOk()->assertJsonPath('data.latest.status', 'failed')
            ->assertJsonPath('data.active', true);
        $this->get($firstPath)->assertOk();
        $this->assertCount(1, $this->outbox->messages());
        Mail::mailer('smtp')->setSymfonyTransport($this->outbox);
        $this->postJson($url)->assertOk()->assertJsonPath('data.latest.status', 'sent');
        $replacement = $this->emailPath($school->email);
        $this->assertNotSame($firstPath, $replacement);
        $this->get($replacement)->assertOk()->assertSee($order->invoice['invoiceNumber']);
        $this->get($firstPath)->assertNotFound()->assertDontSee($order->invoice['invoiceNumber']);
        $this->assertSame($order->invoice, $order->fresh()->invoice);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);
        $this->assertDatabaseCount('sales_orders', 1);
    }

    public function test_first_send_failure_is_visible_and_does_not_create_customer_access(): void
    {
        $order = $this->orderFor(Customer::factory()->create());
        $url = $this->deliveryUrl($order);
        $this->failNextDelivery();
        $this->postJson($url)->assertServiceUnavailable();
        $this->getJson($url)->assertOk()->assertJsonPath('data.latest.status', 'failed')
            ->assertJsonPath('data.active', false);
        $this->assertCount(0, $this->outbox->messages());
        $this->assertSame($order->invoice, $order->fresh()->invoice);
    }

    public function test_overlapping_send_cannot_dispatch_a_second_email(): void
    {
        $school = Customer::factory()->create();
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $this->duringNextDelivery(function () use ($url, &$whileSending, &$duplicate) {
            $whileSending = $this->getJson($url);
            $duplicate = $this->postJson($url);
        });

        $this->postJson($url)->assertOk();
        $whileSending->assertOk()->assertJsonPath('data.latest.status', 'pending')->assertJsonPath('data.active', false);
        $duplicate->assertConflict();
        $this->assertCount(1, $this->outbox->messages());
        $this->assertDatabaseCount('invoice_deliveries', 1);
        $this->get($this->emailPath($school->email))->assertOk();
    }

    public function test_a_timed_out_attempt_can_be_retried_and_a_late_transport_result_cannot_reactivate_its_link(): void
    {
        $school = Customer::factory()->create();
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $this->duringNextDelivery(function () use ($url, $school, &$timedOut, &$retry, &$newPath) {
            $this->travel(3)->minutes();
            $timedOut = $this->getJson($url);
            $retry = $this->postJson($url);
            if ($retry->isOk()) {
                $newPath = $this->emailPath($school->email);
            }
        });

        $this->postJson($url);
        $timedOut->assertOk()->assertJsonPath('data.latest.status', 'failed')->assertJsonPath('data.active', false);
        $retry->assertOk()->assertJsonPath('data.latest.status', 'sent')->assertJsonPath('data.active', true);
        $stalePath = $this->emailPath($school->email);
        $this->assertNotSame($newPath, $stalePath);
        $this->get($newPath)->assertOk();
        $this->get($stalePath)->assertNotFound();
        $this->assertSame($order->invoice, $order->fresh()->invoice);
    }

    public function test_revocation_during_send_cannot_be_undone_by_smtp_completion(): void
    {
        $school = Customer::factory()->create();
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $this->postJson($url)->assertOk();
        $first = $this->emailPath($school->email);
        $this->duringNextDelivery(function () use ($url, &$revoked) {
            $revoked = $this->deleteJson($url);
        });
        $this->postJson($url);
        $revoked->assertOk()->assertJsonPath('data.active', false);
        $this->assertCount(2, $this->outbox->messages());
        $this->get($this->emailPath($school->email))->assertNotFound();
        $this->get($first)->assertNotFound();
        $this->getJson($url)->assertOk()->assertJsonPath('data.active', false);
    }

    public function test_expiration_is_enforced_on_the_public_page_and_in_finance_status(): void
    {
        $school = Customer::factory()->create();
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $expiry = $this->postJson($url)->assertOk()->json('data.expiresAt');
        $path = $this->emailPath($school->email);
        $this->travelTo(Carbon::parse($expiry)->addSecond());
        $expired = $this->get($path)->assertNotFound()->assertDontSee($order->invoice['invoiceNumber']);
        $this->assertStringContainsString('no-store', $expired->headers->get('Cache-Control'));
        $this->getJson($url)->assertOk()->assertJsonPath('data.active', false);
    }

    public function test_replacing_the_billing_contact_invalidates_the_old_link_and_resend_uses_the_new_contact(): void
    {
        $school = Customer::factory()->create(['email' => 'bendahara.lama@example.test']);
        $order = $this->orderFor($school);
        $url = $this->deliveryUrl($order);
        $this->postJson($url)->assertOk();
        $old = $this->emailPath($school->email);
        $school->update(['email' => 'bendahara.baru@example.test']);
        $this->get($old)->assertNotFound()->assertDontSee($order->invoice['invoiceNumber']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.recipient', $school->email)
            ->assertJsonPath('data.active', false);
        $this->postJson($url)->assertOk()->assertJsonPath('data.latest.recipient', $school->email);
        $this->get($this->emailPath($school->email))->assertOk();
        $this->get($old)->assertNotFound();
    }
}
