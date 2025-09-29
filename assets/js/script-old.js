/**
 * Dinamik VirtualPOS - Main JavaScript
 */

jQuery(function($) {
    // Kart Numarası Formatlama
    $('#card-number').on('input', function(e) {
        const input = this;
        const initialCursorPos = input.selectionStart;
        const initialLength = input.value.length;

        // Sadece rakamları al ve 16 haneden fazlasını kes
        let cleanedValue = input.value.replace(/\D/g, '').substring(0, 16);

        // 4'lü gruplara ayır
        let formattedValue = '';
        for (let i = 0; i < cleanedValue.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += cleanedValue[i];
        }

        // Input değerini güncelle
        input.value = formattedValue;

        // İmleç pozisyonunu düzelt
        const newLength = formattedValue.length;
        const lengthDifference = newLength - initialLength;
        input.setSelectionRange(initialCursorPos + lengthDifference, initialCursorPos + lengthDifference);

        // Kart tipini tespit et ve göster
        detectCardType(cleanedValue);
    });



    // Son Kullanma Tarihi Formatlama
    $('#card-expiry').on('input', function(e) {
        const input = this;
        const initialCursorPos = input.selectionStart;
        const initialValue = input.value;

        // Sadece rakamları al ve en fazla 4 haneyi tut (MMYY)
        let cleanedValue = initialValue.replace(/\D/g, '').substring(0, 4);

        // '/' karakterini ekle
        let formattedValue = cleanedValue;
        if (cleanedValue.length > 2) {
            formattedValue = cleanedValue.substring(0, 2) + '/' + cleanedValue.substring(2);
        }

        // Input değerini güncelle
        input.value = formattedValue;

        // İmleç pozisyonunu düzelt
        const newLength = formattedValue.length;
        const lengthDifference = newLength - initialValue.length;
        let newCursorPos = initialCursorPos + lengthDifference;

        // Kullanıcı '/' işaretini elle silerse imleci doğru konuma al
        if (initialValue.length > 2 && formattedValue.length === 2) {
            newCursorPos = 2;
        }

        // İmleci ayarla
        input.setSelectionRange(newCursorPos, newCursorPos);

        // Geçerli tarih kontrolü
        validateExpiryDate(formattedValue);
    }).on('keypress', function(e) {
        // Sadece rakam ve '/' karakterine izin ver
        const allowedChars = /[0-9\/]/;
        return allowedChars.test(String.fromCharCode(e.which));
    });



    // CVV Formatlama
    $('#card-cvv').on('input', function(e) {
        // Sadece rakamları al ve 3 haneyle sınırla
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        
        // CVV 3 haneli olduğunda kart ikonunu göster
        if (e.target.value.length === 3) {
            $('.card-icon').text('💳').attr('title', 'Visa/Mastercard');
        } else {
            $('.card-icon').text('').attr('title', '');
        }
    }).on('keypress', function(e) {
        // Sadece rakam kabul et
        return /[0-9]/.test(String.fromCharCode(e.which));
    });

    // Paste işlemi için de aynı kısıtlama
    $('#card-cvv').on('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        const digits = pastedText.replace(/\D/g, '').substring(0, 3);
        this.value = digits;
        
        if (digits.length === 3) {
            $('.card-icon').text('💳').attr('title', 'Visa/Mastercard');
        }
    });




    // Kart Üzerindeki İsim Formatlama
    $('#card-name').on('input', function(e) {
        const input = this;
        const initialCursorPos = input.selectionStart;
        const initialValue = input.value;

        // Sadece harf ve boşluk
        let cleanedValue = initialValue.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');

        // Metni tamamen büyük harfe dönüştür
        let formattedValue = cleanedValue.toUpperCase();

        // Input değerini güncelle
        input.value = formattedValue;
        
        // İmleç pozisyonunu düzelt
        const newLength = formattedValue.length;
        const lengthDifference = newLength - initialValue.length;
        input.setSelectionRange(initialCursorPos + lengthDifference, initialCursorPos + lengthDifference);

    }).on('keypress', function(e) {
        // Sadece harf ve boşluk kabul et
        return /[a-zA-ZğüşıöçĞÜŞİÖÇ\s]/.test(String.fromCharCode(e.which));
    });

    // Kart Tipi Tespiti
    function detectCardType(cardNumber) {
        const cardIcon = $('.card-icon');
        
        if (/^4/.test(cardNumber)) {
            cardIcon.text('💳').attr('title', 'Visa');
        } else if (/^5[1-5]/.test(cardNumber)) {
            cardIcon.text('💳').attr('title', 'Mastercard');
        } else if (/^3[47]/.test(cardNumber)) {
            cardIcon.text('💎').attr('title', 'American Express');
        } else if (/^6/.test(cardNumber)) {
            cardIcon.text('🏦').attr('title', 'Discover');
        } else if (cardNumber.length > 0) {
            cardIcon.text('💳').attr('title', 'Kredi Kartı');
        } else {
            cardIcon.text('').attr('title', '');
        }
    }

    // Son Kullanma Tarihi Validasyonu
    function validateExpiryDate(date) {
        if (date.length === 5) {
            const [month, year] = date.split('/');
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear() % 100;
            const currentMonth = currentDate.getMonth() + 1;
            
            const inputYear = parseInt(year);
            const inputMonth = parseInt(month);
            
            if (inputMonth < 1 || inputMonth > 12) {
                showError($('#card-expiry'), 'Geçersiz ay');
            } else if (inputYear < currentYear || (inputYear === currentYear && inputMonth < currentMonth)) {
                showError($('#card-expiry'), 'Kartınızın süresi dolmuş');
            } else {
                clearError($('#card-expiry'));
            }
        }
    }

    // Hata Gösterimi
    function showError($element, message) {
        $element.addClass('error');
        $element.attr('title', message);
        
        // Hata tooltip'i
        if (!$element.next('.error-tooltip').length) {
            $element.after('<div class="error-tooltip">' + message + '</div>');
        }
    }

    // Hata Temizleme
    function clearError($element) {
        $element.removeClass('error');
        $element.removeAttr('title');
        $element.next('.error-tooltip').remove();
    }

    // Input focus olduğunda hataları temizle
    $('input').on('focus', function() {
        clearError($(this));
    });

    // Form gönderimi öncesi validasyon
    $('.virtualpos_payment_form').on('submit', function(e) {
        let isValid = true;
        
        // Kart numarası kontrolü
        const cardNumber = $('#card-number').val().replace(/\s/g, '');
        if (cardNumber.length !== 16) {
            showError($('#card-number'), 'Kart numarası 16 haneli olmalıdır');
            isValid = false;
        }
        
        // Son kullanma tarihi kontrolü
        const expiry = $('#card-expiry').val();
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            showError($('#card-expiry'), 'Geçerli bir tarih girin (AA/YY)');
            isValid = false;
        }
        
        // CVV kontrolü
        const cvv = $('#card-cvv').val();
        if (cvv.length < 3) {
            showError($('#card-cvv'), 'CVV 3 veya 4 haneli olmalıdır');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // İlk hataya focusla
            $('.error').first().focus();
        }
    });

    //console.log('VirtualPOS maskeleri yüklendi!');
});



