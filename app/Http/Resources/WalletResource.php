<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => (float) $this->balance,
            'balance_pending' => (float) $this->balance_pending,
            'balance_frozen' => (float) $this->balance_frozen,
        ];
    }
}
