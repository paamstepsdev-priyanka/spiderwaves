<?php

namespace App\Services;

/**
 * WastageService
 * 
 * Encapsulates the domain business calculations for Wastage and Loss Percentages
 * in SpiderWaves, matching the exact algorithms from CodeIgniter 2/3:
 * - common_func_helper.php
 * - raw_waves.php
 * - process_waves.php
 * - waves.php
 */
class WastageService
{
    /**
     * Calculate OR (Old Rate) Fine Gold Equivalent.
     * Formula: OR Equivalent = Net Weight * (Purity + Wastage) / 100
     * Precision: milligram (0.001g, 3 decimal places)
     *
     * @param float $netWeight Net weight in grams
     * @param float $purity Base metal purity percentage (e.g. 75.0, 92.0)
     * @param float $wastage Customer wastage allowance percentage (e.g. 2.5)
     * @return float Fine gold weight equivalent
     */
    public function calculateOREquivalent(float $netWeight, float $purity, float $wastage): float
    {
        if ($netWeight <= 0) {
            return 0.000;
        }

        $effectivePurity = $purity + $wastage;
        return round(($netWeight * $effectivePurity) / 100, 3);
    }

    /**
     * Calculate Standard Fine Weight (MT mode).
     * Formula: Fine Weight = Net Weight * Purity / 100
     * Precision: milligram (0.001g, 3 decimal places)
     *
     * @param float $netWeight Net weight in grams
     * @param float $purity Base metal purity percentage
     * @return float Fine gold weight
     */
    public function calculateFineWeight(float $netWeight, float $purity): float
    {
        if ($netWeight <= 0) {
            return 0.000;
        }

        return round(($netWeight * $purity) / 100, 3);
    }

    /**
     * Calculate Actual Workshop Stage Loss Percentage.
     * Formula: Actual Loss % = (Account Balance / Account Receive) * 100
     * Precision: 2 decimal places (as used in process_waves.php:check_loss_validation)
     *
     * @param float $balance Net remaining / loss balance
     * @param float $receive Total received metal in stage
     * @return float Loss percentage (e.g. 2.45)
     */
    public function calculateProcessStageLoss(float $balance, float $receive): float
    {
        if ($receive <= 0) {
            return 0.00;
        }

        return round(($balance / $receive) * 100, 2);
    }

    /**
     * Validate whether actual stage loss is within allowable threshold.
     * In legacy process_waves.php:1147:
     * if ($loss_percentage <= $set_loss_percentage) => Acceptable
     *
     * @param float $actualLoss
     * @param float $thresholdPermissible
     * @return bool
     */
    public function isLossAcceptable(float $actualLoss, float $thresholdPermissible): bool
    {
        return $actualLoss <= $thresholdPermissible;
    }

    /**
     * Calculate Workshop Refining Net Loss Fine Gold.
     * Formula: Net Loss = Loss Weight * Purity / 100
     *
     * @param float $lossWeight
     * @param float $purity
     * @return float
     */
    public function calculateNetLossFineGold(float $lossWeight, float $purity): float
    {
        if ($lossWeight <= 0) {
            return 0.000;
        }

        return round(($lossWeight * $purity) / 100, 3);
    }
}
