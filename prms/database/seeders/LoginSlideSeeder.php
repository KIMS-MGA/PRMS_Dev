<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoginSlideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('login_slides')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $loginSlides = [
            [
                'id' => 1,
                'title' => 'Sample Announcement',
                'subtitle' => 'sample sub',
                'image_path' => 'login-slides/16fedc35-911c-455a-ade2-145d1a654073.png',
                'sort_order' => 5,
                'is_active' => 0,
                'created_at' => '2026-05-08 15:36:49',
                'updated_at' => '2026-05-19 16:40:44',
            ],
            [
                'id' => 4,
                'title' => 'samples',
                'subtitle' => 'sample',
                'image_path' => 'login-slides/0b88352d-1240-41c9-a139-5efb98545bc4.png',
                'sort_order' => 1,
                'is_active' => 0,
                'created_at' => '2026-05-08 17:15:19',
                'updated_at' => '2026-05-19 16:31:59',
            ],
            [
                'id' => 5,
                'title' => 'sample1',
                'subtitle' => 'sadjslkajd',
                'image_path' => 'login-slides/d3dbbfb3-05be-4ed7-b1e0-e9998cfab6df.png',
                'sort_order' => 2,
                'is_active' => 0,
                'created_at' => '2026-05-08 17:16:37',
                'updated_at' => '2026-05-19 16:32:01',
            ],
            [
                'id' => 6,
                'title' => 'pr',
                'subtitle' => null,
                'image_path' => 'login-slides/109d2bb2-4901-40c7-991e-2008a3fabefa.png',
                'sort_order' => 3,
                'is_active' => 0,
                'created_at' => '2026-05-19 16:17:01',
                'updated_at' => '2026-05-19 16:40:41',
            ],
            [
                'id' => 8,
                'title' => 'ti',
                'subtitle' => null,
                'image_path' => 'login-slides/0b69de7b-8e67-43ef-87bd-fd6cd3df131f.png',
                'sort_order' => 4,
                'is_active' => 0,
                'created_at' => '2026-05-19 16:31:30',
                'updated_at' => '2026-05-19 16:40:43',
            ],
            [
                'id' => 9,
                'title' => 'PRMS',
                'subtitle' => null,
                'image_path' => 'login-slides/bd50a086-b3ae-4bb4-ae11-16b639695614.png',
                'sort_order' => 6,
                'is_active' => 0,
                'created_at' => '2026-05-19 16:40:53',
                'updated_at' => '2026-05-25 09:03:31',
            ],
            [
                'id' => 11,
                'title' => 'PRMS1',
                'subtitle' => null,
                'image_path' => 'login-slides/d58217b7-f19d-4f28-bc6c-6683f6f3930c.png',
                'sort_order' => 7,
                'is_active' => 0,
                'created_at' => '2026-05-25 09:03:53',
                'updated_at' => '2026-05-25 09:03:53',
            ],
        ];

        DB::table('login_slides')->insert($loginSlides);
    }
}
