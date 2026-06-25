<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 管理画面のCSVエクスポートを確認するテスト
class ExportTest extends TestCase
{
    use RefreshDatabase;

    // 問い合わせ分類を作成
    private function createCategory(string $content = '商品のお届けについて'): Category
    {
        return Category::create([
            'content' => $content,
        ]);
    }

    // 管理画面へアクセスする認証済みユーザーを作成
    private function createUser(): User
    {
        return User::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | CSVエクスポート
    |--------------------------------------------------------------------------
    */

    // ログイン済みユーザーがフィルタ条件付きでCSVをダウンロードできることを確認
    public function test_authenticated_user_can_export_filter_contacts_csv(): void
    {
        $user = $this->createUser();

        $targetCategory = $this->createCategory('商品のお届けについて');
        $otherCategory = $this->createCategory('返品について');

        Contact::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都多摩市',
            'building' => 'テストビル101',
            'category_id' => $targetCategory->id,
            'detail' => 'CSV出力対象のお問い合わせです。',
            'created_at' => '2026-06-23 10:00:00',
        ]);

        Contact::factory()->create([
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '08012345678',
            'address' => '東京都府中市',
            'building' => null,
            'category_id' => $otherCategory->id,
            'detail' => 'CSV出力対象外のお問い合わせです。',
            'created_at' => '2026-06-22 10:00:00',
        ]);

        $response = $this->actingAs($user)->get('/contacts/export?keyword=山田&gender=1');

        $response->assertStatus(200);
        $response->assertDownload('contacts.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('ID', $csv);
        $this->assertStringContainsString('氏名', $csv);
        $this->assertStringContainsString('山田 太郎', $csv);
        $this->assertStringContainsString('男性', $csv);
        $this->assertStringContainsString('yamada@example.com', $csv);
        $this->assertStringContainsString('商品のお届けについて', $csv);
        $this->assertStringContainsString('CSV出力対象のお問い合わせです。', $csv);

        $this->assertStringNotContainsString('佐藤 花子', $csv);
        $this->assertStringNotContainsString('sato@example.com', $csv);
    }

    // フィルタ未指定時、CSVが新着順で出力されることを確認
    public function test_contacts_csv_is_export_in_latest_order_without_filter(): void
    {
        $user = $this->createUser();

        $category = $this->createCategory();

        Contact::factory()->create([
            'first_name' => '古い',
            'last_name' => '問い合わせ',
            'gender' => 1,
            'email' => 'old@example.com',
            'category_id' => $category->id,
        ]);

        Contact::factory()->create([
            'first_name' => '新しい',
            'last_name' => '問い合わせ',
            'gender' => 2,
            'email' => 'new@example.com',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertStatus(200);
        $response->assertDownload('contacts.csv');

        $csv = $response->streamedContent();

        // CSV内に両方の問い合わせが含まれていることを確認
        $this->assertStringContainsString('新しい 問い合わせ', $csv);
        $this->assertStringContainsString('古い 問い合わせ', $csv);

        // CSV内で「新しい 問い合わせ」が「古い 問い合わせ」より前にあることを確認
        $this->assertLessThan(
            strpos($csv, '古い 問い合わせ'),
            strpos($csv, '新しい 問い合わせ')
        );
    }
}
