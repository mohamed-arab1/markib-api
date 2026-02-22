<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Basic stats
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'total_trips' => Trip::where('status', 'scheduled')->where('date', '>=', now())->count(),
            'total_bookings' => Booking::whereIn('status', ['pending', 'confirmed'])->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'today_bookings' => Booking::whereDate('created_at', today())->count(),
            'total_locations' => Location::where('is_active', true)->count(),
        ];

        // Booking status counts
        $stats['confirmed_bookings'] = Booking::where('status', 'confirmed')->count();
        $stats['pending_bookings'] = Booking::where('status', 'pending')->count();
        $stats['cancelled_bookings'] = Booking::where('status', 'cancelled')->count();

        // Daily revenue (last 7 days)
        $stats['daily_revenue'] = $this->getDailyRevenue();

        // Weekly revenue (last 4 weeks)
        $stats['weekly_revenue'] = $this->getWeeklyRevenue();

        // Monthly revenue (last 12 months)
        $stats['monthly_revenue'] = $this->getMonthlyRevenue();

        // Yearly revenue (last 3 years)
        $stats['yearly_revenue'] = $this->getYearlyRevenue();

        // Daily bookings (last 7 days)
        $stats['daily_bookings'] = $this->getDailyBookings();

        // Weekly bookings (last 4 weeks)
        $stats['weekly_bookings'] = $this->getWeeklyBookings();

        // Monthly bookings (last 12 months)
        $stats['monthly_bookings'] = $this->getMonthlyBookings();

        // Yearly bookings (last 3 years)
        $stats['yearly_bookings'] = $this->getYearlyBookings();

        // Popular trips
        $stats['popular_trips'] = $this->getPopularTrips();

        // Popular locations
        $stats['popular_locations'] = $this->getPopularLocations();

        return response()->json($stats);
    }

    private function getDailyRevenue(): array
    {
        $days = [];
        $arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            $days[] = [
                'name' => $arabicDays[$date->dayOfWeek],
                'date' => $date->format('Y-m-d'),
                'revenue' => (float) $revenue,
            ];
        }
        
        return $days;
    }

    private function getWeeklyRevenue(): array
    {
        $weeks = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $revenue = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('amount');
            
            $weeks[] = [
                'name' => 'أسبوع ' . (4 - $i),
                'start' => $startOfWeek->format('Y-m-d'),
                'end' => $endOfWeek->format('Y-m-d'),
                'revenue' => (float) $revenue,
            ];
        }
        
        return $weeks;
    }

    private function getMonthlyRevenue(): array
    {
        $months = [];
        $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue = Payment::where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            
            $months[] = [
                'name' => $arabicMonths[$date->month - 1],
                'year' => $date->year,
                'month' => $date->month,
                'revenue' => (float) $revenue,
            ];
        }
        
        return $months;
    }

    private function getYearlyRevenue(): array
    {
        $years = [];
        
        for ($i = 2; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $revenue = Payment::where('status', 'completed')
                ->whereYear('created_at', $year)
                ->sum('amount');
            
            $years[] = [
                'name' => (string) $year,
                'year' => $year,
                'revenue' => (float) $revenue,
            ];
        }
        
        return $years;
    }

    private function getDailyBookings(): array
    {
        $days = [];
        $arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Booking::whereDate('created_at', $date)->count();
            
            $days[] = [
                'name' => $arabicDays[$date->dayOfWeek],
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        return $days;
    }

    private function getWeeklyBookings(): array
    {
        $weeks = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $count = Booking::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            
            $weeks[] = [
                'name' => 'أسبوع ' . (4 - $i),
                'count' => $count,
            ];
        }
        
        return $weeks;
    }

    private function getMonthlyBookings(): array
    {
        $months = [];
        $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = Booking::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $months[] = [
                'name' => $arabicMonths[$date->month - 1],
                'year' => $date->year,
                'month' => $date->month,
                'count' => $count,
            ];
        }
        
        return $months;
    }

    private function getYearlyBookings(): array
    {
        $years = [];
        
        for ($i = 2; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $count = Booking::whereYear('created_at', $year)->count();
            
            $years[] = [
                'name' => (string) $year,
                'year' => $year,
                'count' => $count,
            ];
        }
        
        return $years;
    }

    private function getPopularTrips(): array
    {
        return Trip::select('trips.id', 'vessels.name_ar as vessel_name', 'locations.name_ar as location_name')
            ->join('vessels', 'trips.vessel_id', '=', 'vessels.id')
            ->join('locations', 'trips.location_id', '=', 'locations.id')
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get()
            ->map(fn($trip) => [
                'name' => $trip->vessel_name . ' - ' . $trip->location_name,
                'bookings' => $trip->bookings_count,
            ])
            ->toArray();
    }

    private function getPopularLocations(): array
    {
        return Location::select('id', 'name_ar')
            ->withCount(['trips as bookings_count' => function($query) {
                $query->join('bookings', 'bookings.trip_id', '=', 'trips.id');
            }])
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get()
            ->map(fn($location) => [
                'name' => $location->name_ar,
                'bookings' => $location->bookings_count,
            ])
            ->toArray();
    }
}
