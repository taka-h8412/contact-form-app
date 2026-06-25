<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 公開側のお問い合わせフォームの画面表示・確認・保存フローを確認するテスト
class ContactFlowTest extends TestCase
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

    // お問い合わせフォームから送信する正常データを作成
    private function validContactPayload(array $overrides = []): array
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => 'お問い合わせ内容のテストです。',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | 画面アクセス
    |--------------------------------------------------------------------------
    */

    // お問い合わせフォーム入力ページが表示され、categories・tags がビューに渡されることを確認
    public function test_contact_input_page_can_display(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertSee('Contact');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    // サンクスページが正常に表示されることを確認
    public function test_thanks_page_can_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertStatus(200);
        $response->assertViewIs('contact.thanks');
        $response->assertSee('Thank you');
        $response->assertSee('お問い合わせありがとうございました');
    }

    /*
    |--------------------------------------------------------------------------
    | お問い合わせ確認画面表示
    |--------------------------------------------------------------------------
    */

    // 正常な入力内容で確認ページが表示され、入力内容・カテゴリ名・タグ名が表示されることを確認
    public function test_contact_confirm_page_can_display_with_valid_input(): void
    {
        $payload = $this->validContactPayload();

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');

        $response->assertSee('Confirm');
        $response->assertSee('山田 太郎');
        $response->assertSee('男性');
        $response->assertSee('yamada@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('東京都多摩市');
        $response->assertSee('テストビル101');
        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');
        $response->assertSee('お問い合わせ内容のテストです。');
    }

    // 入力内容に不備がある場合、確認ページへ進めずバリデーションエラーになることを確認
    public function test_contact_confirm_returns_validation_errors_when_input_is_invalid(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', [
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => 'invalid-email',
            'tel1' => '',
            'tel2' => '',
            'tel3' => '',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | お問い合わせ送信
    |--------------------------------------------------------------------------
    */

    // 正常な入力内容でお問い合わせが保存され、タグも紐づき、サンクスページへリダイレクトされることを確認
    public function test_contact_can_store_and_redirect_to_thanks_page(): void
    {
        $payload = $this->validContactPayload();

        $response = $this->post('/contacts', $payload);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'detail' => 'お問い合わせ内容のテストです。',
        ]);

        $contact = Contact::where('email', 'yamada@example.com')->first();
        $tag = Tag::where('name', '質問')->first();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    // 保存時に入力内容に不備がある場合、保存されずバリデーションエラーになることを確認
    public function test_contact_store_return_validation_error_when_input_is_invalid(): void
    {
        $response = $this->from('/contacts/confirm')->post('/contacts', [
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => 'invalid-email',
            'tel1' => '',
            'tel2' => '',
            'tel3' => '',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ]);

        $response->assertRedirect('/contacts/confirm');
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);

        $this->assertDatabaseCount('contacts', 0);
    }
}
