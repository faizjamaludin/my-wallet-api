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
     * Resolve the statement_day to use for a given user + optional card_id.
     * Falls back to the user's first credit card, then to 18.
     */
    private function resolveStatementDay(int $userId, ?int $cardId): int
    {
        if ($cardId) {
            $card = Card::where('id', $cardId)->where('user_id', $userId)->first();
            if ($card) return $card->statement_day;
        }
        $firstCC = Card::where('user_id', $userId)->where('type', 'credit')->orderBy('created_at')->first();
        return $firstCC?->statement_day ?? 18;
    }

    public function index(Request $request)
    {
        $userId  = $request->user()->id;
        $cardId  = $request->query('card_id') ? (int) $request->query('card_id') : null;

        $statementDay = $this->resolveStatementDay($userId, $cardId);
        $cycleMonth   = $request->query('month', BillingCycle::currentCycleMonth($statementDay));

        [$cycleStart, $cycleEnd] = BillingCycle::dateRange($cycleMonth, $statementDay);

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
        $data = $request->validate([
            'card_id'     => 'nullable|exists:cards,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date_format:Y-m-d',
            'description' => 'nullable|string|max:255',
            'merchant'    => 'nullable|string|max:100',
        ]);

        $data['user_id'] = $request->user()->id;

        $statementDay  = $this->resolveStatementDay($data['user_id'], $data['card_id'] ?? null);
        $data['month'] = BillingCycle::cycleMonthFor($data['date'], $statementDay);

        $transaction = Transaction::create($data);
        $transaction->load(['card', 'category']);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'card_id'     => 'nullable|exists:cards,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount'      => 'sometimes|numeric|min:0.01',
            'date'        => 'sometimes|date_format:Y-m-d',
            'description' => 'nullable|string|max:255',
            'merchant'    => 'nullable|string|max:100',
        ]);

        if (isset($data['date'])) {
            $cardId        = $data['card_id'] ?? $transaction->card_id;
            $statementDay  = $this->resolveStatementDay($transaction->user_id, $cardId);
            $data['month'] = BillingCycle::cycleMonthFor($data['date'], $statementDay);
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
        $file    = $request->file('file');
        $handle  = fopen($file->getRealPath(), 'r');

        $statementDay = $this->resolveStatementDay($userId, $cardId);
        $headers      = array_map('strtolower', array_map('trim', fgetcsv($handle)));
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
                    'month'       => BillingCycle::cycleMonthFor($dateStr, $statementDay),
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
