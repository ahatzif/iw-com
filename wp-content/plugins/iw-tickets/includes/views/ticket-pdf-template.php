<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            background: #fff;
            font-size: 14px;
            line-height: 1.25;
            color: #31312F;
        }
        .page {
            width: 186mm;
            box-sizing: border-box;
            background: #fff;
            padding: 14mm 12mm;
        }
        .top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        .top td {
            vertical-align: middle;
            padding: 0 2.5mm;
        }
        .qr-wrap {
            width: 34%;
        }
        .brand {
            font-size: 0;
            line-height: 1;
        }
        .brand-logo {
            width: 86mm;
            max-width: 100%;
            height: auto;
            display: block;
        }
        .brand-fallback {
            font-size: 26px;
            letter-spacing: 1px;
            font-weight: 700;
            line-height: 1.1;
        }
        .ticket-number {
            font-size: 11px;
            margin-top: 5mm;
            color: #808080;
        }
        .qr-box {
            width: 35mm;
            height: 35mm;
            box-sizing: border-box;
            margin-left: auto;
            margin-right: 0;
        }
        .qr-box img {
            width: 35mm;
            height: 35mm;
            display: block;
            margin: 0 auto;
        }
        .scan-note {
            text-align: right;
            margin-top: 3mm;
            font-size: 10px;
            color: #808080;
        }
        .swatches {
            width: 100%;
            margin-bottom: 8mm;
            border-collapse: collapse;
        }
        .swatches td {
            width: 12.5%;
            padding-right: 4mm;
        }
        .swatches td:last-child {
            padding-right: 0;
        }
        .swatch {
            height: 16mm;
            border-radius: 1px;
        }
        .intro {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }
        .intro td {
            width: 50%;
            vertical-align: top;
            font-size: 8.8mm;
            padding: 1.7mm 2.5mm;
        }
        .intro p {
            margin: 0;
            font-size: 2.8mm;
            line-height: 1.12;
        }
        .intro .left {
            padding-right: 6mm;
        }
        .intro .right {

        }
        .section-title, .section-subtitle  {
            font-size: 3.4mm;
            line-height: 1;
            margin: 0 0 0.8mm 0;
            font-weight: 700;
        }
        .info-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .info-header td {
            vertical-align: bottom;
            padding: 1.7mm 2.5mm;
        }
        .etickets {
            text-align: right;
            font-size: 23mm;
            letter-spacing: 0.6mm;
            line-height: 1;
            font-weight: 400;
        }
        .etickets span {
            font-size: 12mm;
            vertical-align: middle;
            margin: 0 1.3mm;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.8mm;
        }
        .info-table td {
            padding: 1.5mm 2.5mm;
            border-bottom: 0.35mm solid #c1c1c1;
            vertical-align: top;
            font-size: 2.8mm;
            line-height: 1;
        }
        .info-table td:first-child {
            width: 50%;
        }
        .info-table td:last-child {
            width: 50%;
        }
        .font-bold {
            font-weight: 700;
        }
        .vat-notes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }
        .vat-notes td {
            width: 50%;
            font-size: 2.8mm;
            padding: 1.7mm 2.5mm;
        }
        .visit-header {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 4px 0;
        }
        .visit-header td {
            width: 50%;
            vertical-align: top;
            font-weight: 700;
            font-size: 3.2mm;
            padding: 2.5mm 2.5mm;
        }
        .visit-body {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        .visit-body td {
            width: 50%;
            vertical-align: top;
            font-size: 2.15mm;
            line-height: 0.97;
            padding: 0 2.5mm;
        }
        .visit-body td:last-child {
            padding-right: 0;
        }
        .visit-body ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .visit-body li {
            margin-bottom: 0.8mm;
        }
        .visit-body li:before {
            content: "• ";
        }
        .footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7mm;
        }
        .footer td {
            width: 50%;
            font-size: 3.2mm;
            line-height: 1;
            vertical-align: top;
            padding: 0 2.5mm;
        }
    </style>
</head>
<body>
<div class="page">
    <table class="top">
        <tr>
            <td>
                <div class="brand">
                    <?php if ( ! empty( $logo_data_uri ) ) : ?>
                        <img class="brand-logo" src="<?php echo esc_attr( $logo_data_uri ); ?>" alt="<?php echo esc_attr( $brand_name ?? get_bloginfo( 'name' ) ); ?>">
                    <?php else : ?>
                        <div class="brand-fallback"><?php echo esc_html( $brand_name ?? get_bloginfo( 'name' ) ); ?></div>
                    <?php endif; ?>
                </div>

            </td>
            <td class="qr-wrap">
                <div class="qr-box"><img src="<?php echo esc_attr( $qr_data_uri ); ?>" alt="QR"></div>
            </td>
        </tr>
    </table>


    <table class="intro">
        <tr>
            <td class="left">
                <p>Το παρόν αποτελεί το νόμιμο εισιτήριό σας. Παρακαλείσθε να το επιδείξετε εκτυπωμένο ή σε ηλεκτρονική μορφή στη συσκευή σας κατά την είσοδό σας στον χώρο της έκθεσης/εκδήλωσης.</p>
            </td>
            <td class="right">
                <p>This is your actual ticket. Please print it or present it digitally on your portable device at the exhibition / event entrance.</p>
            </td>
        </tr>
    </table>

    <table class="info-header">
        <tr>
            <td>
                <p class="section-title">ΠΛΗΡΟΦΟΡΙΕΣ ΕΙΣΙΤΗΡΙΟΥ</p>
                <p class="section-subtitle">TICKET INFORMATION</p>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <?php foreach ( $info_rows as $row ) : ?>
            <tr>
                <td><?php echo esc_html( $row['label'] ); ?></td>
                <td>
                    <?php
                    if ( ! empty( $row['is_html'] ) ) {
                        echo wp_kses(
                            (string) $row['value'],
                            [
                                'span' => [
                                    'class' => true,
                                    'style' => true,
                                ],
                                'strong' => [],
                                'b'      => [],
                                'br'     => [],
                            ]
                        );
                    } else {
                        echo esc_html( $row['value'] );
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <table class="vat-notes">
        <tr>
            <td>*Απαλλαγή ΦΠΑ βάσει του άρθρου 22 παράγραφος ιστ' του υπ΄αριθ.Ν.2859</td>
            <td>*VAT Exemption in accordance with article 22, paragraph 16 of Law Nr. 2859</td>
        </tr>
    </table>

    <table class="visit-header">
        <tr>
            <td>ΧΡΗΣΙΜΕΣ ΠΛΗΡΟΦΟΡΙΕΣ ΓΙΑ ΤΗΝ ΕΠΙΣΚΕΨΗ ΣΑΣ</td>
            <td>USEFUL INFORMATION FOR YOUR VISIT</td>
        </tr>
    </table>

    <table class="visit-body">
        <tr>
            <td>
                <ul>
                    <?php foreach ( $left_info_lines as $line ) : ?>
                        <li><?php echo esc_html( $line ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ( $right_info_lines as $line ) : ?>
                        <li><?php echo esc_html( $line ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td><?php echo wp_kses_post( nl2br( esc_html( $pdf_footer_left ?? '' ) ) ); ?></td>
            <td><?php echo wp_kses_post( nl2br( esc_html( $pdf_footer_right ?? '' ) ) ); ?></td>
        </tr>
    </table>
</div>
</body>
</html>
