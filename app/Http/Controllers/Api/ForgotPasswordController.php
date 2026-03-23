<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Send OTP code to user's email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'البريد الإلكتروني المدخل غير مسجل لدينا.',
        ]);

        $email = $request->email;
        $otp = rand(100000, 999999);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Save new OTP code
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($otp),
            'created_at' => Carbon::now()
        ]);

        // Send Email
        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send forgot-password OTP email.', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء إرسال البريد الإلكتروني. يرجى المحاولة لاحقاً.'
            ], 500);
        }

        return response()->json([
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.'
        ]);
    }

    /**
     * Verify the received OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:6',
        ], [
            'email.exists' => 'البريد الإلكتروني المدخل غير مسجل.',
            'otp.digits' => 'رمز التحقق يجب أن يكون 6 أرقام.'
        ]);

        $resetRow = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRow) {
            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق غير موجود أو غير صالح.'],
            ]);
        }

        // Check token expiration (e.g. 10 minutes)
        if (Carbon::parse($resetRow->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق منتهي الصلاحية.'],
            ]);
        }

        if (!Hash::check($request->otp, $resetRow->token)) {
            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        return response()->json([
            'message' => 'تم التحقق بنجاح. يمكنك الآن تغيير كلمة المرور.'
        ]);
    }

    /**
     * Reset the password using email, valid OTP and new password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.exists' => 'البريد الإلكتروني المدخل غير مسجل.',
            'otp.digits' => 'رمز التحقق يجب أن يكون 6 أرقام.'
        ]);

        $resetRow = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRow || !Hash::check($request->otp, $resetRow->token)) {
            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق غير صحيح أو غير صالح.'],
            ]);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.'
        ]);
    }
}
