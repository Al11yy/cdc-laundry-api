<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    // 1. Endpoint Laporan Pendapatan
    public function income()
    {
        // Menghitung total sum(total_price) dari transaksi berstatus paid
        $totalIncome = Transaction::where('payment_status', 'paid')->sum('total_price');
        
        // Pendapatan bulan ini
        $monthlyIncome = Transaction::where('payment_status', 'paid')
                            ->whereMonth('paid_at', Carbon::now()->month)
                            ->whereYear('paid_at', Carbon::now()->year)
                            ->sum('total_price');

        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => $totalIncome,
                'monthly_income' => $monthlyIncome
            ]
        ]);
    }

    // 2. Endpoint Statistik Transaksi
    public function statistics()
    {
        // Menghitung jumlah transaksi per hari di bulan berjalan
        $dailyTransactions = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total_transactions')
            )
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Menghitung jumlah transaksi per bulan di tahun berjalan
        $monthlyTransactions = Transaction::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as total_transactions')
            )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

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