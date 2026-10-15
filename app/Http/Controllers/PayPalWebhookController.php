<?php

namespace App\Http\Controllers;

use App\Models\Suscripcion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $eventType = (string) $request->input('event_type');
        $reference = $this->resolveReference($request);

        Log::info('PayPal webhook received.', [
            'event_type' => $eventType,
            'resource_id' => $request->input('resource.id'),
            'reference' => $reference,
            'payload' => $request->all(),
        ]);

        if (blank($reference)) {
            return response()->json(['ok' => true]);
        }

        $subscription = Suscripcion::query()
            ->where('referencia_externa', $reference)
            ->first();

        if (! $subscription) {
            Log::info('PayPal webhook without local subscription match.', [
                'event_type' => $eventType,
                'reference' => $reference,
            ]);

            return response()->json(['ok' => true]);
        }

        $updates = [
            'estado' => $this->resolveStatus($request),
            'payload' => $request->all(),
        ];

        $periodStart = $this->parseDate(
            $request->input('resource.start_time')
                ?? $request->input('resource.create_time')
        );
        $periodEnd = $this->parseDate(
            $request->input('resource.billing_info.next_billing_time')
                ?? $request->input('resource.agreement_details.next_billing_date')
        );
        $statusUpdatedAt = $this->parseDate($request->input('resource.status_update_time'));

        if ($periodStart) {
            $updates['periodo_inicio'] = $periodStart;
        }

        if ($periodEnd) {
            $updates['periodo_fin'] = $periodEnd;
        }

        if (in_array($updates['estado'], ['active', 'approved', 'approval_pending'], true)) {
            $updates['fecha_activacion'] = $subscription->fecha_activacion ?? $statusUpdatedAt ?? now();
        }

        if (in_array($updates['estado'], ['cancelled', 'expired'], true)) {
            $updates['fecha_cancelacion'] = $statusUpdatedAt ?? now();
        }

        $subscription->fill($updates);
        $subscription->save();

        return response()->json(['ok' => true]);
    }

    protected function resolveReference(Request $request): ?string
    {
        $eventType = (string) $request->input('event_type');

        if (Str::startsWith($eventType, 'BILLING.SUBSCRIPTION.')) {
            return $request->input('resource.id');
        }

        return $request->input('resource.billing_agreement_id')
            ?? $request->input('resource.supplementary_data.related_ids.subscription_id')
            ?? $request->input('resource.id');
    }

    protected function resolveStatus(Request $request): string
    {
        $eventType = (string) $request->input('event_type');
        $resourceStatus = Str::lower((string) $request->input('resource.status'));

        if ($resourceStatus !== '') {
            return $resourceStatus;
        }

        return match (true) {
            Str::contains($eventType, 'ACTIVATED') => 'active',
            Str::contains($eventType, 'APPROVAL') => 'approval_pending',
            Str::contains($eventType, 'CANCELLED') => 'cancelled',
            Str::contains($eventType, 'SUSPENDED') => 'suspended',
            Str::contains($eventType, 'EXPIRED') => 'expired',
            Str::contains($eventType, 'PAYMENT.FAILED') => 'payment_failed',
            Str::contains($eventType, 'PAYMENT.SALE.COMPLETED') => 'active',
            default => 'paypal_event',
        };
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
