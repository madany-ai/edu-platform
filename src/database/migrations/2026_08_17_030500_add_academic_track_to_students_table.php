<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('academic_track')->nullable()->after('academic_year');
        });

        // Update existing students based on the requested logic
        $students = DB::table('students')->get();
        foreach ($students as $student) {
            $track = null;
            $prefix = '';
            
            $groupName = '';
            if ($student->group_id) {
                // If the group table exists, check the name
                if (Schema::hasTable('center_groups')) {
                    $group = DB::table('center_groups')->where('id', $student->group_id)->first();
                    if ($group) $groupName = $group->name;
                } elseif (Schema::hasTable('groups')) {
                    $group = DB::table('groups')->where('id', $student->group_id)->first();
                    if ($group) $groupName = $group->name;
                }
            }
            
            switch ($student->academic_year) {
                case 'prep_1': $prefix = '71'; break;
                case 'prep_2': $prefix = '81'; break;
                case 'prep_3': $prefix = '91'; break;
                case 'sec_1': 
                    $prefix = '12'; 
                    $track = 'general';
                    break;
                case 'sec_2': 
                    $prefix = '22'; 
                    $track = 'science';
                    break;
                case 'sec_3': 
                    if (str_contains($groupName, 'إحصاء') || str_contains($groupName, 'ادبي') || str_contains($groupName, 'أدبي')) {
                        $prefix = '32';
                        $track = 'literary';
                    } else {
                        $prefix = '31';
                        $track = 'math';
                    }
                    break;
                default: 
                    $prefix = '99'; 
                    break;
            }

            // Time based 4 digits as user requested (minutes and seconds)
            $newCode = $prefix . date('is');
            // Ensure unique student code
            while(DB::table('students')->where('student_code', $newCode)->where('id', '!=', $student->id)->exists()) {
                $newCode = $prefix . rand(1000, 9999);
            }

            DB::table('students')->where('id', $student->id)->update([
                'academic_track' => $track,
                'student_code' => $newCode
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('academic_track');
        });
    }
};