jQuery(function($) {
    function updateExpiryFields() {
        const val = $('#card-expiry').val().trim();
        const parts = val.split('/');
        if (parts.length === 2) {
            const month = parts[0];
            const year  = parts[1];
            if (/^(0[1-9]|1[0-2])$/.test(month) && /^[0-9]{2}$/.test(year)) {
                $('#expiry-month').val(month);
                $('#expiry-year').val(year);
                return;
            }
        }
        // Geçersizse temizle
        $('#expiry-month').val('');
        $('#expiry-year').val('');
    }

    function updateCardNumberField() {
        const val = $('#card-number').val().trim();
        const formatted = val.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        const cleanNumber = formatted.replace(/\s+/g, '');
        console.log(cleanNumber);
        // Formatlanmış halini göster
        //$('#card-number').val(formatted);
        
        // Boşluksuz halini hidden input'a aktar
        $('input[name="card_number"]').val(cleanNumber);
    }

    // Sayfa yüklenince
    updateExpiryFields();
    updateCardNumberField();

    // Input değiştiğinde (blur veya input event ile)
    $('#card-expiry').on('input blur change', updateExpiryFields);
    $('#card-number').on('input blur change', updateCardNumberField);
});

//Bin numarası kontrolü
jQuery(function ($) {
    const $cardInput = $('#card-number');      // kart input ID'si
    const $cardInfo  = $('#card-type');       // sonucu yazacağın alan
    const $select    = $('select#installment');

    let lastBin = null;
    $cardInput.on('input', function () {
        const digits = $(this).val().replace(/\D/g, '');
        if (digits.length >= 6) {
            const bin = digits.substr(0, 6);
            if (bin === lastBin) return; // aynı BIN için tekrar sorgulama
            lastBin = bin;

            $select.html(
                $("<option>", { text: "Hesaplanıyor...", disabled: true })
            );
            $.post(virtualpos_params.ajax_url, {
                action: 'dinamik_bin_lookup',
                security: virtualpos_params.security,
                bin: bin
            }, function (res) {
                if (!res || !res.success) {
                    $cardInfo.text('Kart bilgisi bulunamadı');
                    return;
                }

                const data = res.data;

                $select.html('');
                $.each(data.prices, function (i, price) {
                    let index = parseInt(i, 10); // i değerini integer'a çeviriyoruz

                    let taksitText = index === 1 ? 'Tek Çekim' : index + " Taksit";
                    let text = taksitText + ": " + price.aylik.toFixed(2) + " TL";

                    text += (index > 0 ? " x " + index : ""); // Tek çekimde x 1 yazmaması için
                        //" = " + price.toplam.toFixed(2) + " TL";

                    $select.append(
                        $("<option>", {
                            value: index,
                            text: text,
                            "data-price": price.toplam 
                        })
                    );
                });
                // Örnek gösterim: "Visa — İş Bankası (World)"
                let label = [];
                if (data.scheme) label.push(data.scheme);                // Visa / MasterCard / etc
                if (data.bank_name) label.push(data.bank_name);        // issuer bank
                if (data.product) label.push(`(${data.product})`);     // optional: World/Bonus/Axess

                $cardInfo.text(label.join(' — '));
            }, 'json')
            .fail(function (jqXHR, textStatus, errorThrown) {
                $cardInfo.text('Sorgu hatası');
                console.error('Sorgu hatası:', textStatus, errorThrown);
                console.error('HTTP Status:', jqXHR.status);
                console.error('Response:', jqXHR.responseText);
            });
        } else {
            lastBin = null;
            $cardInfo.text('');
        }
    });
});