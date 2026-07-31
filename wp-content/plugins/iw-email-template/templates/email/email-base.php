<?php extract( wp_parse_args( $args,[
    'subject' => "Test subject",
    'body' => "Test email",
    'logo' => get_field( 'iw_email_template_logo', 'options' ),
    'cover' => get_field( 'iw_email_template_cover', 'options' ),
]) );

$logo = apply_filters( 'iw_email_template_logo', $logo );
$cover = apply_filters( 'iw_email_template_cover', $cover );
$email_language = apply_filters( 'wpml_current_language', null );
$email_home_url = apply_filters( 'wpml_home_url', home_url( '/' ), $email_language );
$email_site_name = get_bloginfo( 'name' );

if ( function_exists( 'iw_email_template_translate_string' ) ) {
    $email_site_name = iw_email_template_translate_string( 'site-name', $email_site_name, 'el' );
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head>
    <!-- NAME: 1 COLUMN -->
    <!--[if gte mso 15]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if (!empty($subject)) {
            echo esc_html( $subject );
        } ?></title>

    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body{
            background: #EBE6D6;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        #bodyCell{
            padding:10px;
        }
        .templateContainer{
            max-width:600px !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage,.mcnRetinaImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
        body,#bodyTable{
            background-color:#EBE6D6;
        }
        #bodyCell{
            border-top:0;
        }
        .templateContainer{
            border:0;
            border-radius:24px;
            overflow:hidden;
        }
        h1{
            color:#173276;
            font-family:Arial;
            font-size:26px;
            font-style:normal;
            font-weight:bold;
            line-height:125%;
            letter-spacing:normal;
            text-align:left;
        }
        h2{
            color:#173276;
            font-family:Arial;
            font-size:22px;
            font-style:normal;
            font-weight:bold;
            line-height:125%;
            letter-spacing:normal;
            text-align:left;
        }
        h3{
            color:#173276;
            font-family:Arial;
            font-size:20px;
            font-style:normal;
            font-weight:bold;
            line-height:125%;
            letter-spacing:normal;
            text-align:left;
        }
        h4{
            color:#173276;
            font-family:Arial;
            font-size:18px;
            font-style:normal;
            font-weight:bold;
            line-height:125%;
            letter-spacing:normal;
            text-align:left;
        }
        #templatePreheader{
            background-color:#FFF;
            background-image:none;
            background-repeat:no-repeat;
            background-position:center;
            background-size:cover;
            border-top:0;
            border-bottom:0;
            padding-top:9px;
            padding-bottom:9px;
        }
        #templatePreheader .mcnTextContent,#templatePreheader .mcnTextContent p{
            color:#656565;
            font-family:Arial;
            font-size:12px;
            line-height:150%;
            text-align:left;
        }
        #templatePreheader .mcnTextContent a,#templatePreheader .mcnTextContent p a{
            color:#656565;
            font-weight:normal;
            text-decoration:underline;
        }
        #templateHeader{
            background-color:#FFFFFF;
            background-image:none;
            background-repeat:no-repeat;
            background-position:center;
            background-size:cover;
            border-top:0;
            border-bottom:0;
            padding-top: 0;
            padding-bottom:0;
        }
        #templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
            color:#173276;
            font-family:Arial;
            font-size:16px;
            line-height:150%;
            text-align:left;
        }
        #templateHeader .mcnTextContent a,#templateHeader .mcnTextContent p a{
            color:#173276;
            font-weight:normal;
            text-decoration:underline;
        }
        #templateBody{
            background-color:#FFFFFF;
            background-image:none;
            background-repeat:no-repeat;
            background-position:center;
            background-size:cover;
            border-top:0;
            padding-top:0;
            padding-bottom:9px;
        }
        #templateBody .mcnTextContent,#templateBody .mcnTextContent p{
            color:#173276;
            font-family:Arial;
            font-size:16px;
            line-height:150%;
            text-align:left;
        }
        #templateBody .mcnTextContent a,#templateBody .mcnTextContent p a{
            color:#173276;
            font-weight:normal;
            text-decoration:underline;
        }
        #templateFooter{
            background-color:#FFF;
            background-image:none;
            background-repeat:no-repeat;
            background-position:center;
            background-size:cover;
            border-top:0;
            border-bottom:0;
            padding-top:9px;
            padding-bottom:9px;
        }
        #templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
            color:#173276;
            font-family:Arial;
            font-size:12px;
            line-height:150%;
            text-align:left;
        }
        #templateFooter .mcnTextContent a,#templateFooter .mcnTextContent p a{
            color:#173276;
            font-weight:normal;
            text-decoration:underline;
        }
        @media only screen and (min-width:768px){
            .templateContainer{
                width:600px !important;
            }

        }	@media only screen and (max-width: 480px){
            body,table,td,p,a,li,blockquote{
                -webkit-text-size-adjust:none !important;
            }

        }	@media only screen and (max-width: 480px){
            body{
                width:100% !important;
                min-width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnRetinaImage{
                max-width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImage{
                width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer,.mcnImageCardLeftImageContentContainer,.mcnImageCardRightImageContentContainer{
                max-width:100% !important;
                width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnBoxedTextContentContainer{
                min-width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageGroupContent{
                padding:9px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
                padding-top:9px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageCardTopImageContent,.mcnCaptionBottomContent:last-child .mcnCaptionBottomImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
                padding-top:18px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageCardBottomImageContent{
                padding-bottom:9px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageGroupBlockInner{
                padding-top:0 !important;
                padding-bottom:0 !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageGroupBlockOuter{
                padding-top:9px !important;
                padding-bottom:9px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnTextContent,.mcnBoxedTextContentColumn{
                padding-right:18px !important;
                padding-left:18px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
                padding-right:18px !important;
                padding-bottom:0 !important;
                padding-left:18px !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcpreview-image-uploader{
                display:none !important;
                width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
            h1{
                font-size:22px !important;
                line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){
            h2{
                font-size:20px !important;
                line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){
            h3{
                font-size:18px !important;
                line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){
            h4{
                font-size:16px !important;
                line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){
            .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
                font-size:14px !important;
                line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){
            #templatePreheader{
                display:block !important;
            }

        }	@media only screen and (max-width: 480px){
            #templatePreheader .mcnTextContent,#templatePreheader .mcnTextContent p{
                font-size:14px !important;
                line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){
            #templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
                font-size:16px !important;
                line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){
            #templateBody .mcnTextContent,#templateBody .mcnTextContent p{
                font-size:16px !important;
                line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){
            #templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
                font-size:14px !important;
                line-height:150% !important;
            }

        }</style><script async="" src="https://edge.fullstory.com/s/fs.js" crossorigin="anonymous"></script></head>
<body style="height:100%;margin:0;padding:0;width:100%;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#EBE6D6;">

<!--[if !gte mso 9]>
<span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;"><?php if(!empty($subject)){
echo $subject;
} ?></span>
<!--<![endif]-->

<center>
    <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" bgcolor="#EBE6D6" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;height:100%;margin:0;padding:0;width:100%;background-color:#EBE6D6;">
        <tbody><tr>
            <td align="center" valign="top" id="bodyCell" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 80px 10px;width: 100%;border-top: 0;">
                <!-- BEGIN TEMPLATE // -->
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600" style="width:600px;">
                    <tr>
                        <td align="center" valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" class="templateContainer" style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;border:0;max-width:600px !important;background-color:#FFFFFF;border-radius:24px;overflow:hidden;">
                    <tbody>
                    <?php if(  ! empty( $logo ) ) { ?>
                    <tr>
                        <td valign="top" id="templateHeader" bgcolor="#FFFFFF" style="min-width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;padding-top:0;background:#FFFFFF none no-repeat center/cover;mso-line-height-rule:exactly;border-top:0;border-bottom:0;padding-bottom:0;border-radius:24px 24px 0 0;"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" >
                                <tbody class="mcnImageBlockOuter">
                                <tr>
                                    <td valign="top" style="padding:36px 50px 32px 50px;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;" class="mcnImageBlockInner">
                                        <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                            <tbody><tr>
                                                <td class="mcnImageContent" valign="top" style="text-align:left;padding-top:0;padding-bottom:0;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">


                                                    <a href="<?php echo esc_url( $email_home_url );?>"><img align="left" alt="<?php echo esc_attr( $email_site_name ); ?>" src="<?php echo esc_url( $logo ); ?>" width="159" style="max-width:159px;padding-bottom:0;display:block !important;vertical-align:bottom;border:0;height:auto;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;" class="mcnImage"></a>


                                                </td>
                                            </tr>
                                            </tbody></table>
                                    </td>
                                </tr>
                                </tbody>
                            </table></td>
                    </tr>
                    <?php } ?>
                    <?php if(  ! empty( $cover ) ) { ?>
                        <tr>
                            <td valign="top" bgcolor="#FFFFFF" style="background:#FFFFFF none no-repeat center/cover;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#FFFFFF;background-image:none;background-repeat:no-repeat;background-position:center;background-size:cover;border-top:0;border-bottom:0;padding:0 30px;"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">
                                    <tbody class="mcnImageBlockOuter">
                                    <tr>
                                        <td valign="top" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnImageBlockInner">
                                            <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                                <tbody><tr>
                                                    <td class="mcnImageContent" valign="top" style="padding-top: 0;padding-bottom: 30px;text-align: left;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">


                                                        <a href="<?php echo esc_url( $email_home_url );?>"><img align="center" alt="" src="<?php echo esc_url( $cover ); ?>" width="540" style="max-width:100%;padding-bottom:0;display:inline-block !important;vertical-align:bottom;border:0;border-radius:16px;height:auto;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;" class="mcnImage"></a>


                                                    </td>
                                                </tr>
                                                </tbody></table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td valign="top" id="templateBody" bgcolor="#FFFFFF" style="background:#FFFFFF none no-repeat center/cover;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#FFFFFF;background-image:none;background-repeat:no-repeat;background-position:center;background-size:cover;border-top:0;padding-bottom:32px;<?php echo empty( $logo ) ? 'border-radius:24px 24px 0 0;' : ''; ?>">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                <tbody class="mcnTextBlockOuter">
                                <tr>
                                    <td valign="top" class="mcnTextBlockInner" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                        <!--[if mso]>
                                        <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                                            <tr>
                                        <![endif]-->

                                        <!--[if mso]>
                                        <td valign="top" width="600" style="width:600px;">
                                        <![endif]-->
                                        <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 100%;min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" width="100%" class="mcnTextContentContainer">
                                            <tbody><tr>

                                                <td valign="top" class="mcnTextContent" style="padding-top:<?php echo empty( $logo ) ? '50px' : '0'; ?>;padding-right:50px;padding-left:50px;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;word-break:break-word;font-family:Arial,sans-serif;font-size:14px;line-height:150%;text-align:left;color:#173276;">
                                                    <?php if( ! empty( $body ) ) { ?>
                                                    <?php //$body = strip_tags( $body, '<p><a><br><strong><span><h1><h2><h3><table><tr><td>'); ?>
                                                    <?php //$body = str_replace( "<a ", '<a style="color: #7fd8d5 !important;"', $body); ?>
                                                    <?php $body = str_replace( "<h1", '<h1 style="text-align:left;display:block;margin-bottom:20px;padding:0;color:#173276;font-family:Arial;font-size:24px;font-style:normal;font-weight:normal;line-height:116%;letter-spacing:normal;" ', $body); ?>
                                                    <?php $body = str_replace( "<h2", '<h2 style="text-align:left;display:block;margin-bottom:20px;padding:0;color:#173276;font-family:Arial;font-size:22px;font-style:normal;font-weight:normal;line-height:125%;letter-spacing:normal;" ', $body); ?>
                                                    <?php $body = str_replace( "<h3", '<h3 style="text-align:left;display:block;margin-bottom:20px;padding:0;color:#173276;font-family:Arial;font-size:18px;font-style:normal;font-weight:bold;line-height:125%;letter-spacing:normal;" ', $body); ?>
                                                    <?php $body = str_replace( '<p', '<p style="text-align:left;margin-bottom:20px;padding:0;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;color:#173276;font-family:Arial;font-size:16px;line-height:137.5%;"', $body) ?>
                                                    <?php echo $body; ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            </tbody></table>
                                        <!--[if mso]>
                                        </td>
                                        <![endif]-->

                                        <!--[if mso]>
                                        </tr>
                                        </table>
                                        <![endif]-->
                                    </td>
                                </tr>
                                </tbody>
                            </table></td>
                    </tr>


                    <?php
                    $copyrightText = get_field( 'iw_email_template_copyright_text', 'option' );
                    $legalMenu = get_field( 'iw_email_template_legal_menu', 'options' );
                    $disclaimer = get_field( 'iw_email_template_disclaimer', 'option' );

                    if ( empty( $legalMenu ) ) {
                        $legalMenu = wp_get_nav_menu_object( 'email-footer' );
                    }

                    if ( empty( $copyrightText ) ) {
                        $copyrightText = '© Copyright Messolonghi %s. All Rights Reserved.';
                    }

                    if ( empty( $disclaimer ) ) {
                        $disclaimer = 'Λάβατε αυτό το μήνυμα επειδή έχετε λογαριασμό, πραγματοποιήσατε συναλλαγή ή ζητήσατε ενημέρωση από τον Δήμο Ιεράς Πόλης Μεσολογγίου. Πρόκειται για αυτοματοποιημένο μήνυμα· παρακαλούμε μην απαντήσετε.';
                    }

                    if ( function_exists( 'iw_email_template_translate_string' ) ) {
                        $copyrightText = iw_email_template_translate_string( 'footer-copyright', $copyrightText, 'en' );
                        $disclaimer = iw_email_template_translate_string( 'footer-disclaimer', $disclaimer, 'el' );
                    }
                    ?>
                    <?php if( ! empty( $legalMenu ) || ! empty( $copyrightText ) || ! empty( $disclaimer ) ) { ?>
                    <tr>
                        <td valign="top" id="templateFooter" bgcolor="#FFFFFF" style="border-radius:0 0 24px 24px;background:#FFFFFF none no-repeat center/cover;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#FFFFFF;background-image:none;background-repeat:no-repeat;background-position:center;background-size:cover;border-top:0;padding:0 50px 32px 50px;"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">
                                <tbody class="mcnTextBlockOuter">
                                <tr>
                                    <td valign="top" class="mcnTextBlockInner" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                        <!--[if mso]>
                                        <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                                            <tr>
                                        <![endif]-->

                                        <!--[if mso]>
                                        <td valign="top" width="600" style="width:600px;">
                                        <![endif]-->
                                        <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%;min-width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;" width="100%" class="mcnTextContentContainer">
                                            <tbody>
                                            <tr>
                                                <td valign="top" style="padding:0 0 20px 0;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;word-break:break-word;font-family:Arial,sans-serif;font-size:12px;line-height:150%;text-align:left;color:#173276;">
                                                    <?php echo wp_kses_post( nl2br( (string) $disclaimer ) ); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td valign="top" style="padding:0;mso-line-height-rule:exactly;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;font-family:Arial,sans-serif;font-size:11px;line-height:150%;text-align:left;color:#173276;white-space:nowrap;">
                                                    <?php
                                                    $menu_reference = is_object( $legalMenu ) && ! empty( $legalMenu->ID )
                                                        ? (int) $legalMenu->ID
                                                        : $legalMenu;
                                                    $menu_items = ! empty( $menu_reference ) ? wp_get_nav_menu_items( $menu_reference ) : [];
                                                    foreach ( (array) $menu_items as $key => $item ) {
                                                        $item_title = (string) $item->title;
                                                        if ( function_exists( 'iw_email_template_translate_string' ) ) {
                                                            $item_title = iw_email_template_translate_string(
                                                                'legal-menu-item-' . (int) $item->ID,
                                                                $item_title,
                                                                'el'
                                                            );
                                                        }
                                                        ?>
                                                        <a href="<?php echo esc_url( $item->url ); ?>" target="_blank" style="font-weight:400 !important;text-decoration:underline;color:#173276 !important;" title="<?php echo esc_attr( $item_title ); ?>"><?php echo esc_html( $item_title ); ?></a><?php if ( $key !== count( $menu_items ) - 1 ) { ?><span style="display:inline-block;color:#173276;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><?php } ?>
                                                    <?php } ?>
                                                    <?php if ( ! empty( $menu_items ) && ! empty( $copyrightText ) ) { ?>&nbsp;&nbsp;&nbsp;<?php } ?><?php echo esc_html( wp_strip_all_tags( sprintf( (string) $copyrightText, date( 'Y' ) ) ) ); ?>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <!--[if mso]>
                                        </td>
                                        <![endif]-->

                                        <!--[if mso]>
                                        </tr>
                                        </table>
                                        <![endif]-->
                                    </td>
                                </tr>
                                </tbody>
                            </table></td>
                    </tr>
                    <?php } ?>



                    </tbody></table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
                <!-- // END TEMPLATE -->
            </td>
        </tr>
        </tbody></table>
</center>


</body></html>
