<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|integer|in:1,2,3',
            'email' => 'required|email|max:255',
            'tel' => 'required|string|regex:/^[0-9]{10,11}$/',
            'address' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'detail' => 'required|string|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            // お問い合わせ入力ページ a〜j
            'first_name.required' => '姓を入力してください',
            'last_name.required' => '名を入力してください',

            'gender.required' => '性別を選択してください',

            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',

            'tel.required' => '電話番号を入力してください',

            'address.required' => '住所を入力してください',

            'category_id.required' => 'お問い合わせの種類を選択してください',

            'detail.required' => 'お問い合わせ内容を入力してください',
            'detail.max' => 'お問い合わせ内容は120文字以内で入力してください',

            // AP3追加要件 g～j
            'tel.regex' => '電話番号はハイフンなしの10〜11桁で入力してください',

            'gender.integer' => '性別の値が不正です',
            'gender.in' => '性別の値が不正です',

            'category_id.integer' => '選択されたカテゴリーが存在しません',
            'category_id.exists' => '選択されたカテゴリーが存在しません',

            'tag_ids.*.integer' => '選択されたタグが存在しません',
            'tag_ids.*.exists' => '選択されたタグが存在しません',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $messages = $validator->errors()->all();
        // 最初のエラーだけを入れる
        $message = $messages[0];

        // 最初の1個を表示して、件数を付与
        if (count($messages) > 1) {
            $message .= ' (and ' . (count($messages) - 1) . ' more errors)';
        }

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $validator->errors(),
        ], 422));
    }
}