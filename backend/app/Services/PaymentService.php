<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Jobs\GenerateTicketJob;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\Payment\Data\VerificationResult;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayManager $gateways,
        private readonly NotificationDispatcher $notifications,
        private readonly ActivityLogger $activity,
    ) {
    }

    /* ------------------------------ Initiate ------------------------------ */

    /**
     * Create a payment session for a registration and return the redirect target.
     *
     * @return array{payment: Payment, redirect_url: string, sandbox: bool}
     *
     * @throws ValidationException
     */
    public function initiate(EventRegistration $registration, string $gatewayName, User $user): array
    {
        if ($registration->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'registration' => ['You can only pay for your own registration.'],
            ]);
        }

        if ($registration->status === RegistrationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'registration' => ['This registration has been cancelled.'],
            ]);
        }

        if ((float) $registration->amount <= 0) {
            throw ValidationException::withMessages([
                'registration' => ['This is a free event — no payment is required.'],
            ]);
        }

        if ($registration->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'registration' => ['This registration is already paid.'],
            ]);
        }

        $gateway = $this->gateways->make($gatewayName);

        return DB::transaction(function () use ($registration, $gateway) {
            /** @var Payment $payment */
            $payment = $this->payments->create([
                'registration_id' => $registration->id,
                'transaction_id'  => $this->generateTransactionId(),
                'amount'          => $registration->amount,
                'currency'        => config('payment.currency', 'BDT'),
                'gateway'         => $gateway->key(),
                'status'          => PaymentStatus::Pending->value,
            ]);

            $result = $gateway->initiate($payment);

            $this->payments->update($payment, [
                'meta' => array_merge((array) $payment->meta, ['initiate' => $result->raw]),
            ]);

            if (! $result->success) {
                throw ValidationException::withMessages([
                    'gateway' => [$result->message ?? 'Unable to start payment.'],
                ]);
            }

            return [
                'payment'      => $payment->fresh(),
                'redirect_url' => $result->redirectUrl,
                'sandbox'      => $result->sandbox,
            ];
        });
    }

    /* ------------------------------- Verify ------------------------------- */

    /**
     * Verify a gateway return/IPN. `transaction_id` (our ref) locates the payment.
     *
     * @param array<string, mixed> $data
     */
    public function handleGatewayCallback(string $gatewayName, array $data): Payment
    {
        $transactionId = $data['transaction_id'] ?? $data['tran_id'] ?? $data['merchantInvoiceNumber'] ?? null;

        $payment = $transactionId ? $this->payments->findByTransactionId($transactionId) : null;

        if (! $payment) {
            throw ValidationException::withMessages([
                'payment' => ['Payment record not found for this transaction.'],
            ]);
        }

        $gateway = $this->gateways->make($gatewayName);
        $result = $gateway->verify($payment, $data);

        return $this->applyResult($payment, $result);
    }

    /**
     * Complete a sandbox payment triggered from the simulated gateway page.
     *
     * @param array<string, mixed> $data  expects: token, outcome
     */
    public function completeSandbox(int $paymentId, array $data, User $user): Payment
    {
        /** @var Payment $payment */
        $payment = $this->payments->findOrFail($paymentId);
        $payment->loadMissing('registration');

        if ($payment->registration?->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'payment' => ['This payment does not belong to you.'],
            ]);
        }

        $gateway = $this->gateways->make($payment->gateway->value);
        $result = $gateway->verify($payment, $data);

        return $this->applyResult($payment, $result);
    }

    /**
     * Apply a verification outcome to the payment + registration (idempotent).
     */
    private function applyResult(Payment $payment, VerificationResult $result): Payment
    {
        // Already settled — don't double-process (e.g. IPN + return race).
        if ($payment->status === PaymentStatus::Paid) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $result) {
            if ($result->paid) {
                $this->payments->update($payment, [
                    'status'                 => PaymentStatus::Paid->value,
                    'gateway_transaction_id' => $result->gatewayTransactionId,
                    'payment_date'           => Carbon::now(),
                    'meta'                   => array_merge((array) $payment->meta, ['verify' => $result->raw]),
                ]);

                $registration = $payment->registration;
                if ($registration) {
                    $registration->forceFill([
                        'payment_status' => PaymentStatus::Paid->value,
                        'status'         => RegistrationStatus::Confirmed->value,
                    ])->save();

                    // Fire-and-forget ticket generation + email.
                    GenerateTicketJob::dispatch($registration->id);

                    // Notify the payer + confirm their registration; log the payment.
                    $this->notifications->paymentSuccess($payment->fresh('registration.user.roles') ?? $payment);
                    $this->notifications->registrationConfirmed($registration->fresh('user'));
                    $this->activity->log(
                        ActivityAction::Payment,
                        'Payment received: '.$payment->transaction_id,
                        $registration->user,
                        $payment,
                        ['amount' => (float) $payment->amount, 'gateway' => (string) $payment->gateway->value],
                    );
                }
            } else {
                $this->payments->update($payment, [
                    'status' => PaymentStatus::Failed->value,
                    'meta'   => array_merge((array) $payment->meta, ['verify' => $result->raw]),
                ]);
            }

            return $payment->fresh('registration');
        });
    }

    /* ------------------------------- Admin -------------------------------- */

    public function adminList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->payments->paginateWithFilters($filters, $perPage);
    }

    public function userList(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->payments->paginateForUser($userId, $filters, $perPage);
    }

    public function find(int $id): Payment
    {
        /** @var Payment $payment */
        $payment = $this->payments->findOrFail($id);

        return $payment->load(['registration.event', 'registration.user']);
    }

    public function revenue(): array
    {
        return $this->payments->revenueSummary();
    }

    /**
     * Admin marks a paid payment as refunded.
     */
    public function refund(int $paymentId): Payment
    {
        /** @var Payment $payment */
        $payment = $this->payments->findOrFail($paymentId);

        if ($payment->status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'payment' => ['Only paid payments can be refunded.'],
            ]);
        }

        return DB::transaction(function () use ($payment) {
            $this->payments->update($payment, ['status' => PaymentStatus::Refunded->value]);

            $registration = $payment->registration;
            if ($registration) {
                $registration->forceFill([
                    'payment_status' => PaymentStatus::Refunded->value,
                    'status'         => RegistrationStatus::Cancelled->value,
                    'cancelled_at'   => Carbon::now(),
                ])->save();
            }

            $this->activity->log(
                ActivityAction::Refund,
                'Payment refunded: '.$payment->transaction_id,
                subject: $payment,
                properties: ['amount' => (float) $payment->amount],
            );

            return $payment->fresh('registration');
        });
    }

    private function generateTransactionId(): string
    {
        do {
            $txn = 'TXN-'.Carbon::now()->format('Y').'-'.strtoupper(Str::random(10));
        } while (Payment::where('transaction_id', $txn)->exists());

        return $txn;
    }
}
