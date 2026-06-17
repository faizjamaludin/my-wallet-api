<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Card;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\Rule;
use App\Models\Saving;
use App\Models\Transaction;
use App\Support\BillingCycle;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        $today  = now()->day;

        // Resolve the statement_day from the user's first credit card
        $firstCC      = Card::where('user_id', $userId)->where('type', 'credit')->orderBy('created_at')->first();
        $statementDay = $firstCC?->statement_day ?? 18;
        $paymentDay   = $firstCC?->payment_day ?? 1;

        $cycleMonth = $request->query('month', BillingCycle::currentCycleMonth($statementDay));
        [$cycleStart, $cycleEnd] = BillingCycle::dateRange($cycleMonth, $statementDay);
        $statementDate  = BillingCycle::statementDate($cycleMonth, $statementDay);
        $paymentDueDate = BillingCycle::paymentDueDate($cycleMonth, $statementDay, $paymentDay);

        // Transactions in this billing cycle
        $transactions = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$cycleStart, $cycleEnd])
            ->get();

        $totalOutflow = $transactions->sum('amount');

        // By card
        $cards  = Card::where('user_id', $userId)->get();
        $byCard = $cards->map(function (Card $card) use ($transactions, $cycleMonth) {
            // Per-card cycle may differ if statement_days differ, but use global cycle for now
            $spent = $transactions->where('card_id', $card->id)->sum('amount');
            return [
                'card_id'         => $card->id,
                'name'            => $card->name,
                'type'            => $card->type,
                'spent'           => (float) $spent,
                'limit'           => (float) $card->credit_limit,
                'utilization_pct' => $card->credit_limit > 0
                    ? round(($spent / $card->credit_limit) * 100)
                    : null,
                'statement_day'   => $card->statement_day,
                'payment_day'     => $card->payment_day,
            ];
        })->values();

        // By category
        $categories = Category::where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        })->get()->keyBy('id');

        $budgets = Budget::where('user_id', $userId)->where('month', $cycleMonth)
            ->get()->keyBy('category_id');

        $byCategory = $transactions->groupBy('category_id')->map(function ($items, $categoryId) use ($categories, $budgets) {
            $category = $categories->get($categoryId);
            $spent    = $items->sum('amount');
            $budget   = $budgets->get($categoryId);
            return [
                'category_id' => $categoryId,
                'name'        => $category?->name ?? 'Uncategorised',
                'spent'       => (float) $spent,
                'budget'      => $budget ? (float) $budget->amount : null,
            ];
        })->values();

        // Commitments (still month-scoped — commitments are calendar-month obligations)
        $commitments       = Commitment::where('user_id', $userId)->where('month', $cycleMonth)->get();
        $commitmentsPaid   = $commitments->where('is_paid', true)->count();
        $commitmentsUnpaid = $commitments->where('is_paid', false)->count();

        $commitmentsDueSoon = $commitments->filter(function ($c) use ($today) {
            if ($c->is_paid || is_null($c->due_day)) return false;
            $diff = $c->due_day - $today;
            return $diff >= 0 && $diff <= 3;
        })->count();

        // Savings
        $totalSavings     = Saving::where('user_id', $userId)->sum('amount');
        $savingsThisMonth = Saving::where('user_id', $userId)
            ->whereRaw("to_char(date, 'YYYY-MM') = ?", [$cycleMonth])
            ->sum('amount');

        // Rule health
        $rule       = Rule::where('user_id', $userId)->first();
        $ruleHealth = null;

        if ($rule && $totalOutflow > 0) {
            $totalCommitmentsAmt = $commitments->sum('amount');
            $needsPct   = round(($totalCommitmentsAmt / $totalOutflow) * 100);
            $savingsPct = round(((float) $savingsThisMonth / $totalOutflow) * 100);
            $wantsPct   = max(0, 100 - $needsPct - $savingsPct);

            $ruleHealth = [
                'type'        => $rule->type,
                'needs_pct'   => $needsPct,
                'wants_pct'   => $wantsPct,
                'savings_pct' => $savingsPct,
            ];
        }

        return response()->json([
            'month'                => $cycleMonth,
            'cycle_start'          => $cycleStart,
            'cycle_end'            => $cycleEnd,
            'statement_date'       => $statementDate,
            'payment_due'          => $paymentDueDate,
            'total_inflow'         => 0,
            'total_outflow'        => (float) $totalOutflow,
            'net_position'         => -(float) $totalOutflow,
            'by_card'              => $byCard,
            'by_category'          => $byCategory,
            'commitments_paid'     => $commitmentsPaid,
            'commitments_unpaid'   => $commitmentsUnpaid,
            'commitments_due_soon' => $commitmentsDueSoon,
            'savings_this_month'   => (float) $savingsThisMonth,
            'total_savings'        => (float) $totalSavings,
            'rule_health'          => $ruleHealth,
        ]);
    }
}
