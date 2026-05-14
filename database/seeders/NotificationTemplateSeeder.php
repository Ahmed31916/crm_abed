<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateLang;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();

        $templates = [
            // Lead Create
            [
                'name' => 'Lead Create',
                'translations' => [
                    'en' => [
                        'title' => 'New Lead Create',
                        'content' => 'Hello {lead_name}, thank you for showing interest in {company_name}.Our team will contact you shortly to assist with your needs. - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Nuevo Lead Creado',
                        'content' => 'Hola {lead_name}, gracias por mostrar interés en {company_name}. Nuestro equipo se pondrá en contacto contigo pronto para ayudarte con tus necesidades. - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'عميل محتمل جديد',
                        'content' => 'مرحبا {lead_name}، شكرا لك لإظهار الاهتمام في {company_name}. سيتصل بك فريقنا قريبا لمساعدتك في احتياجاتك. - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Nyt Lead Oprettet',
                        'content' => 'Hej {lead_name}, tak for at vise interesse for {company_name}. Vores team vil kontakte dig snart for at hjælpe med dine behov. - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Neuer Lead Erstellt',
                        'content' => 'Hallo {lead_name}, vielen Dank für Ihr Interesse an {company_name}. Unser Team wird Sie bald kontaktieren, um Ihnen bei Ihren Bedürfnissen zu helfen. - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Nouveau Lead Créé',
                        'content' => 'Bonjour {lead_name}, merci de montrer de l\'intérêt pour {company_name}. Notre équipe vous contactera bientôt pour vous aider avec vos besoins. - {company_name}'
                    ],
                    'he' => [
                        'title' => 'ליד חדש נוצר',
                        'content' => 'שלום {lead_name}, תודה על הענין ב{company_name}. הצוות שלנו יצור איתך קשר בקרוב כדי לעזור עם הצרכים שלך. - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Nuovo Lead Creato',
                        'content' => 'Ciao {lead_name}, grazie per aver mostrato interesse in {company_name}. Il nostro team ti contatterà presto per aiutarti con le tue esigenze. - {company_name}'
                    ],
                    'ja' => [
                        'title' => '新しいリード作成',
                        'content' => 'こんにちは{lead_name}、{company_name}に興味を示していただきありがとうございます。私たちのチームがあなたのニーズをお手伝いするためにすぐにご連絡いたします。 - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe Lead Aangemaakt',
                        'content' => 'Hallo {lead_name}, bedankt voor je interesse in {company_name}. Ons team zal je binnenkort contacteren om je te helpen met je behoeften. - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Nowy Lead Utworzony',
                        'content' => 'Cześć {lead_name}, dziękujemy za zainteresowanie {company_name}. Nasz zespół skontaktuje się z Tobą wkrótce, aby pomóc z Twoimi potrzebami. - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Novo Lead Criado',
                        'content' => 'Olá {lead_name}, obrigado por mostrar interesse em {company_name}. Nossa equipe entrará em contato em breve para ajudar com suas necessidades. - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo Lead Criado',
                        'content' => 'Olá {lead_name}, obrigado por mostrar interesse em {company_name}. Nossa equipe entrará em contato em breve para ajudar com suas necessidades. - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Новый Лид Создан',
                        'content' => 'Привет {lead_name}, спасибо за интерес к {company_name}. Наша команда свяжется с вами в ближайшее время, чтобы помочь с вашими потребностями. - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Yeni Müşteri Adayı Oluşturuldu',
                        'content' => 'Merhaba {lead_name}, {company_name}\'e ilgi gösterdiğiniz için teşekkürler. Ekibimiz ihtiyaçlarınızla ilgili yardım etmek için yakında sizinle iletişime geçecek. - {company_name}'
                    ],
                    'zh' => [
                        'title' => '新潜在客户创建',
                        'content' => '你好{lead_name}，感谢您对{company_name}的关注。我们的团队将很快与您联系，协助满足您的需求。 - {company_name}'
                    ]
                ]
            ],
            // Opportunity create
            [
                'name' => 'Opportunity create',
                'translations' => [
                    'en' => [
                        'title' => 'New opportunity',
                        'content' => 'New opportunity: {opportunity_name} worth ${amount}. Account: {account_name}. Close date: {close_date}. Take action now! - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Nueva oportunidad',
                        'content' => 'Nueva oportunidad: {opportunity_name} por valor de ${amount}. Cuenta: {account_name}. Fecha de cierre: {close_date}. ¡Actúa ahora! - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'فرصة جديدة',
                        'content' => 'فرصة جديدة: {opportunity_name} بقيمة ${amount}. الحساب: {account_name}. تاريخ الإغلاق: {close_date}. اتخذ إجراء الآن! - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Ny mulighed',
                        'content' => 'Ny mulighed: {opportunity_name} til værdi af ${amount}. Konto: {account_name}. Lukkedato: {close_date}. Tag handling nu! - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Neue Gelegenheit',
                        'content' => 'Neue Gelegenheit: {opportunity_name} im Wert von ${amount}. Konto: {account_name}. Abschlussdatum: {close_date}. Handeln Sie jetzt! - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle opportunité',
                        'content' => 'Nouvelle opportunité: {opportunity_name} d\'une valeur de ${amount}. Compte: {account_name}. Date de clôture: {close_date}. Agissez maintenant! - {company_name}'
                    ],
                    'he' => [
                        'title' => 'הזדמנות חדשה',
                        'content' => 'הזדמנות חדשה: {opportunity_name} בשווי ${amount}. חשבון: {account_name}. תאריך סגירה: {close_date}. פעל עכשיו! - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Nuova opportunità',
                        'content' => 'Nuova opportunità: {opportunity_name} del valore di ${amount}. Account: {account_name}. Data di chiusura: {close_date}. Agisci ora! - {company_name}'
                    ],
                    'ja' => [
                        'title' => '新しい機会',
                        'content' => '新しい機会: {opportunity_name} 価値${amount}。アカウント: {account_name}。クローズ日: {close_date}。今すぐ行動を！ - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe kans',
                        'content' => 'Nieuwe kans: {opportunity_name} ter waarde van ${amount}. Account: {account_name}. Sluitdatum: {close_date}. Onderneem nu actie! - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Nowa szansa',
                        'content' => 'Nowa szansa: {opportunity_name} o wartości ${amount}. Konto: {account_name}. Data zamknięcia: {close_date}. Działaj teraz! - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Nova oportunidade',
                        'content' => 'Nova oportunidade: {opportunity_name} no valor de ${amount}. Conta: {account_name}. Data de fechamento: {close_date}. Aja agora! - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova oportunidade',
                        'content' => 'Nova oportunidade: {opportunity_name} no valor de ${amount}. Conta: {account_name}. Data de fechamento: {close_date}. Aja agora! - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Новая возможность',
                        'content' => 'Новая возможность: {opportunity_name} стоимостью ${amount}. Аккаунт: {account_name}. Дата закрытия: {close_date}. Действуйте сейчас! - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Yeni fırsat',
                        'content' => 'Yeni fırsat: {opportunity_name} ${amount} değerinde. Hesap: {account_name}. Kapanış tarihi: {close_date}. Şimdi harekete geç! - {company_name}'
                    ],
                    'zh' => [
                        'title' => '新机会',
                        'content' => '新机会: {opportunity_name} 价值${amount}。账户: {account_name}。关闭日期: {close_date}。立即行动！ - {company_name}'
                    ]
                ]
            ],
            // Account Create
            [
                'name' => 'Account create',
                'translations' => [
                    'en' => [
                        'title' => 'Welcome to our family',
                        'content' => 'Welcome {account_name}! Your account has been created successfully. We are excited to work with you! - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Bienvenido a nuestra familia',
                        'content' => '¡Bienvenido {account_name}! Tu cuenta ha sido creada exitosamente. ¡Estamos emocionados de trabajar contigo! - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'مرحبا بك في عائلتنا',
                        'content' => 'مرحبا {account_name}! تم إنشاء حسابك بنجاح. نحن متحمسون للعمل معك! - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Velkommen til vores familie',
                        'content' => 'Velkommen {account_name}! Din konto er blevet oprettet med succes. Vi er begejstrede for at arbejde med dig! - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Willkommen in unserer Familie',
                        'content' => 'Willkommen {account_name}! Ihr Konto wurde erfolgreich erstellt. Wir freuen uns auf die Zusammenarbeit mit Ihnen! - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Bienvenue dans notre famille',
                        'content' => 'Bienvenue {account_name}! Votre compte a été créé avec succès. Nous sommes ravis de travailler avec vous! - {company_name}'
                    ],
                    'he' => [
                        'title' => 'ברוכים הבאים למשפחה שלנו',
                        'content' => 'ברוך הבא {account_name}! החשבון שלך נוצר בהצלחה. אנחנו נרגשים לעבוד איתך! - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Benvenuto nella nostra famiglia',
                        'content' => 'Benvenuto {account_name}! Il tuo account è stato creato con successo. Siamo entusiasti di lavorare con te! - {company_name}'
                    ],
                    'ja' => [
                        'title' => '私たちの家族へようこそ',
                        'content' => 'ようこそ{account_name}！あなたのアカウントが正常に作成されました。私たちはあなたと一緒に働くことを楽しみにしています！ - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Welkom bij onze familie',
                        'content' => 'Welkom {account_name}! Je account is succesvol aangemaakt. We zijn enthousiast om met je te werken! - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Witamy w naszej rodzinie',
                        'content' => 'Witamy {account_name}! Twoje konto zostało pomyślnie utworzone. Jesteśmy podekscytowani współpracą z Tobą! - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Bem-vindo à nossa família',
                        'content' => 'Bem-vindo {account_name}! Sua conta foi criada com sucesso. Estamos animados para trabalhar com você! - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Bem-vindo à nossa família',
                        'content' => 'Bem-vindo {account_name}! Sua conta foi criada com sucesso. Estamos animados para trabalhar com você! - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Добро пожаловать в нашу семью',
                        'content' => 'Добро пожаловать {account_name}! Ваш аккаунт был успешно создан. Мы рады работать с вами! - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Ailemize hoş geldiniz',
                        'content' => 'Hoş geldin {account_name}! Hesabın başarıyla oluşturuldu. Seninle çalışmaktan heyecan duyuyoruz! - {company_name}'
                    ],
                    'zh' => [
                        'title' => '欢迎加入我们的大家庭',
                        'content' => '欢迎{account_name}！您的账户已成功创建。我们很高兴与您合作！ - {company_name}'
                    ]
                ]
            ],
            // Quote Create
            [
                'name' => 'Quote Create',
                'translations' => [
                    'en' => [
                        'title' => 'New Quote Created',
                        'content' => 'Quote #{quote_number} created for {account_name}. Amount: ${total_amount}. Valid until {valid_until}. Follow up soon! - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Nueva Cotización Creada',
                        'content' => 'Cotización #{quote_number} creada para {account_name}. Monto: ${total_amount}. Válida hasta {valid_until}. ¡Haz seguimiento pronto! - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'عرض أسعار جديد',
                        'content' => 'عرض أسعار #{quote_number} تم إنشاؤه لـ {account_name}. المبلغ: ${total_amount}. صالح حتى {valid_until}. تابع قريبا! - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Nyt Tilbud Oprettet',
                        'content' => 'Tilbud #{quote_number} oprettet for {account_name}. Beløb: ${total_amount}. Gyldig indtil {valid_until}. Følg op snart! - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Neues Angebot Erstellt',
                        'content' => 'Angebot #{quote_number} für {account_name} erstellt. Betrag: ${total_amount}. Gültig bis {valid_until}. Bald nachfassen! - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Nouveau Devis Créé',
                        'content' => 'Devis #{quote_number} créé pour {account_name}. Montant: ${total_amount}. Valide jusqu\'au {valid_until}. Suivez bientôt! - {company_name}'
                    ],
                    'he' => [
                        'title' => 'הצעת מחיר חדשה נוצרה',
                        'content' => 'הצעת מחיר #{quote_number} נוצרה עבור {account_name}. סכום: ${total_amount}. תקף עד {valid_until}. עקוב בקרוב! - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Nuovo Preventivo Creato',
                        'content' => 'Preventivo #{quote_number} creato per {account_name}. Importo: ${total_amount}. Valido fino al {valid_until}. Segui presto! - {company_name}'
                    ],
                    'ja' => [
                        'title' => '新しい見積もり作成',
                        'content' => '見積もり#{quote_number}が{account_name}用に作成されました。金額: ${total_amount}。{valid_until}まで有効。すぐにフォローアップ！ - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe Offerte Aangemaakt',
                        'content' => 'Offerte #{quote_number} aangemaakt voor {account_name}. Bedrag: ${total_amount}. Geldig tot {valid_until}. Volg snel op! - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Nowa Oferta Utworzona',
                        'content' => 'Oferta #{quote_number} utworzona dla {account_name}. Kwota: ${total_amount}. Ważna do {valid_until}. Śledź wkrótce! - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Nova Cotação Criada',
                        'content' => 'Cotação #{quote_number} criada para {account_name}. Valor: ${total_amount}. Válida até {valid_until}. Acompanhe em breve! - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova Cotação Criada',
                        'content' => 'Cotação #{quote_number} criada para {account_name}. Valor: ${total_amount}. Válida até {valid_until}. Acompanhe em breve! - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Новое Предложение Создано',
                        'content' => 'Предложение #{quote_number} создано для {account_name}. Сумма: ${total_amount}. Действительно до {valid_until}. Следите скоро! - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Yeni Teklif Oluşturuldu',
                        'content' => 'Teklif #{quote_number} {account_name} için oluşturuldu. Tutar: ${total_amount}. {valid_until} tarihine kadar geçerli. Yakında takip et! - {company_name}'
                    ],
                    'zh' => [
                        'title' => '新报价创建',
                        'content' => '为{account_name}创建报价#{quote_number}。金额: ${total_amount}。有效期至{valid_until}。尽快跟进！ - {company_name}'
                    ]
                ]
            ],
            // Case Create
            [
                'name' => 'Case Create',
                'translations' => [
                    'en' => [
                        'title' => 'Case Received',
                        'content' => 'Your case is received. Thank you! We will resolve it soon. Case: {case_subject} - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Caso Recibido',
                        'content' => 'Tu caso ha sido recibido. ¡Gracias! Lo resolveremos pronto. Caso: {case_subject} - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'تم استلام الحالة',
                        'content' => 'تم استلام حالتك. شكرا لك! سنحلها قريبا. الحالة: {case_subject} - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Sag Modtaget',
                        'content' => 'Din sag er modtaget. Tak! Vi vil løse det snart. Sag: {case_subject} - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Fall Erhalten',
                        'content' => 'Ihr Fall wurde erhalten. Danke! Wir werden es bald lösen. Fall: {case_subject} - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Cas Reçu',
                        'content' => 'Votre cas est reçu. Merci! Nous le résoudrons bientôt. Cas: {case_subject} - {company_name}'
                    ],
                    'he' => [
                        'title' => 'המקרה התקבל',
                        'content' => 'המקרה שלך התקבל. תודה! אנחנו נפתור את זה בקרוב. מקרה: {case_subject} - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Caso Ricevuto',
                        'content' => 'Il tuo caso è stato ricevuto. Grazie! Lo risolveremo presto. Caso: {case_subject} - {company_name}'
                    ],
                    'ja' => [
                        'title' => 'ケース受信',
                        'content' => 'あなたのケースを受信しました。ありがとうございます！すぐに解決します。ケース: {case_subject} - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Case Ontvangen',
                        'content' => 'Je case is ontvangen. Dank je! We zullen het binnenkort oplossen. Case: {case_subject} - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Sprawa Otrzymana',
                        'content' => 'Twoja sprawa została otrzymana. Dziękujemy! Rozwiążemy to wkrótce. Sprawa: {case_subject} - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Caso Recebido',
                        'content' => 'Seu caso foi recebido. Obrigado! Resolveremos em breve. Caso: {case_subject} - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Caso Recebido',
                        'content' => 'Seu caso foi recebido. Obrigado! Resolveremos em breve. Caso: {case_subject} - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Обращение Получено',
                        'content' => 'Ваше обращение получено. Спасибо! Мы решим это в ближайшее время. Обращение: {case_subject} - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Vaka Alındı',
                        'content' => 'Vakanız alındı. Teşekkürler! Yakında çözeceğiz. Vaka: {case_subject} - {company_name}'
                    ],
                    'zh' => [
                        'title' => '案例已收到',
                        'content' => '您的案例已收到。谢谢！我们将很快解决。案例: {case_subject} - {company_name}'
                    ]
                ]
            ],
            // Meeting Create
            [
                'name' => 'Meeting Create',
                'translations' => [
                    'en' => [
                        'title' => 'New Meeting Scheduled',
                        'content' => 'Meeting scheduled: {meeting_subject} on {meeting_date} at {meeting_time}. Total Attendees: {attendee_count}. Be prepared! - {company_name}'
                    ],
                    'es' => [
                        'title' => 'Nueva Reunión Programada',
                        'content' => 'Reunión programada: {meeting_subject} el {meeting_date} a las {meeting_time}. Total de Asistentes: {attendee_count}. ¡Prepárate! - {company_name}'
                    ],
                    'ar' => [
                        'title' => 'اجتماع جديد مجدول',
                        'content' => 'اجتماع مجدول: {meeting_subject} في {meeting_date} في {meeting_time}. إجمالي الحضور: {attendee_count}. كن مستعدا! - {company_name}'
                    ],
                    'da' => [
                        'title' => 'Nyt Møde Planlagt',
                        'content' => 'Møde planlagt: {meeting_subject} den {meeting_date} kl. {meeting_time}. Samlede deltagere: {attendee_count}. Vær forberedt! - {company_name}'
                    ],
                    'de' => [
                        'title' => 'Neues Meeting Geplant',
                        'content' => 'Meeting geplant: {meeting_subject} am {meeting_date} um {meeting_time}. Gesamte Teilnehmer: {attendee_count}. Seien Sie vorbereitet! - {company_name}'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle Réunion Programmée',
                        'content' => 'Réunion programmée: {meeting_subject} le {meeting_date} à {meeting_time}. Total des participants: {attendee_count}. Soyez prêt! - {company_name}'
                    ],
                    'he' => [
                        'title' => 'פגישה חדשה נקבעה',
                        'content' => 'פגישה נקבעה: {meeting_subject} ב{meeting_date} ב{meeting_time}. סך המשתתפים: {attendee_count}. היו מוכנים! - {company_name}'
                    ],
                    'it' => [
                        'title' => 'Nuovo Meeting Programmato',
                        'content' => 'Meeting programmato: {meeting_subject} il {meeting_date} alle {meeting_time}. Totale partecipanti: {attendee_count}. Preparatevi! - {company_name}'
                    ],
                    'ja' => [
                        'title' => '新しい会議がスケジュール',
                        'content' => '会議がスケジュールされました: {meeting_subject} {meeting_date} {meeting_time}。参加者総数: {attendee_count}。準備してください！ - {company_name}'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe Vergadering Gepland',
                        'content' => 'Vergadering gepland: {meeting_subject} op {meeting_date} om {meeting_time}. Totaal deelnemers: {attendee_count}. Wees voorbereid! - {company_name}'
                    ],
                    'pl' => [
                        'title' => 'Nowe Spotkanie Zaplanowane',
                        'content' => 'Spotkanie zaplanowane: {meeting_subject} dnia {meeting_date} o {meeting_time}. Łączna liczba uczestników: {attendee_count}. Bądź przygotowany! - {company_name}'
                    ],
                    'pt' => [
                        'title' => 'Nova Reunião Agendada',
                        'content' => 'Reunião agendada: {meeting_subject} em {meeting_date} às {meeting_time}. Total de Participantes: {attendee_count}. Esteja preparado! - {company_name}'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova Reunião Agendada',
                        'content' => 'Reunião agendada: {meeting_subject} em {meeting_date} às {meeting_time}. Total de Participantes: {attendee_count}. Esteja preparado! - {company_name}'
                    ],
                    'ru' => [
                        'title' => 'Новая Встреча Запланирована',
                        'content' => 'Встреча запланирована: {meeting_subject} {meeting_date} в {meeting_time}. Всего участников: {attendee_count}. Будьте готовы! - {company_name}'
                    ],
                    'tr' => [
                        'title' => 'Yeni Toplantı Planlandı',
                        'content' => 'Toplantı planlandı: {meeting_subject} {meeting_date} tarihinde {meeting_time} saatinde. Toplam Katılımcı: {attendee_count}. Hazır olun! - {company_name}'
                    ],
                    'zh' => [
                        'title' => '新会议已安排',
                        'content' => '会议已安排: {meeting_subject} 于{meeting_date} {meeting_time}。参与者总数: {attendee_count}。请做好准备！ - {company_name}'
                    ]
                ]
            ],
        ];

        // Get all companies
        $companies = \App\Models\User::where('type', 'company')->get();
        function addNotificationTemplates($templates, $companies, $langCodes, $notificationType)
        {
            foreach ($templates as $templateData) {
                // Create global template (once)
                $template = NotificationTemplate::firstOrCreate(
                    [
                        'name' => $templateData['name'],
                        'type' => $notificationType
                    ]
                );

                // Create content for each company
                foreach ($companies as $company) {
                    foreach ($langCodes as $langCode) {
                        $existingContent = NotificationTemplateLang::where('parent_id', $template->id)
                            ->where('lang', $langCode)
                            ->where('created_by', $company->id)
                            ->first();

                        if ($existingContent) {
                            continue;
                        }

                        $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                        NotificationTemplateLang::create([
                            'parent_id' => $template->id,
                            'lang' => $langCode,
                            'title' => $translation['title'],
                            'content' => $translation['content'],
                            'created_by' => $company->id
                        ]);
                    }
                }
                // Create content for global template
                foreach ($langCodes as $langCode) {
                    $existingContent = NotificationTemplateLang::where('parent_id', $template->id)
                        ->where('lang', $langCode)
                        ->where('created_by', 1)
                        ->first();

                    if ($existingContent) {
                        continue;
                    }

                    $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                    NotificationTemplateLang::create([
                        'parent_id' => $template->id,
                        'lang' => $langCode,
                        'title' => $translation['title'],
                        'content' => $translation['content'],
                        'created_by' => 1
                    ]);
                }
            }
        }

        addNotificationTemplates($templates, $companies, $langCodes, 'twilio');
        addNotificationTemplates($templates, $companies, $langCodes, 'slack');
    }
}
