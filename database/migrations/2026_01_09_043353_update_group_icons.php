<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('groups')->where('name', 'Dubbo trip')->update(['icon' => '✈️']);
        DB::table('groups')->where('name', 'Inner peace ✌️')->update(['icon' => '🧘']);
        DB::table('groups')->where('name', 'All in all')->update(['icon' => '🎉']);
        DB::table('groups')->where('name', 'Pendle hill bill')->update(['icon' => '🥩']);
        DB::table('groups')->where('name', 'Home exp')->update(['icon' => '🏠']);
        DB::table('groups')->where('name', 'Sabarimala')->update(['icon' => '🏛️']);
        DB::table('groups')->where('name', 'Nishanth purchase')->update(['icon' => '🛍️']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to default celebration emoji
        DB::table('groups')->whereIn('name', [
            'Dubbo trip',
            'Inner peace ✌️',
            'All in all',
            'Pendle hill bill',
            'Home exp',
            'Sabarimala',
            'Nishanth purchase',
        ])->update(['icon' => '🎉']);
    }
};
