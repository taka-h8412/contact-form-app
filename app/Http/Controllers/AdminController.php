<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // お問い合わせ一覧取得の土台
        $query = Contact::with(['category', 'tags']);

        // 名前・メールアドレスのキーワード検索
        if ($request->keyword) {
            $keyword = $request->keyword;

            $query->where(function ($query) use ($keyword) {
                $query->where('first_name', 'like', '%' . $keyword . '%')
                    ->orWhere('last_name', 'like', '%' . $keyword . '%')
                    ->orWhereRaw('CONCAT(first_name, last_name) LIKE ?', ['%' . $keyword . '%'])
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $keyword . '%'])
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        // 性別検索
        if ($request->gender && $request->gender !== '0') {
            $query->where('gender', $request->gender);
        }

        // お問い合わせ種類検索
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // 日付検索
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        // お問い合わせ一覧を7件ずつ取得
        $contacts = $query->latest('id')->paginate(7);

        // 検索フォームのお問い合わせ種類で使う
        $categories = Category::all();

        // タグ管理で使う
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }

    public function export(Request $request)
    {
        $query = Contact::with('category'); // 検索結果と同じ条件で絞り込めるように準備(検索の土台)

        // 名前・メールアドレスのキーワード検索
        if ($request->keyword) {
            $keyword = $request->keyword;

            $query->where(function ($query) use ($keyword) {
                $query->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhereRaw('CONCAT(first_name, last_name) LIKE ?', ['%' . $keyword . '%'])
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $keyword . '%'])
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        // 性別検索
        if ($request->gender && $request->gender !== '0') {
            $query->where('gender', $request->gender);
        }

        // お問い合わせ種類検索
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // 日付検索
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        // フィルタ未指定時も含め、全件を新着順で取得
        $contacts = $query->latest('id')->get();

        $callback = function () use ($contacts) {
            // ブラウザにCSVとして出力するための準備
            $file = fopen('php://output', 'w');

            // Excel文字化け対策
            fwrite($file, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($file, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {
                if ($contact->gender == 1) {
                    $gender = '男性';
                } elseif ($contact->gender == 2) {
                    $gender = '女性';
                } else {
                    $gender = 'その他';
                }

                // CSVに1行分のデータを書き込む
                fputcsv($file, [
                    $contact->id,
                    $contact->first_name . ' ' . $contact->last_name,
                    $gender,
                    $contact->email,
                    '="' . $contact->tel . '"',
                    $contact->address,
                    $contact->building,
                    $contact->category->content ?? '',
                    $contact->detail,
                    $contact->created_at,
                ]);
            }

            // CSV出力を終了する
            fclose($file);
        };

        // contacts.csvという名前でCSVファイルをダウンロードする
        return response()->streamDownload($callback, 'contacts.csv');
    }
}