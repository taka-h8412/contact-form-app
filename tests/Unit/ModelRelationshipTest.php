<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// モデル間のリレーションで、実際にデータを取得・同期できることを確認するテスト
class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | カテゴリ関係
    |--------------------------------------------------------------------------
    */

    // 1つのカテゴリから、紐づく複数のお問い合わせを取得できることを確認
    public function test_category_can_retrieve_multiple_contacts(): void
    {
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        Contact::factory()->count(2)->create([
            'category_id' => $category->id,
        ]);

        $this->assertCount(2, $category->contacts);
    }

    /*
    |--------------------------------------------------------------------------
    | お問い合わせ関係
    |--------------------------------------------------------------------------
    */

    // 1つのお問い合わせが特定のカテゴリに属していることを確認
    public function test_contact_can_retrieve_its_category(): void
    {
        $category = Category::create([
            'content' => '商品トラブル',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertSame('商品トラブル', $contact->category->content);
    }

    // 1つのお問い合わせに複数のタグをsyncできることを確認
    public function test_contact_can_sync_multiple_tags(): void
    {
        $category = Category::create([
            'content' => 'その他',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $tagA = Tag::create([
            'name' => '質問',
        ]);

        $tagB = Tag::create([
            'name' => '要望',
        ]);

        $contact->tags()->sync([
            $tagA->id,
            $tagB->id,
        ]);

        $this->assertCount(2, $contact->fresh()->tags);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tagA->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tagB->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | タグ関係
    |--------------------------------------------------------------------------
    */

    // 1つのタグが中間テーブルを介して複数のお問い合わせに紐づくことを確認
    public function test_tag_can_retrieve_multiple_contacts(): void
    {
        $category = Category::create([
            'content' => 'その他',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $contactA = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $contactB = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $tag->contacts()->attach([
            $contactA->id,
            $contactB->id,
        ]);

        $this->assertCount(2, $tag->contacts);
    }
}
