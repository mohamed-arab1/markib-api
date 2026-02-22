<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // فريق الدعم يرى فقط العملاء (user) وموظفي الدعم (support) - لا يرى قائمة المدراء
        if (auth()->user()->role === 'support') {
            $query->whereIn('role', ['user', 'support']);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'bookings.trip.vessel', 
            'bookings.trip.location',
            'bookings.payment',
            'supportTickets',
            'reviews.trip.vessel',
        ]);
        $user->loadCount(['bookings']);

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:user,admin,support'],
        ]);

        // فريق الدعم لا يمكنه إضافة مدير - المدير فقط من يضيف مدراء
        if (auth()->user()->role === 'support' && $validated['role'] === 'admin') {
            return response()->json(['message' => 'غير مصرح - لا يمكن لفريق الدعم إضافة مدير'], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'تم إنشاء المستخدم بنجاح',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'in:user,admin,support'],
        ]);

        // فريق الدعم لا يمكنه تعيين أو تغيير دور أي مستخدم إلى مدير
        if (auth()->user()->role === 'support') {
            if (isset($validated['role']) && $validated['role'] === 'admin') {
                return response()->json(['message' => 'غير مصرح - لا يمكن لفريق الدعم تعيين مدير'], 403);
            }
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك'], 422);
        }

        // فريق الدعم لا يمكنه حذف حساب مدير
        if (auth()->user()->role === 'support' && $user->role === 'admin') {
            return response()->json(['message' => 'غير مصرح - لا يمكن لفريق الدعم حذف حساب مدير'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف المستخدم بنجاح']);
    }

    public function loginLogs(Request $request): JsonResponse
    {
        $query = LoginLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('logged_in_at', 'desc')->paginate(30);

        return response()->json($logs);
    }
}
