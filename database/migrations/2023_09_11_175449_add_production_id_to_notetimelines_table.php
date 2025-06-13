 <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notetimelines', function (Blueprint $table) {
            $table->unsignedBigInteger('production_id')->nullable();
            $table->timestamp('return_stop')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notetimelines', function (Blueprint $table) {
            $table->dropColumn('production_id', 'return_stop');
        });
    }
};
