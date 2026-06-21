<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->only([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'building',
            'category_id',
            'tag_ids',
            'detail',
        ]);

        // 問い合わせ分類取得
        $category = Category::find($validated['category_id']);

        $tags = collect();

        // タグ名取得(未選択時はDB取得しない)
        if (!empty($validated['tag_ids'])) {
            $tags = Tag::whereIn('id', $validated['tag_ids'])->get();
        }

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        $data = $request->only([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'building',
            'category_id',
            'detail',
        ]);

        $contact = Contact::create($data);

        if ($request->filled('tag_ids')) {
            $contact->tags()->sync($request->input('tag_ids'));
        }

        return redirect('/thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}