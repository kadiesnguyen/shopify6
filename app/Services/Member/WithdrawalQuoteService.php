<?php

namespace App\Services\Member;

use App\Models\WithdrawalMethod;

class WithdrawalQuoteService
{
    /** @return array{fee_percent: float, fee_amount: float, net_amount: float} */
    public function quote(WithdrawalMethod $method, float $amount, ?string $network = null): array
    {
        $feePercent = $this->feePercent($method, $network);
        $feeAmount = round($amount * $feePercent / 100, 2);
        $netAmount = max(0, round($amount - $feeAmount, 2));

        return [
            'fee_percent' => $feePercent,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
        ];
    }

    private function feePercent(WithdrawalMethod $method, ?string $network): float
    {
        if ($method->type === WithdrawalMethod::TYPE_CRYPTO && $network) {
            foreach ($method->config['networks'] ?? [] as $item) {
                if (($item['label'] ?? null) === $network) {
                    return (float) ($item['fee'] ?? 0);
                }
            }
        }

        return (float) ($method->config['fee_percent'] ?? 0);
    }
}
