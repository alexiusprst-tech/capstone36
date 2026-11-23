<?php

namespace App\Services;

use App\Models\InvoiceDelivery;
use Illuminate\Mail\Message;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Throwable;

class InvoiceEmailSender
{
    public static function usesDefaultRecipient(): bool
    {
        return config('mail.invoice_mailer') === 'mailtrap_demo' && (bool) config('services.mailtrap_demo.use_default');
    }

    public static function recipientFor(?string $customerEmail): ?string
    {
        $email = trim((string) (self::usesDefaultRecipient() ? config('services.mailtrap_demo.recipient') : $customerEmail));

        return $email === '' ? null : $email;
    }

    public static function recipientMatches(InvoiceDelivery $delivery, ?string $customerEmail): bool
    {
        return $delivery->default_recipient
            ? self::usesDefaultRecipient() && $delivery->recipient === self::recipientFor(null)
            : $delivery->recipient === trim((string) $customerEmail);
    }

    public static function channelFor(?string $recipient): string
    {
        $mailer = config('mail.invoice_mailer');
        if ($mailer === 'mailtrap_demo') {
            $allowed = trim((string) config('services.mailtrap_demo.recipient'));

            return filled($recipient) && $allowed !== '' && strcasecmp($recipient, $allowed) === 0
                ? 'mailtrap_demo' : 'mailtrap_sandbox';
        }

        return $mailer === 'mailtrap_sandbox' ? 'mailtrap_sandbox' : 'smtp';
    }

    public function send(InvoiceDelivery $delivery, string $token): bool
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if (! filter_var($appUrl, FILTER_VALIDATE_URL)
            || ! in_array(parse_url($appUrl, PHP_URL_SCHEME), ['https', 'http'], true)) {
            return false;
        }

        $url = $appUrl.route('invoice.customer.access', ['token' => $token], false);
        $expires = $delivery->expires_at->copy()->timezone(config('app.business_timezone'))->format('d/m/Y H:i T');
        $body = "Invoice {$delivery->invoice_number} tersedia melalui tautan berikut:\n\n{$url}\n\nTautan berlaku sampai {$expires}. Simpan tautan ini untuk Anda sendiri.";
        $subject = 'Invoice '.$delivery->invoice_number;
        try {
            if ($delivery->channel === 'mailtrap_demo') {
                if (self::channelFor($delivery->recipient) !== 'mailtrap_demo') {
                    return false;
                }

                return $this->sendViaMailtrap('https://send.api.mailtrap.io/api/send',
                    config('services.mailtrap_demo.token'), 'hello@demomailtrap.co',
                    $delivery->recipient, '[Uji CAPS-26] '.$subject, "Email uji pengiriman invoice.\n\n".$body);
            }
            if ($delivery->channel === 'mailtrap_sandbox') {
                $inbox = (string) config('services.mailtrap_sandbox.inbox_id');
                if (! preg_match('/^[1-9][0-9]*$/D', $inbox)) {
                    return false;
                }

                return $this->sendViaMailtrap('https://sandbox.api.mailtrap.io/api/send/'.$inbox,
                    config('services.mailtrap_sandbox.token'), config('mail.from.address'),
                    $delivery->recipient, $subject, $body);
            }

            return $this->sendViaSmtp(config('mail.invoice_mailer'), $delivery->recipient, $subject, $body);
        } catch (Throwable) {
            // Transport diagnostics can contain credentials or the private invoice URL.
            return false;
        }
    }

    private function sendViaMailtrap(string $endpoint, ?string $token, ?string $from, string $recipient, string $subject, string $body): bool
    {
        if (! filled($token) || ! filled($from)) {
            return false;
        }

        $response = Http::withToken($token)->acceptJson()->withoutRedirecting()
            ->connectTimeout(5)->timeout(10)
            ->post($endpoint, [
                'from' => array_filter(['email' => $from, 'name' => config('mail.from.name')]),
                'to' => [['email' => $recipient]],
                'subject' => $subject,
                'text' => $body,
            ]);

        return $response->successful() && $response->json('success') === true;
    }

    private function sendViaSmtp(string $mailer, string $recipient, string $subject, string $body): bool
    {
        $config = config('mail.mailers.'.$mailer, []);
        $effective = (new ConfigurationUrlParser)->parseConfiguration($config);
        $transport = empty($config['url']) ? ($effective['transport'] ?? null) : ($effective['driver'] ?? null);
        if ($transport !== 'smtp' || isset($effective['to']['address']) || config('mail.to.address')) {
            return false;
        }
        $client = Mail::mailer($mailer);
        $smtp = $client->getSymfonyTransport();
        if ($smtp instanceof SmtpTransport && $smtp->getStream() instanceof SocketStream) {
            $smtp->getStream()->setTimeout(10);
        }

        return $client->raw($body, function (Message $message) use ($recipient, $subject) {
            $message->to($recipient)->subject($subject);
        }) !== null;
    }
}
