jQuery(document).ready(function($) {
    var localeMessages = {
        'tr': {
            addOption: 'Seçenek Ekle',
            allFieldsRemoved: 'Tüm Alanlar Silindi.',
            allowSelect: 'Seçmeye İzin Ver',
            allowMultipleFiles: 'Kullanıcıların Birden Fazla Dosya Yüklemesine İzin Ver',
            autocomplete: 'Otomatik Tamamlama',
            button: 'Buton',
            cannotBeEmpty: 'Bu Alan Boş Olamaz',
            checkboxGroup: 'Onay Kutusu Grubu',
            checkbox: 'Onay Kutusu',
            checkboxes: 'Onay Kutuları',
            className: 'Sınıf',
            clearAllMessage: 'Tüm Alanları Silmek İstediğinize Emin misiniz?',
            clearAll: 'Temizle',
            close: 'Kapat',
            content: 'İçerik',
            copy: 'Panoya Kopyala',
            dateField: 'Tarih',
            description: 'Tanım',
            descriptionField: 'Açıklama',
            devMode: 'Geliştirici Modu',
            editNames: 'İsimleri Düzenle',
            editorTitle: 'Form Elemanları',
            editXML: 'XML Düzenle',
            enableOther: 'Diğerine Olanak Ver',
            enableOtherMsg: 'Kullanıcıların Listelenmemiş Seçenek Girmesine İzin Ver',
            fieldDeleteWarning: 'Alan Silme Uyarısı',
            fieldVars: 'Alan Değişkenleri',
            fieldNonEditable: 'Bu Alan Düzenlenemez.',
            fieldRemoveWarning: 'Bu Alanı Silmek İstediğinize Emin misiniz?',
            fileUpload: 'Dosya Yükleme',
            formUpdated: 'Form Güncellendi',
            getStarted: 'Bir Alanı Buraya Sürükleyin',
            header: 'Başlık',
            hide: 'Düzenle',
            hidden: 'Gizli Girdi',
            label: 'Etiket',
            labelEmpty: 'Alan Etiketi Boş Olamaz',
            limitRole: 'Aşağıdaki Rollerin Bir Veya Daha Fazlasına Sınırlı Erişim:',
            mandatory: 'Zorunlu',
            maxlength: 'Maksimum Uzunluk',
            minOptionMessage: 'Bu Alan En Az 2 Seçenek Gerektirir',
            multipleFiles: 'Çok Sayıda Dosya',
            name: 'Ad',
            no: 'No',
            number: 'Numara',
            off: 'Off',
            on: 'On',
            option: 'Seçenek',
            optional: 'Seçmeli',
            optionLabelPlaceholder: 'Etiket',
            optionValuePlaceholder: 'Değer',
            optionEmpty: 'Seçenek Değeri Gerekli',
            other: 'Diğer',
            paragraph: 'Paragraf',
            placeholder: 'Yer Tutucu',
            placeholders: {
                value: 'Değer',
                label: 'Etiket',
                text: 'Metin',
                textarea: 'Metin alanı',
                email: 'E-mail Girin',
                placeholder: 'Yer Tutucu',
                className: 'Sınıfları Ayırın',
                password: 'Şifrenizi Girin'
            },
            preview: 'Görüntüle',
            radioGroup: 'Radyo Grup',
            radio: 'Radyo',
            removeMessage: 'Elemanı Sil',
            remove: 'Sil',
            required: 'Zorunlu Alan',
            richText: 'Zengin Metin Editörü',
            roles: 'Giriş',
            save: 'Kaydet',
            selectOptions: 'Seçenekler',
            select: 'Seç',
            selectColor: 'Renk Seç',
            selectionsMessage: 'Çoklu Seçimlere İzin Ver',
            size: 'Boyut',
            sizes: {
                xs: 'Çok Küçük',
                sm: 'Küçük',
                m: 'Varsayılan',
                lg: 'Büyük'
            },
            style: 'Stil',
            styles: {
                btn: {
                    'default': 'Varsayılan',
                    danger: 'Tehlike',
                    info: 'Bilgi',
                    primary: 'Birincil',
                    success: 'Başarılı',
                    warning: 'Uyarı'
                }
            },
            subtype: 'Tip',
            subtypes: {
                text: ['metin', 'şifre', 'e-posta', 'renk'],
                button: ['buton', 'gönder'],
                header: ['h1', 'h2', 'h3'],
                paragraph: ['p', 'adres', 'blok alıntı', 'tuval', 'çıktı']
            },
            text: 'Metin',
            textArea: 'Metin Alanı',
            toggle: 'Aç-Kapa Fonksiyonu',
            warning: 'Uyarı!',
            value: 'Değer',
            viewXML: '&lt;/&gt;',
            yes: 'Evet'
        }
    };
    var template = document.getElementById('journal_review_form_content'),
        $buildWrap = $(document.querySelector('.build-wrap')),
        renderWrap = document.querySelector('.render-wrap'),
        editBtn = document.getElementById('edit-form'),
        editing = true;

    var toggleEdit = function() {
        document.body.classList.toggle('editing-form', editing);
        $buildWrap.toggle();
        $(renderWrap).toggle();
        editing = !editing;
    };
    var options = {
        editOnAdd: true,
        disableFields: [
            'button',
            'autocomplete',
            'hidden',
            'file'
        ],
        roles: {
            1: 'Author can not see'
        },
        'messages': localeMessages[current_language] || {}
    };
    $(template).formBuilder(options);

    $('.form-builder-save').click(function() {
        toggleEdit();
        $(template).formRender({
            container: renderWrap
        });
    });

    editBtn.onclick = function() {
        toggleEdit();
    };

    function removeUnnecessaryFields(){
        $('.form-elements .className-wrap').addClass('hidden');
        $('.form-elements .name-wrap').addClass('hidden');
    }
    //bind to div and on change remove unnecessary fields
    //this lines can be removal, because can trigger an browser crash
    $('.build-wrap').bind("DOMSubtreeModified",function(){
        removeUnnecessaryFields();
    });
    removeUnnecessaryFields();
});