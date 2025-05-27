<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $userId ? Rule::unique('users')->ignore($userId) : Rule::unique('users'),
            ],
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:user,admin,owner',
        ];

        // Only apply password rules if it's a new user or password is being updated
        if (!$userId || $this->filled('password')) {
            $rules['password'] = [
                'required',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Het wachtwoord voldoet niet aan de eisen.',
        ];
    }
}
