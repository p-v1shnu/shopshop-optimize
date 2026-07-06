<?php

namespace App\Utils;

use App\Models\RewardRedeem;

class RewardUtil
{
    public static function getRedeemRewardNameBySku(RewardRedeem $redeem): string
    {
        if ($redeem->reward_variation_sku === null) {
            return $redeem->reward->name;
        }

        $reward = $redeem->reward;
        $variation = $reward
            ->variations
            ->where('sku', '=', $redeem->reward_variation_sku)
            ->first();

        if ($variation) {
            return $variation->name;
        }

        return $redeem->reward->name;
    }
}
