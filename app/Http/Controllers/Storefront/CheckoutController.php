<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\GiftCardTheme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /** @var list<string> */
    private const ALLOWED_FORM_FIELDS = [
        'gift_send_option',
        'receiver_name',
        'receiver_email',
        'receiver_mobile',
        'receiver_msg',
        'gift_theme_id',
        'gift_message_title',
        'sender_first_name',
        'delivery_mode',
    ];

    public function saveGiftCardForm(Request $request)
    {
        abort(410, 'Endpoint retired. Use checkout.session.gift_draft.save.');
    }
}
