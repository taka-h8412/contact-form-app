<?php

namespace Tests\Unit;

use App\Http\Requests\AdminSearchRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// 管理画面の一覧検索で使用する検索条件のバリデーションを確認するテスト
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

    // キーワード・性別・カテゴリ・日付フィルタがバリデーションを通ることを確認
    public function test_admin_search_request_valid_filter_pass_validation(): void
    {
        $category = $this->createCategory();

        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'keyword' => '山田',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    // 性別が不正な値の場合、バリデーションエラーになることを確認
    public function test_admin_search_request_invalid_gender_fail_validation(): void
    {
        $request = new AdminSearchRequest();

        $validator = Validator::make([
            'gender' => '9',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }
}
