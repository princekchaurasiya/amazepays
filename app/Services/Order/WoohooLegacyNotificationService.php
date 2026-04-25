<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Mail\AdminOrderFailureMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Legacy mail/SMS notifications used by Woohoo fulfillment.
 *
 * Phase 2 extraction target: keep behavior stable while moving side-effects out of controllers.
 */
final class WoohooLegacyNotificationService
{
    /**
     * @param  array<string, mixed>  $prepareMailDetails
     */
    public function sendTransactionMail(array $prepareMailDetails): void
    {
        $recipientEmail = $prepareMailDetails['billing_email'] ?? null;
        $recipientName = $prepareMailDetails['billing_name'] ?? null;

        if (empty($recipientEmail) || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('Transaction mail not sent: invalid or empty recipient email', [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails['order_id'] ?? 'N/A',
            ]);

            return;
        }

        try {
            $htmlInvoice = app(InvoiceHtmlRenderer::class)->render($prepareMailDetails);
            $pdf = Pdf::loadHTML($htmlInvoice);

            $subject = (string) config('companyDefaultValues.default_subject');
            $html = '<p>Thank you for your purchase. Your invoice is attached.</p>';

            Mail::to($prepareMailDetails['billing_email'], $prepareMailDetails['billing_name'])
                ->send(
                    (new class($subject, $html, $pdf) extends \Illuminate\Mail\Mailable {
                        public function __construct(
                            private readonly string $subjectLine,
                            private readonly string $htmlBody,
                            private readonly \Barryvdh\DomPDF\PDF $pdf,
                        ) {}

                        public function build()
                        {
                            return $this->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                                ->subject($this->subjectLine)
                                ->html($this->htmlBody)
                                ->attachData($this->pdf->output(), 'invoice.pdf');
                        }
                    })
                );
        } catch (\Throwable $e) {
            Log::error('Failed to send transaction mail', [
                'error_message' => $e->getMessage(),
                'order_id' => $prepareMailDetails['order_id'] ?? 'N/A',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $prepareMailDetails
     * @param  array<int, mixed>  $cardsArray
     */
    public function sendGiftMail(array $prepareMailDetails, array $cardsArray): void
    {
        $recipientEmail = $prepareMailDetails['billing_email'] ?? null;
        $recipientName = $prepareMailDetails['billing_name'] ?? null;

        if (empty($recipientEmail) || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('Gift mail not sent: invalid or empty recipient email', [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails['order_id'] ?? 'N/A',
            ]);

            return;
        }

        try {
            $subject = (string) config('companyDefaultValues.gift_subject');
            $html = '<p>You received a gift card. Please view the card details in your AmazePays account.</p>';

            Mail::to($prepareMailDetails['billing_email'], $prepareMailDetails['billing_name'])
                ->send(
                    (new class($subject, $html) extends \Illuminate\Mail\Mailable {
                        public function __construct(
                            private readonly string $subjectLine,
                            private readonly string $htmlBody,
                        ) {}

                        public function build()
                        {
                            return $this->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                                ->subject($this->subjectLine)
                                ->html($this->htmlBody);
                        }
                    })
                );
        } catch (\Throwable $e) {
            Log::error('Failed to send gift mail', [
                'error_message' => $e->getMessage(),
                'order_id' => $prepareMailDetails['order_id'] ?? 'N/A',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $prepareSmsDetails
     */
    public function sendTransactionalMessage(array $prepareSmsDetails): void
    {
        try {
            $name = (string) ($prepareSmsDetails['name'] ?? '');
            $orderAmount = (string) ($prepareSmsDetails['order_amount'] ?? '');
            $orderNumber = (string) ($prepareSmsDetails['order_id'] ?? '');
            $productName = (string) ($prepareSmsDetails['cardProductName'] ?? '');
            $destination = (string) ($prepareSmsDetails['billing_tel'] ?? '');

            if ($destination === '' || ! preg_match('/^[6-9]\d{9}$/', $destination)) {
                return;
            }

            $smsApiUrl = (string) config('transactionSms.sms_api_url');
            $smsUserName = (string) config('transactionSms.sms_user_name');
            $smsUserPassword = (string) config('transactionSms.sms_user_password');
            $smsSource = (string) config('transactionSms.sms_source');
            $smsEntityId = (string) config('transactionSms.sms_entity_id');
            $smsTempId = (string) config('transactionSms.sms_temp_id');
            $smsTmid = (string) config('transactionSms.sms_tmid');

            $smsMessage = 'Hello '.$name.', Your order no '.$orderNumber.' of '.$orderAmount.' is generated successfully. Please check out respected Email for that. Thanks - FRENETIC INDIA.';

            $apiUrl = "$smsApiUrl?username=$smsUserName&password=$smsUserPassword&type=0&dlr=1&destination={$destination}&source=$smsSource&message=$smsMessage&entityid=$smsEntityId&tempid=$smsTempId&tmid=$smsTmid";

            Http::get($apiUrl);
        } catch (\Throwable $e) {
            Log::error('Transactional SMS failed', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $prepareSmsDetails
     * @param  array<int, array<string, mixed>>  $cardsArray
     */
    public function sendGiftMessage(array $prepareSmsDetails, array $cardsArray): void
    {
        try {
            $name = (string) ($prepareSmsDetails['shipToName'] ?? ($prepareSmsDetails['billing_name'] ?? 'Customer'));
            $destination = (string) ($prepareSmsDetails['shipToContactNo'] ?? ($prepareSmsDetails['billing_tel'] ?? ''));

            if ($destination === '' || ! preg_match('/^[6-9]\d{9}$/', $destination)) {
                return;
            }

            $smsApiUrl = (string) config('giftSms.sms_api_url');
            $smsUserName = (string) config('giftSms.sms_user_name');
            $smsUserPassword = (string) config('giftSms.sms_user_password');
            $smsSource = (string) config('giftSms.sms_source');
            $smsEntityId = (string) config('giftSms.sms_entity_id');
            $smsTempId = (string) config('giftSms.sms_temp_id');
            $smsTmid = (string) config('giftSms.sms_tmid');

            foreach ($cardsArray as $card) {
                $cardId = (string) ($card['cardNumber'] ?? '');
                $cardPin = (string) ($card['cardPin'] ?? '');
                $cardAmount = (string) ($card['amount'] ?? '');
                $cardActivationCode = (string) ($card['activationCode'] ?? '');
                $cardActivationUrl = (string) ($card['activationUrl'] ?? '');
                $validityRaw = (string) ($card['validity'] ?? '');
                $cardValidity = $validityRaw !== '' ? date('d-M-Y', strtotime($validityRaw)) : '';

                $smsMessage = 'Hello '.$name.' You received a gift card and your Card details: '.'Card ID: '.$cardId.' Card Pin: '.$cardPin.' Amount '.$cardAmount.' Activation Code '.$cardActivationCode.' Activation URL '.$cardActivationUrl.' Validity '.$cardValidity.' Please check your respected Email for more information. Thanks - FRENETIC INDIA';
                $apiUrl = "$smsApiUrl?username=$smsUserName&password=$smsUserPassword&type=0&dlr=1&destination={$destination}&source=$smsSource&message=$smsMessage&entityid=$smsEntityId&tempid=$smsTempId&tmid=$smsTmid";
                Http::get($apiUrl);
            }
        } catch (\Throwable $e) {
            Log::error('Gift SMS failed', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function sendOrderFailureMail(Order $order): void
    {
        $orderFailureAdminEmail = env('ORDER_FAILURE_ADMIN_EMAIL');
        $orderFailureITAdminEmail = env('ORDER_FAILURE_IT_ADMIN_EMAIL');

        if (empty($orderFailureAdminEmail) || ! filter_var($orderFailureAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureAdminEmail = config('companyDefaultValues.company_email');
        }
        if (empty($orderFailureAdminEmail) || ! filter_var($orderFailureAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureAdminEmail = 'itsupport@amazepays.in';
        }
        if (empty($orderFailureITAdminEmail) || ! filter_var($orderFailureITAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureITAdminEmail = 'itsupport@amazepays.in';
        }

        $fromEmail = config('companyDefaultValues.sendMailFrom');
        if (empty($fromEmail) || ! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $mailable = (new AdminOrderFailureMail($order))
                ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'));

            $mailer = Mail::to($orderFailureAdminEmail);
            if (! empty($orderFailureITAdminEmail)) {
                $mailer->cc($orderFailureITAdminEmail);
            }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Failed to send order failure email', [
                'order_id' => $order->id,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
