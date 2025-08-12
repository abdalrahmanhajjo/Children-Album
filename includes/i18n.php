<?php
class i18n {
    private $language = 'en';
    private $availableLanguages = [
        'en' => 'English',
        'ar' => 'العربية',
        'de' => 'Deutsch'
    ];
    
    private $translations = [
        'en' => [
            'choose_theme' => 'Choose a Theme',
            'girl_themes' => 'Girl Themes',
            'boy_themes' => 'Boy Themes',
            'neutral_themes' => 'Neutral Themes',
            'change_theme' => 'Change Theme',
            'our_precious' => 'Our precious little',
            'old' => 'old',
            'born' => 'Born',
            'view_photos' => 'View Photos',
            'send_wishes' => 'Send Wishes',
            'about' => 'About',
            'birth_date' => 'Birth Date',
            'birth_weight' => 'Birth Weight',
            'birth_length' => 'Birth Length',
            'add_photo' => 'Add Photo',
            'add_milestone' => 'Add Milestone',
            'photo_gallery' => 'Photo Gallery',
            'no_photos_yet' => 'No Photos Yet',
            'start_gallery_text' => 'Start building your child\'s photo gallery by adding their first photo.',
            'add_first_photo' => 'Add First Photo',
            'milestones' => 'Milestones',
            'no_milestones_yet' => 'No Milestones Yet',
            'document_milestones_text' => 'Document your child\'s important achievements and special moments.',
            'add_first_milestone' => 'Add First Milestone',
            'wishes_for' => 'Wishes for',
            'share_wishes_text' => 'Share your love and blessings for',
            'your_name' => 'Your Name',
            'your_message' => 'Your Message',
            'your_relationship' => 'Your Relationship',
            'family' => 'Family',
            'friend' => 'Friend',
            'colleague' => 'Colleague',
            'other' => 'Other',
            'send_wish' => 'Send Wish',
            'no_wishes_yet' => 'No Wishes Yet',
            'be_first_wish_text' => 'Be the first to send your love and blessings to',
            'and_counting' => 'And counting!',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'confirm_delete_photo' => 'Are you sure you want to delete this photo?',
            'confirm_delete_milestone' => 'Are you sure you want to delete this milestone?',
            'error_changing_theme' => 'Error changing theme',
            'error_changing_language' => 'Error changing language',
            'untitled' => 'Untitled'
        ],
        'ar' => [
            'choose_theme' => 'اختر سمة',
            'girl_themes' => 'سمات البنات',
            'boy_themes' => 'سمات الأولاد',
            'neutral_themes' => 'سمات محايدة',
            'change_theme' => 'تغيير السمة',
            'our_precious' => 'غاليتنا الصغيرة',
            'old' => 'سنة',
            'born' => 'ولدت في',
            'view_photos' => 'عرض الصور',
            'send_wishes' => 'إرسال التمنيات',
            'about' => 'حول',
            'birth_date' => 'تاريخ الميلاد',
            'birth_weight' => 'وزن الولادة',
            'birth_length' => 'طول الولادة',
            'add_photo' => 'إضافة صورة',
            'add_milestone' => 'إضافة معلماً',
            'photo_gallery' => 'معرض الصور',
            'no_photos_yet' => 'لا توجد صور بعد',
            'start_gallery_text' => 'ابدأ بناء معرض الصور لطفلك بإضافة أول صورة.',
            'add_first_photo' => 'إضافة أول صورة',
            'milestones' => 'المعالم',
            'no_milestones_yet' => 'لا توجد معالم بعد',
            'document_milestones_text' => 'قم بتوثيق إنجازات طفلك المهمة واللحظات الخاصة.',
            'add_first_milestone' => 'إضافة أول معلماً',
            'wishes_for' => 'تمنيات لـ',
            'share_wishes_text' => 'شارك حبك وبركاتك لـ',
            'your_name' => 'اسمك',
            'your_message' => 'رسالتك',
            'your_relationship' => 'علاقتك',
            'family' => 'عائلة',
            'friend' => 'صديق',
            'colleague' => 'زميل',
            'other' => 'آخر',
            'send_wish' => 'إرسال التمنيات',
            'no_wishes_yet' => 'لا توجد تمنيات بعد',
            'be_first_wish_text' => 'كن أول من يرسل حبه وبركاته لـ',
            'and_counting' => 'والعد مستمر!',
            'edit' => 'تعديل',
            'delete' => 'حذف',
            'confirm_delete_photo' => 'هل أنت متأكد أنك تريد حذف هذه الصورة؟',
            'confirm_delete_milestone' => 'هل أنت متأكد أنك تريد حذف هذا المعلم؟',
            'error_changing_theme' => 'خطأ في تغيير السمة',
            'error_changing_language' => 'خطأ في تغيير اللغة',
            'untitled' => 'بدون عنوان'
        ],
        'de' => [
            'choose_theme' => 'Thema auswählen',
            'girl_themes' => 'Mädchen-Themen',
            'boy_themes' => 'Jungen-Themen',
            'neutral_themes' => 'Neutrale Themen',
            'change_theme' => 'Thema ändern',
            'our_precious' => 'Unser kostbares kleines',
            'old' => 'Jahre alt',
            'born' => 'Geboren am',
            'view_photos' => 'Fotos ansehen',
            'send_wishes' => 'Wünsche senden',
            'about' => 'Über',
            'birth_date' => 'Geburtsdatum',
            'birth_weight' => 'Geburtsgewicht',
            'birth_length' => 'Geburtsgröße',
            'add_photo' => 'Foto hinzufügen',
            'add_milestone' => 'Meilenstein hinzufügen',
            'photo_gallery' => 'Fotogalerie',
            'no_photos_yet' => 'Noch keine Fotos',
            'start_gallery_text' => 'Beginnen Sie mit dem Aufbau der Fotogalerie Ihres Kindes, indem Sie sein erstes Foto hinzufügen.',
            'add_first_photo' => 'Erstes Foto hinzufügen',
            'milestones' => 'Meilensteine',
            'no_milestones_yet' => 'Noch keine Meilensteine',
            'document_milestones_text' => 'Dokumentieren Sie die wichtigen Leistungen und besonderen Momente Ihres Kindes.',
            'add_first_milestone' => 'Ersten Meilenstein hinzufügen',
            'wishes_for' => 'Wünsche für',
            'share_wishes_text' => 'Teilen Sie Ihre Liebe und Segenswünsche für',
            'your_name' => 'Ihr Name',
            'your_message' => 'Ihre Nachricht',
            'your_relationship' => 'Ihre Beziehung',
            'family' => 'Familie',
            'friend' => 'Freund',
            'colleague' => 'Kollege',
            'other' => 'Andere',
            'send_wish' => 'Wunsch senden',
            'no_wishes_yet' => 'Noch keine Wünsche',
            'be_first_wish_text' => 'Seien Sie der Erste, der seine Liebe und Segenswünsche an',
            'and_counting' => 'Und es werden mehr!',
            'edit' => 'Bearbeiten',
            'delete' => 'Löschen',
            'confirm_delete_photo' => 'Möchten Sie dieses Foto wirklich löschen?',
            'confirm_delete_milestone' => 'Möchten Sie diesen Meilenstein wirklich löschen?',
            'error_changing_theme' => 'Fehler beim Ändern des Themas',
            'error_changing_language' => 'Fehler beim Ändern der Sprache',
            'untitled' => 'Ohne Titel'
        ]
    ];
    
    public function __construct() {
        if (isset($_SESSION['language']) && array_key_exists($_SESSION['language'], $this->availableLanguages)) {
            $this->language = $_SESSION['language'];
        }
    }
    
    public function setLanguage($lang) {
        if (array_key_exists($lang, $this->availableLanguages)) {
            $this->language = $lang;
            $_SESSION['language'] = $lang;
        }
    }
    
    public function getLanguage() {
        return $this->language;
    }
    
    public function getCurrentLanguageName() {
        return $this->availableLanguages[$this->language];
    }
    
    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }
    
    public function translate($key) {
        return $this->translations[$this->language][$key] ?? $key;
    }
}
?>