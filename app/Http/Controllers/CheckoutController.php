<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\CombinedOrder;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\NotificationUtility;
use App\Services\SendSmsService;
use Session;
use Auth;
use Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Mail;
use App\Mail\MailManager;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        // Allow checkout without forcing login/verification.
        // (Guests already use temp_user_id cart; authenticated users may not be verified yet.)

        $country_id = 0;
        $city_id = 0;
        $area_id = 0;
        $state_id = 0;
        $address_id = 0;
        $shipping_info = array();

        if (auth()->check()) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->active()->get();
            $addresses = Address::where('user_id', $user_id)->get();
            if(count($addresses)){
                $address = $addresses->toQuery()->first();
                $address_id = $address->id;
                $country_id = $address->country_id;
                $city_id = $address->city_id;
                $area_id = $address->area_id;
                $state_id = $address->state_id ?? 0;
                $default_address =$addresses->toQuery()->where('set_default', 1)->first();
                if($default_address != null){
                    $address_id = $default_address->id;
                    $country_id = $default_address->country_id;
                    $city_id = $default_address->city_id;
                    $area_id = $default_address->area_id;
                    $state_id = $default_address->state_id ?? 0;
                }
            }
        }
        else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->active()->get() : [];
        }

        $shipping_info['country_id'] = $country_id;
        $shipping_info['city_id'] = $city_id;
        $shipping_info['area_id'] = $area_id;
        $shipping_info['state_id'] = $state_id;
        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;
        $default_carrier_id = null;
        $default_shipping_type = 'home_delivery';

        if ($carts && count($carts) > 0) {
            $carts->toQuery()->update(['address_id' => $address_id]);
            $carts = $carts->fresh();

            $carrier_list = array();
            if (get_setting('shipping_type') == 'carrier_wise_shipping') {
                $default_shipping_type = 'carrier';
               // $zone = $country_id != 0 ? Country::where('id', $country_id)->first()->zone_id : 0;
               $zone = $country_id != 0 ? Country::where('id', $country_id)->where('status', 1)->first()->zone_id ?? 0 : 0;

                $carrier_query = Carrier::where('status', 1);
                $carrier_query->whereIn('id',function ($query) use ($zone) {
                    $query->select('carrier_id')->from('carrier_range_prices')
                        ->where('zone_id', $zone);
                })->orWhere('free_shipping', 1);
                $carrier_list = $carrier_query->get();

                if (count($carrier_list) > 0) {
                    $default_carrier_id = $carrier_list->toQuery()->first()->id;
                }
            }

            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

                if (get_setting('shipping_type') == 'carrier_wise_shipping') {
                    $cartItem['shipping_cost'] = $country_id != 0 ? getShippingCost($carts, $key, $shipping_info, $default_carrier_id) : 0;
                } else {
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $shipping_info);
                }
                $cartItem['shipping_type'] = $default_shipping_type;
                $cartItem['carrier_id'] = $default_carrier_id;
                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $total = $subtotal + $tax + $shipping;

            $carts = $carts->fresh();

            return view('frontend.checkout', compact('carts', 'address_id', 'total', 'carrier_list', 'shipping_info'));
        }
        flash(translate('Please Select cart items to Proceed'))->error();
        return back();
    }

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(Request $request)
    {
        // Guest checkout: create/find user and verify via OTP before placing order
        if (auth()->user() == null) {
            $gate = $this->ensureCheckoutOtpVerified($request);
            if ($gate !== true) {
                return $gate;
            }
        }

        if ($request->payment_option == null) {
            flash(translate('There is no payment option is selected.'))->warning();
            return redirect()->route('checkout');
        }
        $user = auth()->user();
        $carts = Cart::where('user_id', $user->id)->active()->get();


        // Minumum order amount check
        if(get_setting('minimum_order_amount_check') == 1){
            $subtotal = 0;
            foreach ($carts as $key => $cartItem){
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                flash(translate('You order amount is less than the minimum order amount'))->warning();
                return redirect()->route('home');
            }
        }
        // Minumum order amount check end

        (new OrderController)->store($request);

        if(count($carts) > 0){
            $carts->toQuery()->delete();
        }

        $request->session()->put('payment_type', 'cart_payment');

        $data['combined_order_id'] = $request->session()->get('combined_order_id');
        $data['payment_method'] = $request->payment_option;
        $request->session()->put('payment_data', $data);
        if ($request->session()->get('combined_order_id') != null) {
            // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
            $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
            if (class_exists($decorator)) {
                return (new $decorator)->pay($request);
            }
            else {
                $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));
                $manual_payment_data = array(
                    'name'   => $request->payment_option,
                    'amount' => $combined_order->grand_total,
                    'trx_id' => $request->trx_id,
                    'photo'  => $request->photo
                );
                foreach ($combined_order->orders as $order) {
                    $order->manual_payment = 1;
                    $order->manual_payment_data = json_encode($manual_payment_data);
                    $order->save();
                }
                flash(translate('Your order has been placed successfully.'))->success();
                return redirect()->route('order_confirmed');
            }
        }
    }

    public function verifyCheckoutOtp(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('checkout_otp_user_id');
        if (!$userId) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'result' => false,
                    'message' => translate('Your session expired. Please try again.'),
                ], 422);
            }
            flash(translate('Your session expired. Please try again.'))->warning();
            return redirect()->route('checkout');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget(['checkout_otp_user_id', 'checkout_otp_verified']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'result' => false,
                    'message' => translate('User not found. Please try again.'),
                ], 422);
            }
            flash(translate('User not found. Please try again.'))->warning();
            return redirect()->route('checkout');
        }

        if ((string) $user->verification_code !== (string) $request->verification_code) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'result' => false,
                    'message' => translate('OTP does not match. Please try again.'),
                ], 422);
            }
            return redirect()->route('checkout')->withErrors([
                'verification_code' => translate('OTP does not match. Please try again.'),
            ]);
        }

        $user->email_verified_at = $user->email_verified_at ?? date('Y-m-d H:i:s');
        $user->verification_code = null;
        $user->save();

        auth()->login($user, true);

        // Move guest cart to user cart (if any)
        $temp_user_id = $request->session()->get('temp_user_id');
        if ($temp_user_id) {
            Cart::where('temp_user_id', $temp_user_id)->update([
                'user_id' => $user->id,
                'temp_user_id' => null,
            ]);
            $request->session()->forget('temp_user_id');
        }

        // Persist guest shipping/billing into Address records so OrderController uses correct data.
        $guest = $request->session()->get('checkout_guest_shipping_info');
        if (is_array($guest) && !empty($guest)) {
            $sameAsShipping = ($guest['same_as_shipping'] ?? 0) == 1;

            $address = new Address;
            $address->user_id       = $user->id;
            $address->address       = $guest['address'] ?? null;
            $address->country_id    = $guest['country_id'] ?? null;
            $address->state_id      = $guest['state_id'] ?? null;
            $address->city_id       = resolve_city_id_for_state_wise_shipping(
                $guest['state_id'] ?? null,
                $guest['city_id'] ?? null
            ) ?? ($guest['city_id'] ?? null);
            $address->postal_code   = $guest['postal_code'] ?? null;
            $address->area_id       = $guest['area_id'] ?? null;
            $address->phone         = ($guest['country_code'] ?? '') !== '' ? ('+' . ltrim($guest['country_code'], '+') . ($guest['phone'] ?? '')) : ($guest['phone'] ?? null);
            $address->longitude     = $guest['longitude'] ?? null;
            $address->latitude      = $guest['latitude'] ?? null;
            if (!get_setting('billing_address_required') || $sameAsShipping) {
                $address->set_billing = 1;
            }
            $address->save();

            $billingId = $address->id;
            if (get_setting('billing_address_required') && !$sameAsShipping) {
                $billing = new Address;
                $billing->user_id       = $user->id;
                $billing->address       = $guest['billing_address'] ?? null;
                $billing->country_id    = $guest['billing_country_id'] ?? null;
                $billing->state_id      = $guest['billing_state_id'] ?? null;
                $billing->city_id       = resolve_city_id_for_state_wise_shipping(
                    $guest['billing_state_id'] ?? null,
                    $guest['billing_city_id'] ?? null
                ) ?? ($guest['billing_city_id'] ?? null);
                $billing->postal_code   = $guest['billing_postal_code'] ?? null;
                $billing->area_id       = $guest['billing_area_id'] ?? null;
                $billing->phone         = $guest['billing_phone'] ?? null;
                $billing->save();
                $billingId = $billing->id;
            }

            Cart::where('user_id', $user->id)->active()->update([
                'address_id' => $address->id,
                'billing_address' => $billingId,
            ]);

            $request->session()->forget('checkout_guest_shipping_info');
        }

        $request->session()->put('checkout_otp_verified', $user->id);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'result' => true,
                'message' => translate('OTP verified'),
            ]);
        }
        flash(translate('OTP verified. You can place your order now.'))->success();
        return redirect()->route('checkout');
    }

    public function requestCheckoutOtp(Request $request)
    {
        // Already verified in this session
        $sessionVerified = $request->session()->get('checkout_otp_verified');
        $sessionUserId = $request->session()->get('checkout_otp_user_id');
        if ($sessionVerified && $sessionUserId && (int) $sessionVerified === (int) $sessionUserId) {
            return response()->json([
                'result' => true,
                'already_verified' => true,
                'message' => translate('Already verified'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:20',
            'country_code' => 'nullable|string|max:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors()->all(),
            ], 422);
        }

        $email = trim((string) $request->email);
        $rawPhone = preg_replace('/\s+/', '', (string) $request->phone);
        $countryCode = trim((string) ($request->country_code ?? ''));
        $phoneE164 = $countryCode !== '' ? ('+' . ltrim($countryCode, '+') . $rawPhone) : $rawPhone;

        $user = User::where('user_type', 'customer')
            ->where(function ($q) use ($email, $phoneE164) {
                $q->where('email', $email);
                if ($phoneE164 !== '') {
                    $q->orWhere('phone', $phoneE164);
                }
            })
            ->first();

        $isNewCheckoutAccount = false;
        if (!$user) {
            $isNewCheckoutAccount = true;
            $password = substr(hash('sha512', rand()), 0, 12);
            $user = new User();
            $user->name = $request->name;
            $user->email = $email;
            $user->phone = $phoneE164 ?: null;
            $user->password = Hash::make($password);
            $user->user_type = 'customer';
        }

        $user->verification_code = rand(100000, 999999);
        $user->save();

        if ($isNewCheckoutAccount) {
            $request->session()->put('checkout_new_account_user_id', $user->id);
        }

        // Store guest shipping/billing info for later Address creation after OTP verification
        $resolvedShippingCity = resolve_city_id_for_state_wise_shipping($request->state_id, $request->city_id);
        $resolvedBillingCity = resolve_city_id_for_state_wise_shipping(
            $request->billing_state_id,
            $request->billing_city_id
        );

        $guest = [
            'same_as_shipping' => (int) (($request->same_as_shipping ?? 0) == 1),
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $resolvedShippingCity ?? $request->city_id,
            'area_id' => $request->area_id,
            'postal_code' => $request->postal_code,
            'country_code' => $request->country_code,
            'phone' => $request->phone,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'billing_address' => $request->billing_address,
            'billing_country_id' => $request->billing_country_id,
            'billing_state_id' => $request->billing_state_id,
            'billing_city_id' => $resolvedBillingCity ?? $request->billing_city_id,
            'billing_area_id' => $request->billing_area_id,
            'billing_postal_code' => $request->billing_postal_code,
            'billing_phone' => $request->billing_phone,
        ];
        $request->session()->put('checkout_guest_shipping_info', $guest);

        try {
            $to = $phoneE164 ?: $user->phone;
            if (empty($to)) {
                throw new \Exception('Missing phone for OTP');
            }
            $message = 'Your ' . ($site = (get_setting('site_name') ?: config('app.name'))) . ' OTP is: ' . $user->verification_code;
            (new SendSmsService)->sendSMS($to, env('MIM_SENDER_ID'), $message, null);
        } catch (\Exception $e) {
            Log::warning('checkout_otp_send_failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'result' => false,
                'message' => translate('Could not send OTP. Please try again later.'),
            ], 500);
        }

        $request->session()->put('checkout_otp_user_id', $user->id);
        $request->session()->forget('checkout_otp_verified');

        return response()->json([
            'result' => true,
            'message' => translate('OTP sent to your phone'),
        ]);
    }

    private function ensureCheckoutOtpVerified(Request $request)
    {
        $sessionVerified = $request->session()->get('checkout_otp_verified');
        $sessionUserId = $request->session()->get('checkout_otp_user_id');
        if ($sessionVerified && $sessionUserId && (int) $sessionVerified === (int) $sessionUserId) {
            return true;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:20',
            'country_code' => 'nullable|string|max:8',
        ]);

        if ($validator->fails()) {
            return redirect()->route('checkout')->withErrors($validator->errors());
        }

        $email = trim((string) $request->email);
        $rawPhone = preg_replace('/\s+/', '', (string) $request->phone);
        $countryCode = trim((string) ($request->country_code ?? ''));
        $phoneE164 = $countryCode !== '' ? ('+' . ltrim($countryCode, '+') . $rawPhone) : $rawPhone;

        $user = User::where('user_type', 'customer')
            ->where(function ($q) use ($email, $phoneE164) {
                $q->where('email', $email);
                if ($phoneE164 !== '') {
                    $q->orWhere('phone', $phoneE164);
                }
            })
            ->first();

        $isNewCheckoutAccount = false;
        if (!$user) {
            $isNewCheckoutAccount = true;
            $password = substr(hash('sha512', rand()), 0, 12);
            $user = new User();
            $user->name = $request->name;
            $user->email = $email;
            $user->phone = $phoneE164 ?: null;
            $user->password = Hash::make($password);
            $user->user_type = 'customer';
        }

        $user->verification_code = rand(100000, 999999);
        $user->save();

        if ($isNewCheckoutAccount) {
            $request->session()->put('checkout_new_account_user_id', $user->id);
        }

        // Send OTP via SMS
        try {
            $to = $phoneE164 ?: $user->phone;
            if (empty($to)) {
                throw new \Exception('Missing phone for OTP');
            }
            $site = get_setting('site_name') ?: config('app.name');
            $message = 'Your ' . $site . ' OTP is: ' . $user->verification_code;
            (new SendSmsService)->sendSMS($to, env('MIM_SENDER_ID'), $message, null);
        } catch (\Exception $e) {
            Log::warning('checkout_otp_send_failed_redirect', [
                'message' => $e->getMessage(),
            ]);
            flash(translate('Could not send OTP. Please try again later.'))->warning();
            return redirect()->route('checkout');
        }

        $request->session()->put('checkout_otp_user_id', $user->id);
        $request->session()->forget('checkout_otp_verified');
        flash(translate('We sent an OTP to your phone. Please enter it to continue.'))->info();
        return redirect()->route('checkout');
    }

    public function createUser($guest_shipping_info)
    {
        if (! checkout_requires_city_for_shipping_quote()) {
            $guest_shipping_info['city_id'] = resolve_city_id_for_state_wise_shipping(
                $guest_shipping_info['state_id'] ?? null,
                $guest_shipping_info['city_id'] ?? null
            );
            if (!(($guest_shipping_info['same_as_shipping'] ?? 0) == 1) && get_setting('billing_address_required')) {
                $guest_shipping_info['billing_city_id'] = resolve_city_id_for_state_wise_shipping(
                    $guest_shipping_info['billing_state_id'] ?? null,
                    $guest_shipping_info['billing_city_id'] ?? null
                );
            }
        }

        $validator = Validator::make($guest_shipping_info, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'phone' => 'required|max:12',
            'address' => 'required|max:255',
            'country_id' => 'required|Integer',
            'state_id' => get_setting('has_state') == 1 ? 'required|integer' : 'nullable|integer',
            'city_id' => 'required|Integer',
            'area_id'  => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $success = 1;
        $password = substr(hash('sha512', rand()), 0, 8);
        $isEmailVerificationEnabled = get_setting('email_verification');

        // User Create
        $user = new User();
        $user->name = $guest_shipping_info['name'];
        $user->email = $guest_shipping_info['email'];
        $user->phone = addon_is_activated('otp_system') ? '+'.$guest_shipping_info['country_code'].$guest_shipping_info['phone'] : null;
        $user->password = Hash::make($password);
        $user->user_type = 'customer';
        $user->email_verified_at = $isEmailVerificationEnabled != 1 ? date('Y-m-d H:m:s') : null;
        $user->save();

        Session::put('checkout_new_account_user_id', $user->id);

        // Sending email verification Notification
        if($isEmailVerificationEnabled == 1){
            EmailUtility::email_verification($user, 'customer');
        }

        // Customer Account Opening Email to Admin
        if ((get_email_template_data('customer_reg_email_to_admin', 'status') == 1)) {
            try {
                EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            } catch (\Exception $e) {}
        }

        // User Address Create
        $sameAsShipping   = ($guest_shipping_info['same_as_shipping'] ?? 0) == 1;
        $address = new Address;
        $address->user_id       = $user->id;
        $address->address       = $guest_shipping_info['address'];
        $address->country_id    = $guest_shipping_info['country_id'];
        $address->state_id      = $guest_shipping_info['state_id'] ?? null;
        $address->city_id       = $guest_shipping_info['city_id'];
        $address->postal_code   = $guest_shipping_info['postal_code'];
        $address->area_id       = $guest_shipping_info['area_id'] ?? null;
        $address->phone         = '+'.$guest_shipping_info['country_code'].$guest_shipping_info['phone'];
        $address->longitude     = isset($guest_shipping_info['longitude']) ? $guest_shipping_info['longitude'] : null;
        $address->latitude      = isset($guest_shipping_info['latitude']) ? $guest_shipping_info['latitude'] : null;
        if (!get_setting('billing_address_required') || $sameAsShipping) {
            $address->set_billing = 1;
        }
        $address->save();
        $address_billing_id=$address->id; 

        //user billing Address
        if(get_setting('billing_address_required') && !$sameAsShipping){
        $billing_address = new Address;
        $billing_address->user_id       = $user->id;
        $billing_address->address       = $guest_shipping_info['billing_address'];
        $billing_address->country_id    = $guest_shipping_info['billing_country_id'];
        $billing_address->state_id      = $guest_shipping_info['billing_state_id'] ?? null;
        $billing_address->city_id       = $guest_shipping_info['billing_city_id'];
        $billing_address->postal_code   = $guest_shipping_info['billing_postal_code'];
        $billing_address->area_id       = $guest_shipping_info['billing_area_id'] ?? null;
        $billing_address->phone         = $guest_shipping_info['billing_phone'];
        $address->set_billing           = 1;
        $billing_address->save();
        $address_billing_id=$billing_address->id;

        }
        

        $carts = Cart::where('temp_user_id', session('temp_user_id'))->get();
        $carts->toQuery()->update([
                'user_id' => $user->id,
                'temp_user_id' => null
            ]);
        $carts->toQuery()->active()->update([
                'address_id' => $address->id,
                'billing_address' => $address_billing_id
            ]);

        auth()->login($user);

        Session::forget('temp_user_id');
        Session::forget('guest_shipping_info');

        return $success;
    }

    //redirects to this method after a successfull checkout
    public function checkout_done1($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            // Order paid notification to Customer, Seller, & Admin
            EmailUtility::order_email($order, 'paid'); 
            
            // Calculate Commission from seller, Customer Affiliate earning and Customers Club Point
            calculateCommissionAffilationClubPoint($order);
        }
        Session::put('combined_order_id', $combined_order_id);
    }
    
    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            // Order paid notification to Customer, Seller, & Admin
            EmailUtility::order_email($order, 'paid'); 
            
            // Calculate Commission from seller, Customer Affiliate earning and Customers Club Point
            calculateCommissionAffilationClubPoint($order);
        }
        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    // ================ Will not use after single page checkout ========[start]
    public function get_shipping_info(Request $request)
    {
        if(get_setting('guest_checkout_activation') == 0 && auth()->user() == null){
            return redirect()->route('user.login');
        }

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
        }
        else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->get() : [];
        }
        if ($carts && count($carts) > 0) {
            $categories = Category::all();
            return view('frontend.shipping_info', compact('categories', 'carts'));
        }
        flash(translate('Your cart is empty'))->success();
        return back();
    }

    public function store_shipping_info(Request $request)
    {
        $auth_user = auth()->user();
        $temp_user_id = $request->session()->has('temp_user_id') ? $request->session()->get('temp_user_id') : null;
        $guestCityId = null;

        if($auth_user == null && get_setting('guest_checkout_activation') == 0){
            return redirect()->route('user.login');
        }

        if($auth_user != null){
            if($request->address_id == null){
                flash(translate("Please add shipping address"))->warning();
                return redirect()->route('checkout.shipping_info');
            }

            $carts = Cart::where('user_id', $auth_user->id)->get();
            foreach ($carts as $key => $cartItem) {
                $cartItem->address_id = $request->address_id;
                $cartItem->save();
            }
        }
        else{
            if(get_setting('guest_checkout_activation') == 1){
                $guestCityId = resolve_city_id_for_state_wise_shipping($request->state_id, $request->city_id);
                if($request->name == null || $request->email == null || $request->address == null ||
                    $request->country_id == null || $request->state_id == null || $guestCityId == null ||
                        $request->postal_code == null || $request->phone == null) {
                    flash(translate("Please add shipping address"))->warning();
                    return redirect()->route('checkout.shipping_info');
                }
                $shipping_info['name'] = $request->name;
                $shipping_info['email'] = $request->email;
                $shipping_info['address'] = $request->address;
                $shipping_info['country_id'] = $request->country_id;
                $shipping_info['state_id'] = $request->state_id;
                $shipping_info['city_id'] = $guestCityId;
                $shipping_info['area_id'] = $request->area_id;
                $shipping_info['postal_code'] = $request->postal_code;
                $shipping_info['phone'] = '+'.$request->country_code.$request->phone;
                $shipping_info['longitude'] = $request->longitude;
                $shipping_info['latitude'] = $request->latitude;
                $request->session()->put('guest_shipping_info', $shipping_info);
            }
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->get() : [];
        }

        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $deliveryInfo = [];

        // Logged In User Delivery info
        if($auth_user != null){
            $address = Address::where('id', $carts[0]['address_id'])->first();
            $deliveryInfo['country_id'] = $address->country_id;
            $deliveryInfo['city_id'] = $address->city_id;
            $deliveryInfo['area_id'] = $address->area_id;
        }

        // Guest User Delivery info
        elseif($temp_user_id != null){
            $deliveryInfo['country_id'] = $request->country_id;
            $deliveryInfo['city_id'] = $guestCityId ?? $request->city_id;
            $deliveryInfo['area_id'] = $request->area_id;
        }

        $carrier_list = array();
        if (get_setting('shipping_type') == 'carrier_wise_shipping') {
            $country_id = $auth_user != null ? $carts[0]['address']['country_id'] : $request->country_id;
            $zone = Country::where('id', $country_id)->first()->zone_id;

            $carrier_query = Carrier::where('status', 1);
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();
        }

        return view('frontend.delivery_info', compact('carts', 'carrier_list', 'deliveryInfo'));
    }

    public function store_delivery_info(Request $request)
    {
        $authUser = auth()->user();
        $tempUser = $request->session()->has('temp_user_id') ? $request->session()->get('temp_user_id') : null;
        $carts = auth()->user() != null ?
                Cart::where('user_id', $authUser->id)->get() :
                ($tempUser != null ? Cart::where('temp_user_id', $request->session()->get('temp_user_id'))->get() : null);

        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $shipping_info = $authUser != null ? Address::where('id', $carts[0]['address_id'])->first() : null;
        $deliveryInfo = [];

        // Logged In User Delivery info
        if($authUser != null){
            $deliveryInfo['country_id'] = $shipping_info->country_id;
            $deliveryInfo['city_id'] = $shipping_info->city_id;
             $deliveryInfo['area_id'] = $shipping_info->area_id;
        }

        // Guest User Shipping info
        elseif($tempUser != null){
            $deliveryInfo['country_id'] = Session::get('guest_shipping_info')['country_id'];
            $deliveryInfo['city_id'] = Session::get('guest_shipping_info')['city_id'];
            $deliveryInfo['area_id'] = Session::get('guest_shipping_info')['area_id'];
        }

        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;

        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

                if (get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point') {
                    if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                    } else {
                        $cartItem['shipping_type'] = 'home_delivery';
                    }
                    $cartItem['shipping_cost'] = 0;
                    if ($cartItem['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_cost'] = getShippingCost($carts, $key, $deliveryInfo);
                    }
                } else {
                    $cartItem['shipping_type'] = 'carrier';
                    $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $deliveryInfo, $cartItem['carrier_id']);
                }

                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $total = $subtotal + $tax + $shipping;

            return view('frontend.payment_select', compact('carts', 'shipping_info', 'total'));
        } else {
            flash(translate('Your Cart was empty'))->warning();
            return redirect()->route('home');
        }
    }
    // ================ Will not use after single page checkout ========[End]

    public function apply_coupon_code(Request $request)
    {
        $user       = auth()->user();
        $temp_user  = Session::has('temp_user_id') ? Session::get('temp_user_id') : null;
        $coupon     = Coupon::where('code', $request->code)->first();
        $proceed    = $request->proceed;
        $response_message = array();

        // if the Coupon type is Welcome base, check the user has this coupon or not
        $canUseCoupon = true;
        if($coupon && $coupon->type == 'welcome_base'){
            if($user != null) {
                // $userCoupon = user assigned coupon
                $userCoupon = $user->userCoupon;
                if(!$userCoupon){
                    $canUseCoupon = false;
                }
            }
            else {
                $canUseCoupon = false;
            }
        }

        if ($coupon != null && $canUseCoupon) {

            //  Coupon expiry Check
            if($coupon->type != 'welcome_base') {
                $validationDateCheckCondition  = strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date;
            }
            else {
                $validationDateCheckCondition = false;
                if($userCoupon){
                    $validationDateCheckCondition  = $userCoupon->expiry_date >= strtotime(date('d-m-Y H:i:s')) ;
                }
            }
            if ($validationDateCheckCondition) {
                if (($user == null && Session::has('temp_user_id')) || CouponUsage::where('user_id', $user->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $user_carts = $user != null ?
                            Cart::where('user_id', $user->id)->where('owner_id', $coupon->user_id)->active()->get() :
                            Cart::where('owner_id', $coupon->user_id)->where('temp_user_id', $temp_user)->active()->get();

                    $coupon_discount = 0;

                    if ($coupon->type == 'cart_base' || $coupon->type == 'welcome_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($user_carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($coupon->type == 'cart_base' && $sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
                        }
                        elseif ($coupon->type == 'welcome_base' && $sum >= $userCoupon->min_buy)  {
                            $coupon_discount  = $userCoupon->discount_type == 'percent' ?  (($sum * $userCoupon->discount) / 100) : $userCoupon->discount;
                        }
                    }
                    elseif ($coupon->type == 'product_base') {
                        foreach ($user_carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }

                    if ($coupon_discount > 0) {

                        $user_carts->toQuery()->update(
                            [
                                'discount' => $coupon_discount / count($user_carts),
                                'coupon_code' => $request->code,
                                'coupon_applied' => 1
                            ]
                        );

                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    } else {
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }
                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        if ($user != null) {
            $carts = Cart::where('user_id', $user->id)->active()->get();
        } else {
            $carts = ($temp_user != null) ? Cart::where('temp_user_id', $temp_user)->active()->get() : [];
        }
        // $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $returnHTML = view('frontend.partials.cart.cart_summary', compact('coupon', 'carts', 'proceed'))->render();
        return response()->json(array('response_message' => $response_message, 'html'=>$returnHTML));
    }

    public function remove_coupon_code(Request $request)
    {
        $user       = auth()->user();
        $temp_user  = Session::has('temp_user_id') ? Session::get('temp_user_id') : null;
        $proceed    = $request->proceed;
        $carts = $user != null ? Cart::where('user_id', $user->id) : Cart::where('temp_user_id', $temp_user);
        $carts->update(
            [
                'discount' => 0.00,
                'coupon_code' => '',
                'coupon_applied' => 0
            ]
        );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = $carts->active()->get();

        // $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        return view('frontend.partials.cart.cart_summary', compact('coupon', 'carts', 'proceed'));
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        // Cart::where('user_id', $combined_order->user_id)
        //     ->delete();

        Session::forget('club_point');
        Session::forget('combined_order_id');

        foreach($combined_order->orders as $order){
            if($order->notified == 0){
                NotificationUtility::sendOrderPlacedNotification($order);
                $order->notified = 1;
                $order->save();
            }
        }

        // Extra SMS after successful order confirmation (OTP uses SMS; other notifications remain email)
        try {
            $shipping = json_decode($combined_order->shipping_address);
            $to = $shipping->phone ?? null;
            if (!empty($to)) {
                $site = get_setting('site_name') ?: config('app.name');
                $text = 'Order confirmed. Thank you for choosing ' . $site . '.';
                (new SendSmsService)->sendSMS($to, env('MIM_SENDER_ID'), $text, null);
            }
        } catch (\Exception $e) {
            // avoid breaking order confirmed page
        }

        $this->sendCheckoutNewAccountSetupEmail($combined_order->orders->first()->code ?? null);

        return view('frontend.order_confirmed', compact('combined_order'));
    }

    /**
     * After checkout, email new auto-created customers a link to set their password.
     */
    private function sendCheckoutNewAccountSetupEmail(?string $orderCode = null): void
    {
        $userId = Session::get('checkout_new_account_user_id');
        if (!$userId) {
            return;
        }

        Session::forget('checkout_new_account_user_id');

        $user = User::find($userId);
        if (!$user || empty($user->email)) {
            return;
        }

        try {
            EmailUtility::sendCheckoutAccountPasswordSetupEmail($user, $orderCode);
        } catch (\Exception $e) {
            Log::warning('checkout_account_setup_email_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function guestCustomerInfoCheck(Request $request){
        $user = addon_is_activated('otp_system') ?
                User::where('email', $request->email)->orWhere('phone','+'.$request->phone)->first() :
                User::where('email', $request->email)->first();
        return ($user != null) ? true : false;
    }

    public function updateDeliveryAddress(Request $request)
    {
        $proceed = 0;
        $default_carrier_id = null;
        $default_shipping_type = 'home_delivery';
        $user = auth()->user();
        $shipping_info = array();

        $carts = $user != null ?
                Cart::where('user_id', $user->id)->active()->get() :
                Cart::where('temp_user_id', $request->session()->get('temp_user_id'))->active()->get();

        $carts->toQuery()->update(['address_id' => $request->address_id]);

        $country_id = $user != null ?
                    Address::findOrFail($request->address_id)->country_id :
                    $request->address_id;
        $city_id = $user != null ?
                    Address::findOrFail($request->address_id)->city_id :
                    $request->city_id;
        $area_id = $user != null ?
                    Address::findOrFail($request->address_id)->area_id :
                    $request->area_id;
        $state_id = $user != null ?
                    (Address::findOrFail($request->address_id)->state_id ?? 0) :
                    (int) ($request->state_id ?? 0);

        if (! checkout_requires_city_for_shipping_quote()) {
            $city_id = resolve_city_id_for_state_wise_shipping($state_id, $city_id) ?: $city_id;
        }

        $shipping_info['country_id'] = $country_id;
        $shipping_info['city_id'] = $city_id;
        $shipping_info['area_id'] = $area_id;
        $shipping_info['state_id'] = $state_id;
        $carrier_list = array();
        if (get_setting('shipping_type') == 'carrier_wise_shipping') {
            $default_shipping_type = 'carrier';
            //$zone = Country::where('id', $country_id)->first()->zone_id;
            $zone = $country_id != 0 ? (optional(Country::where('id', $country_id)->where('status', 1)->first())->zone_id ?? 0) : 0;

            $carrier_query = Carrier::where('status', 1);
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();

            if (count($carrier_list) > 1) {
                $default_carrier_id = $carrier_list->toQuery()->first()->id;
            }
        }

        $carts = $carts->fresh();

        foreach ($carts as $key => $cartItem) {
            if (get_setting('shipping_type') == 'carrier_wise_shipping') {
                $cartItem['shipping_cost'] = getShippingCost($carts, $key, $shipping_info, $default_carrier_id);
            } else {
                $cartItem['shipping_cost'] = getShippingCost($carts, $key, $shipping_info);
            }
            $cartItem['address_id'] = $user != null ? $request->address_id : 0;
            $cartItem['shipping_type'] = $default_shipping_type;
            $cartItem['carrier_id'] = $default_carrier_id;
            $cartItem->save();
        }

        $carts = $carts->fresh();

        return array(
            'delivery_info' => view('frontend.partials.cart.delivery_info', compact('carts', 'carrier_list', 'shipping_info'))->render(),
            'cart_summary' => view('frontend.partials.cart.cart_summary', compact('carts', 'proceed'))->render(),
            'carrier_count' => count($carrier_list)
        );
    }

    public function updateBillingAddress(Request $request)
    {
        $user = auth()->user();

        $carts = $user != null ?
                Cart::where('user_id', $user->id)->active()->get() :
                Cart::where('temp_user_id', $request->session()->get('temp_user_id'))->active()->get();
        $carts->toQuery()->update(['billing_address' => $request->billing_address_id]);
        $carts = $carts->fresh();
    }

    public function updateDeliveryInfo(Request $request)
    {
        $proceed = 0;
        $user = auth()->user();
        $shipping_info = array();

        if ($user != null) {
            $carts = Cart::where('user_id', $user->id)->active()->get();
        }
        else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->active()->get() : [];
        }

        $user_carts = $carts->toQuery()->where('owner_id', $request->user_id)->get();

        $country_id = $user != null ?
                    Address::findOrFail($carts[0]->address_id)->country_id : $request->country_id;
        $city_id = $user != null ?
                    Address::findOrFail($carts[0]->address_id)->city_id : $request->city_id;
        $area_id = $user != null ?
                    Address::findOrFail($carts[0]->address_id)->area_id : $request->area_id;
        $state_id = $user != null ?
                    (Address::findOrFail($carts[0]->address_id)->state_id ?? 0) :
                    (int) ($request->state_id ?? 0);
        if (! checkout_requires_city_for_shipping_quote()) {
            $city_id = resolve_city_id_for_state_wise_shipping($state_id, $city_id) ?: $city_id;
        }
        $shipping_info['country_id'] = $country_id;
        $shipping_info['city_id'] = $city_id;
        $shipping_info['area_id'] = $area_id;
        $shipping_info['state_id'] = $state_id;
        $shipping_type = $request->shipping_type;
        foreach ($user_carts as $key => $cartItem) {
            if ($shipping_type != 'carrier' || $shipping_type == 'pickup_point') {
                if ($shipping_type == 'pickup_point') {
                    $cartItem['shipping_type'] = 'pickup_point';
                    $cartItem['pickup_point'] = $request->type_id;
                } else {
                    $cartItem['shipping_type'] = 'home_delivery';
                }
                $cartItem['shipping_cost'] = 0;
                if ($cartItem['shipping_type'] == 'home_delivery') {
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $shipping_info);
                }
            } else {
                $cartItem['shipping_type'] = 'carrier';
                $cartItem['carrier_id'] = $request->type_id;
                $cartItem['shipping_cost'] = getShippingCost($user_carts, $key, $shipping_info, $cartItem['carrier_id']);
            }

            $cartItem->save();
        }

        $carts = $carts->fresh();

        return view('frontend.partials.cart.cart_summary', compact('carts', 'proceed'))->render();
    }

    public function orderRePayment(Request $request){
        $order = Order::findOrFail($request->order_id);
        if($order != null){
            $request->session()->put('payment_type', 'order_re_payment');
            $data['order_id'] = $order->id;
            $data['payment_method'] = $request->payment_option;
            $request->session()->put('payment_data', $data);

            // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
            $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
            if (class_exists($decorator)) {
                return (new $decorator)->pay($request);
            }
            else {
                $manual_payment_data = array(
                    'name'   => $request->payment_option,
                    'amount' => $order->grand_total,
                    'trx_id' => $request->trx_id,
                    'photo'  => $request->photo
                );

                $order->payment_type = $request->payment_option;
                $order->manual_payment = 1;
                $order->manual_payment_data = json_encode($manual_payment_data);
                $order->save();

                flash(translate('Payment done.'))->success();
                return redirect()->route('purchase_history.details', encrypt($order->id));
            }
        }
        flash(translate('Order Not Found'))->warning();
        return back();
    }

    public function orderRePaymentDone($payment_data, $payment_details = null)
    {
        $order = Order::findOrFail($payment_data['order_id']);
        $order->payment_status = 'paid';
        $order->payment_details = $payment_details;
        $order->payment_type = $payment_data['payment_method'];
        $order->save();
        calculateCommissionAffilationClubPoint($order);

        if($order->notified == 0){
            NotificationUtility::sendOrderPlacedNotification($order);
            $order->notified = 1;
            $order->save();
        }

        Session::forget('payment_type');
        Session::forget('order_id');

        flash(translate('Payment done.'))->success();
        return redirect()->route('purchase_history.details', encrypt($order->id));
    }
}
