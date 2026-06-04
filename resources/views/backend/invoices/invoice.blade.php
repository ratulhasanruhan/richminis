<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('INVOICE') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <style media="all">
        @page { margin: 0; padding: 0; }
        body {
            font-size: 10pt;
            font-family: '<?php echo $font_family; ?>';
            font-weight: normal;
            direction: <?php echo $direction; ?>;
            text-align: <?php echo $text_align; ?>;
            padding: 0;
            margin: 0;
            color: #111111;
            background-color: #ffffff;
        }
        table { width: 100%; border-collapse: collapse; }
        .text-left { text-align: <?php echo $text_align; ?>; }
        .text-right { text-align: <?php echo $not_text_align; ?>; }
        .strong { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $shipping = json_decode($order->shipping_address);
        $billing = json_decode($order->billing_address) ?? $shipping;
        $first_order = $order->orderDetails->first();
        $ink = '#111111';
        $muted = '#555555';
        $line = '#e0e0e0';
        $soft = '#fafafa';
        $accent = '#111111';
        $invDate = date('j F Y', $order->date);
        $companyBrand = 'RICHMINIS';
    @endphp

    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td style="padding:22px 26px 18px;">

                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="52%" valign="top" style="padding-<?php echo $text_align === 'right' ? 'left' : 'right'; ?>:10px;">
                            <div style="font-size:22pt;font-weight:bold;letter-spacing:0.06em;color:{{ $ink }};line-height:1.1;">
                                {{ $companyBrand }}
                            </div>
                        </td>
                        <td width="48%" valign="top" class="text-right" style="padding-<?php echo $text_align === 'right' ? 'right' : 'left'; ?>:10px;">
                            <div style="font-size:26pt;font-weight:bold;letter-spacing:0.02em;color:{{ $ink }};line-height:1;">
                                @if($order->order_from == 'pos')
                                    {{ translate('POS INVOICE') }}
                                @else
                                    {{ translate('INVOICE') }}
                                @endif
                            </div>
                            <div style="font-size:11pt;font-weight:bold;margin-top:8px;color:{{ $ink }};">INV #{{ $order->code }}</div>
                            <div style="font-size:10pt;color:{{ $muted }};margin-top:8px;">{{ $invDate }}</div>
                        </td>
                    </tr>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:6px;">
                    <tr>
                        <td style="height:3px;background-color:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:16px;">
                    <tr>
                        <td width="50%" valign="top" style="padding-<?php echo $text_align === 'right' ? 'left' : 'right'; ?>:12px;vertical-align:top;">
                            <div style="font-size:7pt;color:{{ $muted }};text-transform:uppercase;letter-spacing:0.14em;font-weight:bold;">
                                {{ translate('Issued by') }}
                            </div>
                            <div style="font-size:13pt;font-weight:bold;margin-top:6px;color:{{ $ink }};">{{ $companyBrand }}</div>
                            <div style="font-size:9pt;color:{{ $muted }};margin-top:8px;line-height:1.5;">
                                {{ get_setting('contact_address') }}<br>
                                {{ get_setting('contact_email') }} · {{ get_setting('contact_phone') }}
                            </div>
                            @php $gstin = get_seller_gstin($order); @endphp
                            @if($gstin != null && is_numeric($first_order->gst_amount))
                                <div style="font-size:9pt;margin-top:8px;color:{{ $ink }};">
                                    <span style="font-weight:bold;">{{ translate('GSTIN') }}:</span> {{ $gstin }}
                                </div>
                            @endif
                        </td>
                        <td width="50%" valign="top" class="text-right" style="padding-<?php echo $text_align === 'right' ? 'right' : 'left'; ?>:12px;vertical-align:top;">
                            <div style="font-size:7pt;color:{{ $muted }};text-transform:uppercase;letter-spacing:0.14em;font-weight:bold;">
                                {{ translate('Bill to') }}
                            </div>
                            <div style="font-size:13pt;font-weight:bold;margin-top:6px;color:{{ $ink }};">{{ $billing->name }}</div>
                            <div style="font-size:9pt;color:{{ $muted }};margin-top:6px;line-height:1.45;">
                                {{ !empty($billing->state) ? $billing->state : '' }}
                                @if(!empty($billing->address)) <br>{{ $billing->address }} @endif
                            </div>
                            @if($billing->email)
                                <div style="font-size:9pt;margin-top:4px;color:{{ $ink }};">{{ $billing->email }}</div>
                            @endif
                            @if($billing->phone)
                                <div style="font-size:9pt;color:{{ $ink }};">{{ $billing->phone }}</div>
                            @endif
                        </td>
                    </tr>
                </table>

                <table cellpadding="5" cellspacing="0" border="0" width="100%" style="margin-top:20px;border-top:1px solid {{ $line }};">
                    <thead>
                        <tr>
                            <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px;border-bottom:1px solid {{ $line }};font-weight:bold;">
                                {{ translate('Description') }}
                            </th>
                            <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px;border-bottom:1px solid {{ $line }};font-weight:bold;">
                                {{ translate('Qty') }}
                            </th>
                            @if(is_numeric($first_order->gst_amount))
                                <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('Gross Amount')}}</th>
                                <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('Discount/ Coupon')}}</th>
                                <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('Taxable Value')}}</th>
                                @if(same_state_shipping($order))
                                    <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('CGST') }}</th>
                                    <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('SGST') }}</th>
                                @else
                                    <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;font-size:7pt;text-transform:uppercase;padding:10px 6px;border-bottom:1px solid {{ $line }};font-weight:bold;">{{ translate('IGST') }}</th>
                                @endif
                            @else
                                <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px;border-bottom:1px solid {{ $line }};font-weight:bold;">
                                    {{ translate('Unit Price') }}
                                </th>
                                <th bgcolor="#f2f2f2" class="text-left" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px;border-bottom:1px solid {{ $line }};font-weight:bold;">
                                    {{ translate('Tax') }}
                                </th>
                            @endif
                            <th bgcolor="#f2f2f2" class="text-right" style="background-color:#f2f2f2;color:{{ $ink }};font-size:7pt;text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px;border-bottom:1px solid {{ $line }};font-weight:bold;">
                                {{ translate('Total') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->orderDetails as $key => $orderDetail)
                            @if ($orderDetail->product != null)
                                <tr>
                                    <td style="border-bottom:1px solid {{ $line }};padding:10px 8px;vertical-align:top;">
                                        <span style="font-weight:bold;font-size:10pt;">{{ $orderDetail->product->name }}</span>
                                        @if($orderDetail->variation != null)
                                            <span style="color:{{ $muted }};font-size:9pt;"> — {{ $orderDetail->variation }}</span>
                                        @endif
                                        <br>
                                        <span style="color:{{ $muted }};font-size:8pt;">
                                            @php $product_stock = json_decode($orderDetail->product->stocks->first(), true); @endphp
                                            {{ translate('SKU') }}: {{ $product_stock['sku'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="border-bottom:1px solid {{ $line }};padding:10px 8px;">{{ $orderDetail->quantity }}</td>
                                    @if(is_numeric($first_order->gst_amount))
                                        <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($orderDetail->price) }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($orderDetail->coupon_discount) }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($orderDetail->price - $orderDetail->coupon_discount) }}</td>
                                        @php
                                            $gst_amount = get_gst_by_price_and_rate($orderDetail->price - $orderDetail->coupon_discount , $orderDetail->gst_rate);
                                            $shipping_gst = get_gst_by_price_and_rate($orderDetail->shipping_cost, $orderDetail->gst_rate);
                                        @endphp
                                        @if(same_state_shipping($order))
                                            <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($gst_amount/2) }}</td>
                                            <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($gst_amount/2) }}</td>
                                        @else
                                            <td style="border-bottom:1px solid {{ $line }};padding:10px 6px;">{{ single_price($gst_amount) }}</td>
                                        @endif
                                    @else
                                        <td style="border-bottom:1px solid {{ $line }};padding:10px 8px;">{{ single_price($orderDetail->price/$orderDetail->quantity) }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:10px 8px;">{{ single_price($orderDetail->tax/$orderDetail->quantity) }}</td>
                                    @endif
                                    @if(is_numeric($first_order->gst_amount))
                                        <td class="text-right" style="border-bottom:1px solid {{ $line }};padding:10px 8px;font-weight:bold;">{{ single_price($orderDetail->price - $orderDetail->coupon_discount + $gst_amount) }}</td>
                                    @else
                                        <td class="text-right" style="border-bottom:1px solid {{ $line }};padding:10px 8px;font-weight:bold;">{{ single_price($orderDetail->price+$orderDetail->tax) }}</td>
                                    @endif
                                </tr>
                                @if(is_numeric($first_order->gst_amount))
                                    <tr>
                                        <td style="color:{{ $muted }};border-bottom:1px solid {{ $line }};padding:8px 8px;">{{ translate('Shipping') }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:8px 8px;">1</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price($orderDetail->shipping_cost) }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price(0) }}</td>
                                        <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price($orderDetail->shipping_cost) }}</td>
                                        @if(same_state_shipping($order))
                                            <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price($shipping_gst/2) }}</td>
                                            <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price($shipping_gst/2) }}</td>
                                        @else
                                            <td style="border-bottom:1px solid {{ $line }};padding:8px 6px;">{{ single_price($shipping_gst) }}</td>
                                        @endif
                                        <td class="text-right" style="border-bottom:1px solid {{ $line }};padding:8px 8px;">{{ single_price($orderDetail->shipping_cost + (($orderDetail->shipping_cost* $orderDetail->gst_rate)/100)) }}</td>
                                    </tr>
                                @endif
                            @endif
                        @endforeach
                    </tbody>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px;">
                    <tr>
                        <td style="font-size:9pt;color:{{ $muted }};line-height:1.5;">
                            <span style="font-weight:bold;color:{{ $ink }};">{{ translate('Payment method') }}:</span>
                            {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}
                            <br>
                            <span style="font-weight:bold;color:{{ $ink }};">{{ translate('Delivery Type') }}:</span>
                            @if ($order->shipping_type != null && $order->shipping_type == 'home_delivery')
                                {{ translate('Home Delivery') }}
                            @elseif ($order->shipping_type == 'pickup_point')
                                @if ($order->pickup_point != null)
                                    {{ $order->pickup_point->getTranslation('name') }}
                                @else
                                    {{ translate('Pickup Point') }}
                                @endif
                            @elseif ($order->shipping_type == 'carrier')
                                {{ $order->carrier->name ?? translate('Carrier') }}
                            @else
                                {{ translate('N/A') }}
                            @endif
                        </td>
                    </tr>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:18px;">
                    <tr>
                        <td width="100%" valign="top">
                            <table cellpadding="0" cellspacing="0" border="0" width="55%" align="<?php echo $not_text_align; ?>">
                                <tbody>
                                    @if(is_numeric($first_order->gst_amount))
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Sub Total') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->orderDetails->sum('price') + $order->orderDetails->sum('shipping_cost') - $order->orderDetails->sum('coupon_discount')) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Total GST') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->orderDetails->sum('gst_amount')) }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Sub Total') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->orderDetails->sum('price')) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Shipping Cost') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->orderDetails->sum('shipping_cost')) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Total Tax') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->orderDetails->sum('tax')) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left" style="font-weight:bold;font-size:8pt;text-transform:uppercase;letter-spacing:0.08em;color:{{ $muted }};padding:6px 8px;">{{ translate('Coupon Discount') }}</th>
                                            <td class="text-right" style="padding:6px 8px;font-size:10pt;">{{ single_price($order->coupon_discount) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th class="text-left" style="font-weight:bold;font-size:10pt;text-transform:uppercase;letter-spacing:0.06em;padding:12px 8px;border-top:2px solid {{ $accent }};">
                                            {{ translate('Grand Total') }}
                                        </th>
                                        <td class="text-right" style="font-weight:bold;font-size:14pt;padding:12px 8px;border-top:2px solid {{ $accent }};">
                                            {{ single_price($order->grand_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>

                @php $removedXML = '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:28px;">
                    <tr>
                        <td align="center" style="text-align:center;padding-top:4px;padding-bottom:16px;">
                            <div style="font-size:9pt;color:{{ $muted }};line-height:1.55;">
                                Thank you for choosing {{ $companyBrand }}. We appreciate your preference for excellence.
                            </div>
                            <div style="font-size:8pt;color:{{ $muted }};margin-top:12px;">
                                {{ date('Y') }} © {{ $companyBrand }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="bottom" align="right" style="white-space:nowrap;">
                            <table cellpadding="0" cellspacing="0" border="0" align="right" style="border:1px solid {{ $line }};background-color:{{ $soft }};">
                                <tr>
                                    <td style="padding:10px;">
                                        {!! str_replace($removedXML, '', QrCode::size(100)->generate($order->code)) !!}
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size:8pt;color:{{ $muted }};margin-top:6px;text-align:right;">{{ translate('Order ID') }}: {{ $order->code }}</div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
