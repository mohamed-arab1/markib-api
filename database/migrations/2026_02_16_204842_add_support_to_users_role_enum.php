<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fixes: CHECK constraint failed: role (allow 'support' in addition to 'user','admin')
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: enum is implemented as CHECK constraint; we replace it with a string column
            DB::statement('ALTER TABLE users ADD COLUMN role_new VARCHAR(20) DEFAULT \'user\'');
            DB::statement('UPDATE users SET role_new = role');
            DB::statement('ALTER TABLE users DROP COLUMN role');
            DB::statement('ALTER TABLE users RENAME COLUMN role_new TO role');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'support') NOT NULL DEFAULT 'user'");
            return;
        }

        // Fallback (e.g. pgsql): drop and re-add as string to avoid enum limits
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE users ADD COLUMN role_old VARCHAR(20) DEFAULT \'user\'');
            DB::statement("UPDATE users SET role_old = CASE WHEN role IN ('user','admin') THEN role ELSE 'user' END");
            DB::statement('ALTER TABLE users DROP COLUMN role');
            DB::statement('ALTER TABLE users RENAME COLUMN role_old TO role');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
        }
    }
};
