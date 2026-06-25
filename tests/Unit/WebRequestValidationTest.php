<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

// Web画面用FormRequestのバリデーションルールを単体で確認するテスト
class WebRequestValidationTest extends TestCase
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
    | 問い合わせ保存バリデーション: StoreContactRequest
    |--------------------------------------------------------------------------
    */

    // 問い合わせフォームの正常データが、バリデーションを通ることを確認
    public function test_webお問い合わせ入力の正しい値はバリデーションを通過する(): void
    {
        $category = $this->createCategory();

        $request = new StoreContactRequest;

        $validator = Validator::make([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // 必須項目が空の場合に、requiredエラーになることを確認
    public function test_webお問い合わせ入力の必須項目不足はバリデーションエラーになる(): void
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

    // 不正な値・存在しないカテゴリ・文字数超過でバリデーションエラーになることを確認
    public function test_webお問い合わせ入力の不正な値はバリデーションエラーになる(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 0,
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '東京都多摩市',
            'category_id' => 999999,
            'detail' => str_repeat('あ', 121), // 120文字制限を超える値
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());

        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $validator->errors()->first('detail'));
    }

    /*
    |--------------------------------------------------------------------------
    | タグ新規登録バリデーション: StoreTagRequest
    |--------------------------------------------------------------------------
    */

    // タグ名の正常データが、バリデーションを通ることを確認
    public function test_タグ作成の正しい値はバリデーションを通過する(): void
    {
        $request = new StoreTagRequest;

        $validator = Validator::make([
            'name' => '新規タグ',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    // タグ名が空の場合に、requiredエラーになることを確認
    public function test_タグ作成でタグ名未入力はバリデーションエラーになる(): void
    {
        $request = new StoreTagRequest;

        $validator = Validator::make([
            'name' => '',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertSame('タグ名を入力してください', $validator->errors()->first('name'));
    }

    // タグ名が50文字を超える場合、バリデーションエラーになることを確認
    public function test_タグ作成でタグ名が50文字超過ならバリデーションエラーになる(): void
    {
        $request = new StoreTagRequest;

        $validator = Validator::make([
            'name' => str_repeat('あ', 51),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertSame('タグ名は50文字以内で入力してください', $validator->errors()->first('name'));
    }

    // タグ名が重複している場合に、uniqueエラーになることを確認
    public function test_タグ作成でタグ名重複はバリデーションエラーになる(): void
    {
        Tag::create([
            'name' => '質問',
        ]);

        $request = new StoreTagRequest;

        $validator = Validator::make([
            'name' => '質問',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }

    /*
    |--------------------------------------------------------------------------
    | タグ更新バリデーション: UpdateTagRequest
    |--------------------------------------------------------------------------
    */

    // タグ更新時に、自分自身のタグ名であれば重複エラーにならないことを確認
    public function test_タグ更新で自身の現在名はバリデーションを通過する(): void
    {
        $tag = Tag::create([
            'name' => '質問',
        ]);

        $validator = Validator::make(
            ['name' => '質問'],
            [
                'name' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tags', 'name')->ignore($tag->id),
                ],
            ]
        );

        $this->assertFalse($validator->fails());
    }

    // 他のタグと同じ名前に更新しようとした場合に、uniqueエラーになることを確認
    public function test_タグ更新で他タグと重複する名前はバリデーションエラーになる(): void
    {
        $currentTag = Tag::create([
            'name' => '質問',
        ]);

        Tag::create([
            'name' => '要望',
        ]);

        $validator = Validator::make(
            ['name' => '要望'],
            [
                'name' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tags', 'name')->ignore($currentTag->id),
                ],
            ],
            [
                'name.unique' => 'そのタグ名は既に使用されています',
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }
}
