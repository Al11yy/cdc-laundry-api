<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // 1. Endpoint Laporan Pendapatan
    public function income(Request $request)
    {
        $filter = $request->query('filter');

        $query = Transaction::where('payment_status', 'paid');

        if ($filter === 'hari-ini') {
            $query->whereDate('paid_at', Carbon::today());
        } elseif ($filter === 'minggu-ini') {
            $query->whereBetween('paid_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'bulan-ini') {
            $query->whereMonth('paid_at', Carbon::now()->month)
                  ->whereYear('paid_at', Carbon::now()->year);
        }
        // Jika filter kosong, null, atau sepanjang-masa, tidak difilter (semua data historis)

        $totalIncome = $query->sum('total_price');
        
        // Pendapatan bulan ini tetap dihitung berdasarkan bulan berjalan sebagai baseline operasional
        $monthlyIncome = Transaction::where('payment_status', 'paid')
                            ->whereMonth('paid_at', Carbon::now()->month)
                            ->whereYear('paid_at', Carbon::now()->year)
                            ->sum('total_price');

        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => (float)$totalIncome,
                'monthly_income' => (float)$monthlyIncome
            ]
        ]);
    }

    // 2. Endpoint Statistik Transaksi
    public function statistics(Request $request)
    {
        $filter = $request->query('filter');

        $dailyQuery = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total_transactions')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc');

        $monthlyQuery = Transaction::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as total_transactions')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc');

        if ($filter === 'hari-ini') {
            $dailyQuery->whereDate('created_at', Carbon::today());
            $monthlyQuery->whereMonth('created_at', Carbon::now()->month)
                         ->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'minggu-ini') {
            $dailyQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            $monthlyQuery->whereMonth('created_at', Carbon::now()->month)
                         ->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'bulan-ini') {
            $dailyQuery->whereMonth('created_at', Carbon::now()->month)
                       ->whereYear('created_at', Carbon::now()->year);
            $monthlyQuery->whereMonth('created_at', Carbon::now()->month)
                         ->whereYear('created_at', Carbon::now()->year);
        } else {
            // Sepanjang masa: tampilkan tahun berjalan sebagai filter default statistik chart harian/bulanan
            $dailyQuery->whereYear('created_at', Carbon::now()->year);
            $monthlyQuery->whereYear('created_at', Carbon::now()->year);
        }

        $dailyTransactions = $dailyQuery->get();
        $monthlyTransactions = $monthlyQuery->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_stats' => $dailyTransactions,
                'monthly_stats' => $monthlyTransactions,
                'total_all_time' => Transaction::count()
            ]
        ]);
    }
}