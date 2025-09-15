<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
            'appointment_date' => $this->appointment_date->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'patient' => new UserResource($this->whenLoaded('patient')),
            'doctor' => new UserResource($this->whenLoaded('doctor')),
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'description' => $this->service->description,
                'price' => $this->service->price,
            ],
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_rescheduled' => method_exists($this, 'canBeRescheduled') ? $this->canBeRescheduled() : false,
            'is_free_consultation' => method_exists($this, 'isFreeConsultation') ? $this->isFreeConsultation() : false,
            'is_paid_appointment' => method_exists($this, 'isPaidAppointment') ? $this->isPaidAppointment() : false,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}