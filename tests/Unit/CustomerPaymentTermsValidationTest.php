<?php

namespace Tests\Unit;

use App\Http\Requests\StoreCustomerRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CustomerPaymentTermsValidationTest extends TestCase
{
    /** @dataProvider paymentTermCases */
    public function test_days_are_required_and_positive_integers_only_when_the_term_uses_days($term, $days, $valid)
    {
        $rules = (new StoreCustomerRequest())->rules();
        $validator = Validator::make([
            'terms_of_payment' => $term,
            'terms_of_payment_in_days' => $days,
        ], [
            'terms_of_payment' => $rules['terms_of_payment'],
            'terms_of_payment_in_days' => $rules['terms_of_payment_in_days'],
        ]);

        $this->assertSame($valid, $validator->passes());
    }

    public function paymentTermCases()
    {
        return [
            'day term missing count' => ['Pay in number of days', null, false],
            'day term zero count' => ['Pay in number of days', 0, false],
            'day term non-integer count' => ['Pay in number of days', '2.5', false],
            'day term valid count' => ['Pay in number of days', 30, true],
            'COD without day count' => ['COD', null, true],
        ];
    }
}
