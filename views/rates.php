<?php
/**
 * PayTR Taksit Oranları Tablo Görünümü
 * $rates değişkeni controller'dan gönderilecek
 * $product_price ürün fiyatı (örneğin 10₺)
 */

if (!defined('ABSPATH')) {
    exit;
}?>
<div class="paytr-installment-table-wrapper">
    <div id="paytr_taksit_tablosu">
        <?php foreach ($oranlar as $brand => $taksitler): ?>
            <div class="taksit-tablosu-wrapper">
                <div class="taksit-logo">
                    <img src="https://www.paytr.com/img/odeme_sayfasi/<?php echo esc_attr($brand); ?>.png"
                         alt="<?php echo esc_attr($brand); ?>">
                </div>
                
                <div class="taksit-baslik">
                    <div class="taksit-tutari-text">Taksit Tutarıı</div>
                    <div class="taksit-tutari-text">Toplam Tutar</div>
                </div>

                <?php
                /** =========================
                 *  PEŞİN (TEK ÇEKİM)
                 *  ========================= */
                if (isset($taksitler['tek_cekim'])):
                    $pesin_toplam = (float) $taksitler['tek_cekim'];
                ?>
                    <div class="taksit-tutar-wrapper taksit-tutari-bold">
                        <div class="taksit-tutari">
                            Tek Çekim
                        </div>
                        <div class="taksit-tutari">
                            <?php echo number_format($pesin_toplam, 2, ',', '.'); ?> TL
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($taksitler as $key => $oran): ?>
                    <?php
                    if (!preg_match('/taksit_(\d+)/', $key, $m)) continue;
                    $adet = (int) $m[1];
                    if ($adet < 2 || $adet > 6) continue;

                    $toplam = $reflect === "yes" ? $amount * (1 + $oran / 100) : $amount;
                    $aylik  = $toplam / $adet;
                    ?>
                    <div class="taksit-tutar-wrapper<?php echo $toplam == $amount ? ' taksit-tutari-bold' : ''; ?>">
                        <div class="taksit-tutari"><?php echo $adet . ' x ' . number_format($aylik, 2, ',', '.'); ?> TL</div>
                        <div class="taksit-tutari"><?php echo number_format($toplam, 2, ',', '.'); ?> TL</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    #paytr_taksit_tablosu{clear: both;font-size: 12px;max-width: 1200px;text-align: center;font-family: Arial, sans-serif;}
    #paytr_taksit_tablosu::before {display: table;content: " ";}
    #paytr_taksit_tablosu::after {content: "";clear: both;display: table;}
    .taksit-tablosu-wrapper{margin: 5px;width: 280px;padding: 12px;cursor: default;text-align: center;display: inline-block;border: 1px solid #e1e1e1;}
    .taksit-logo img{max-height: 28px;padding-bottom: 10px;}
    .taksit-tutari-text{float: left;width: 126px;color: #a2a2a2;margin-bottom: 5px;}
    .taksit-tutar-wrapper{display: inline-block;background-color: #f7f7f7;}
    .taksit-tutar-wrapper:hover{background-color: #e8e8e8;}
    .taksit-tutari{float: left;width: 126px;padding: 6px 0;color: #474747;border: 2px solid #ffffff;}
    .taksit-tutari-bold{font-weight: bold;}
    @media all and (max-width: 600px) {.taksit-tablosu-wrapper {margin: 5px 0;}}
</style>