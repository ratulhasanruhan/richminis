<script type="text/javascript">

    function rmFirstCityOptionValue(htmlFragment) {
        if (!htmlFragment) {
            return '';
        }
        var $tmp = $('<select></select>').html(htmlFragment);
        var v = '';
        $tmp.find('option').each(function () {
            var val = $(this).attr('value');
            if (val !== undefined && val !== null && String(val).length) {
                v = val;
                return false;
            }
        });
        return v;
    }

    /**
     * @param {string} fieldBaseName city_id or billing_city_id
     * @param {string} obj HTML <option> list from server
     * @param {string} emptyLabel translated "no cities" message
     * @param {boolean} isBilling whether to refresh billing area
     */
    function rmApplyCityAjaxResponse(fieldBaseName, obj, emptyLabel, isBilling) {
        var $hidden = $('input[type="hidden"][name="' + fieldBaseName + '"]');
        var $select = $('select[name="' + fieldBaseName + '"]');
        var hasOpts = obj !== '' && $('<select></select>').html(obj).find('option').length > 1;
        if (!hasOpts) {
            if ($hidden.length) {
                $hidden.val('');
            }
            if ($select.length) {
                $select.html('<option value="">' + emptyLabel + '</option>');
                $select.attr('disabled', true);
                AIZ.plugins.bootstrapSelect('refresh');
            }
            return;
        }
        var firstVal = rmFirstCityOptionValue(obj);
        if ($hidden.length) {
            $hidden.val(firstVal);
            if (isBilling) {
                get_billing_area(firstVal);
            } else {
                get_area(firstVal);
            }
            if ($select.length) {
                $select.attr('disabled', false);
                $select.html(obj);
                AIZ.plugins.bootstrapSelect('refresh');
            }
            return;
        }
        if ($select.length) {
            $select.attr('disabled', false);
            $select.html(obj);
            AIZ.plugins.bootstrapSelect('refresh');
        }
    }

    function submitShippingInfoForm(el) {
        // Guest checkout should work for both new and existing users.
        // If the email/phone already exists, OTP will be used to authenticate on order submit.
        $('#shipping_info_form').submit();
    }

    function add_new_address(){
        $('#new-address-modal').modal('show');
    }

     function add_new_billing_address(){
        $('#new-billing-address-modal').modal('show');
    }

    function edit_address(address) {
        var url = '{{ route("addresses.edit", ":id") }}';
        url = url.replace(':id', address);

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url,
            type: 'GET',
            success: function (response) {
                $('#edit_modal_body').html(response.html);
                $('#edit-address-modal').modal('show');
                AIZ.plugins.bootstrapSelect('refresh');

                @if (get_setting('google_map') == 1)
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = parseFloat(response.data.address_data.latitude);
                        long    = parseFloat(response.data.address_data.longitude);
                    }

                    initialize(lat, long, 'edit_');
                @endif
                @if(get_active_countries()->count() == 1)
                    if (response.data.address_data.country_id != {{ get_active_countries()->first()->id }}) {
                        get_states({{ get_active_countries()->first()->id }});
                    }
                @endif
            }
        });
    }

    function edit_billing_address(address) {
        var url = '{{ route("billing_addresses.edit", ":id") }}';
        url = url.replace(':id', address);

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url,
            type: 'GET',
            success: function (response) {
                $('#edit_modal_body').html(response.html);
                $('#edit-address-modal').modal('show');
                AIZ.plugins.bootstrapSelect('refresh');

                @if (get_setting('google_map') == 1)
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = parseFloat(response.data.address_data.latitude);
                        long    = parseFloat(response.data.address_data.longitude);
                    }

                    initialize(lat, long, 'edit_');
                @endif
                @if(get_active_countries()->count() == 1)
                    if (response.data.address_data.country_id != {{ get_active_countries()->first()->id }}) {
                        get_states({{ get_active_countries()->first()->id }});
                    }
                @endif
            }
        });
    }

    $(document).on('change', '[name=country_id]', function() {
        var country_id = $(this).val();
        // Always prefer state -> city flow if state selector exists (even if has_state setting is off)
        if ($('[name="state_id"]').length) {
            get_states(country_id);
        } else {
            get_city_by_country(country_id);
        }
    });

    $(document).on('change', '[name=state_id]', function() {
        var state_id = $(this).val();
        get_city(state_id);
    });

    $(document).on('change', '[name=city_id]', function() {
        var city_id = $(this).val();
        get_area(city_id);
    });


    $(document).on('change', '[name=billing_country_id]', function() {
        var country_id = $(this).val();
        @if(get_setting('has_state') == 1)
            get_billing_states(country_id);
        @else
            get_billing_city_by_country(country_id);
        @endif
    });

    $(document).on('change', '[name=billing_state_id]', function() {
        var state_id = $(this).val();
        get_billing_city(state_id);
    });

    $(document).on('change', '[name=billing_city_id]', function() {
        var city_id = $(this).val();
        get_billing_area(city_id);
    });

    function get_states(country_id) {
        $('[name="state"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-state')}}",
            type: 'POST',
            data: {
                country_id  : country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    $('[name="state_id"]').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                    var $state = $('[name="state_id"]');
                    if ($state.find('option').length > 1 && (!$state.val() || $state.val() === '')) {
                        var preferVal = null;
                        if ($('#checkout-form').length) {
                            $state.find('option').each(function () {
                                var v = $(this).val();
                                if (!v) return;
                                var t = ($(this).text() || '').trim().toLowerCase();
                                if (t === 'dhaka' || t.indexOf('dhaka') !== -1) {
                                    preferVal = v;
                                    return false;
                                }
                            });
                        }
                        var pickVal = preferVal || $state.find('option').filter(function () {
                            return $(this).val() !== '';
                        }).first().val();
                        if (pickVal) {
                            $state.val(pickVal);
                            AIZ.plugins.bootstrapSelect('refresh');
                            $state.trigger('change');
                        }
                    }
                }
            }
        });
    }

    function get_billing_states(country_id) {
        $('[name="billing_state"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-state')}}",
            type: 'POST',
            data: {
                country_id  : country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    $('[name="billing_state_id"]').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                    var $bs = $('[name="billing_state_id"]');
                    if ($('#checkout-form').length && $bs.length && $bs.find('option').length > 1 && (!$bs.val() || $bs.val() === '')) {
                        var preferVal = null;
                        $bs.find('option').each(function () {
                            var v = $(this).val();
                            if (!v) return;
                            var t = ($(this).text() || '').trim().toLowerCase();
                            if (t === 'dhaka' || t.indexOf('dhaka') !== -1) {
                                preferVal = v;
                                return false;
                            }
                        });
                        var pickVal = preferVal || $bs.find('option').filter(function () {
                            return $(this).val() !== '';
                        }).first().val();
                        if (pickVal) {
                            $bs.val(pickVal);
                            AIZ.plugins.bootstrapSelect('refresh');
                            $bs.trigger('change');
                        }
                    }
                }
            }
        });
    }



    function get_city(state_id) {
        $('[name="city"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city')}}",
            type: 'POST',
            data: {
                state_id: state_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                rmApplyCityAjaxResponse(
                    'city_id',
                    obj,
                    '{{ translate('No cities are available under this state.') }}',
                    false
                );
            }
        });
    }

    function get_billing_city(state_id) {
        $('[name="billing_city"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city')}}",
            type: 'POST',
            data: {
                state_id: state_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                rmApplyCityAjaxResponse(
                    'billing_city_id',
                    obj,
                    '{{ translate('No cities are available under this state.') }}',
                    true
                );
            }
        });
    }

    

    function get_area(city_id) {
        $('[name="area"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-area')}}",
            type: 'POST',
            data: {
                city_id: city_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                $('[name="area_id"]').html(obj);
                AIZ.plugins.bootstrapSelect('refresh');
                if (obj.includes('<option') && !obj.includes('disabled selected')) {
                    $('[name="area_id"]').attr('required', true);
                    $('.area-field').removeClass('d-none'); 
                } else {
                    $('[name="area_id"]').removeAttr('required');
                    $('.area-field').addClass('d-none');
                }
            }
        });
    }


    function get_city_by_country(country_id){
        $('[name="city"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city-by-country')}}",
            type: 'POST',
            data: {
                country_id: country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                rmApplyCityAjaxResponse(
                    'city_id',
                    obj,
                    '{{ translate('No cities are available under this country.') }}',
                    false
                );
            }
        });
    }


     function get_billing_area(city_id) {
        $('[name="billing_area"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-area')}}",
            type: 'POST',
            data: {
                city_id: city_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                $('[name="billing_area_id"]').html(obj);
                AIZ.plugins.bootstrapSelect('refresh');
                if (obj.includes('<option') && !obj.includes('disabled selected')) {
                    $('[name="billing_area_id"]').attr('required', true);
                    $('.billing-area-field').removeClass('d-none'); 
                } else {
                    $('[name="billing_area_id"]').removeAttr('required');
                    $('.billing-area-field').addClass('d-none');
                }
            }
        });
    }


    function get_billing_city_by_country(country_id){
        $('[name="billing_city"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city-by-country')}}",
            type: 'POST',
            data: {
                country_id: country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                rmApplyCityAjaxResponse(
                    'billing_city_id',
                    obj,
                    '{{ translate('No cities are available under this country.') }}',
                    true
                );
            }
        });
    }

   
    $(document).on('change', '#sameAsShipping', function () {

        const billingTab  = $('#profile-tab');
        const billingPane = $('#billing-address');

        if (!billingTab.length || !billingPane.length) {
            return;
        }

        if (this.checked) {
            billingTab
                .addClass('disabled')
                .removeAttr('data-toggle')
                .attr('aria-disabled', 'true')
                .css('pointer-events', 'none');

            if (billingTab.hasClass('active')) {
                $('.nav-link:not(#profile-tab)').first().tab('show');
            }
            billingPane.find('input, textarea, select').each(function () {
                $(this).val('');
            });
            billingPane.find('[required]').each(function () {
                $(this).data('was-required', true).removeAttr('required');
            });

            billingPane.removeClass('show active').hide();

        } else {
            billingTab
                .removeClass('disabled')
                .attr('data-toggle', 'tab')
                .attr('aria-disabled', 'false')
                .css('pointer-events', '');
            billingPane.find('[data-was-required]').each(function () {
                $(this).attr('required', true).removeData('was-required');
            });
            billingPane.show();
        }
    });

    // Guest checkout: auto-load state/city for default country and auto-use shipping as billing
    $(document).ready(function () {
        try {
            if ($('#sameAsShipping').length) {
                $('#sameAsShipping').prop('checked', true).trigger('change');
            }
        } catch (e) {}

        try {
            var $country = $('[name="country_id"]');
            if ($country.length && $country.val()) {
                if ($('[name="state_id"]').length) {
                    get_states($country.val());
                } else {
                    get_city_by_country($country.val());
                }
            }
        } catch (e) {}
    });


</script>
