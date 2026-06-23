<?php

namespace Tests\Unit;

use App\Http\Requests\AdminSearchRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// 管理画面の一覧検索・CSVエクスポートで使用する検索条件のバリデーションを確認するテスト
class AdminSearchRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    // category_id の exists バリデーションを通すためのカテゴリを作成
    private function createCategory(string $content = '商品のお届けについて'): Category
    {
        return Category::create([
            'content' => $content,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 問い合わせ一覧検索バリデーション
    |--------------------------------------------------------------------------
    */

    // 正しい検索条件がバリデーションを通ることを確認
    public function test_admin_search_request_valid_filter_conditions_pass_validation(): void
    {
        $category = $this->createCategory();

        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'keyword' => '山田',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // 性別が不正な値の場合、バリデーションエラーになることを確認
    public function test_admin_search_request_invalid_gender_fails_validation(): void
    {
        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'gender' => '9',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    // キーワードが255文字を超える場合、バリデーションエラーになることを確認
    public function test_admin_search_request_keyword_over_max_length_fails_validation(): void
    {
        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'keyword' => str_repeat('あ', 256),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keyword', $validator->errors()->toArray());
        $this->assertSame('キーワードは255文字以内で入力してください', $validator->errors()->first('keyword'));
    }

    // 日付が不正な形式の場合、バリデーションエラーになることを確認
    public function test_admin_search_request_invalid_date_fails_validation(): void
    {
        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'date' => 'invalid-date',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
        $this->assertSame('日付は正しい日付形式で入力してください', $validator->errors()->first('date'));
    }

    /*
    |--------------------------------------------------------------------------
    | CSVエクスポート検索条件バリデーション
    |--------------------------------------------------------------------------
    */

    // CSVエクスポートで正しいフィルタ条件がバリデーションを通ることを確認
    public function test_csv_export_request_valid_filter_conditions_pass_validation(): void
    {
        $category = $this->createCategory();

        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'keyword' => 'taro@example.com',
            'gender' => '2',
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // CSVエクスポートで存在しないカテゴリIDを指定した場合、バリデーションエラーになることを確認
    public function test_csv_export_request_not_existing_category_id_fails_validation(): void
    {
        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'category_id' => 999999,
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
    }
}