<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\CreateCheckoutSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CheckoutRequest;
use App\Http\Resources\BillingResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Invoice;

class BillingController extends Controller
{
    public function __construct(
        private readonly CreateCheckoutSessionAction $createCheckoutSessionAction,
    ) {}

    public function show(Request $request): BillingResource
    {
        /** @var User $user */
        $user = $request->user();

        return new BillingResource($user);
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $url = $this->createCheckoutSessionAction->execute($user, $request->toData()->plan);

        return response()->json(['url' => $url]);
    }

    public function cancel(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->subscription()->cancel();

        return response()->json(['message' => 'Subscription cancelled. Access continues until the end of the billing period.']);
    }

    public function resume(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->subscription()->resume();

        return response()->json(['message' => 'Subscription resumed.']);
    }

    public function invoices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Invoice[] $invoices */
        $invoices = $user->invoices();

        $data = array_map(function (Invoice $invoice): array {
            $stripeInvoice = $invoice->asStripeInvoice();

            return [
                'id' => $stripeInvoice->id ?? '',
                'date' => $invoice->date()->toDateString(),
                'total' => $invoice->total(),
                'status' => $stripeInvoice->status ?? '',
            ];
        }, $invoices);

        return response()->json(['data' => $data]);
    }
}
