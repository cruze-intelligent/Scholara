<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_administrations', function (Blueprint $table) {
            $table->string('route')->nullable()->after('dose');
            $table->timestamp('scheduled_time')->nullable()->after('administered_at');
            $table->boolean('checked_right_patient')->default(false)->after('scheduled_time');
            $table->boolean('checked_right_drug')->default(false)->after('checked_right_patient');
            $table->boolean('checked_right_dose')->default(false)->after('checked_right_drug');
            $table->boolean('checked_right_route')->default(false)->after('checked_right_dose');
            $table->boolean('checked_right_time')->default(false)->after('checked_right_route');
            $table->dropColumn('five_rights_checked');
        });
    }

    public function down(): void
    {
        Schema::table('medication_administrations', function (Blueprint $table) {
            $table->boolean('five_rights_checked')->default(false);
            $table->dropColumn([
                'route', 'scheduled_time', 'checked_right_patient', 'checked_right_drug',
                'checked_right_dose', 'checked_right_route', 'checked_right_time',
            ]);
        });
    }
};
