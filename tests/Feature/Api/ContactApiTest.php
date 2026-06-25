<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $content = '商品のお届けについて'): Category
    {
        return Category::create([
            'content' => $content,
        ]);
    }

    private function createTag(string $name = '質問'): Tag
    {
        return Tag::create([
            'name' => $name,
        ]);
    }

    // 正常問い合わせデータ一式
    private function validContactPayload(array $overrides = []): array
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        return array_merge([
            'first_name' => 'API',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'api@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => 'APIテストのお問い合わせ内容です。',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | AP1: お問い合わせ一覧取得API
    |--------------------------------------------------------------------------
    */

    public function test_contacts_can_list(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category',
                        'first_name',
                        'last_name',
                        'gender',
                        'email',
                        'tel',
                        'address',
                        'building',
                        'detail',
                        'tags',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.first_name', '山田')
            ->assertJsonPath('data.0.category.content', '商品のお届けについて')
            ->assertJsonPath('data.0.tags.0.name', '質問');
    }

    public function test_contacts_can_filter_keyword_gender_category_date(): void
    {
        $categoryA = $this->createCategory('商品のお届けについて');
        $categoryB = $this->createCategory('その他');

        Contact::factory()->create([
            'category_id' => $categoryA->id,
            'first_name' => '検索',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'search@example.com',
            'created_at' => '2026-06-22 10:00:00',
        ]);

        Contact::factory()->create([
            'category_id' => $categoryB->id,
            'first_name' => '対象外',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'other@example.com',
            'created_at' => '2026-06-21 10:00:00',
        ]);

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => '検索',
            'gender' => 1,
            'category_id' => $categoryA->id,
            'date' => '2026-06-22',
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', '検索')
            ->assertJsonPath('data.0.email', 'search@example.com');
    }

    public function test_contacts_index_return_422_when_gender_invalid(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender'])
            ->assertJsonPath('errors.gender.0', '性別の値が不正です');
    }

    public function test_contacts_index_return_422_when_category_not_exist(): void
    {
        $response = $this->getJson('/api/v1/contacts?category_id=999999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id'])
            ->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
    }

    public function test_contacts_can_paginate(): void
    {
        $category = $this->createCategory();

        Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/contacts?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    /*
    |--------------------------------------------------------------------------
    | AP2: お問い合わせ詳細取得API
    |--------------------------------------------------------------------------
    */

    public function test_contact_can_show(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '詳細',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'show@example.com',
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->getJson('/api/v1/contacts/'.$contact->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '詳細')
            ->assertJsonPath('data.last_name', '太郎')
            ->assertJsonPath('data.email', 'show@example.com')
            ->assertJsonPath('data.category.content', '商品のお届けについて')
            ->assertJsonPath('data.tags.0.name', '質問');
    }

    public function test_contact_show_return_404_when_contact_not_exist(): void
    {
        $response = $this->getJson('/api/v1/contacts/999999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'お問い合わせが見つかりませんでした。',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AP3: お問い合わせ作成API
    |--------------------------------------------------------------------------
    */

    public function test_contact_can_create(): void
    {
        $payload = $this->validContactPayload();

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.first_name', 'API')
            ->assertJsonPath('data.last_name', '太郎')
            ->assertJsonPath('data.email', 'api@example.com')
            ->assertJsonPath('data.tel', '09012345678')
            ->assertJsonPath('data.category.id', $payload['category_id'])
            ->assertJsonPath('data.tags.0.id', $payload['tag_ids'][0]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'API',
            'last_name' => '太郎',
            'email' => 'api@example.com',
            'tel' => '09012345678',
            'category_id' => $payload['category_id'],
        ]);

        $contactId = $response->json('data.id');

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contactId,
            'tag_id' => $payload['tag_ids'][0],
        ]);
    }

    public function test_contact_create_return_422_when_validation_fail(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'category_id',
                'detail',
            ])
            ->assertJsonPath('errors.first_name.0', '姓を入力してください')
            ->assertJsonPath('errors.last_name.0', '名を入力してください')
            ->assertJsonPath('errors.gender.0', '性別を選択してください')
            ->assertJsonPath('errors.email.0', 'メールアドレスはメール形式で入力してください')
            ->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください')
            ->assertJsonPath('errors.address.0', '住所を入力してください')
            ->assertJsonPath('errors.category_id.0', 'お問い合わせの種類を選択してください')
            ->assertJsonPath('errors.detail.0', 'お問い合わせ内容を入力してください');
    }

    public function test_contact_create_return_422_when_tag_not_exist(): void
    {
        $payload = $this->validContactPayload([
            'tag_ids' => [999999],
        ]);

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tag_ids.0']);

        $this->assertSame(
            '選択されたタグが存在しません',
            $response->json('errors')['tag_ids.0'][0]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AP4: お問い合わせ更新API
    |--------------------------------------------------------------------------
    */

    public function test_contact_can_update(): void
    {
        $oldCategory = $this->createCategory('商品のお届けについて');
        $oldTag = $this->createTag('質問');

        $contact = Contact::factory()->create([
            'category_id' => $oldCategory->id,
            'first_name' => '更新前',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'before@example.com',
        ]);

        $contact->tags()->attach($oldTag->id);

        $newCategory = $this->createCategory('その他');
        $newTag = $this->createTag('要望');

        $payload = [
            'first_name' => '更新後',
            'last_name' => '次郎',
            'gender' => 2,
            'email' => 'after@example.com',
            'tel' => '08012345678',
            'address' => '東京都府中市',
            'building' => '更新ビル202',
            'category_id' => $newCategory->id,
            'tag_ids' => [$newTag->id],
            'detail' => '更新APIテストのお問い合わせ内容です。',
        ];

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '更新後')
            ->assertJsonPath('data.last_name', '次郎')
            ->assertJsonPath('data.email', 'after@example.com')
            ->assertJsonPath('data.tel', '08012345678')
            ->assertJsonPath('data.category.id', $newCategory->id)
            ->assertJsonPath('data.tags.0.id', $newTag->id);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '更新後',
            'last_name' => '次郎',
            'gender' => 2,
            'email' => 'after@example.com',
            'tel' => '08012345678',
            'address' => '東京都府中市',
            'building' => '更新ビル202',
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_contact_update_syncs_tags(): void
    {
        $category = $this->createCategory();
        $oldTag = $this->createTag('質問');
        $newTag = $this->createTag('要望');

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $contact->tags()->attach($oldTag->id);

        $payload = [
            'first_name' => 'タグ',
            'last_name' => '更新',
            'gender' => 1,
            'email' => 'tag-update@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => null,
            'category_id' => $category->id,
            'tag_ids' => [$newTag->id],
            'detail' => 'タグ更新確認用のお問い合わせ内容です。',
        ];

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $payload);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $oldTag->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $newTag->id,
        ]);
    }

    public function test_contact_update_return_404_when_contact_not_exist(): void
    {
        $payload = $this->validContactPayload();

        $response = $this->putJson('/api/v1/contacts/999999', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'お問い合わせが見つかりませんでした。',
            ]);
    }

    public function test_contact_update_return_422_when_validation_fail(): void
    {
        $category = $this->createCategory();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $payload = $this->validContactPayload([
            'tel' => '090-1234-5678',
        ]);

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tel'])
            ->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください');
    }

    /*
    |--------------------------------------------------------------------------
    | AP5: お問い合わせ削除API
    |--------------------------------------------------------------------------
    */

    public function test_contact_can_delete(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->deleteJson('/api/v1/contacts/'.$contact->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_contact_delete_removes_contact_tag(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $contact->tags()->attach($tag->id);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $response = $this->deleteJson('/api/v1/contacts/'.$contact->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_delete_return_404_when_contact_not_exist(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/999999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'お問い合わせが見つかりませんでした。',
            ]);
    }
}
