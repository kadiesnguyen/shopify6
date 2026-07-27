<?php

namespace App\Services\Admin;

use App\Models\ChatMessage;
use App\Models\RechargeRequest;
use App\Models\WithdrawalRequest;

class AdminAlertCountsService
{
    /** @return array{recharge_pending: int, withdrawal_pending: int, chat_unread: int} */
    public function counts(): array
    {
        return [
            'recharge_pending' => (int) RechargeRequest::query()
                ->where('status', RechargeRequest::STATUS_PENDING)
                ->count(),
            'withdrawal_pending' => (int) WithdrawalRequest::query()
                ->where('status', WithdrawalRequest::STATUS_PENDING)
                ->count(),
            'chat_unread' => (int) ChatMessage::query()
                ->where('sender_role', ChatMessage::ROLE_USER)
                ->whereNull('read_by_admin_at')
                ->count(),
        ];
    }
}
