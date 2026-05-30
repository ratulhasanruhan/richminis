@php
    $emailContent = str_ireplace(
        ['<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>', '<p>', '<ul>', '<ol>', '<li>'],
        [
            '<h1 style="margin:0 0 18px; font-size:32px; line-height:1.3; font-weight:700; color:#111827;">',
            '<h2 style="margin:0 0 16px; font-size:28px; line-height:1.35; font-weight:700; color:#111827;">',
            '<h3 style="margin:0 0 14px; font-size:24px; line-height:1.4; font-weight:700; color:#111827;">',
            '<h4 style="margin:0 0 12px; font-size:20px; line-height:1.45; font-weight:700; color:#111827;">',
            '<h5 style="margin:0 0 10px; font-size:18px; line-height:1.5; font-weight:700; color:#111827;">',
            '<h6 style="margin:0 0 10px; font-size:16px; line-height:1.5; font-weight:700; color:#111827;">',
            '<p style="margin:0 0 14px; font-size:16px; line-height:1.7; color:#1f2937;">',
            '<ul style="margin:0 0 14px 20px; padding:0; font-size:16px; line-height:1.7; color:#1f2937;">',
            '<ol style="margin:0 0 14px 20px; padding:0; font-size:16px; line-height:1.7; color:#1f2937;">',
            '<li style="margin:0 0 8px; font-size:16px; line-height:1.7; color:#1f2937;">',
        ],
        $content
    );
@endphp

<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#e8ebef">
    @php
    $logo = get_setting('header_logo');
    @endphp
    <tr>
        <td align="center" valign="top" style="padding:50px 10px;">
            <!-- Container -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td align="center">
                        <table width="650" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td bgcolor="#ffffff" style="width:650px; min-width:650px; line-height:0pt; padding:0; margin:0; font-weight:normal;">
                                    <!-- Header -->
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="background-color: #f8fafa" >
                                        <tr>
                                            <td style="padding: 40px 30px 40px 30px;">
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <th style="line-height:0pt; padding:0; margin:0; font-weight:normal;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td style="line-height:0pt; text-align:left;"><img src="{{ uploaded_asset($logo) }}" width="" height="26" border="0" alt="" /></td>
                                                                </tr>
                                                            </table>
                                                        </th>
                                                        <th width="170" style="line-height:0pt; padding:0; margin:0; font-weight:normal;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td style="color:#000000; font-family:'Public Sans', sans-serif; font-size:14px; line-height:16px; text-align:right;">
                                                                        <a href="{{ env('APP_URL') }}" target="_blank" style="color:#000001; text-decoration:none; font-weight: 500">
                                                                            <span  style="color:#000001; text-decoration:none;">{{ env('APP_NAME') }}</span>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </th>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- END Header -->

                                    <!-- Content -->
                                    <div style="padding: 10px 30px 70px 30px;">
                                        <div style="font-size:16px; line-height:1.7; color:#1f2937;">
                                            {!! $emailContent !!}
                                        </div>
                                    </div>
                                    
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- END Container -->
        </td>
    </tr>
</table>