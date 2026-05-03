@extends('frontend.layouts.app')

@section('content')

    <section class="my-4 gry-bg">
        <div class="container">
            <div class="row cols-xs-space cols-sm-space cols-md-space">
                <div class="col-lg-8 mx-auto">
                    <form class="form-default" data-toggle="validator" action="{{ route('payment.checkout') }}" role="form" method="POST" id="checkout-form">
                        @csrf

                        <div class="accordion" id="accordioncCheckoutInfo">

                            <!-- Shipping Info -->
                            <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem;">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingShippingInfo" type="button" data-toggle="collapse" data-target="#collapseShippingInfo" aria-expanded="true" aria-controls="collapseShippingInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Shipping Info') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapseShippingInfo" class="collapse show" aria-labelledby="headingShippingInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="shipping_info">
                                       @include('frontend.partials.cart.shipping_info', ['address_id' => $address_id])
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Info (hidden — shipping defaults applied server-side) -->
                            <div id="rm-checkout-delivery-section" class="card rounded-0 border shadow-none d-none" style="margin-bottom: 2rem; overflow: visible !important;">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingDeliveryInfo" type="button" data-toggle="collapse" data-target="#collapseDeliveryInfo" aria-expanded="true" aria-controls="collapseDeliveryInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Delivery Info') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapseDeliveryInfo" class="collapse show" aria-labelledby="headingDeliveryInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="delivery_info">
                                        @include('frontend.partials.cart.delivery_info', ['carts' => $carts, 'carrier_list' => $carrier_list, 'shipping_info' => $shipping_info])
                                    </div>
                                </div>
                            </div>


                            <!-- Payment Info -->
                            <div class="card rounded-0 mb-0 border shadow-none">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingPaymentInfo" type="button" data-toggle="collapse" data-target="#collapsePaymentInfo" aria-expanded="true" aria-controls="collapsePaymentInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Payment') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapsePaymentInfo" class="collapse show" aria-labelledby="headingPaymentInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="payment_info">
                                        @include('frontend.partials.cart.payment_info', ['carts' => $carts, 'total' => $total])

                                        <!-- Agree Box -->
                                        <div class="pt-2rem fs-14">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" required id="agree_checkbox" checked onchange="stepCompletionPaymentInfo()">
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate('I agree to the') }}</span>
                                            </label>
                                            <a href="{{ route('terms') }}"
                                                class="fw-700">{{ translate('terms and conditions') }}</a>,
                                            <a href="{{ route('returnpolicy') }}"
                                                class="fw-700">{{ translate('return policy') }}</a> &
                                            <a href="{{ route('privacypolicy') }}"
                                                class="fw-700">{{ translate('privacy policy') }}</a>
                                        </div>

                                        <div class="row align-items-center pt-3 mb-4">
                                            <!-- Return to shop -->
                                            <div class="col-6">
                                                <a href="{{ route('home') }}" class="btn btn-link fs-14 fw-700 px-0">
                                                    <i class="las la-arrow-left fs-16"></i>
                                                    {{ translate('Return to shop') }}
                                                </a>
                                            </div>
                                            <!-- Complete Ordert -->
                                            <div class="col-6 text-right">
                                                <button type="button" onclick="submitOrder(this)" id="submitOrderBtn"
                                                    class="btn btn-primary fs-14 fw-700 rounded-0 px-4">
                                                    <span class="rm-complete-order-text">{{ translate('Complete Order') }}</span>
                                                    <span class="rm-complete-order-spinner d-none" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
                <!-- Cart Summary -->
                <div class="col-lg-4 mt-4 mt-lg-0" id="cart_summary">
                    @include('frontend.partials.cart.cart_summary', ['proceed' => 0, 'carts' => $carts])
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modal')
    <!-- Address Modal -->
    @if(Auth::check())
        @include('frontend.partials.address.address_modal')
         @include('frontend.partials.address.billing_address_modal')
    @endif

    <!-- Checkout OTP Modal (guest) -->
    <div class="modal fade" id="rm-checkout-otp-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Verify OTP') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 text-muted">{{ translate('We sent an OTP to your email. Enter it to continue.') }}</div>
                    <div class="form-group mb-2">
                        <input type="text" id="rm-checkout-otp-code" class="form-control rounded-0"
                            inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            placeholder="{{ translate('Enter 6 digit OTP') }}">
                        <small class="text-danger d-none" id="rm-checkout-otp-error"></small>
                    </div>
                    <button type="button" class="btn btn-dark rounded-0 px-4" id="rm-checkout-otp-verify-btn">
                        <span class="rm-btn-text">{{ translate('Verify and Complete Order') }}</span>
                        <span class="rm-spinner d-none" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
       var carrierCount=0;
        var rmCheckout = {
            isGuest: {{ Auth::check() ? 'false' : 'true' }},
            otpVerified: {{ (session()->has('checkout_otp_user_id') && session('checkout_otp_verified') == session('checkout_otp_user_id')) ? 'true' : 'false' }},
            requestOtpUrl: "{{ route('checkout.request_otp') }}",
            verifyOtpUrl: "{{ route('checkout.verify_otp') }}"
        };

        function rmSetBtnLoading($btn, isLoading, loadingText) {
            if (!$btn || !$btn.length) return;
            $btn.prop('disabled', !!isLoading);
            $btn.toggleClass('disabled', !!isLoading);
            // Prefer inline label + spinner (for Complete Order button)
            var $label = $btn.find('.rm-complete-order-text');
            var $spinner = $btn.find('.rm-complete-order-spinner');
            if ($label.length && $spinner.length) {
                if (!$btn.data('rm-original-html')) {
                    $btn.data('rm-original-label', $label.text().trim());
                }
                if (isLoading) {
                    $label.text(loadingText || $btn.data('rm-original-label') || 'Please wait...');
                    $spinner.removeClass('d-none');
                } else {
                    $label.text($btn.data('rm-original-label') || 'Complete Order');
                    $spinner.addClass('d-none');
                }
                return;
            }

            // Fallback for other buttons
            var original = $btn.data('rm-original-text');
            if (!original) {
                $btn.data('rm-original-text', $btn.text().trim());
                original = $btn.data('rm-original-text');
            }
            if (isLoading) {
                $btn.text(loadingText || 'Please wait...');
            } else {
                $btn.text(original);
            }
        }

        // Simple spinner style (modal button uses .rm-spinner)
        (function () {
            if (document.getElementById('rm-inline-spinner-style')) return;
            var css = document.createElement('style');
            css.id = 'rm-inline-spinner-style';
            css.innerHTML = ".rm-spinner,.rm-complete-order-spinner{display:inline-block;width:14px;height:14px;margin-left:10px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:rmspin .8s linear infinite}.rm-complete-order-spinner{vertical-align:-2px}@keyframes rmspin{to{transform:rotate(360deg)}}";
            document.head.appendChild(css);
        })();

        $(document).ready(function() {
            $(".online_payment").click(function() {
                $('#manual_payment_description').parent().addClass('d-none');
            });
            toggleManualPaymentData($('input[name=payment_option]:checked').data('id'));

            // OTP modal verify action
            $('#rm-checkout-otp-verify-btn').on('click', function () {
                var $btn = $(this);
                var code = ($('#rm-checkout-otp-code').val() || '').trim();
                $('#rm-checkout-otp-error').addClass('d-none').text('');

                if (!/^\d{6}$/.test(code)) {
                    $('#rm-checkout-otp-error').removeClass('d-none').text("{{ translate('Please enter a valid 6 digit OTP') }}");
                    return;
                }

                $btn.find('.rm-btn-text').text("{{ translate('Verifying...') }}");
                $btn.find('.rm-spinner').removeClass('d-none');
                $btn.prop('disabled', true);

                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    method: "POST",
                    url: rmCheckout.verifyOtpUrl,
                    data: { verification_code: code },
                    success: function (res) {
                        rmCheckout.otpVerified = true;
                        $('#rm-checkout-otp-modal').modal('hide');
                        // Now submit checkout for final order/payment
                        $('#checkout-form').submit();
                    },
                    error: function (xhr) {
                        var msg = "{{ translate('OTP does not match. Please try again.') }}";
                        try {
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = Array.isArray(xhr.responseJSON.message) ? xhr.responseJSON.message.join(', ') : xhr.responseJSON.message;
                            }
                        } catch (e) {}
                        $('#rm-checkout-otp-error').removeClass('d-none').text(msg);
                        $btn.prop('disabled', false);
                        $btn.find('.rm-spinner').addClass('d-none');
                        $btn.find('.rm-btn-text').text("{{ translate('Verify and Complete Order') }}");
                    }
                });
            });
        });

        var minimum_order_amount_check = {{ get_setting('minimum_order_amount_check') == 1 ? 1 : 0 }};
        var minimum_order_amount =
            {{ get_setting('minimum_order_amount_check') == 1 ? get_setting('minimum_order_amount') : 0 }};

        function use_wallet() {
            $('input[name=payment_option]').val('wallet');
            if ($('#agree_checkbox').is(":checked")) {
                ;
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var allIsOk = false;
                    var isOkShipping = stepCompletionShippingInfo();
                    var isOkDelivery = stepCompletionDeliveryInfo();
                    var isOkPayment = stepCompletionWalletPaymentInfo();
                    if(isOkShipping && isOkDelivery && isOkPayment) {
                        allIsOk = true;
                    }else{
                        AIZ.plugins.notify('danger', '{{ translate("Please fill in all mandatory fields!") }}');
                        $('#checkout-form [required]').each(function (i, el) {
                            if ($(el).val() == '' || $(el).val() == undefined) {
                                var is_trx_id = $('.d-none #trx_id').length;
                                if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                    $(el).focus();
                                    $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                    return false;
                                }
                            }
                        });
                    }

                    if (allIsOk) {
                        $('#checkout-form').submit();
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
            }
        }

        function submitOrder(el) {
            var $btn = $(el);
            rmSetBtnLoading($btn, true, "{{ translate('Processing...') }}");
            if ($('#agree_checkbox').is(":checked")) {
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var offline_payment_active = '{{ addon_is_activated('offline_payment') }}';
                    if (offline_payment_active == '1' && $('.offline_payment_option').is(":checked") && $('#trx_id')
                        .val() == '') {
                        AIZ.plugins.notify('danger', '{{ translate('You need to put Transaction id') }}');
                        $(el).prop('disabled', false);
                    } else {
                        var allIsOk = false;
                        var isOkShipping = stepCompletionShippingInfo();
                        var isOkDelivery = stepCompletionDeliveryInfo();
                        var isOkPayment = stepCompletionPaymentInfo();
                        if(isOkShipping && isOkDelivery && isOkPayment) {
                            allIsOk = true;
                        }else{
                            AIZ.plugins.notify('danger', '{{ translate("Please fill in all mandatory fields!") }}');
                            $('#checkout-form [required]').each(function (i, el) {
                                if ($(el).val() == '' || $(el).val() == undefined) {
                                    var is_trx_id = $('.d-none #trx_id').length;
                                    if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                        $(el).focus();
                                        $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                        return false;
                                    }
                                }
                            });
                        }

                        if (allIsOk) {
                            // Guest flow: request OTP first, show modal, then finally submit after verification
                            if (rmCheckout.isGuest && !rmCheckout.otpVerified) {
                                rmSetBtnLoading($btn, true, "{{ translate('Sending OTP...') }}");
                                $.ajax({
                                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                                    method: "POST",
                                    url: rmCheckout.requestOtpUrl,
                                    data: $('#checkout-form').serialize(),
                                    success: function (res) {
                                        rmSetBtnLoading($btn, false);
                                        $('#rm-checkout-otp-code').val('');
                                        $('#rm-checkout-otp-error').addClass('d-none').text('');
                                        $('#rm-checkout-otp-verify-btn').prop('disabled', false);
                                        $('#rm-checkout-otp-verify-btn').find('.rm-spinner').addClass('d-none');
                                        $('#rm-checkout-otp-verify-btn').find('.rm-btn-text').text("{{ translate('Verify and Complete Order') }}");
                                        $('#rm-checkout-otp-modal').modal({ backdrop: 'static', keyboard: false });
                                    },
                                    error: function (xhr) {
                                        rmSetBtnLoading($btn, false);
                                        var msg = "{{ translate('Could not send OTP. Please try again later.') }}";
                                        try {
                                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                                msg = Array.isArray(xhr.responseJSON.message) ? xhr.responseJSON.message.join(', ') : xhr.responseJSON.message;
                                            }
                                        } catch (e) {}
                                        AIZ.plugins.notify('danger', msg);
                                    }
                                });
                                return;
                            }

                            $('#checkout-form').submit();
                        }
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
                rmSetBtnLoading($btn, false);
            }
        }

        function toggleManualPaymentData(id) {
            if (typeof id != 'undefined') {
                $('#manual_payment_description').parent().removeClass('d-none');
                $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            }
        }
        // coupon apply
        $(document).on("click", "#coupon-apply", function() {
            @if (Auth::check())
                @if(Auth::user()->user_type != 'customer')
                    AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to apply coupon code.') }}");
                    return false;
                @endif

                var data = new FormData($('#apply-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.apply_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        AIZ.plugins.notify(data.response_message.response, data.response_message.message);
                        $("#cart_summary").html(data.html);
                    }
                });
            @else
                $('#login_modal').modal('show');
            @endif
        });

        // coupon remove
        $(document).on("click", "#coupon-remove", function() {
            @if (Auth::check() && Auth::user()->user_type == 'customer')
                var data = new FormData($('#remove-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.remove_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        $("#cart_summary").html(data);
                    }
                });
            @endif
        });

        function rmShippingInfoCountryId() {
            var $r = $('#shipping_info');
            if (!$r.length) {
                return $('select[name="country_id"]').val() != null ? $('select[name="country_id"]').val() : ($('input[name="country_id"]').val() || 0);
            }
            if ($r.find('select[name="country_id"]').length) {
                return $r.find('select[name="country_id"]').val() || 0;
            }
            return $r.find('input[name="country_id"]').val() || 0;
        }

        function rmShippingInfoCityId() {
            var $r = $('#shipping_info');
            if (!$r.length) {
                var $gh = $('input[type="hidden"][name="city_id"]');
                if ($gh.length) {
                    return $gh.val() || 0;
                }
                return $('select[name="city_id"]').val() != null ? $('select[name="city_id"]').val() : 0;
            }
            var $h = $r.find('input[type="hidden"][name="city_id"]');
            if ($h.length) {
                return $h.val() || 0;
            }
            var $s = $r.find('select[name="city_id"]');
            return $s.length ? ($s.val() || 0) : 0;
        }

        function updateDeliveryAddress(id, city_id = 0, area_id=0) {
            $('.aiz-refresh').addClass('active');
            var state_id = $('select[name="state_id"]').length ? ($('select[name="state_id"]').val() || 0) : 0;
            $.post('{{ route('checkout.updateDeliveryAddress') }}', {
                _token: AIZ.data.csrf,
                address_id: id,
                city_id: city_id,
                area_id: area_id,
                state_id: state_id
            }, function(data) {
                $('#delivery_info').html(data.delivery_info);
                $('#cart_summary').html(data.cart_summary);
                $('.aiz-refresh').removeClass('active');
                carrierCount = data.carrier_count;
                checkCarrerShippingInfo();
            });
           
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function updateBillingAddress(id) {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateBillingAddress') }}', {
                _token: AIZ.data.csrf,
                billing_address_id: id
            }, function(data) {
                $('.aiz-refresh').removeClass('active');
            });
        }

        function stepCompletionShippingInfo() {
            var headColor = '#9d9da6';
            var allOk = false;
            @if (Auth::check())
                var length = $('input[name="address_id"]:checked').length;
                if (length > 0) {
                    headColor = '#15a405';
                    allOk = true;
                }
            @else
                var count = 0;
                var length = $('#shipping_info [required]').length;
                $('#shipping_info [required]').each(function (i, el) {
                    if ($(el).val() != '' && $(el).val() != undefined && $(el).val() != null) {
                        count += 1;
                    }
                });
                if (count == length) {
                    headColor = '#15a405';
                    allOk = true;
                }
            @endif

            $('#headingShippingInfo svg *').css('fill', headColor);
            return allOk;
        }

        $('#shipping_info [required]').each(function (i, el) {
            $(el).change(function(){
                if ($(el).attr('name') == 'address_id') {
                    updateDeliveryAddress($(el).val());
                    setDefaultshippingAddress();
                    setBillingAddress();
                }
                @if (get_setting('shipping_type') == 'area_wise_shipping')
                    if ($(el).attr('name') == 'city_id') {
                        let country_id = $('select[name="country_id"]').length? $('select[name="country_id"]').val() : $('input[name="country_id"]').val();
                        let city_id = $(this).val();
                        updateDeliveryAddress(country_id, city_id);
                    }
                @endif
                if ($(el).attr('name') == 'billing_address_id') {
                    setBillingAddress(el);
                }
                
                
                stepCompletionShippingInfo();
            });
        });

        $('select[name="area_id"].guest-checkout').change(function () {
            let country_id = rmShippingInfoCountryId();
            let city_id = rmShippingInfoCityId();
            let area_id = $(this).val();

            if (area_id) {
                updateDeliveryAddress(country_id, city_id, area_id);
            } else {
                updateDeliveryAddress(country_id, city_id);
            }

            stepCompletionShippingInfo();
        });

        @if (!checkout_requires_city_for_shipping_quote())
        $('select[name="state_id"].guest-checkout').on('change', function () {
            let country_id = rmShippingInfoCountryId();
            let city_id = rmShippingInfoCityId();
            let area_id = $('select[name="area_id"]').length ? ($('select[name="area_id"]').val() || 0) : 0;
            let stateVal = $(this).val();
            if (country_id && stateVal) {
                updateDeliveryAddress(country_id, city_id, area_id);
            }
            stepCompletionShippingInfo();
        });
        @endif

        function stepCompletionDeliveryInfo() {
            if ($('#rm-checkout-delivery-section').length && $('#rm-checkout-delivery-section').hasClass('d-none')) {
                return true;
            }
            var headColor = '#9d9da6';
            var allOk = false;
            var content = $('#delivery_info [required]');
            if (content.length > 0) {
                var content_checked = $('#delivery_info [required]:checked');
                if (content_checked.length > 0) {
                    content_checked.each(function (i, el) {
                        allOk = false;
                        if($(el).val() == 'carrier'){
                            var owner = $(el).attr('data-owner');
                            if ($('input[name=carrier_id_'+owner+']:checked').length > 0) {
                                allOk = true;
                            }
                        }else if($(el).val() == 'pickup_point'){
                            var owner = $(el).attr('data-owner');
                            if ($('select[name="pickup_point_id_'+owner+'"]').val() != '') {
                                allOk = true;
                            }
                        }else{
                            allOk = true;
                        }

                        if(allOk == false) {
                            return false;
                        }
                    });

                    if (allOk) {
                        headColor = '#15a405';
                    }
                }
            }else{
                allOk = true
                headColor = '#15a405';
            }

            $('#headingDeliveryInfo svg *').css('fill', headColor);
            return allOk;
        }

        function updateDeliveryInfo(shipping_type, type_id, user_id, country_id = 0, city_id = 0) {
            country_id = rmShippingInfoCountryId();
            city_id = rmShippingInfoCityId();
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryInfo') }}', {
                _token: AIZ.data.csrf,
                shipping_type: shipping_type,
                type_id: type_id,
                user_id: user_id,
                country_id: country_id,
                city_id: city_id
            }, function(data) {
                $('#cart_summary').html(data);
                checkCarrerShippingInfo();
                stepCompletionDeliveryInfo();
                $('.aiz-refresh').removeClass('active');
            });
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function show_pickup_point(el, user_id) {
        	var type = $(el).val();
        	var target = $(el).data('target');
            var type_id = null;

        	if(type == 'home_delivery' || type == 'carrier'){
                if(!$(target).hasClass('d-none')){
                    $(target).addClass('d-none');
                }
                $('.carrier_id_'+user_id).removeClass('d-none');
        	}else{
        		$(target).removeClass('d-none');
        		$('.carrier_id_'+user_id).addClass('d-none');
        	}

            if(type == 'carrier'){
                type_id = $('input[name=carrier_id_'+user_id+']:checked').val();
            }else if(type == 'pickup_point'){
                type_id = $('select[name=pickup_point_id_'+user_id+']').val();
            }
            updateDeliveryInfo(type, type_id, user_id);
        }

        function stepCompletionPaymentInfo() {
            var headColor = '#9d9da6';
            var payment = false;
            var agree = false;
            var allOk = false;
            var length = $('input[name="payment_option"]:checked').length;
            if(length > 0){
                if ($('input[name="payment_option"]:checked').hasClass('offline_payment_option')) {
                    if ($('#trx_id').val() != '' && $('#trx_id').val() != undefined && $('#trx_id').val() != null) {
                        payment = true;
                    }
                } else {
                    payment = true;
                }

                if ($('#agree_checkbox').is(":checked")){
                    agree = true;
                }

                if (payment && agree) {
                    headColor = '#15a405';
                    allOk = true;
                }
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            return allOk;
        }

        function stepCompletionWalletPaymentInfo() {
            var headColor = '#9d9da6';
            var allOk = false;
            if ($('#agree_checkbox').is(":checked")){
                headColor = '#15a405';
                allOk = true;
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            return allOk;
        }

        $('input[name="payment_option"]').change(function(){
            stepCompletionPaymentInfo();
        });

        function checkCarrerShippingInfo(){
           const shippingType = @json(get_setting('shipping_type'));
            let carrierSelected = false;
            let pickupSelected = false;
            $('.shipping-type-radio').each(function () {
                if ($(this).is(':checked') && $(this).val() === 'carrier') {
                    carrierSelected = true;
                }
            });
            $('.shipping-type-radio').each(function () {
                if ($(this).is(':checked') && $(this).val() === 'pickup_point') {
                    pickupSelected = true;
                }
            });
            // Keep "Complete Order" enabled; validate on click instead
            $('#agree_checkbox').prop('disabled', false);
            $('.online_payment, .offline_payment_option').prop('disabled', false);
        }

        $(document).ready(function(){
            carrierCount = parseInt(document.getElementById('carrierCount')?.value || 0);
            checkCarrerShippingInfo();
            stepCompletionShippingInfo();
            stepCompletionDeliveryInfo();
            stepCompletionPaymentInfo();
            
        });

        function changeShippingAddress(){
            $('#choose-address-modal').modal('hide');
        }

        function setDefaultshippingAddress() {
            let checkedAddress = $('input[name="address_id"]:checked');

            if (checkedAddress.length) {

                let selectedText = checkedAddress.closest('label').find('.address-text').html();
                $('#choose-default').html(selectedText);
                $('#default-address-change-btn').attr('onclick', "edit_address('" + checkedAddress.val() + "')");
                $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                let $box = $('#default-address-box');
                if ($box.length) {
                    $box.removeClass('border-danger');
                    checkedAddress.prop('checked', true);
                    checkedAddress.prop('disabled', false);
                    $box.find('#hide-no-longer-div').remove();
                    
                }
            }
        }

        function setBillingAddress(el) {
            let type = $(el).data('type');
            let checkedAddress = $(el);
           if(type === 'billing'){
                let checkedAddress = $('input[name="billing_address_id"]:checked');
                if (checkedAddress.length) {

                    let selectedText = checkedAddress.closest('label').find('.address-text').html();
                    $('#choose-default-billing').html(selectedText);
                    $('#default-address-change-btn').attr('onclick', "edit_billing_address('" + checkedAddress.val() + "')");
                    $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                    let $box = $('#default-billing-address-box');
                    if ($box.length) {
                        $box.removeClass('border-danger');
                        checkedAddress.prop('checked', true);
                        checkedAddress.prop('disabled', false);
                        $box.find('#hide-no-valid-div').remove();
                        
                    }
                }
            } else{
                let checkedAddress = $('input[name="address_id"]:checked');
                if (checkedAddress.length) {
                    let selectedText = checkedAddress.closest('label').find('.address-text').html();
                    $('#choose-default-billing').html(selectedText);
                    $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                }
            }
            updateBillingAddress(checkedAddress.val());
        }


    </script>

    @include('frontend.partials.address.address_js')

    @if(get_active_countries()->count() == 1)
    <script>
        $(document).ready(function() {
            @if(get_setting('has_state') == 1)
                get_states(@json(get_active_countries()[0]->id));
                @if(get_setting('billing_address_required') == 1)
                  get_billing_states(@json(get_active_countries()[0]->id));
                @endif
            @else
                get_city_by_country(@json(get_active_countries()[0]->id));
                @if(get_setting('billing_address_required') == 1)
                  get_billing_city_by_country(@json(get_active_countries()[0]->id));
                @endif
            @endif
        });
         @if(get_setting('shipping_type') == 'carrier_wise_shipping' && !Auth::check() )
            updateDeliveryAddress({{ get_active_countries()[0]->id }});
         @endif
    </script>
    @endif

    @if (get_setting('google_map') == 1)
        @include('frontend.partials.google_map')
    @endif

@endsection
