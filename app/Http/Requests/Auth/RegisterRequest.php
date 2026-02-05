<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(5)],
            'secret_tag' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $tag = strtoupper(trim((string) $value));

                    if ($tag === 'MAKSSNAKEST') {
                        return;
                    }

                    if (! preg_match('/^[A-Z0-9]{5,10}$/', $tag)) {
                        $fail('secret_tag musi byc alfanumeryczny i miec dlugosc 5-10 znakow lub miec wartosc MAKSSNAKEST.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwe uzytkownika.',
            'name.string' => 'Nazwa uzytkownika musi byc tekstem.',
            'name.max' => 'Nazwa uzytkownika moze miec maksymalnie :max znakow.',

            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj poprawny adres e-mail.',
            'email.max' => 'Adres e-mail moze miec maksymalnie :max znakow.',
            'email.unique' => 'Konto z tym adresem e-mail juz istnieje.',

            'password.required' => 'Podaj haslo.',
            'password.confirmed' => 'Hasla musza byc identyczne.',
            'password.min' => 'Haslo musi miec co najmniej :min znakow.',

            'secret_tag.required' => 'Podaj SECRET_TAG.',
            'secret_tag.string' => 'SECRET_TAG musi byc tekstem.',
        ];
    }
}
