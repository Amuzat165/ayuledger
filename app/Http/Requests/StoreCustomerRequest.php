<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:customers'], // hi@x.io
            'phone' => ['required', 'min:10'],
            'address' => ['required'],
            'income_expense_type_id' => ['required', 'exists:income_expense_types,id'],
            'terms_of_payment' => ['required'],
            'terms_of_payment_in_days' => ['required_if:terms_of_payment,Pay in number of days', 'nullable', 'integer', 'min:1'],
        ];
    }
}
