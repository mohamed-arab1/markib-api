<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserDashboardController extends Controller
{
    /**
     * Get user dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $stats = [
            'total_bookings' => $user->bookings()->count(),
            'confirmed_bookings' => $user->bookings()->where('status', 'confirmed')->count(),
            'pending_bookings' => $user->bookings()->where('status', 'pending')->count(),
            'cancelled_bookings' => $user->bookings()->where('status', 'cancelled')->count(),
            'upcoming_trips' => $user->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereHas('trip', fn ($q) => $q->where('date', '>=', now()->toDateString()))
                ->count(),
            'total_spent' => $user->bookings()
                ->whereHas('payment', fn ($q) => $q->where('status', 'completed'))
                ->with('payment')
                ->get()
                ->sum(fn ($b) => $b->payment->amount ?? 0),
        ];

        return response()->json($stats);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }
}
