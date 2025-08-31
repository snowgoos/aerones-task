<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\File;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $files = [
            [
                'name' => 'output_20sec.mp4',
                'url'      => 'https://storage.googleapis.com/public_test_access_ae/output_20sec.mp4',
                'status'   => File::STATUS_PENDING,
            ],
            [
                'name' => 'output_30sec.mp4',
                'url'      => 'https://storage.googleapis.com/public_test_access_ae/output_30sec.mp4',
                'status'   => File::STATUS_PENDING,
            ],
            [
                'name' => 'output_40sec.mp4',
                'url'      => 'https://storage.googleapis.com/public_test_access_ae/output_40sec.mp4',
                'status'   => File::STATUS_PENDING,
            ],
            [
                'name' => 'output_50sec.mp4',
                'url'      => 'https://storage.googleapis.com/public_test_access_ae/output_50sec.mp4',
                'status'   => File::STATUS_PENDING,
            ],
            [
                'name' => 'output_60sec.mp4',
                'url'      => 'https://storage.googleapis.com/public_test_access_ae/output_60sec.mp4',
                'status'   => File::STATUS_PENDING,
            ],
        ];

        DB::table('files')->insert($files);
    }
}
