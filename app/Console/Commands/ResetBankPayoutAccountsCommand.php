<?php

namespace App\Console\Commands;

use App\Models\UserPayoutAccount;
use Illuminate\Console\Command;

class ResetBankPayoutAccountsCommand extends Command
{
    protected $signature = 'payout-accounts:reset-bank';

    protected $description = 'Delete every saved bank payout account so members can link one account again';

    public function handle(): int
    {
        $deleted = UserPayoutAccount::query()
            ->where('type', UserPayoutAccount::TYPE_BANK)
            ->delete();

        $this->components->info("Deleted {$deleted} bank payout accounts.");

        return self::SUCCESS;
    }
}
