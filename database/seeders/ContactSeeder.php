<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        Contact::factory(20)->create()->each(function (Contact $contact) {
            $tagIds = Tag::inRandomOrder()->take(rand(1, 3))->pluck('id');

            $contact->tags()->attach($tagIds);
        });
    }
}
