<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'paid_at' => $this->paid_at?->toISOString(),
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'is_paid' => $this->isPaid(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}