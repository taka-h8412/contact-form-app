<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// API用FormRequestのバリデーションルールを単体で確認するテスト
class ApiContactRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    // category_id の exists バリデーションを通すためのカテゴリを作成
    private function createCategory(string $content = '商品のお届けについて'): Category
    {
        return Category::create([
            'content' => $content,
        ]);
    }

    // tag_ids の exists バリデーションを通すためのタグを作成
    private function createTag(string $name = '質問'): Tag
    {
        return Tag::create([
            'name' => $name,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | API検索バリデーション: IndexContactRequest
    |--------------------------------------------------------------------------
    */

    // API一覧検索で使用できる検索条件が、正常にバリデーションを通ることを確認
    public function test_api検索の正しい条件はバリデーションを通過する(): void
    {
        $category = $this->createCategory();

        $request = new IndexContactRequest;

        $validator = Validator::make([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-06-22',
            'page' => 1,
            'per_page' => 20,
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // 不正な検索条件を指定した場合に、各項目でバリデーションエラーになることを確認
    public function test_api検索の不正な条件はバリデーションエラーになる(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make([
            'gender' => 0,
            'category_id' => 999999,
            'date' => 'invalid-date',
            'page' => 0, // 1以上である必要がある
            'per_page' => 101, // 最大100件を超える値
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
        $this->assertArrayHasKey('page', $validator->errors()->toArray());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());

        $this->assertSame(
            '性別の値が不正です',
            $validator->errors()->first('gender')
        );

        $this->assertSame(
            '選択されたカテゴリーが存在しません',
            $validator->errors()->first('category_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | API作成バリデーション: StoreContactRequest
    |--------------------------------------------------------------------------
    */

    // API作成時に必要な正常データ一式が、バリデーションを通ることを確認
    public function test_apiお問い合わせ作成の正しい入力はバリデーションを通過する(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $request = new StoreContactRequest;

        $validator = Validator::make([
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
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // 必須項目が空の場合に、requiredエラーになることを確認
    public function test_apiお問い合わせ作成の必須項目不足はバリデーションエラーになる(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make([
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => '',
            'tel' => '',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('last_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());

        $this->assertSame('姓を入力してください', $validator->errors()->first('first_name'));
        $this->assertSame('名を入力してください', $validator->errors()->first('last_name'));
        $this->assertSame('性別を選択してください', $validator->errors()->first('gender'));
        $this->assertSame('メールアドレスを入力してください', $validator->errors()->first('email'));
        $this->assertSame('電話番号を入力してください', $validator->errors()->first('tel'));
        $this->assertSame('住所を入力してください', $validator->errors()->first('address'));
        $this->assertSame('お問い合わせの種類を選択してください', $validator->errors()->first('category_id'));
        $this->assertSame('お問い合わせ内容を入力してください', $validator->errors()->first('detail'));
    }

    // 形式不正・存在しないID・文字数超過などでバリデーションエラーになることを確認
    public function test_apiお問い合わせ作成の不正な値はバリデーションエラーになる(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make([
            'first_name' => 'API',
            'last_name' => '太郎',
            'gender' => 0,
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '東京都多摩市',
            'category_id' => 999999,
            'tag_ids' => [999999],
            'detail' => str_repeat('あ', 121), // 120文字制限を超える値
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('tag_ids.0', $validator->errors()->toArray());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());

        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertSame('選択されたタグが存在しません', $validator->errors()->first('tag_ids.0'));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $validator->errors()->first('detail'));
    }
}
