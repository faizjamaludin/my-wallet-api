<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BillingCycle
{
    /**
     * Given a transaction date and a card's statement_day, return the cycle month (YYYY-MM).
     *
     * Convention: the cycle is named by the month in which it STARTS.
     *   statement_day = 18, date = Jun 18 → cycle = "2026-06" (Jun 18 – Jul 17)
     *   statement_day = 18, date = Jun 17 → cycle = "2026-05" (May 18 – Jun 17)
     */
    public static function cycleMonthFor(string $date, int $statementDay): string
    {
        $d = Carbon::parse($date);
        if ($d->day >= $statementDay) {
            return $d->format('Y-m');
        }
        return $d->copy()->subMonthNoOverflow()->format('Y-m');
    }

    /**
     * Given a cycle month (YYYY-MM) and statement_day, return [cycleStart, cycleEnd] as Y-m-d strings.
     *
     * e.g. cycleMonth="2026-06", statementDay=18 → ["2026-06-18", "2026-07-17"]
     */
    public static function dateRange(string $cycleMonth, int $statementDay): array
    {
        $start = Carbon::parse($cycleMonth . '-' . str_pad($statementDay, 2, '0', STR_PAD_LEFT));
        $end   = $start->copy()->addMonthNoOverflow()->subDay();
        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /**
     * Statement date: the day the statement is generated (start of next cycle).
     * e.g. cycleMonth="2026-06", statementDay=18 → "2026-07-18"
     */
    public static function statementDate(string $cycleMonth, int $statementDay): string
    {
        $start = Carbon::parse($cycleMonth . '-' . str_pad($statementDay, 2, '0', STR_PAD_LEFT));
        return $start->copy()->addMonthNoOverflow()->format('Y-m-d');
    }

    /**
     * Payment due date: paymentDay of the month after the statement date.
     * e.g. cycleMonth="2026-06", statementDay=18, paymentDay=1 → "2026-08-01"
     */
    public static function paymentDueDate(string $cycleMonth, int $statementDay, int $paymentDay): string
    {
        $statementDate = static::statementDate($cycleMonth, $statementDay);
        $due = Carbon::parse($statementDate)->addMonthNoOverflow();
        $due->day = $paymentDay;
        return $due->format('Y-m-d');
    }

    /**
     * Determine the current cycle month for today, given a statement_day.
     */
    public static function currentCycleMonth(int $statementDay = 18): string
    {
        return static::cycleMonthFor(now()->format('Y-m-d'), $statementDay);
    }
}
