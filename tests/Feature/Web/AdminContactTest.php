<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 管理画面のお問い合わせ一覧・検索・詳細・削除を確認するテスト
class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    // 問い合わせ分類を作成
    private function createCategory(string $content = '商品のお届けについて'): Category
    {
        return Category::create([
            'content' => $content,
        ]);
    }

    // タグを作成
    private function createTag(string $name = '質問'): Tag
    {
        return Tag::create([
            'name' => $name,
        ]);
    }

    // 管理画面へアクセスする認証済みユーザーを作成
    private function createUser(): User
    {
        return User::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | 管理画面一覧・検索
    |--------------------------------------------------------------------------
    */

    // 管理画面でキーワード・性別・カテゴリ・日付フィルタが機能することを確認
    public function test_管理画面でお問い合わせを検索できる(): void
    {
        $user = $this->createUser();

        $targetCategory = $this->createCategory('商品のお届けについて');
        $otherCategory = $this->createCategory('返品について');

        $targetTag = $this->createTag('質問');
        $otherTag = $this->createTag('要望');

        $targetContact = Contact::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => '2026-06-23 10:00:00',
        ]);

        $targetContact->tags()->attach($targetTag->id);

        $otherContact = Contact::factory()->create([
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'sato@example.com',
            'category_id' => $otherCategory->id,
            'created_at' => '2026-06-22 10:00:00',
        ]);

        $otherContact->tags()->attach($otherTag->id);

        $response = $this->actingAs($user)->get('/admin?'.http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'date' => '2026-06-23',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');

        $response->assertSee('山田 太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('男性');
        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');

        $response->assertDontSee('佐藤 花子');
        $response->assertDontSee('sato@example.com');
    }

    // 管理画面のお問い合わせ一覧が7件ごとにページネーションされることを確認
    public function test_管理画面のお問い合わせ一覧は7件ずつ表示される(): void
    {
        $user = $this->createUser();

        $category = $this->createCategory();

        Contact::factory()->count(8)->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);

        $contacts = $response->viewData('contacts');

        $this->assertSame(7, $contacts->perPage());
        $this->assertSame(8, $contacts->total());
        $this->assertCount(7, $contacts->items());
    }

    /*
    |--------------------------------------------------------------------------
    | 管理画面詳細
    |--------------------------------------------------------------------------
    */

    // お問い合わせ詳細ページが表示され、カテゴリ・タグを含む詳細情報が表示されることを確認
    public function test_管理画面でお問い合わせ詳細を表示できる(): void
    {
        $user = $this->createUser();

        $category = $this->createCategory('商品のお届けについて');
        $tag = $this->createTag('質問');

        $contact = Contact::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ詳細のテストです。',
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->actingAs($user)->get('/admin/contacts/'.$contact->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');

        $response->assertSee('お問い合わせ詳細');
        $response->assertSee('山田 太郎');
        $response->assertSee('男性');
        $response->assertSee('yamada@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('東京都多摩市');
        $response->assertSee('テストビル101');
        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');
        $response->assertSee('お問い合わせ詳細のテストです。');
    }

    /*
    |--------------------------------------------------------------------------
    | 管理画面削除
    |--------------------------------------------------------------------------
    */

    // お問い合わせを削除でき、削除後に /admin へリダイレクトされることを確認
    public function test_管理画面でお問い合わせを削除して一覧へ戻る(): void
    {
        $user = $this->createUser();

        $category = $this->createCategory();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'delete@example.com',
        ]);

        $response = $this->actingAs($user)->delete('/admin/contacts/'.$contact->id);

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'email' => 'delete@example.com',
        ]);
    }
}
