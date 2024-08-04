<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="application/pdf">
    <meta name="x-apple-disable-message-reformatting">
    <title>IFCA - BTID</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('public/images/KuraKuraBali-ico.ico') }}">
    
    <style>
        body {
            font-family: Arial;
            margin: 0;
            padding: 0 !important;
            mso-line-height-rule: exactly;
            background-color: #ffffff;
            font-family: Arial;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .custom-table {
                background-color:#e0e0e0;"
            }

        td {
            padding: 8px;
        }

        @media only screen and (max-width: 620px) {
            table {
                width: 100% !important;
            }

            td {
                display: block;
                width: 100% !important;
                box-sizing: border-box;
            }
            .custom-table {
                background-color:#ffffff;"
            }
            
        }
    </style>
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;font-family: Arial;">
	<div style="width: 100%; background-color: #ffffff; text-align: center;">
        <table width="80%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="margin-left: auto;margin-right: auto;" >
            <tr>
               <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding-bottom:25px">
                                    <img width = "120" src="{{ url('public/images/KURAKURABALI_LOGO.jpg') }}" alt="logo">
                                        <p style="font-size: 16px; color: #026735; padding-top: 0px;">{{ $dataArray['entity_name'] }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#e0e0e0;">
                        <tbody>
                            <tr>
                                <td style="padding: 30px 30px">
                                    <h5 style="text-align:left;margin-bottom: 24px; color: #000000; font-size: 20px; font-weight: 400; line-height: 28px;">Dear {{ $dataArray['user_name'] }}, </h5>
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">Below is a Varian Order that requires your approval :</p>
                                    <p style="text-align:left; margin-bottom: 15px; margin-top: 0; color: #000000; font-size: 16px; list-style-type: circle;">
                                        <b>{{ $dataArray['descs'] }}</b><br>
                                        
                                    </p>
                                    <p style="text-align:left; margin-bottom: 15px; margin-top: 0; color: #000000; font-size: 16px; list-style-type: circle;">
                                        <b>VO No. :{{ $dataArray['doc_no'] }}</b><br>
                                        With a VO amount of {{ $dataArray['curr_cd'] }} {{ $dataArray['vo_appr_amt'] }}<br>
                                    </p>

                                    @php
                                        $hasAttachment = false;
                                    @endphp
                    
                                    @foreach($dataArray['url_file'] as $key => $url_file)
                                        @if($url_file !== '' && $dataArray['file_name'][$key] !== '' && $url_file !== 'EMPTY' && $dataArray['file_name'][$key] !== 'EMPTY')
                                            @if(!$hasAttachment)
                                                @php
                                                    $hasAttachment = true;
                                                @endphp
                                                <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 16px;">
                                                    <span>To view a detailed request for this progress, please click on the link below :</span><br>
                                            @endif
                                            <a href="{{ $url_file }}" target="_blank">{{ $dataArray['file_name'][$key] }}</a><br>
                                        @endif
                                    @endforeach
                    
                                    @if($hasAttachment)
                                        </p>
                                    @endif


                                    <a href="{{ url('api') }}/varianorder/A/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #1ee0ac; border-radius: 4px; color: #ffffff;">Approve</a>
                                    <a href="{{ url('api') }}/varianorder/R/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #f4bd0e; border-radius: 4px; color: #ffffff;">Revise</a>
                                    <a href="{{ url('api') }}/varianorder/C/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #e85347; border-radius: 4px; color: #ffffff;">Reject</a>
                                    <br>
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        In case you need some clarification, kindly approach : <br>
                                        <a href="mailto:{{ $dataArray['clarify_email'] }}" style="text-decoration: none; color: inherit;">
                                            {{ $dataArray['clarify_user'] }}
                                        </a>
                                    </p>
                    
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        <b>Thank you,</b><br>
                                        <a href="mailto:{{ $dataArray['sender_addr'] }}" style="text-decoration: none; color: inherit;">
                                            {{ $dataArray['sender'] }}
                                        </a>
                                    </p>
                    
                                    @php
                                        $hasApproval = false;
                                        $counter = 0;
                                    @endphp
                    
                                    @foreach($dataArray['approve_list'] as $key => $approve_list)
                                        @if($approve_list !== '' && $approve_list !== 'EMPTY')
                                            @if(!$hasApproval)
                                                @php
                                                    $hasApproval = true;
                                                @endphp
                                                <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 16px;">
                                                    <span>This request approval has been approved by :</span><br>
                                            @endif
                                            {{ ++$counter }}. {{ $approve_list }}<br>
                                        @endif
                                    @endforeach
                    
                                    @if($hasApproval)
                                        </p>
                                    @endif
                    
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        <b>Please do not reply, as this is an automated-generated email.</b><br>
                                    </p>
                    
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding:25px 20px 0;">
                                    <p style="font-size: 13px;">Copyright © 2023 IFCA Software. All rights reserved.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
               </td>
            </tr>
        </table>
        </div>
</body>
</html>