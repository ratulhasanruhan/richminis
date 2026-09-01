<div class="tab-content" id="shippingTabContent">
    <!--Shipping Address-->
    <div class="tab-pane fade show active" id="shipping-address" role="tabpanel"
        aria-labelledby="shipping-address-tab">
        <div class="p-3">
            <!-- Full name -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Full Name')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <input class="form-control mb-3 rounded-0" placeholder="{{ translate('Your full name')}}" rows="2" name="name" required></input>
                </div>
            </div>

            <!-- Phone -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Phone')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <div class="mb-3">
                        <input type="tel" id="phone-code" class="form-control rounded-0" placeholder="" name="phone" autocomplete="off" required>
                        <input type="hidden" name="country_code" value="">
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Email')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <input type="email" class="form-control mb-3 rounded-0" placeholder="{{ translate('Your Email')}}" name="email" value="" required>
                </div>
            </div>

            @php
                $activeCountries = get_active_countries();
                $bdCountry = null;
                try {
                    $bdCountry = $activeCountries->where('code', 'BD')->first();
                    if (!$bdCountry) {
                        $bdCountry = $activeCountries->where('name', 'Bangladesh')->first();
                    }
                } catch (\Throwable $e) {
                    $bdCountry = null;
                }
                $defaultCountryId = ($bdCountry ? $bdCountry->id : null) ?? ($activeCountries->first()->id ?? null);
            @endphp

            <!-- Country -->
            @if ($activeCountries->count() > 1)
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Country')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <div class="mb-3">
                        <select class="form-control aiz-selectpicker rounded-0" @if (get_setting('shipping_type') == 'carrier_wise_shipping') onchange="updateDeliveryAddress(this.value)" @endif
                            data-live-search="true" data-placeholder="{{ translate('Select your country') }}" name="country_id" required>
                            <option value="">{{ translate('Select your country') }}</option>
                            @foreach ($activeCountries as $key => $country)
                                <option value="{{ $country->id }}" @if($defaultCountryId && (int)$country->id === (int)$defaultCountryId) selected @endif>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @elseif($activeCountries->count() == 1)
            <input type="hidden" name="country_id" value="{{ $defaultCountryId }}">
            @endif

            <!-- District / City (state) -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('District/City')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0 guest-checkout" data-live-search="true" name="state_id" required>

                    </select>
                </div>
            </div>

            <!-- City (hidden when state-wise shipping: server picks a city in the state) -->
            @if (!checkout_requires_city_for_shipping_quote())
            <input type="hidden" name="city_id" value="">
            @else
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('City')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0" data-live-search="true" name="city_id" required>

                    </select>
                </div>
            </div>
            @endif

            <!-- Address -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Address')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <textarea class="form-control mb-3 rounded-0" placeholder="{{ translate('Your Address')}}" rows="2" name="address" required></textarea>
                </div>
            </div>

            <!--Area-->
            <div class="row area-field d-none">
                <div class="col-md-2">
                    <label>{{ translate('Area')}}<span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0 guest-checkout" data-live-search="true" name="area_id">

                    </select>
                </div>
            </div>

            @if (get_setting('google_map') == 1)
                <!-- Google Map -->
                <div class="row mt-3 mb-3">
                    <input id="searchInput" class="controls" type="text" placeholder="{{translate('Enter a location')}}">
                    <div id="map"></div>
                    <ul id="geoData">
                        <li style="display: none;">{{ translate('Address') }}: <span id="location"></span></li>
                        <li style="display: none;">Postal Code: <span id="postal_code"></span></li>
                        <li style="display: none;">Country: <span id="country"></span></li>
                        <li style="display: none;">Latitude: <span id="lat"></span></li>
                        <li style="display: none;">Longitude: <span id="lon"></span></li>
                    </ul>
                </div>
                <!-- Longitude -->
                <div class="row">
                    <div class="col-md-2" id="">
                        <label for="exampleInputuname">{{ translate('Longitude')}}</label>
                    </div>
                    <div class="col-md-10" id="">
                        <input type="text" class="form-control mb-3 rounded-0" id="longitude" name="longitude" readonly="">
                    </div>
                </div>
                <!-- Latitude -->
                <div class="row">
                    <div class="col-md-2" id="">
                        <label for="exampleInputuname">{{ translate('Latitude')}}</label>
                    </div>
                    <div class="col-md-10" id="">
                        <input type="text" class="form-control mb-3 rounded-0" id="latitude" name="latitude" readonly="">
                    </div>
                </div>
            @endif

            <!-- Postal code removed from UI; submit fallback value for validation -->
            <input type="hidden" name="postal_code" value="00000">

            <input type="checkbox" id="sameAsShipping" name="same_as_shipping" value="1" checked style="display:none;">
        </div>
    </div>

    @if (get_setting('billing_address_required'))
    <div class="tab-pane fade" id="billing-address" role="tabpanel" aria-labelledby="billing-address-tab">
        <div class="p-3">
            <!-- Full name (billing) -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Full Name')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <input class="form-control mb-3 rounded-0" placeholder="{{ translate('Your full name')}}" rows="2" name="billing_name" required></input>
                </div>
            </div>

            <!-- Address -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Address')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <textarea class="form-control mb-3 rounded-0" placeholder="{{ translate('Your Address')}}" rows="2" name="billing_address" required></textarea>
                </div>
            </div>

            <!-- Country -->
            @if (get_active_countries()->count() > 1)
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Country')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <div class="mb-3">
                        <select class="form-control aiz-selectpicker rounded-0" 
                            data-live-search="true" data-placeholder="{{ translate('Select your country') }}" name="billing_country_id" required>
                            <option value="">{{ translate('Select your country') }}</option>
                            @foreach (get_active_countries() as $key => $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @elseif(get_active_countries()->count() == 1)
            <input type="hidden" name="billing_country_id" value="{{get_active_countries()[0]->id }}">
            @endif

            @if(get_setting('has_state') == 1)
            <!-- District / City (billing state) -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('District/City')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0" data-live-search="true" name="billing_state_id" required>

                    </select>
                </div>
            </div>
            @endif

            @if (!checkout_requires_city_for_shipping_quote())
            <input type="hidden" name="billing_city_id" value="">
            @else
            <!-- City -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('City')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0" data-live-search="true" name="billing_city_id" required>

                    </select>
                </div>
            </div>
            @endif

            <!--Area-->
            <div class="row billing-area-field d-none">
                <div class="col-md-2">
                    <label>{{ translate('Area')}}<span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <select class="form-control mb-3 aiz-selectpicker rounded-0 guest-checkout" data-live-search="true" name="billing_area_id">

                    </select>
                </div>
            </div>

            <!-- Postal code removed from UI; submit fallback value for validation -->
            <input type="hidden" name="billing_postal_code" value="00000">

            <!-- Phone -->
            <div class="row">
                <div class="col-md-2 mt-md-2">
                    <label>{{ translate('Phone')}} <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-10">
                    <input type="tel" class="form-control rounded-0" placeholder="" name="billing_phone" autocomplete="off" required>
                </div>
            </div>
        </div>     
    </div>
    @endif
</div>



@if (get_setting('checkout_otp_verification', 1) == 1 && addon_is_activated('otp_system'))
<div class="px-3 pt-3 pb-4 row">
    <div class="col-md-2 mt-md-2"></div>
    <div class="col-md-10">
        <div class="bg-soft-info p-2">
            {{ translate("We will send an OTP to your 'phone' to confirm your order.") }}
        </div>
    </div>
</div>
@endif
