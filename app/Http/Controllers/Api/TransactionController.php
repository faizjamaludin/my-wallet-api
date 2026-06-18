<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\BillingCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    /**
     * Resolve the card for a given user + optional card_id.
     */
    private function resolveCard(int $userId, ?int $cardId): ?Card
    {
        if ($cardId) {
            return Card::where('id', $cardId)->where('user_id', $userId)->first();
        }
        return null;
    }

    /**
     * Resolve the statement_day to use for a given user + optional card_id.
     * Falls back to the user's first credit card, then to 18.
     */
    private function resolveStatementDay(int $userId, ?int $cardId): int
    {
        $card = $this->resolveCard($userId, $cardId);
        if ($card) return $card->statement_day;
        $firstCC = Card::where('user_id', $userId)->where('type', 'credit')->orderBy('created_at')->first();
        return $firstCC?->statement_day ?? 18;
    }

    /**
     * Whether a specific card is a debit card.
     */
    private function isDebitCard(int $userId, ?int $cardId): bool
    {
        if (!$cardId) return false;
        $card = $this->resolveCard($userId, $cardId);
        return $card?->type === 'debit';
    }

    /**
     * Compute the month string to store for a transaction.
     * Debit cards: calendar month (YYYY-MM of the date).
     * Credit cards / no card: billing cycle month.
     */
    private function computeMonth(string $date, int $userId, ?int $cardId): string
    {
        if ($this->isDebitCard($userId, $cardId)) {
            return substr($date, 0, 7); // YYYY-MM
        }
        $statementDay = $this->resolveStatementDay($userId, $cardId);
        return BillingCycle::cycleMonthFor($date, $statementDay);
    }

    public function index(Request $request)
    {
        $userId  = $request->user()->id;
        $cardId  = $request->query('card_id') ? (int) $request->query('card_id') : null;

        // Debit cards use calendar month; credit/no-card use billing cycle
        if ($this->isDebitCard($userId, $cardId)) {
            $defaultMonth = date('Y-m');
            $month        = $request->query('month', $defaultMonth);
            [$cycleStart, $cycleEnd] = BillingCycle::calendarDateRange($month);
        } else {
            $statementDay = $this->resolveStatementDay($userId, $cardId);
            $month        = $request->query('month', BillingCycle::currentCycleMonth($statementDay));
            [$cycleStart, $cycleEnd] = BillingCycle::dateRange($month, $statementDay);
        }

        $query = Transaction::with(['card', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$cycleStart, $cycleEnd]);

        if ($cardId) {
            $query->where('card_id', $cardId);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $view         = $request->query('view', 'monthly');
        $transactions = $query->orderByDesc('date')->orderByDesc('created_at')->get();

        if ($view === 'daily') {
            $grouped = $transactions->groupBy(fn ($t) => $t->date->format('Y-m-d'));
            return response()->json($grouped);
        }

        if ($view === 'weekly') {
            $grouped = $transactions->groupBy(fn ($t) => 'Week ' . $t->date->weekOfMonth);
            return response()->json($grouped);
        }

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'card_id'     => 'nullable|exists:cards,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date_format:Y-m-d',
            'description' => 'nullable|string|max:255',
            'merchant'    => 'nullable|string|max:100',
        ]);

        // Verify card belongs to this user
        if (!empty($data['card_id'])) {
            abort_unless(
                Card::where('id', $data['card_id'])->where('user_id', $userId)->exists(),
                403, 'Card does not belong to you'
            );
        }

        // Verify category is preset (user_id = null) or owned by this user
        if (!empty($data['category_id'])) {
            abort_unless(
                Category::where('id', $data['category_id'])
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
                    ->exists(),
                403, 'Category does not belong to you'
            );
        }

        $data['user_id'] = $userId;

        $data['month'] = $this->computeMonth($data['date'], $data['user_id'], $data['card_id'] ?? null);

        $transaction = Transaction::create($data);
        $transaction->load(['card', 'category']);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $userId = $request->user()->id;
        abort_unless($transaction->user_id === $userId, 403);

        $data = $request->validate([
            'card_id'     => 'nullable|exists:cards,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount'      => 'sometimes|numeric|min:0.01',
            'date'        => 'sometimes|date_format:Y-m-d',
            'description' => 'nullable|string|max:255',
            'merchant'    => 'nullable|string|max:100',
        ]);

        // Verify card belongs to this user
        if (!empty($data['card_id'])) {
            abort_unless(
                Card::where('id', $data['card_id'])->where('user_id', $userId)->exists(),
                403, 'Card does not belong to you'
            );
        }

        // Verify category is preset or owned by this user
        if (!empty($data['category_id'])) {
            abort_unless(
                Category::where('id', $data['category_id'])
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
                    ->exists(),
                403, 'Category does not belong to you'
            );
        }

        if (isset($data['date'])) {
            $cardId        = $data['card_id'] ?? $transaction->card_id;
            $data['month'] = $this->computeMonth($data['date'], $transaction->user_id, $cardId);
        }

        $transaction->update($data);
        $transaction->load(['card', 'category']);

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->delete(); // soft delete

        return response()->json(['message' => 'Deleted']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'    => 'required|file|mimes:csv,txt|max:5120', // 5 MB
            'card_id' => 'nullable|exists:cards,id',
        ]);

        $userId  = $request->user()->id;
        $cardId  = $request->input('card_id') ? (int) $request->input('card_id') : null;

        // Verify card belongs to this user
        if ($cardId) {
            abort_unless(
                Card::where('id', $cardId)->where('user_id', $userId)->exists(),
                403, 'Card does not belong to you'
            );
        }
        $file    = $request->file('file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $imported     = 0;
        $skipped      = 0;
        $errors       = [];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $line = array_combine($headers, $row);

                if (empty($line['date']) || empty($line['amount'])) {
                    $skipped++;
                    continue;
                }

                // Resolve category by name (fallback to Other)
                $categoryName = trim($line['category'] ?? 'Other');
                $category = Category::whereNull('user_id')
                    ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                    ->first()
                    ?? Category::whereNull('user_id')->where('slug', 'other')->first();

                $dateStr = Carbon::parse($line['date'])->format('Y-m-d');

                Transaction::create([
                    'user_id'     => $userId,
                    'card_id'     => $cardId,
                    'category_id' => $category?->id,
                    'amount'      => (float) str_replace(',', '', $line['amount']),
                    'date'        => $dateStr,
                    'month'       => $this->computeMonth($dateStr, $userId, $cardId),
                    'description' => trim($line['description'] ?? ''),
                    'merchant'    => trim($line['merchant'] ?? ''),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        fclose($handle);

        return response()->json(['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
    }
}
