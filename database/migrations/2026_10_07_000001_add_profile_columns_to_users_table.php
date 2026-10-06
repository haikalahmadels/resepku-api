<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data profil yang tampil di halaman Profil aplikasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('address');
            // Path foto relatif terhadap folder public, mis. "images/profiles/sari.jpg".
            $table->string('photo')->nullable()->after('birth_date');
            // Indeks karakter CuteAvatarData.presets yang dipilih pengguna.
            $table->unsignedTinyInteger('avatar_index')->default(0)->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'birth_date', 'photo', 'avatar_index']);
        });
    }
};
