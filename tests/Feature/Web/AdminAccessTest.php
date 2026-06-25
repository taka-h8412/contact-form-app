<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 管理画面へのアクセス制御を確認するテスト
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 管理画面アクセス制御
    |--------------------------------------------------------------------------
    */

    // 未認証ユーザーが管理画面へアクセスした場合、ログイン画面へリダイレクトされることを確認
    public function test_未ログインでは管理画面にアクセスできない(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    // 認証済みユーザーが管理画面を表示できることを確認
    public function test_ログイン済みユーザーは管理画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
        $response->assertViewHas('contacts');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
    }
}
