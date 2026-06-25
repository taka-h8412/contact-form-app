<?php

namespace Tests\Feature\Web;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 管理画面のタグ作成・編集・更新・削除を確認するテスト
class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    // 管理画面へアクセスする認証済みユーザーを作成
    private function createUser(): User
    {
        return User::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | タグ管理アクセス制御
    |--------------------------------------------------------------------------
    */

    // 未認証ユーザーがタグ作成を行おうとした場合、ログイン画面へリダイレクトされることを確認
    public function test_guest_user_is_redirect_to_login_when_creating_tag(): void
    {
        $response = $this->post('/admin/tags', [
            'name' => '質問',
        ]);

        $response->assertRedirect('/login');
    }

    // 未認証ユーザーがタグ編集画面へアクセスした場合、ログイン画面へリダイレクトされることを確認
    public function test_guest_user_is_redirect_to_login_when_accessing_tag_edit_page(): void
    {
        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertRedirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | タグ作成
    |--------------------------------------------------------------------------
    */

    // 認証済みユーザーがタグを作成でき、/admin へリダイレクトされることを確認
    public function test_authenticated_user_can_create_tag(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => '新規タグ',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '新規タグ',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | タグ編集・更新
    |--------------------------------------------------------------------------
    */

    // 認証済みユーザーがタグ編集画面を表示できることを確認
    public function test_authenticated_user_can_access_tag_edit_page(): void
    {
        $user = $this->createUser();

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->actingAs($user)->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('タグ編集');
        $response->assertSee('質問');
    }

    // 認証済みユーザーがタグを更新でき、/admin へリダイレクトされることを確認
    public function test_authenticated_user_can_update_tag(): void
    {
        $user = $this->createUser();

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->actingAs($user)->put('/admin/tags/'.$tag->id, [
            'name' => '重要',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '重要',
        ]);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
            'name' => '質問',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | タグ削除
    |--------------------------------------------------------------------------
    */

    // 認証済みユーザーがタグを削除でき、/admin へリダイレクトされることを確認
    public function test_authenticated_user_can_delete_tag(): void
    {
        $user = $this->createUser();

        $tag = Tag::create([
            'name' => '削除対象',
        ]);

        $response = $this->actingAs($user)->delete('/admin/tags/'.$tag->id);

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
            'name' => '削除対象',
        ]);
    }
}
