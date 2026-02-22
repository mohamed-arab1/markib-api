<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Policy;
use App\Models\User;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Vessel;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        // Return flat key-value pairs for the frontend
        $settings = Setting::all()->mapWithKeys(function ($setting) {
            $value = match ($setting->type) {
                'boolean' => (bool) $setting->value,
                'integer' => (int) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
            return [$setting->key => $value];
        });

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = $request->all();

        // If old format (with 'settings' key)
        if (isset($settings['settings'])) {
            foreach ($settings['settings'] as $setting) {
                Setting::set(
                    $setting['key'],
                    $setting['value'],
                    $setting['type'] ?? 'string',
                    $setting['group'] ?? 'general'
                );
            }
            return response()->json(['message' => 'تم حفظ الإعدادات']);
        }

        // New flat format
        $settingTypes = [
            // General
            'site_name' => 'string',
            'site_name_en' => 'string',
            'site_description' => 'string',
            'contact_email' => 'string',
            'contact_phone' => 'string',
            'contact_address' => 'string',
            
            // Booking
            'max_passengers_per_booking' => 'integer',
            'min_booking_hours_advance' => 'integer',
            'cancellation_hours_before' => 'integer',
            'cancellation_fee_percentage' => 'integer',
            'allow_same_day_booking' => 'boolean',
            'require_phone_verification' => 'boolean',
            'auto_confirm_bookings' => 'boolean',
            
            // Payment
            'currency' => 'string',
            'enable_online_payment' => 'boolean',
            'enable_cash_payment' => 'boolean',
            'deposit_percentage' => 'integer',
            
            // Notifications
            'send_booking_confirmation_email' => 'boolean',
            'send_booking_reminder_email' => 'boolean',
            'reminder_hours_before' => 'integer',
            'send_cancellation_email' => 'boolean',
            'admin_email_notifications' => 'boolean',
            
            // Maintenance
            'maintenance_mode' => 'boolean',
            'maintenance_message' => 'string',
        ];

        $settingGroups = [
            'site_name' => 'general',
            'site_name_en' => 'general',
            'site_description' => 'general',
            'contact_email' => 'general',
            'contact_phone' => 'general',
            'contact_address' => 'general',
            
            'max_passengers_per_booking' => 'booking',
            'min_booking_hours_advance' => 'booking',
            'cancellation_hours_before' => 'booking',
            'cancellation_fee_percentage' => 'booking',
            'allow_same_day_booking' => 'booking',
            'require_phone_verification' => 'booking',
            'auto_confirm_bookings' => 'booking',
            
            'currency' => 'payment',
            'enable_online_payment' => 'payment',
            'enable_cash_payment' => 'payment',
            'deposit_percentage' => 'payment',
            
            'send_booking_confirmation_email' => 'notifications',
            'send_booking_reminder_email' => 'notifications',
            'reminder_hours_before' => 'notifications',
            'send_cancellation_email' => 'notifications',
            'admin_email_notifications' => 'notifications',
            
            'maintenance_mode' => 'maintenance',
            'maintenance_message' => 'maintenance',
        ];

        foreach ($settings as $key => $value) {
            if (isset($settingTypes[$key])) {
                Setting::set(
                    $key,
                    $value,
                    $settingTypes[$key],
                    $settingGroups[$key] ?? 'general'
                );
            }
        }

        return response()->json(['message' => 'تم حفظ الإعدادات بنجاح']);
    }

    public function clearCache(): JsonResponse
    {
        Cache::flush();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');

        return response()->json(['message' => 'تم مسح الكاش بنجاح']);
    }

    public function policies(): JsonResponse
    {
        $policies = Policy::orderBy('created_at')->get();

        return response()->json($policies);
    }

    public function storePolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'unique:policies'],
            'title' => ['required', 'string'],
            'title_ar' => ['required', 'string'],
            'content' => ['required', 'string'],
            'content_ar' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $policy = Policy::create($validated);

        return response()->json([
            'message' => 'تم إنشاء السياسة',
            'policy' => $policy,
        ], 201);
    }

    public function updatePolicy(Request $request, Policy $policy): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string'],
            'title_ar' => ['sometimes', 'string'],
            'content' => ['sometimes', 'string'],
            'content_ar' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $policy->update($validated);

        return response()->json([
            'message' => 'تم تحديث السياسة',
            'policy' => $policy->fresh(),
        ]);
    }

    public function deletePolicy(Policy $policy): JsonResponse
    {
        $policy->delete();

        return response()->json(['message' => 'تم حذف السياسة']);
    }

    /**
     * Get system health status
     */
    public function systemHealth(): JsonResponse
    {
        $health = [
            'database' => $this->checkDatabaseConnection(),
            'storage' => $this->checkStorageWritable(),
            'cache' => $this->checkCacheWorking(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'disk_free_space' => $this->formatBytes(disk_free_space(base_path())),
            'disk_total_space' => $this->formatBytes(disk_total_space(base_path())),
            'disk_usage_percent' => round((1 - disk_free_space(base_path()) / disk_total_space(base_path())) * 100, 2),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'uptime' => $this->getServerUptime(),
            'last_backup' => $this->getLastBackupTime(),
        ];

        $allHealthy = $health['database']['status'] === 'ok' 
            && $health['storage']['status'] === 'ok' 
            && $health['cache']['status'] === 'ok';

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'issues',
            'checks' => $health,
        ]);
    }

    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $tables = DB::select('SHOW TABLES');
            return [
                'status' => 'ok',
                'message' => 'قاعدة البيانات متصلة',
                'tables_count' => count($tables),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ];
        }
    }

    private function checkStorageWritable(): array
    {
        $storagePath = storage_path('app');
        if (is_writable($storagePath)) {
            return [
                'status' => 'ok',
                'message' => 'مجلد التخزين قابل للكتابة',
            ];
        }
        return [
            'status' => 'error',
            'message' => 'مجلد التخزين غير قابل للكتابة',
        ];
    }

    private function checkCacheWorking(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value === 'test') {
                return [
                    'status' => 'ok',
                    'message' => 'الكاش يعمل بشكل صحيح',
                    'driver' => config('cache.default'),
                ];
            }
            return [
                'status' => 'error',
                'message' => 'الكاش لا يعمل بشكل صحيح',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في الكاش: ' . $e->getMessage(),
            ];
        }
    }

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getServerUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (int) explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                return "{$days} يوم، {$hours} ساعة، {$minutes} دقيقة";
            }
        }
        return 'غير متاح';
    }

    private function getLastBackupTime(): ?string
    {
        $backupPath = storage_path('app/backups');
        if (!File::exists($backupPath)) {
            return null;
        }
        
        $files = File::files($backupPath);
        if (empty($files)) {
            return null;
        }
        
        $lastModified = 0;
        foreach ($files as $file) {
            if ($file->getMTime() > $lastModified) {
                $lastModified = $file->getMTime();
            }
        }
        
        return $lastModified ? Carbon::createFromTimestamp($lastModified)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Create database backup
     */
    public function backupDatabase(): JsonResponse
    {
        try {
            $backupPath = storage_path('app/backups');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupPath . '/' . $filename;

            // Get database config
            $host = config('database.connections.mysql.host');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Export using mysqldump
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !File::exists($filepath) || File::size($filepath) === 0) {
                // Fallback: Create simple JSON backup
                $backup = [
                    'created_at' => now()->toIso8601String(),
                    'users_count' => User::count(),
                    'bookings_count' => Booking::count(),
                    'trips_count' => Trip::count(),
                    'vessels_count' => Vessel::count(),
                    'locations_count' => Location::count(),
                    'settings' => Setting::all()->toArray(),
                ];
                
                $jsonFilepath = $backupPath . '/backup_' . date('Y-m-d_H-i-s') . '.json';
                File::put($jsonFilepath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                return response()->json([
                    'message' => 'تم إنشاء نسخة احتياطية (JSON)',
                    'filename' => basename($jsonFilepath),
                    'size' => $this->formatBytes(File::size($jsonFilepath)),
                ]);
            }

            return response()->json([
                'message' => 'تم إنشاء نسخة احتياطية بنجاح',
                'filename' => $filename,
                'size' => $this->formatBytes(File::size($filepath)),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء النسخة الاحتياطية',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase(): JsonResponse
    {
        try {
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database');
            $tableKey = 'Tables_in_' . $dbName;
            
            $optimized = [];
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimized[] = $tableName;
            }

            // Clear query log
            DB::flushQueryLog();

            return response()->json([
                'message' => 'تم تحسين قاعدة البيانات بنجاح',
                'tables_optimized' => count($optimized),
                'tables' => $optimized,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تحسين قاعدة البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system logs
     */
    public function getLogs(Request $request): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return response()->json([
                'logs' => [],
                'message' => 'لا توجد سجلات',
            ]);
        }

        $lines = $request->get('lines', 100);
        
        // Read last N lines
        $content = '';
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        $logs = [];
        while (!$file->eof()) {
            $line = $file->fgets();
            if (trim($line)) {
                $logs[] = $line;
            }
        }

        return response()->json([
            'logs' => array_reverse($logs),
            'total_lines' => $totalLines,
            'file_size' => $this->formatBytes(File::size($logFile)),
        ]);
    }

    /**
     * Clear system logs
     */
    public function clearLogs(): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return response()->json([
            'message' => 'تم مسح السجلات بنجاح',
        ]);
    }

    /**
     * Send test email
     */
    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $siteName = Setting::get('site_name', 'Markeb');
            
            Mail::raw("هذا بريد اختباري من {$siteName}. إذا وصلتك هذه الرسالة، فإن إعدادات البريد تعمل بشكل صحيح.", function ($message) use ($request, $siteName) {
                $message->to($request->email)
                    ->subject("اختبار البريد - {$siteName}");
            });

            return response()->json([
                'message' => 'تم إرسال البريد الاختباري بنجاح',
                'sent_to' => $request->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل إرسال البريد',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database statistics
     */
    public function databaseStats(): JsonResponse
    {
        $stats = [
            'users' => User::count(),
            'bookings' => Booking::count(),
            'trips' => Trip::count(),
            'vessels' => Vessel::count(),
            'locations' => Location::count(),
            'settings' => Setting::count(),
            'policies' => Policy::count(),
            'recent_bookings' => Booking::where('created_at', '>=', now()->subDays(7))->count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
        ];

        // Get database size
        $dbName = config('database.connections.mysql.database');
        $result = DB::select("SELECT 
            SUM(data_length + index_length) as size 
            FROM information_schema.tables 
            WHERE table_schema = ?", [$dbName]);
        
        $stats['database_size'] = $this->formatBytes($result[0]->size ?? 0);

        return response()->json($stats);
    }

    /**
     * Get list of backups
     */
    public function listBackups(): JsonResponse
    {
        $backupPath = storage_path('app/backups');
        
        if (!File::exists($backupPath)) {
            return response()->json(['backups' => []]);
        }

        $files = File::files($backupPath);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
            ];
        }

        // Sort by date descending
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return response()->json(['backups' => $backups]);
    }

    /**
     * Download backup file
     */
    public function downloadBackup(string $filename)
    {
        $filepath = storage_path('app/backups/' . $filename);
        
        if (!File::exists($filepath)) {
            return response()->json(['message' => 'الملف غير موجود'], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Delete backup file
     */
    public function deleteBackup(string $filename): JsonResponse
    {
        $filepath = storage_path('app/backups/' . $filename);
        
        if (!File::exists($filepath)) {
            return response()->json(['message' => 'الملف غير موجود'], 404);
        }

        File::delete($filepath);

        return response()->json(['message' => 'تم حذف النسخة الاحتياطية']);
    }
}
