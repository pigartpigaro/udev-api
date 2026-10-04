<?php

namespace Database\Seeders;

use App\Models\Master\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Aplikasi Web', 'Pengembangan aplikasi yang berjalan melalui browser web.'],
            ['Aplikasi Mobile', 'Pengembangan aplikasi untuk perangkat Android atau iOS.'],
            ['Aplikasi Desktop', 'Pengembangan aplikasi yang dipasang dan berjalan di komputer.'],
            ['Pengembangan Aplikasi Lainnya', 'Pengembangan aplikasi yang tidak termasuk kategori web, mobile, atau desktop.'],
        ] as [$name, $description]) {
            ProjectType::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true],
            );
        }
    }
}
