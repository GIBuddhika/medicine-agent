<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 0
        }

        #m_MessageViewBody a {
            color: inherit;
            text-decoration: none
        }

        p {
            line-height: inherit
        }

        .m_desktop_hide,
        .m_desktop_hide table {
            display: none;
            max-height: 0;
            overflow: hidden
        }

        @media (max-width:720px) {
            .m_social_block.m_desktop_hide .m_social-table {
                display: inline-block !important
            }

            .m_row-content {
                width: 100% !important
            }

            .m_mobile_hide {
                display: none
            }

            .m_stack .m_column {
                width: 100%;
                display: block
            }

            .m_mobile_hide {
                min-height: 0;
                max-height: 0;
                max-width: 0;
                overflow: hidden;
                font-size: 0
            }

            .m_desktop_hide,
            .m_desktop_hide table {
                display: table !important;
                max-height: none !important
            }
        }
    </style>
</head>

<body><u></u>
    <div style="background-color:#fff;margin:0;padding:0">
        <table class="m_nl-container" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#fff">
            <tbody>
                <tr>
                    <td>
                        <table class="m_row m_row-1" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="m_row-content m_stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="color:#000;width:700px" width="700">
                                            <tbody>
                                                <tr>
                                                    <td class="m_column m_column-1" width="100%" style="font-weight:400;text-align:left;vertical-align:top;padding-top:30px;padding-bottom:20px;border-top:0;border-right:0;border-bottom:0;border-left:0">
                                                        <table class="m_image_block m_block-1" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td class="m_pad" style="width:100%;padding-right:0;padding-left:0">
                                                                    <div class="m_alignment" align="center" style="line-height:10px">
                                                                        <a href="http://localhost:4200" style="outline:none" target="_blank" rel="noreferrer"><img style="display:block;height:auto;border:0;width:245px;max-width:100%" src="https://programmersdiary.tech/wp-content/uploads/logo-big.png" width="245" alt="Enginemailer logo" title="Enginemailer logo"></a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="m_row m_row-2" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: {{{$is_admin?'#4a65ad':'#16d2be'}}};background-image:url();background-position:top center;background-repeat:no-repeat">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="m_row-content m_stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="color:#000;width:700px" width="700">
                                            <tbody>
                                                <tr>
                                                    <td class="m_column m_column-1" width="100%" style="font-weight:400;text-align:left;vertical-align:top;padding-top:40px;padding-bottom:0;border-top:0;border-right:0;border-bottom:0;border-left:0">
                                                        <table class="m_text_block m_block-1" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="word-break:break-word">
                                                            <tr>
                                                                <td class="m_pad" style="padding-bottom:10px;padding-left:10px;padding-right:10px;padding-top:30px">
                                                                    <div style="font-family:sans-serif">
                                                                        <div style="font-size:12px;color:#fff;line-height:1.2;font-family:Arial,Helvetica Neue,Helvetica,sans-serif">
                                                                            <p style="margin:0;font-size:12px;text-align:center"><span style="font-size:30px">Here&#39;s your password reset link</span></p>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table class="m_text_block m_block-2" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="word-break:break-word">
                                                            <tr>
                                                                <td class="m_pad" style="padding-bottom:10px;padding-left:20px;padding-right:20px;padding-top:10px">
                                                                    <div style="font-family:sans-serif">
                                                                        <div style="font-size:12px;color:#fff;line-height:1.5;font-family:Arial,Helvetica Neue,Helvetica,sans-serif">
                                                                            <p style="margin:0;font-size:14px;text-align:left; text-transform: capitalize;"><span style="font-size:16px">Hi {{$name}},</span></p>
                                                                            <p style="margin:0;font-size:14px;"><span style="font-size:16px">You&#39;ve requested to reset password recently. Please click on the link below or click on the button. This link will expire within 24hours.</span></p>
                                                                            <p style="margin:0;font-size:14px;"><span style="font-size:16px">If this action didn&#39;t made by you, please contact the admin.</span></p>
                                                                            <br>
                                                                            <p style="margin:0;font-size:14px;"><span style="font-size:16px"><a style="color:white;" href="{{$resetLink}}">{{$resetLink}}</a></span></p>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                        </table>
                                                        <table class="m_button_block m_block-3" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td class="m_pad" style="padding-bottom:50px;padding-left:10px;padding-right:10px;padding-top:30px;text-align:center">
                                                                    <div class="m_alignment" align="center">

                                                                        <a href="{{$resetLink}}" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#3aaee0;border-radius:30px;width:auto;border-top:0px solid transparent;font-weight:400;border-right:0px solid transparent;border-bottom:0px solid transparent;border-left:0px solid transparent;padding-top:8px;padding-bottom:8px;font-family:Arial,Helvetica Neue,Helvetica,sans-serif;font-size:16px;text-align:center;word-break:keep-all" target="_blank" rel="noreferrer"><span style="padding-left:40px;padding-right:40px;font-size:16px;display:inline-block;letter-spacing:normal"><span dir="ltr" style="word-break:break-word;line-height:32px">
                                                                                    <strong>Click here to Reset password</strong></span></span></a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="m_row m_row-3" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="m_row-content m_stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="color:#000;width:700px" width="700">
                                            <tbody>
                                                <tr>
                                                    <td class="m_column m_column-1" width="100%" style="font-weight:400;text-align:left;vertical-align:top;padding-top:25px;padding-bottom:25px;border-top:0;border-right:0;border-bottom:0;border-left:0">

                                                        <table class="m_text_block m_block-3" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="word-break:break-word">
                                                            <tr>
                                                                <td class="m_pad">
                                                                    <div style="font-family:sans-serif">
                                                                        <div style="font-size:12px;color:#555;line-height:1.2;font-family:Arial,Helvetica Neue,Helvetica,sans-serif">
                                                                            <p style="margin:0;font-size:14px;text-align:center"><span style="font-size:12px"><strong>This email has been sent from MedicineAgent.com</strong></span></p>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>