<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    public function definition()
    {
        return [
            'attendance_id'    => Attendance::factory(),
            'user_id'          => User::factory(),
            'requested_in'     => '09:00:00',
            'requested_out'    => '17:00:00',
            'requested_breaks' => [],
            'comment'          => $this->faker->sentence,
            'status'           => CorrectionRequest::STATUS_PENDING,
        ];
    }
}
