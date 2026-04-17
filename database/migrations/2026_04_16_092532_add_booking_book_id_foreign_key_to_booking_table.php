<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('booking') || ! Schema::hasColumn('booking', 'book_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('booking', function (Blueprint $table) {
            $table->index('book_id', 'booking_book_id_index');

            $table->foreign('book_id', 'booking_book_id_foreign')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('booking') || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('booking', function (Blueprint $table) {
            $table->dropForeign('booking_book_id_foreign');
            $table->dropIndex('booking_book_id_index');
        });
    }
};
