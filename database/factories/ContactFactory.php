<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create('ja_JP');

        $categoryId = fake()->numberBetween(1, 5);

        $details = [
            1 => '商品がまだ届いていないため、配送状況を確認したいです。',
            2 => '商品のサイズが合わなかったため、交換を希望します。',
            3 => '商品に破損があったため、返品または返金対応をお願いします。',
            4 => 'ショップの営業時間について教えてください。',
            5 => 'その他の内容について問い合わせです。',
        ];

        $buildings = [
            null,
            'テストマンション101',
            'テストビル202',
            'テスト団地303',
            'テストアパート405',
        ];

        return [
            'category_id' => $categoryId,
            'first_name' => $faker->lastName(),
            'last_name' => $faker->firstName(),
            'gender' => $faker->numberBetween(1, 3),
            'email' => $faker->unique()->safeEmail(),
            'tel' => '090'.$faker->numberBetween(10000000, 99999999),
            'address' => $faker->prefecture().$faker->city().$faker->streetAddress(),
            'building' => $faker->randomElement($buildings),
            'detail' => $details[$categoryId],
        ];
    }
}
