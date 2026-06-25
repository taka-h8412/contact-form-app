<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                '質問',
                '要望',
                '不具合報告',
                'ご意見',
                'その他',
            ]),
        ];
    }
}
