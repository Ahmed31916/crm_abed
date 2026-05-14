<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\UserEmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();

        $templates = [
            // User Created
            [
                'name' => 'User Created',
                'from' => 'Support Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Welcome to our platform - {user_name}',
                        'content' => '<p>Hello {user_name},</p><p>Your account has been successfully created.</p><p><strong>Login Details:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Password: {user_password}</li><li>Account Type: {user_type}</li></ul><p>Please keep this information secure.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Bienvenido a nuestra plataforma - {user_name}',
                        'content' => '<p>Hola {user_name},</p><p>Su cuenta ha sido creada exitosamente.</p><p><strong>Detalles de acceso:</strong></p><ul><li>Sitio web: {app_url}</li><li>Email: {user_email}</li><li>Contraseña: {user_password}</li><li>Tipo de cuenta: {user_type}</li></ul><p>Por favor mantenga esta información segura.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'مرحباً بك في منصتنا - {user_name}',
                        'content' => '<p>مرحباً {user_name}،</p><p>تم إنشاء حسابك بنجاح.</p><p><strong>تفاصيل تسجيل الدخول:</strong></p><ul><li>الموقع: {app_url}</li><li>البريد الإلكتروني: {user_email}</li><li>كلمة المرور: {user_password}</li><li>نوع الحساب: {user_type}</li></ul><p>يرجى الاحتفاظ بهذه المعلومات آمنة.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Velkommen til vores platform - {user_name}',
                        'content' => '<p>Hej {user_name},</p><p>Din konto er blevet oprettet med succes.</p><p><strong>Login detaljer:</strong></p><ul><li>Hjemmeside: {app_url}</li><li>Email: {user_email}</li><li>Adgangskode: {user_password}</li><li>Kontotype: {user_type}</li></ul><p>Hold venligst disse oplysninger sikre.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Willkommen auf unserer Plattform - {user_name}',
                        'content' => '<p>Hallo {user_name},</p><p>Ihr Konto wurde erfolgreich erstellt.</p><p><strong>Anmeldedaten:</strong></p><ul><li>Website: {app_url}</li><li>E-Mail: {user_email}</li><li>Passwort: {user_password}</li><li>Kontotyp: {user_type}</li></ul><p>Bitte bewahren Sie diese Informationen sicher auf.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Bienvenue sur notre plateforme - {user_name}',
                        'content' => '<p>Bonjour {user_name},</p><p>Votre compte a été créé avec succès.</p><p><strong>Détails de connexion:</strong></p><ul><li>Site web: {app_url}</li><li>Email: {user_email}</li><li>Mot de passe: {user_password}</li><li>Type de compte: {user_type}</li></ul><p>Veuillez garder ces informations en sécurité.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'ברוכים הבאים לפלטפורמה שלנו - {user_name}',
                        'content' => '<p>שלום {user_name},</p><p>החשבון שלך נוצר בהצלחה.</p><p><strong>פרטי התחברות:</strong></p><ul><li>אתר: {app_url}</li><li>אימייל: {user_email}</li><li>סיסמה: {user_password}</li><li>סוג חשבון: {user_type}</li></ul><p>אנא שמרו על המידע הזה בבטחה.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Benvenuto sulla nostra piattaforma - {user_name}',
                        'content' => '<p>Ciao {user_name},</p><p>Il tuo account è stato creato con successo.</p><p><strong>Dettagli di accesso:</strong></p><ul><li>Sito web: {app_url}</li><li>Email: {user_email}</li><li>Password: {user_password}</li><li>Tipo di account: {user_type}</li></ul><p>Si prega di mantenere queste informazioni al sicuro.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => 'プラットフォームへようこそ - {user_name}',
                        'content' => '<p>こんにちは {user_name}さん、</p><p>アカウントが正常に作成されました。</p><p><strong>ログイン詳細:</strong></p><ul><li>ウェブサイト: {app_url}</li><li>メール: {user_email}</li><li>パスワード: {user_password}</li><li>アカウントタイプ: {user_type}</li></ul><p>この情報を安全に保管してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Welkom op ons platform - {user_name}',
                        'content' => '<p>Hallo {user_name},</p><p>Uw account is succesvol aangemaakt.</p><p><strong>Inloggegevens:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Wachtwoord: {user_password}</li><li>Accounttype: {user_type}</li></ul><p>Houd deze informatie veilig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Witamy na naszej platformie - {user_name}',
                        'content' => '<p>Witaj {user_name},</p><p>Twoje konto zostało pomyślnie utworzone.</p><p><strong>Szczegóły logowania:</strong></p><ul><li>Strona internetowa: {app_url}</li><li>Email: {user_email}</li><li>Hasło: {user_password}</li><li>Typ konta: {user_type}</li></ul><p>Prosimy o bezpieczne przechowywanie tych informacji.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Bem-vindo à nossa plataforma - {user_name}',
                        'content' => '<p>Olá {user_name},</p><p>A sua conta foi criada com sucesso.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Palavra-passe: {user_password}</li><li>Tipo de conta: {user_type}</li></ul><p>Por favor, mantenha esta informação segura.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Bem-vindo à nossa plataforma - {user_name}',
                        'content' => '<p>Olá {user_name},</p><p>Sua conta foi criada com sucesso.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Senha: {user_password}</li><li>Tipo de conta: {user_type}</li></ul><p>Por favor, mantenha essas informações seguras.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Добро пожаловать на нашу платформу - {user_name}',
                        'content' => '<p>Привет {user_name},</p><p>Ваш аккаунт был успешно создан.</p><p><strong>Данные для входа:</strong></p><ul><li>Веб-сайт: {app_url}</li><li>Email: {user_email}</li><li>Пароль: {user_password}</li><li>Тип аккаунта: {user_type}</li></ul><p>Пожалуйста, храните эту информацию в безопасности.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Platformumuza hoş geldiniz - {user_name}',
                        'content' => '<p>Merhaba {user_name},</p><p>Hesabınız başarıyla oluşturuldu.</p><p><strong>Giriş Detayları:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Şifre: {user_password}</li><li>Hesap Türü: {user_type}</li></ul><p>Lütfen bu bilgileri güvenli tutun.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '欢迎来到我们的平台 - {user_name}',
                        'content' => '<p>你好 {user_name}，</p><p>您的账户已成功创建。</p><p><strong>登录详情：</strong></p><ul><li>网站：{app_url}</li><li>邮箱：{user_email}</li><li>密码：{user_password}</li><li>账户类型：{user_type}</li></ul><p>请妥善保管这些信息。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Lead Assigned
            [
                'name' => 'Lead Assigned',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Lead Assigned to You - {lead_name}',
                        'content' => '<p>Hello {assigned_user_name},</p><p>A new lead has been assigned to you. Please review the details below and follow up accordingly.</p><p><strong>Lead Details:</strong></p><ul><li>Name: {lead_name}</li><li>Email: {lead_email}</li><li>Phone: {lead_phone}</li><li>Company: {lead_company}</li></ul><p>Please contact this lead as soon as possible to maximize conversion opportunities.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nuevo Lead Asignado - {lead_name}',
                        'content' => '<p>Hola {assigned_user_name},</p><p>Se le ha asignado un nuevo lead. Por favor revise los detalles a continuación y haga el seguimiento correspondiente.</p><p><strong>Detalles del Lead:</strong></p><ul><li>Nombre: {lead_name}</li><li>Email: {lead_email}</li><li>Teléfono: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Por favor contacte a este lead lo antes posible para maximizar las oportunidades de conversión.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم تعيين عميل محتمل جديد - {lead_name}',
                        'content' => '<p>مرحباً {assigned_user_name}،</p><p>تم تعيين عميل محتمل جديد لك. يرجى مراجعة التفاصيل أدناه والمتابعة وفقاً لذلك.</p><p><strong>تفاصيل العميل المحتمل:</strong></p><ul><li>الاسم: {lead_name}</li><li>البريد الإلكتروني: {lead_email}</li><li>الهاتف: {lead_phone}</li><li>الشركة: {lead_company}</li></ul><p>يرجى التواصل مع هذا العميل المحتمل في أقرب وقت ممكن لتعظيم فرص التحويل.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Nyt Lead Tildelt - {lead_name}',
                        'content' => '<p>Hej {assigned_user_name},</p><p>Et nyt lead er blevet tildelt til dig. Gennemgå venligst detaljerne nedenfor og følg op i overensstemmelse hermed.</p><p><strong>Lead Detaljer:</strong></p><ul><li>Navn: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Virksomhed: {lead_company}</li></ul><p>Kontakt venligst dette lead så hurtigt som muligt for at maksimere konverteringsmuligheder.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neuer Lead zugewiesen - {lead_name}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>Ein neuer Lead wurde Ihnen zugewiesen. Bitte überprüfen Sie die Details unten und folgen Sie entsprechend nach.</p><p><strong>Lead Details:</strong></p><ul><li>Name: {lead_name}</li><li>E-Mail: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Unternehmen: {lead_company}</li></ul><p>Bitte kontaktieren Sie diesen Lead so schnell wie möglich, um die Konvertierungsmöglichkeiten zu maximieren.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Lead Assigné - {lead_name}',
                        'content' => '<p>Bonjour {assigned_user_name},</p><p>Un nouveau lead vous a été assigné. Veuillez examiner les détails ci-dessous et faire le suivi en conséquence.</p><p><strong>Détails du Lead:</strong></p><ul><li>Nom: {lead_name}</li><li>Email: {lead_email}</li><li>Téléphone: {lead_phone}</li><li>Entreprise: {lead_company}</li></ul><p>Veuillez contacter ce lead dès que possible pour maximiser les opportunités de conversion.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'ליד חדש הוקצה - {lead_name}',
                        'content' => '<p>שלום {assigned_user_name},</p><p>ליד חדש הוקצה לך. אנא עיין בפרטים להלן ועשה מעקב בהתאם.</p><p><strong>פרטי הליד:</strong></p><ul><li>שם: {lead_name}</li><li>אימייל: {lead_email}</li><li>טלפון: {lead_phone}</li><li>חברה: {lead_company}</li></ul><p>אנא צור קשר עם הליד הזה בהקדם האפשרי כדי למקסם את הזדמנויות ההמרה.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Lead Assegnato - {lead_name}',
                        'content' => '<p>Ciao {assigned_user_name},</p><p>Un nuovo lead ti è stato assegnato. Si prega di rivedere i dettagli qui sotto e seguire di conseguenza.</p><p><strong>Dettagli Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefono: {lead_phone}</li><li>Azienda: {lead_company}</li></ul><p>Si prega di contattare questo lead il prima possibile per massimizzare le opportunità di conversione.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しいリードが割り当てられました - {lead_name}',
                        'content' => '<p>こんにちは {assigned_user_name}さん、</p><p>新しいリードがあなたに割り当てられました。以下の詳細を確認し、適切にフォローアップしてください。</p><p><strong>リード詳細:</strong></p><ul><li>名前: {lead_name}</li><li>メール: {lead_email}</li><li>電話: {lead_phone}</li><li>会社: {lead_company}</li></ul><p>コンバージョンの機会を最大化するために、できるだけ早くこのリードに連絡してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Lead Toegewezen - {lead_name}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>Een nieuwe lead is aan je toegewezen. Bekijk de details hieronder en volg dienovereenkomstig op.</p><p><strong>Lead Details:</strong></p><ul><li>Naam: {lead_name}</li><li>Email: {lead_email}</li><li>Telefoon: {lead_phone}</li><li>Bedrijf: {lead_company}</li></ul><p>Neem zo snel mogelijk contact op met deze lead om conversiekansen te maximaliseren.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowy Lead Przypisany - {lead_name}',
                        'content' => '<p>Witaj {assigned_user_name},</p><p>Nowy lead został Ci przypisany. Przejrzyj szczegóły poniżej i podejmij odpowiednie działania.</p><p><strong>Szczegóły Lead:</strong></p><ul><li>Nazwa: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Firma: {lead_company}</li></ul><p>Skontaktuj się z tym leadem jak najszybciej, aby zmaksymalizować możliwości konwersji.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Novo Lead Atribuído - {lead_name}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>Um novo lead foi atribuído a si. Por favor, reveja os detalhes abaixo e faça o seguimento em conformidade.</p><p><strong>Detalhes do Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefone: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Por favor, contacte este lead o mais rapidamente possível para maximizar as oportunidades de conversão.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Novo Lead Atribuído - {lead_name}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>Um novo lead foi atribuído a você. Por favor, revise os detalhes abaixo e faça o acompanhamento adequadamente.</p><p><strong>Detalhes do Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefone: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Por favor, entre em contato com este lead o mais rápido possível para maximizar as oportunidades de conversão.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый лид назначен - {lead_name}',
                        'content' => '<p>Привет {assigned_user_name},</p><p>Вам назначен новый лид. Пожалуйста, просмотрите детали ниже и проведите соответствующие действия.</p><p><strong>Детали лида:</strong></p><ul><li>Имя: {lead_name}</li><li>Email: {lead_email}</li><li>Телефон: {lead_phone}</li><li>Компания: {lead_company}</li></ul><p>Пожалуйста, свяжитесь с этим лидом как можно скорее, чтобы максимизировать возможности конверсии.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Müşteri Adayı Atandı - {lead_name}',
                        'content' => '<p>Merhaba {assigned_user_name},</p><p>Size yeni bir müşteri adayı atandı. Lütfen aşağıdaki detayları inceleyin ve buna göre takip edin.</p><p><strong>Müşteri Adayı Detayları:</strong></p><ul><li>Ad: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Şirket: {lead_company}</li></ul><p>Dönüşüm fırsatlarını maksimize etmek için lütfen bu müşteri adayıyla mümkün olan en kısa sürede iletişime geçin.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新的潜在客户已分配 - {lead_name}',
                        'content' => '<p>你好 {assigned_user_name}，</p><p>一个新的潜在客户已分配给您。请查看以下详细信息并相应进行跟进。</p><p><strong>潜在客户详情：</strong></p><ul><li>姓名：{lead_name}</li><li>邮箱：{lead_email}</li><li>电话：{lead_phone}</li><li>公司：{lead_company}</li></ul><p>请尽快与这个潜在客户联系，以最大化转化机会。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Lead Moved
            [
                'name' => 'Lead Moved',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Lead Moved - {lead_name}',
                        'content' => '<p>Hello {assigned_user_name},</p><p>The lead <strong>{lead_name}</strong> has been moved from <strong>{old_lead_stage}</strong> to <strong>{new_lead_stage}</strong>. Please review the details below and follow up accordingly.</p><p><strong>Lead Details:</strong></p><ul><li>Name: {lead_name}</li><li>Email: {lead_email}</li><li>Phone: {lead_phone}</li><li>Company: {lead_company}</li></ul><p>Thank you for your continued hard work and dedication.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Lead Movido - {lead_name}',
                        'content' => '<p>Hola {assigned_user_name},</p><p>El lead <strong>{lead_name}</strong> ha sido movido de <strong>{old_lead_stage}</strong> a <strong>{new_lead_stage}</strong>. Por favor, revisa los detalles a continuación y haz el seguimiento correspondiente.</p><p><strong>Detalles del Lead:</strong></p><ul><li>Nombre: {lead_name}</li><li>Correo electrónico: {lead_email}</li><li>Teléfono: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Gracias por tu continuo esfuerzo y dedicación.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم نقل العميل المحتمل - {lead_name}',
                        'content' => '<p>مرحباً {assigned_user_name}،</p><p>تم نقل العميل المحتمل <strong>{lead_name}</strong> من <strong>{old_lead_stage}</strong> إلى <strong>{new_lead_stage}</strong>. يرجى مراجعة التفاصيل أدناه والمتابعة وفقاً لذلك.</p><p><strong>تفاصيل العميل المحتمل:</strong></p><ul><li>الاسم: {lead_name}</li><li>البريد الإلكتروني: {lead_email}</li><li>الهاتف: {lead_phone}</li><li>الشركة: {lead_company}</li></ul><p>شكراً لك على جهدك وتفانيك المستمر.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Lead Flyttet - {lead_name}',
                        'content' => '<p>Hej {assigned_user_name},</p><p>Lead <strong>{lead_name}</strong> er blevet flyttet fra <strong>{old_lead_stage}</strong> til <strong>{new_lead_stage}</strong>. Gennemgå venligst detaljerne nedenfor og følg op i overensstemmelse hermed.</p><p><strong>Lead Detaljer:</strong></p><ul><li>Navn: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Virksomhed: {lead_company}</li></ul><p>Tak for dit fortsatte hårde arbejde og dedikation.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Lead verschoben - {lead_name}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>Der Lead <strong>{lead_name}</strong> wurde von <strong>{old_lead_stage}</strong> zu <strong>{new_lead_stage}</strong> verschoben. Bitte überprüfen Sie die Details unten und folgen Sie entsprechend nach.</p><p><strong>Lead Details:</strong></p><ul><li>Name: {lead_name}</li><li>E-Mail: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Unternehmen: {lead_company}</li></ul><p>Vielen Dank für Ihre kontinuierliche harte Arbeit und Ihr Engagement.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Lead Déplacé - {lead_name}',
                        'content' => '<p>Bonjour {assigned_user_name},</p><p>Le lead <strong>{lead_name}</strong> a été déplacé de <strong>{old_lead_stage}</strong> vers <strong>{new_lead_stage}</strong>. Veuillez examiner les détails ci-dessous et faire le suivi en conséquence.</p><p><strong>Détails du Lead:</strong></p><ul><li>Nom: {lead_name}</li><li>Email: {lead_email}</li><li>Téléphone: {lead_phone}</li><li>Entreprise: {lead_company}</li></ul><p>Merci pour votre travail acharné et votre dévouement continus.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'ליד הועבר - {lead_name}',
                        'content' => '<p>שלום {assigned_user_name},</p><p>הליד <strong>{lead_name}</strong> הועבר מ-<strong>{old_lead_stage}</strong> ל-<strong>{new_lead_stage}</strong>. אנא עיין בפרטים להלן ועשה מעקב בהתאם.</p><p><strong>פרטי הליד:</strong></p><ul><li>שם: {lead_name}</li><li>אימייל: {lead_email}</li><li>טלפון: {lead_phone}</li><li>חברה: {lead_company}</li></ul><p>תודה על העבודה הקשה והמסירות המתמשכת.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Lead Spostato - {lead_name}',
                        'content' => '<p>Ciao {assigned_user_name},</p><p>Il lead <strong>{lead_name}</strong> è stato spostato da <strong>{old_lead_stage}</strong> a <strong>{new_lead_stage}</strong>. Si prega di rivedere i dettagli qui sotto e seguire di conseguenza.</p><p><strong>Dettagli Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefono: {lead_phone}</li><li>Azienda: {lead_company}</li></ul><p>Grazie per il tuo continuo duro lavoro e dedizione.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => 'リードが移動されました - {lead_name}',
                        'content' => '<p>こんにちは {assigned_user_name}さん、</p><p>リード <strong>{lead_name}</strong> が <strong>{old_lead_stage}</strong> から <strong>{new_lead_stage}</strong> に移動されました。以下の詳細を確認し、適切にフォローアップしてください。</p><p><strong>リード詳細:</strong></p><ul><li>名前: {lead_name}</li><li>メール: {lead_email}</li><li>電話: {lead_phone}</li><li>会社: {lead_company}</li></ul><p>継続的な努力と献身に感謝します。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Lead Verplaatst - {lead_name}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>De lead <strong>{lead_name}</strong> is verplaatst van <strong>{old_lead_stage}</strong> naar <strong>{new_lead_stage}</strong>. Bekijk de details hieronder en volg dienovereenkomstig op.</p><p><strong>Lead Details:</strong></p><ul><li>Naam: {lead_name}</li><li>Email: {lead_email}</li><li>Telefoon: {lead_phone}</li><li>Bedrijf: {lead_company}</li></ul><p>Bedankt voor je voortdurende harde werk en toewijding.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Lead Przeniesiony - {lead_name}',
                        'content' => '<p>Witaj {assigned_user_name},</p><p>Lead <strong>{lead_name}</strong> został przeniesiony z <strong>{old_lead_stage}</strong> do <strong>{new_lead_stage}</strong>. Przejrzyj szczegóły poniżej i podejmij odpowiednie działania.</p><p><strong>Szczegóły Lead:</strong></p><ul><li>Nazwa: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Firma: {lead_company}</li></ul><p>Dziękuję za Twoją ciągłą ciężką pracę i zaangażowanie.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Lead Movido - {lead_name}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>O lead <strong>{lead_name}</strong> foi movido de <strong>{old_lead_stage}</strong> para <strong>{new_lead_stage}</strong>. Por favor, reveja os detalhes abaixo e faça o seguimento em conformidade.</p><p><strong>Detalhes do Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefone: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Obrigado pelo seu trabalho árduo e dedicação contínuos.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Lead Movido - {lead_name}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>O lead <strong>{lead_name}</strong> foi movido de <strong>{old_lead_stage}</strong> para <strong>{new_lead_stage}</strong>. Por favor, revise os detalhes abaixo e faça o acompanhamento adequadamente.</p><p><strong>Detalhes do Lead:</strong></p><ul><li>Nome: {lead_name}</li><li>Email: {lead_email}</li><li>Telefone: {lead_phone}</li><li>Empresa: {lead_company}</li></ul><p>Obrigado pelo seu trabalho árduo e dedicação contínuos.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Лид перемещен - {lead_name}',
                        'content' => '<p>Привет {assigned_user_name},</p><p>Лид <strong>{lead_name}</strong> был перемещен с <strong>{old_lead_stage}</strong> на <strong>{new_lead_stage}</strong>. Пожалуйста, просмотрите детали ниже и проведите соответствующие действия.</p><p><strong>Детали лида:</strong></p><ul><li>Имя: {lead_name}</li><li>Email: {lead_email}</li><li>Телефон: {lead_phone}</li><li>Компания: {lead_company}</li></ul><p>Спасибо за вашу постоянную усердную работу и преданность.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Müşteri Adayı Taşındı - {lead_name}',
                        'content' => '<p>Merhaba {assigned_user_name},</p><p>Müşteri adayı <strong>{lead_name}</strong>, <strong>{old_lead_stage}</strong> aşamasından <strong>{new_lead_stage}</strong> aşamasına taşındı. Lütfen aşağıdaki detayları inceleyin ve buna göre takip edin.</p><p><strong>Müşteri Adayı Detayları:</strong></p><ul><li>Ad: {lead_name}</li><li>Email: {lead_email}</li><li>Telefon: {lead_phone}</li><li>Şirket: {lead_company}</li></ul><p>Sürekli sert çalışmanız ve bağlılığınız için teşekkür ederiz.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '潜在客户已移动 - {lead_name}',
                        'content' => '<p>你好 {assigned_user_name}，</p><p>潜在客户 <strong>{lead_name}</strong> 已从 <strong>{old_lead_stage}</strong> 移动到 <strong>{new_lead_stage}</strong>。请查看以下详细信息并相应进行跟进。</p><p><strong>潜在客户详情：</strong></p><ul><li>姓名：{lead_name}</li><li>邮箱：{lead_email}</li><li>电话：{lead_phone}</li><li>公司：{lead_company}</li></ul><p>感谢您的持续努力和奉献。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Quote Created
            [
                'name' => 'Quote Created',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Quote Created - {quote_name}',
                        'content' => '<p>Hello,</p><p>A new quote has been created. Please review the details below.</p><p><strong>Quote Details:</strong></p><ul><li>Quote Number: {quote_number}</li><li>Quote Name: {quote_name}</li><li>Account: {account_name}</li><li>Contact: {billing_contact_name}</li><li>Total Amount: {quote_total}</li><li>Valid Until: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Assigned Sales Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>If you have any questions about this quote, please contact the assigned representative.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Cotización Creada - {quote_name}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva cotización. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Cotización:</strong></p><ul><li>Número de Cotización: {quote_number}</li><li>Nombre de Cotización: {quote_name}</li><li>Cuenta: {account_name}</li><li>Contacto: {billing_contact_name}</li><li>Monto Total: {quote_total}</li><li>Válida Hasta: {quote_valid_until}</li><li>Estado: {quote_status}</li></ul><p><strong>Representante de Ventas Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si tiene alguna pregunta sobre esta cotización, contacte al representante asignado.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم إنشاء عرض أسعار جديد - {quote_name}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء عرض أسعار جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل عرض الأسعار:</strong></p><ul><li>رقم عرض الأسعار: {quote_number}</li><li>اسم عرض الأسعار: {quote_name}</li><li>الحساب: {account_name}</li><li>جهة الاتصال: {billing_contact_name}</li><li>المبلغ الإجمالي: {quote_total}</li><li>صالح حتى: {quote_valid_until}</li><li>الحالة: {quote_status}</li></ul><p><strong>مندوب المبيعات المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>إذا كان لديك أي أسئلة حول هذا العرض، يرجى التواصل مع الممثل المعين.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Nyt Tilbud Oprettet - {quote_name}',
                        'content' => '<p>Hej,</p><p>Et nyt tilbud er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Tilbud Detaljer:</strong></p><ul><li>Tilbudsnummer: {quote_number}</li><li>Tilbudsnavn: {quote_name}</li><li>Konto: {account_name}</li><li>Kontakt: {billing_contact_name}</li><li>Samlet beløb: {quote_total}</li><li>Gyldig indtil: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Tildelt Salgsrepræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Hvis du har spørgsmål om dette tilbud, kontakt venligst den tildelte repræsentant.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neues Angebot erstellt - {quote_name}',
                        'content' => '<p>Hallo,</p><p>Ein neues Angebot wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Angebot Details:</strong></p><ul><li>Angebotsnummer: {quote_number}</li><li>Angebotsname: {quote_name}</li><li>Konto: {account_name}</li><li>Kontakt: {billing_contact_name}</li><li>Gesamtbetrag: {quote_total}</li><li>Gültig bis: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Zugewiesener Vertriebsmitarbeiter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Wenn Sie Fragen zu diesem Angebot haben, kontaktieren Sie bitte den zugewiesenen Vertreter.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Devis Créé - {quote_name}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau devis a été créé. Veuillez examiner les détails ci-dessous.</p><p><strong>Détails du Devis:</strong></p><ul><li>Numéro de devis: {quote_number}</li><li>Nom du devis: {quote_name}</li><li>Compte: {account_name}</li><li>Contact: {billing_contact_name}</li><li>Montant total: {quote_total}</li><li>Valide jusqu\'au: {quote_valid_until}</li><li>Statut: {quote_status}</li></ul><p><strong>Représentant Commercial Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si vous avez des questions sur ce devis, veuillez contacter le représentant assigné.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הצעת מחיר חדשה נוצרה - {quote_name}',
                        'content' => '<p>שלום,</p><p>הצעת מחיר חדשה נוצרה. אנא עיין בפרטים להלן.</p><p><strong>פרטי הצעת המחיר:</strong></p><ul><li>מספר הצעת מחיר: {quote_number}</li><li>שם הצעת מחיר: {quote_name}</li><li>חשבון: {account_name}</li><li>איש קשר: {billing_contact_name}</li><li>סכום כולל: {quote_total}</li><li>תקף עד: {quote_valid_until}</li><li>סטטוס: {quote_status}</li></ul><p><strong>נציג מכירות מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אם יש לך שאלות על הצעת מחיר זו, אנא צור קשר עם הנציג המוקצה.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Preventivo Creato - {quote_name}',
                        'content' => '<p>Ciao,</p><p>Un nuovo preventivo è stato creato. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Preventivo:</strong></p><ul><li>Numero preventivo: {quote_number}</li><li>Nome preventivo: {quote_name}</li><li>Account: {account_name}</li><li>Contatto: {billing_contact_name}</li><li>Importo totale: {quote_total}</li><li>Valido fino al: {quote_valid_until}</li><li>Stato: {quote_status}</li></ul><p><strong>Rappresentante Vendite Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Per qualsiasi domanda su questo preventivo, contattare il rappresentante assegnato.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい見積もりが作成されました - {quote_name}',
                        'content' => '<p>こんにちは、</p><p>新しい見積もりが作成されました。以下の詳細をご確認ください。</p><p><strong>見積もり詳細:</strong></p><ul><li>見積もり番号: {quote_number}</li><li>見積もり名: {quote_name}</li><li>アカウント: {account_name}</li><li>連絡先: {billing_contact_name}</li><li>合計金額: {quote_total}</li><li>有効期限: {quote_valid_until}</li><li>ステータス: {quote_status}</li></ul><p><strong>担当営業担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>この見積もりについてご質問がございましたら、担当者までご連絡ください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Offerte Aangemaakt - {quote_name}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe offerte is aangemaakt. Bekijk de details hieronder.</p><p><strong>Offerte Details:</strong></p><ul><li>Offertenummer: {quote_number}</li><li>Offertenaam: {quote_name}</li><li>Account: {account_name}</li><li>Contact: {billing_contact_name}</li><li>Totaalbedrag: {quote_total}</li><li>Geldig tot: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Als je vragen hebt over deze offerte, neem contact op met de toegewezen vertegenwoordiger.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowa Oferta Utworzona - {quote_name}',
                        'content' => '<p>Witaj,</p><p>Nowa oferta została utworzona. Przejrzyj szczegóły poniżej.</p><p><strong>Szczegóły Oferty:</strong></p><ul><li>Numer oferty: {quote_number}</li><li>Nazwa oferty: {quote_name}</li><li>Konto: {account_name}</li><li>Kontakt: {billing_contact_name}</li><li>Łączna kwota: {quote_total}</li><li>Ważna do: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Przypisany Przedstawiciel Handlowy:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Jeśli masz pytania dotyczące tej oferty, skontaktuj się z przypisanym przedstawicielem.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Cotação Criada - {quote_name}',
                        'content' => '<p>Olá,</p><p>Uma nova cotação foi criada. Por favor, reveja os detalhes abaixo.</p><p><strong>Detalhes da Cotação:</strong></p><ul><li>Número da cotação: {quote_number}</li><li>Nome da cotação: {quote_name}</li><li>Conta: {account_name}</li><li>Contacto: {billing_contact_name}</li><li>Valor total: {quote_total}</li><li>Válida até: {quote_valid_until}</li><li>Estado: {quote_status}</li></ul><p><strong>Representante de Vendas Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Se tiver questões sobre esta cotação, contacte o representante atribuído.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Cotação Criada - {quote_name}',
                        'content' => '<p>Olá,</p><p>Uma nova cotação foi criada. Por favor, revise os detalhes abaixo.</p><p><strong>Detalhes da Cotação:</strong></p><ul><li>Número da cotação: {quote_number}</li><li>Nome da cotação: {quote_name}</li><li>Conta: {account_name}</li><li>Contato: {billing_contact_name}</li><li>Valor total: {quote_total}</li><li>Válida até: {quote_valid_until}</li><li>Status: {quote_status}</li></ul><p><strong>Representante de Vendas Designado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Se tiver dúvidas sobre esta cotação, entre em contato com o representante designado.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Создано новое предложение - {quote_name}',
                        'content' => '<p>Привет,</p><p>Создано новое предложение. Пожалуйста, просмотрите детали ниже.</p><p><strong>Детали предложения:</strong></p><ul><li>Номер предложения: {quote_number}</li><li>Название предложения: {quote_name}</li><li>Аккаунт: {account_name}</li><li>Контакт: {billing_contact_name}</li><li>Общая сумма: {quote_total}</li><li>Действительно до: {quote_valid_until}</li><li>Статус: {quote_status}</li></ul><p><strong>Назначенный представитель по продажам:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Если у вас есть вопросы по этому предложению, свяжитесь с назначенным представителем.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Teklif Oluşturuldu - {quote_name}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir teklif oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Teklif Detayları:</strong></p><ul><li>Teklif numarası: {quote_number}</li><li>Teklif adı: {quote_name}</li><li>Hesap: {account_name}</li><li>İletişim: {billing_contact_name}</li><li>Toplam tutar: {quote_total}</li><li>Geçerlilik tarihi: {quote_valid_until}</li><li>Durum: {quote_status}</li></ul><p><strong>Atanan Satış Temsilcisi:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bu teklifle ilgili sorularınız varsa lütfen atanan temsilciyle iletişime geçin.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新报价已创建 - {quote_name}',
                        'content' => '<p>你好，</p><p>已创建了新的报价。请查看以下详细信息。</p><p><strong>报价详情：</strong></p><ul><li>报价编号：{quote_number}</li><li>报价名称：{quote_name}</li><li>账户：{account_name}</li><li>联系人：{billing_contact_name}</li><li>总金额：{quote_total}</li><li>有效期至：{quote_valid_until}</li><li>状态：{quote_status}</li></ul><p><strong>指定销售代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>如果您对此报价有任何疑问，请联系指定的代表。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Quote Status Changed
            [
                'name' => 'Quote Status Changed',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Quote Status Updated - {quote_name}',
                        'content' => '<p>Hello,</p><p>The status of the quote has been updated from <strong>{old_quote_status}</strong> to <strong>{new_quote_status}</strong>.</p><p><strong>Quote Details:</strong></p><ul><li>Quote Number: {quote_number}</li><li>Quote Name: {quote_name}</li><li>Contact: {billing_contact_name}</li><li>Account: {account_name}</li><li>Total Amount: {quote_total}</li><li>Valid Until: {quote_valid_until}</li><li>Current Status: {new_quote_status}</li></ul><p><strong>Assigned Sales Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please contact the sales representative if you have any questions about this status change.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Estado de Cotización Actualizado - {quote_name}',
                        'content' => '<p>Hola,</p><p>El estado de la cotización ha sido actualizado de <strong>{old_quote_status}</strong> a <strong>{new_quote_status}</strong>.</p><p><strong>Detalles de la Cotización:</strong></p><ul><li>Número de Cotización: {quote_number}</li><li>Nombre de Cotización: {quote_name}</li><li>Contacto: {billing_contact_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {quote_total}</li><li>Válida Hasta: {quote_valid_until}</li><li>Estado Actual: {new_quote_status}</li></ul><p><strong>Representante de Ventas Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor contacte al representante de ventas si tiene alguna pregunta sobre este cambio de estado.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم تحديث حالة عرض الأسعار - {quote_name}',
                        'content' => '<p>مرحباً،</p><p>تم تحديث حالة عرض الأسعار من <strong>{old_quote_status}</strong> إلى <strong>{new_quote_status}</strong>.</p><p><strong>تفاصيل عرض الأسعار:</strong></p><ul><li>رقم عرض الأسعار: {quote_number}</li><li>اسم عرض الأسعار: {quote_name}</li><li>جهة الاتصال: {billing_contact_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {quote_total}</li><li>صالح حتى: {quote_valid_until}</li><li>الحالة الحالية: {new_quote_status}</li></ul><p><strong>مندوب المبيعات المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى التواصل مع مندوب المبيعات إذا كان لديك أي أسئلة حول هذا التغيير في الحالة.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Tilbudsstatus Opdateret - {quote_name}',
                        'content' => '<p>Hej,</p><p>Status på tilbuddet er blevet opdateret fra <strong>{old_quote_status}</strong> til <strong>{new_quote_status}</strong>.</p><p><strong>Tilbud Detaljer:</strong></p><ul><li>Tilbudsnummer: {quote_number}</li><li>Tilbudsnavn: {quote_name}</li><li>Kontakt: {billing_contact_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {quote_total}</li><li>Gyldig indtil: {quote_valid_until}</li><li>Nuværende status: {new_quote_status}</li></ul><p><strong>Tildelt Salgsrepræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Kontakt venligst salgsrepræsentanten, hvis du har spørgsmål om denne statusændring.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Angebotsstatus Aktualisiert - {quote_name}',
                        'content' => '<p>Hallo,</p><p>Der Status des Angebots wurde von <strong>{old_quote_status}</strong> auf <strong>{new_quote_status}</strong> aktualisiert.</p><p><strong>Angebot Details:</strong></p><ul><li>Angebotsnummer: {quote_number}</li><li>Angebotsname: {quote_name}</li><li>Kontakt: {billing_contact_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {quote_total}</li><li>Gültig bis: {quote_valid_until}</li><li>Aktueller Status: {new_quote_status}</li></ul><p><strong>Zugewiesener Vertriebsmitarbeiter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte kontaktieren Sie den Vertriebsmitarbeiter, wenn Sie Fragen zu dieser Statusänderung haben.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Statut du Devis Mis à Jour - {quote_name}',
                        'content' => '<p>Bonjour,</p><p>Le statut du devis a été mis à jour de <strong>{old_quote_status}</strong> vers <strong>{new_quote_status}</strong>.</p><p><strong>Détails du Devis:</strong></p><ul><li>Numéro de devis: {quote_number}</li><li>Nom du devis: {quote_name}</li><li>Contact: {billing_contact_name}</li><li>Compte: {account_name}</li><li>Montant total: {quote_total}</li><li>Valide jusqu\'au: {quote_valid_until}</li><li>Statut actuel: {new_quote_status}</li></ul><p><strong>Représentant Commercial Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez contacter le représentant commercial si vous avez des questions sur ce changement de statut.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'סטטוס הצעת המחיר עודכן - {quote_name}',
                        'content' => '<p>שלום,</p><p>סטטוס הצעת המחיר עודכן מ-<strong>{old_quote_status}</strong> ל-<strong>{new_quote_status}</strong>.</p><p><strong>פרטי הצעת המחיר:</strong></p><ul><li>מספר הצעת מחיר: {quote_number}</li><li>שם הצעת מחיר: {quote_name}</li><li>איש קשר: {billing_contact_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {quote_total}</li><li>תקף עד: {quote_valid_until}</li><li>סטטוס נוכחי: {new_quote_status}</li></ul><p><strong>נציג מכירות מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא צור קשר עם נציג המכירות אם יש לך שאלות על שינוי סטטוס זה.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Stato Preventivo Aggiornato - {quote_name}',
                        'content' => '<p>Ciao,</p><p>Lo stato del preventivo è stato aggiornato da <strong>{old_quote_status}</strong> a <strong>{new_quote_status}</strong>.</p><p><strong>Dettagli Preventivo:</strong></p><ul><li>Numero preventivo: {quote_number}</li><li>Nome preventivo: {quote_name}</li><li>Contatto: {billing_contact_name}</li><li>Account: {account_name}</li><li>Importo totale: {quote_total}</li><li>Valido fino al: {quote_valid_until}</li><li>Stato attuale: {new_quote_status}</li></ul><p><strong>Rappresentante Vendite Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di contattare il rappresentante vendite per qualsiasi domanda su questo cambio di stato.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '見積もりステータスが更新されました - {quote_name}',
                        'content' => '<p>こんにちは、</p><p>見積もりのステータスが <strong>{old_quote_status}</strong> から <strong>{new_quote_status}</strong> に更新されました。</p><p><strong>見積もり詳細:</strong></p><ul><li>見積もり番号: {quote_number}</li><li>見積もり名: {quote_name}</li><li>連絡先: {billing_contact_name}</li><li>アカウント: {account_name}</li><li>合計金額: {quote_total}</li><li>有効期限: {quote_valid_until}</li><li>現在のステータス: {new_quote_status}</li></ul><p><strong>担当営業担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>このステータス変更についてご質問がございましたら、営業担当者にお問い合わせください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Offerte Status Bijgewerkt - {quote_name}',
                        'content' => '<p>Hallo,</p><p>De status van de offerte is bijgewerkt van <strong>{old_quote_status}</strong> naar <strong>{new_quote_status}</strong>.</p><p><strong>Offerte Details:</strong></p><ul><li>Offertenummer: {quote_number}</li><li>Offertenaam: {quote_name}</li><li>Contact: {billing_contact_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {quote_total}</li><li>Geldig tot: {quote_valid_until}</li><li>Huidige status: {new_quote_status}</li></ul><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Neem contact op met de vertegenwoordiger als je vragen hebt over deze statuswijziging.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Status Oferty Zaktualizowany - {quote_name}',
                        'content' => '<p>Witaj,</p><p>Status oferty został zaktualizowany z <strong>{old_quote_status}</strong> na <strong>{new_quote_status}</strong>.</p><p><strong>Szczegóły Oferty:</strong></p><ul><li>Numer oferty: {quote_number}</li><li>Nazwa oferty: {quote_name}</li><li>Kontakt: {billing_contact_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {quote_total}</li><li>Ważna do: {quote_valid_until}</li><li>Aktualny status: {new_quote_status}</li></ul><p><strong>Przypisany Przedstawiciel Handlowy:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Skontaktuj się z przedstawicielem handlowym, jeśli masz pytania dotyczące tej zmiany statusu.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Estado da Cotação Atualizado - {quote_name}',
                        'content' => '<p>Olá,</p><p>O estado da cotação foi atualizado de <strong>{old_quote_status}</strong> para <strong>{new_quote_status}</strong>.</p><p><strong>Detalhes da Cotação:</strong></p><ul><li>Número da cotação: {quote_number}</li><li>Nome da cotação: {quote_name}</li><li>Contacto: {billing_contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {quote_total}</li><li>Válida até: {quote_valid_until}</li><li>Estado atual: {new_quote_status}</li></ul><p><strong>Representante de Vendas Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor, contacte o representante de vendas se tiver questões sobre esta mudança de estado.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Status da Cotação Atualizado - {quote_name}',
                        'content' => '<p>Olá,</p><p>O status da cotação foi atualizado de <strong>{old_quote_status}</strong> para <strong>{new_quote_status}</strong>.</p><p><strong>Detalhes da Cotação:</strong></p><ul><li>Número da cotação: {quote_number}</li><li>Nome da cotação: {quote_name}</li><li>Contato: {billing_contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {quote_total}</li><li>Válida até: {quote_valid_until}</li><li>Status atual: {new_quote_status}</li></ul><p><strong>Representante de Vendas Designado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor, entre em contato com o representante de vendas se tiver dúvidas sobre esta mudança de status.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Статус предложения обновлен - {quote_name}',
                        'content' => '<p>Привет,</p><p>Статус предложения был обновлен с <strong>{old_quote_status}</strong> на <strong>{new_quote_status}</strong>.</p><p><strong>Детали предложения:</strong></p><ul><li>Номер предложения: {quote_number}</li><li>Название предложения: {quote_name}</li><li>Контакт: {billing_contact_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {quote_total}</li><li>Действительно до: {quote_valid_until}</li><li>Текущий статус: {new_quote_status}</li></ul><p><strong>Назначенный представитель по продажам:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, свяжитесь с представителем по продажам, если у вас есть вопросы по этому изменению статуса.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Teklif Durumu Güncellendi - {quote_name}',
                        'content' => '<p>Merhaba,</p><p>Teklifin durumu <strong>{old_quote_status}</strong> durumundan <strong>{new_quote_status}</strong> durumuna güncellendi.</p><p><strong>Teklif Detayları:</strong></p><ul><li>Teklif numarası: {quote_number}</li><li>Teklif adı: {quote_name}</li><li>İletişim: {billing_contact_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {quote_total}</li><li>Geçerlilik tarihi: {quote_valid_until}</li><li>Mevcut durum: {new_quote_status}</li></ul><p><strong>Atanan Satış Temsilcisi:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bu durum değişikliği hakkında sorularınız varsa lütfen satış temsilcisiyle iletişime geçin.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '报价状态已更新 - {quote_name}',
                        'content' => '<p>你好，</p><p>报价状态已从 <strong>{old_quote_status}</strong> 更新为 <strong>{new_quote_status}</strong>。</p><p><strong>报价详情：</strong></p><ul><li>报价编号：{quote_number}</li><li>报价名称：{quote_name}</li><li>联系人：{billing_contact_name}</li><li>账户：{account_name}</li><li>总金额：{quote_total}</li><li>有效期至：{quote_valid_until}</li><li>当前状态：{new_quote_status}</li></ul><p><strong>指定销售代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>如果您对此状态变更有任何疑问，请联系销售代表。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Task Assigned
            [
                'name' => 'Task Assigned',
                'from' => 'Project Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Task Assigned to You - {task_title}',
                        'content' => '<p>Hello {assigned_user_name},</p><p>A new task has been assigned to you. Please review the details below and take appropriate action.</p><p><strong>Task Details:</strong></p><ul><li>Task Title: {task_title}</li><li>Project: {project_name}</li><li>Priority: {task_priority}</li><li>Due Date: {task_due_date}</li><li>Status: {task_status}</li><li>Estimated Hours: {task_estimated_hours}</li></ul><p><strong>Description:</strong></p><p>{task_description}</p><p><strong>Assigned By:</strong></p><p>{creator_name} - {creator_email}</p><p>Please log into the system to view full task details and update progress as needed.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Tarea Asignada - {task_title}',
                        'content' => '<p>Hola {assigned_user_name},</p><p>Se le ha asignado una nueva tarea. Por favor revise los detalles a continuación y tome las medidas apropiadas.</p><p><strong>Detalles de la Tarea:</strong></p><ul><li>Título de la Tarea: {task_title}</li><li>Proyecto: {project_name}</li><li>Prioridad: {task_priority}</li><li>Fecha de Vencimiento: {task_due_date}</li><li>Estado: {task_status}</li><li>Horas Estimadas: {task_estimated_hours}</li></ul><p><strong>Descripción:</strong></p><p>{task_description}</p><p><strong>Asignado Por:</strong></p><p>{creator_name} - {creator_email}</p><p>Por favor inicie sesión en el sistema para ver los detalles completos de la tarea y actualizar el progreso según sea necesario.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم تعيين مهمة جديدة لك - {task_title}',
                        'content' => '<p>مرحباً {assigned_user_name}،</p><p>تم تعيين مهمة جديدة لك. يرجى مراجعة التفاصيل أدناه واتخاذ الإجراء المناسب.</p><p><strong>تفاصيل المهمة:</strong></p><ul><li>عنوان المهمة: {task_title}</li><li>المشروع: {project_name}</li><li>الأولوية: {task_priority}</li><li>تاريخ الاستحقاق: {task_due_date}</li><li>الحالة: {task_status}</li><li>الساعات المقدرة: {task_estimated_hours}</li></ul><p><strong>الوصف:</strong></p><p>{task_description}</p><p><strong>معين بواسطة:</strong></p><p>{creator_name} - {creator_email}</p><p>يرجى تسجيل الدخول إلى النظام لعرض تفاصيل المهمة الكاملة وتحديث التقدم حسب الحاجة.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Opgave Tildelt - {task_title}',
                        'content' => '<p>Hej {assigned_user_name},</p><p>En ny opgave er blevet tildelt til dig. Gennemgå venligst detaljerne nedenfor og tag passende handling.</p><p><strong>Opgave Detaljer:</strong></p><ul><li>Opgave Titel: {task_title}</li><li>Projekt: {project_name}</li><li>Prioritet: {task_priority}</li><li>Forfaldsdato: {task_due_date}</li><li>Status: {task_status}</li><li>Estimerede Timer: {task_estimated_hours}</li></ul><p><strong>Beskrivelse:</strong></p><p>{task_description}</p><p><strong>Tildelt Af:</strong></p><p>{creator_name} - {creator_email}</p><p>Log venligst ind i systemet for at se fulde opgavedetaljer og opdatere fremskridt efter behov.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Aufgabe zugewiesen - {task_title}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>Eine neue Aufgabe wurde Ihnen zugewiesen. Bitte überprüfen Sie die Details unten und ergreifen Sie entsprechende Maßnahmen.</p><p><strong>Aufgaben Details:</strong></p><ul><li>Aufgaben Titel: {task_title}</li><li>Projekt: {project_name}</li><li>Priorität: {task_priority}</li><li>Fälligkeitsdatum: {task_due_date}</li><li>Status: {task_status}</li><li>Geschätzte Stunden: {task_estimated_hours}</li></ul><p><strong>Beschreibung:</strong></p><p>{task_description}</p><p><strong>Zugewiesen von:</strong></p><p>{creator_name} - {creator_email}</p><p>Bitte loggen Sie sich in das System ein, um vollständige Aufgabendetails anzuzeigen und den Fortschritt bei Bedarf zu aktualisieren.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouvelle Tâche Assignée - {task_title}',
                        'content' => '<p>Bonjour {assigned_user_name},</p><p>Une nouvelle tâche vous a été assignée. Veuillez examiner les détails ci-dessous et prendre les mesures appropriées.</p><p><strong>Détails de la Tâche:</strong></p><ul><li>Titre de la Tâche: {task_title}</li><li>Projet: {project_name}</li><li>Priorité: {task_priority}</li><li>Date d\'échéance: {task_due_date}</li><li>Statut: {task_status}</li><li>Heures Estimées: {task_estimated_hours}</li></ul><p><strong>Description:</strong></p><p>{task_description}</p><p><strong>Assigné Par:</strong></p><p>{creator_name} - {creator_email}</p><p>Veuillez vous connecter au système pour voir les détails complets de la tâche et mettre à jour les progrès si nécessaire.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'משימה חדשה הוקצתה - {task_title}',
                        'content' => '<p>שלום {assigned_user_name},</p><p>משימה חדשה הוקצתה לך. אנא עיין בפרטים להלן ונקט פעולה מתאימה.</p><p><strong>פרטי המשימה:</strong></p><ul><li>כותרת המשימה: {task_title}</li><li>פרויקט: {project_name}</li><li>עדיפות: {task_priority}</li><li>תאריך יעד: {task_due_date}</li><li>סטטוס: {task_status}</li><li>שעות מוערכות: {task_estimated_hours}</li></ul><p><strong>תיאור:</strong></p><p>{task_description}</p><p><strong>הוקצה על ידי:</strong></p><p>{creator_name} - {creator_email}</p><p>אנא התחבר למערכת כדי לראות פרטי משימה מלאים ולעדכן התקדמות לפי הצורך.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Compito Assegnato - {task_title}',
                        'content' => '<p>Ciao {assigned_user_name},</p><p>Un nuovo compito ti è stato assegnato. Si prega di rivedere i dettagli qui sotto e prendere le azioni appropriate.</p><p><strong>Dettagli del Compito:</strong></p><ul><li>Titolo del Compito: {task_title}</li><li>Progetto: {project_name}</li><li>Priorità: {task_priority}</li><li>Data di Scadenza: {task_due_date}</li><li>Stato: {task_status}</li><li>Ore Stimate: {task_estimated_hours}</li></ul><p><strong>Descrizione:</strong></p><p>{task_description}</p><p><strong>Assegnato Da:</strong></p><p>{creator_name} - {creator_email}</p><p>Si prega di accedere al sistema per visualizzare i dettagli completi del compito e aggiornare i progressi secondo necessità.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しいタスクが割り当てられました - {task_title}',
                        'content' => '<p>こんにちは {assigned_user_name}さん、</p><p>新しいタスクがあなたに割り当てられました。以下の詳細を確認し、適切なアクションを取ってください。</p><p><strong>タスク詳細:</strong></p><ul><li>タスクタイトル: {task_title}</li><li>プロジェクト: {project_name}</li><li>優先度: {task_priority}</li><li>期限: {task_due_date}</li><li>ステータス: {task_status}</li><li>予想時間: {task_estimated_hours}</li></ul><p><strong>説明:</strong></p><p>{task_description}</p><p><strong>割り当て者:</strong></p><p>{creator_name} - {creator_email}</p><p>システムにログインしてタスクの詳細を確認し、必要に応じて進捗を更新してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Taak Toegewezen - {task_title}',
                        'content' => '<p>Hallo {assigned_user_name},</p><p>Een nieuwe taak is aan je toegewezen. Bekijk de details hieronder en onderneem passende actie.</p><p><strong>Taak Details:</strong></p><ul><li>Taak Titel: {task_title}</li><li>Project: {project_name}</li><li>Prioriteit: {task_priority}</li><li>Vervaldatum: {task_due_date}</li><li>Status: {task_status}</li><li>Geschatte Uren: {task_estimated_hours}</li></ul><p><strong>Beschrijving:</strong></p><p>{task_description}</p><p><strong>Toegewezen Door:</strong></p><p>{creator_name} - {creator_email}</p><p>Log in op het systeem om volledige taakdetails te bekijken en voortgang bij te werken indien nodig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zadanie Przypisane - {task_title}',
                        'content' => '<p>Witaj {assigned_user_name},</p><p>Nowe zadanie zostało Ci przypisane. Przejrzyj szczegóły poniżej i podejmij odpowiednie działania.</p><p><strong>Szczegóły Zadania:</strong></p><ul><li>Tytuł Zadania: {task_title}</li><li>Projekt: {project_name}</li><li>Priorytet: {task_priority}</li><li>Termin: {task_due_date}</li><li>Status: {task_status}</li><li>Szacowane Godziny: {task_estimated_hours}</li></ul><p><strong>Opis:</strong></p><p>{task_description}</p><p><strong>Przypisane Przez:</strong></p><p>{creator_name} - {creator_email}</p><p>Zaloguj się do systemu, aby zobaczyć pełne szczegóły zadania i zaktualizować postęp w razie potrzeby.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Tarefa Atribuída - {task_title}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>Uma nova tarefa foi atribuída a si. Por favor, reveja os detalhes abaixo e tome a ação apropriada.</p><p><strong>Detalhes da Tarefa:</strong></p><ul><li>Título da Tarefa: {task_title}</li><li>Projeto: {project_name}</li><li>Prioridade: {task_priority}</li><li>Data de Vencimento: {task_due_date}</li><li>Estado: {task_status}</li><li>Horas Estimadas: {task_estimated_hours}</li></ul><p><strong>Descrição:</strong></p><p>{task_description}</p><p><strong>Atribuído Por:</strong></p><p>{creator_name} - {creator_email}</p><p>Por favor, faça login no sistema para ver os detalhes completos da tarefa e atualizar o progresso conforme necessário.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Tarefa Atribuída - {task_title}',
                        'content' => '<p>Olá {assigned_user_name},</p><p>Uma nova tarefa foi atribuída a você. Por favor, revise os detalhes abaixo e tome a ação apropriada.</p><p><strong>Detalhes da Tarefa:</strong></p><ul><li>Título da Tarefa: {task_title}</li><li>Projeto: {project_name}</li><li>Prioridade: {task_priority}</li><li>Data de Vencimento: {task_due_date}</li><li>Status: {task_status}</li><li>Horas Estimadas: {task_estimated_hours}</li></ul><p><strong>Descrição:</strong></p><p>{task_description}</p><p><strong>Atribuído Por:</strong></p><p>{creator_name} - {creator_email}</p><p>Por favor, faça login no sistema para ver os detalhes completos da tarefa e atualizar o progresso conforme necessário.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новая задача назначена - {task_title}',
                        'content' => '<p>Привет {assigned_user_name},</p><p>Вам назначена новая задача. Пожалуйста, просмотрите детали ниже и примите соответствующие меры.</p><p><strong>Детали задачи:</strong></p><ul><li>Название задачи: {task_title}</li><li>Проект: {project_name}</li><li>Приоритет: {task_priority}</li><li>Срок выполнения: {task_due_date}</li><li>Статус: {task_status}</li><li>Оценочные часы: {task_estimated_hours}</li></ul><p><strong>Описание:</strong></p><p>{task_description}</p><p><strong>Назначено:</strong></p><p>{creator_name} - {creator_email}</p><p>Пожалуйста, войдите в систему, чтобы посмотреть полные детали задачи и обновить прогресс по мере необходимости.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Görev Atandı - {task_title}',
                        'content' => '<p>Merhaba {assigned_user_name},</p><p>Size yeni bir görev atandı. Lütfen aşağıdaki detayları inceleyin ve uygun eylemi gerçekleştirin.</p><p><strong>Görev Detayları:</strong></p><ul><li>Görev Başlığı: {task_title}</li><li>Proje: {project_name}</li><li>Öncelik: {task_priority}</li><li>Teslim Tarihi: {task_due_date}</li><li>Durum: {task_status}</li><li>Tahmini Saatler: {task_estimated_hours}</li></ul><p><strong>Açıklama:</strong></p><p>{task_description}</p><p><strong>Atayan:</strong></p><p>{creator_name} - {creator_email}</p><p>Görevin tam detaylarını görmek ve gerektiğinde ilerlemeyi güncellemek için lütfen sisteme giriş yapın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新任务已分配 - {task_title}',
                        'content' => '<p>你好 {assigned_user_name}，</p><p>一个新任务已分配给您。请查看以下详细信息并采取适当行动。</p><p><strong>任务详情：</strong></p><ul><li>任务标题：{task_title}</li><li>项目：{project_name}</li><li>优先级：{task_priority}</li><li>截止日期：{task_due_date}</li><li>状态：{task_status}</li><li>预估小时：{task_estimated_hours}</li></ul><p><strong>描述：</strong></p><p>{task_description}</p><p><strong>分配者：</strong></p><p>{creator_name} - {creator_email}</p><p>请登录系统查看完整的任务详情并根据需要更新进度。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Meeting Invitation
            [
                'name' => 'Meeting Invitation',
                'from' => 'Meeting Organizer',
                'translations' => [
                    'en' => [
                        'subject' => 'Meeting Invitation: {meeting_title}',
                        'content' => '<p>Hello {attendee_name},</p><p>You are invited to attend the following meeting:</p><p><strong>Meeting Details:</strong></p><ul><li>Title: {meeting_title}</li><li>Date: {meeting_date}</li><li>Time: {meeting_start_time} - {meeting_end_time}</li><li>Location: {meeting_location}</li></ul><p><strong>Description:</strong></p><p>{meeting_description}</p><p>Please confirm your attendance and add this meeting to your calendar.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Invitación a Reunión: {meeting_title}',
                        'content' => '<p>Hola {attendee_name},</p><p>Está invitado a asistir a la siguiente reunión:</p><p><strong>Detalles de la Reunión:</strong></p><ul><li>Título: {meeting_title}</li><li>Fecha: {meeting_date}</li><li>Hora: {meeting_start_time} - {meeting_end_time}</li><li>Ubicación: {meeting_location}</li></ul><p><strong>Descripción:</strong></p><p>{meeting_description}</p><p>Por favor confirme su asistencia y agregue esta reunión a su calendario.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'دعوة لاجتماع: {meeting_title}',
                        'content' => '<p>مرحباً {attendee_name}،</p><p>أنت مدعو لحضور الاجتماع التالي:</p><p><strong>تفاصيل الاجتماع:</strong></p><ul><li>العنوان: {meeting_title}</li><li>التاريخ: {meeting_date}</li><li>الوقت: {meeting_start_time} - {meeting_end_time}</li><li>الموقع: {meeting_location}</li></ul><p><strong>الوصف:</strong></p><p>{meeting_description}</p><p>يرجى تأكيد حضورك وإضافة هذا الاجتماع إلى تقويمك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Mødeinvitation: {meeting_title}',
                        'content' => '<p>Hej {attendee_name},</p><p>Du er inviteret til at deltage i følgende møde:</p><p><strong>Møde Detaljer:</strong></p><ul><li>Titel: {meeting_title}</li><li>Dato: {meeting_date}</li><li>Tid: {meeting_start_time} - {meeting_end_time}</li><li>Sted: {meeting_location}</li></ul><p><strong>Beskrivelse:</strong></p><p>{meeting_description}</p><p>Bekræft venligst din deltagelse og tilføj dette møde til din kalender.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Meeting-Einladung: {meeting_title}',
                        'content' => '<p>Hallo {attendee_name},</p><p>Sie sind eingeladen, an folgendem Meeting teilzunehmen:</p><p><strong>Meeting Details:</strong></p><ul><li>Titel: {meeting_title}</li><li>Datum: {meeting_date}</li><li>Zeit: {meeting_start_time} - {meeting_end_time}</li><li>Ort: {meeting_location}</li></ul><p><strong>Beschreibung:</strong></p><p>{meeting_description}</p><p>Bitte bestätigen Sie Ihre Teilnahme und fügen Sie dieses Meeting zu Ihrem Kalender hinzu.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Invitation à la Réunion: {meeting_title}',
                        'content' => '<p>Bonjour {attendee_name},</p><p>Vous êtes invité à assister à la réunion suivante:</p><p><strong>Détails de la Réunion:</strong></p><ul><li>Titre: {meeting_title}</li><li>Date: {meeting_date}</li><li>Heure: {meeting_start_time} - {meeting_end_time}</li><li>Lieu: {meeting_location}</li></ul><p><strong>Description:</strong></p><p>{meeting_description}</p><p>Veuillez confirmer votre présence et ajouter cette réunion à votre calendrier.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנה לפגישה: {meeting_title}',
                        'content' => '<p>שלום {attendee_name},</p><p>אתה מוזמן להשתתף בפגישה הבאה:</p><p><strong>פרטי הפגישה:</strong></p><ul><li>כותרת: {meeting_title}</li><li>תאריך: {meeting_date}</li><li>שעה: {meeting_start_time} - {meeting_end_time}</li><li>מיקום: {meeting_location}</li></ul><p><strong>תיאור:</strong></p><p>{meeting_description}</p><p>אנא אשר את השתתפותך והוסף את הפגישה ליומן שלך.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Invito alla Riunione: {meeting_title}',
                        'content' => '<p>Ciao {attendee_name},</p><p>Sei invitato a partecipare alla seguente riunione:</p><p><strong>Dettagli della Riunione:</strong></p><ul><li>Titolo: {meeting_title}</li><li>Data: {meeting_date}</li><li>Ora: {meeting_start_time} - {meeting_end_time}</li><li>Luogo: {meeting_location}</li></ul><p><strong>Descrizione:</strong></p><p>{meeting_description}</p><p>Si prega di confermare la partecipazione e aggiungere questa riunione al calendario.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '会議のご招待: {meeting_title}',
                        'content' => '<p>こんにちは {attendee_name}さん、</p><p>以下の会議にご参加いただきますようご招待いたします:</p><p><strong>会議詳細:</strong></p><ul><li>タイトル: {meeting_title}</li><li>日付: {meeting_date}</li><li>時間: {meeting_start_time} - {meeting_end_time}</li><li>場所: {meeting_location}</li></ul><p><strong>説明:</strong></p><p>{meeting_description}</p><p>参加の確認とカレンダーへの登録をお願いいたします。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Vergaderuitnodiging: {meeting_title}',
                        'content' => '<p>Hallo {attendee_name},</p><p>Je bent uitgenodigd voor de volgende vergadering:</p><p><strong>Vergader Details:</strong></p><ul><li>Titel: {meeting_title}</li><li>Datum: {meeting_date}</li><li>Tijd: {meeting_start_time} - {meeting_end_time}</li><li>Locatie: {meeting_location}</li></ul><p><strong>Beschrijving:</strong></p><p>{meeting_description}</p><p>Bevestig je aanwezigheid en voeg deze vergadering toe aan je agenda.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Zaproszenie na Spotkanie: {meeting_title}',
                        'content' => '<p>Witaj {attendee_name},</p><p>Jesteś zaproszony na następujące spotkanie:</p><p><strong>Szczegóły Spotkania:</strong></p><ul><li>Tytuł: {meeting_title}</li><li>Data: {meeting_date}</li><li>Czas: {meeting_start_time} - {meeting_end_time}</li><li>Miejsce: {meeting_location}</li></ul><p><strong>Opis:</strong></p><p>{meeting_description}</p><p>Potwierdź swoją obecność i dodaj to spotkanie do swojego kalendarza.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Convite para Reunião: {meeting_title}',
                        'content' => '<p>Olá {attendee_name},</p><p>Está convidado a participar na seguinte reunião:</p><p><strong>Detalhes da Reunião:</strong></p><ul><li>Título: {meeting_title}</li><li>Data: {meeting_date}</li><li>Hora: {meeting_start_time} - {meeting_end_time}</li><li>Local: {meeting_location}</li></ul><p><strong>Descrição:</strong></p><p>{meeting_description}</p><p>Por favor, confirme a sua presença e adicione esta reunião ao seu calendário.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Convite para Reunião: {meeting_title}',
                        'content' => '<p>Olá {attendee_name},</p><p>Você está convidado a participar da seguinte reunião:</p><p><strong>Detalhes da Reunião:</strong></p><ul><li>Título: {meeting_title}</li><li>Data: {meeting_date}</li><li>Horário: {meeting_start_time} - {meeting_end_time}</li><li>Local: {meeting_location}</li></ul><p><strong>Descrição:</strong></p><p>{meeting_description}</p><p>Por favor, confirme sua presença e adicione esta reunião ao seu calendário.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Приглашение на собрание: {meeting_title}',
                        'content' => '<p>Привет {attendee_name},</p><p>Вы приглашены на следующее собрание:</p><p><strong>Детали собрания:</strong></p><ul><li>Название: {meeting_title}</li><li>Дата: {meeting_date}</li><li>Время: {meeting_start_time} - {meeting_end_time}</li><li>Место: {meeting_location}</li></ul><p><strong>Описание:</strong></p><p>{meeting_description}</p><p>Пожалуйста, подтвердите свое участие и добавьте это собрание в свой календарь.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Toplantı Davetiyesi: {meeting_title}',
                        'content' => '<p>Merhaba {attendee_name},</p><p>Aşağıdaki toplantıya davetlisiniz:</p><p><strong>Toplantı Detayları:</strong></p><ul><li>Başlık: {meeting_title}</li><li>Tarih: {meeting_date}</li><li>Saat: {meeting_start_time} - {meeting_end_time}</li><li>Yer: {meeting_location}</li></ul><p><strong>Açıklama:</strong></p><p>{meeting_description}</p><p>Lütfen katılımınızı onaylayın ve bu toplantıyı takviminize ekleyin.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '会议邀请: {meeting_title}',
                        'content' => '<p>你好 {attendee_name}，</p><p>邀请您参加以下会议:</p><p><strong>会议详情：</strong></p><ul><li>标题：{meeting_title}</li><li>日期：{meeting_date}</li><li>时间：{meeting_start_time} - {meeting_end_time}</li><li>地点：{meeting_location}</li></ul><p><strong>描述：</strong></p><p>{meeting_description}</p><p>请确认您的参会并将此会议添加到您的日历中。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Case Created
            [
                'name' => 'Case Created',
                'from' => 'Support Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Support Case - {case_subject}',
                        'content' => '<p>Hello,</p><p>A new support case has been created. Please review the details below and take the necessary actions.</p><p><strong>Case Details:</strong></p><ul><li>Subject: {case_subject}</li><li>Contact: {contact_name}</li><li>Assigned To: {assigned_user_name}</li><li>Priority: {case_priority}</li><li>Status: {case_status}</li><li>Created Date: {case_created_date}</li></ul><p><strong>Description:</strong></p><p>{case_description}</p><p><strong>Assigned Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Thank you for your support.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nuevo Caso de Soporte - {case_subject}',
                        'content' => '<p>Hola,</p><p>Se ha creado un nuevo caso de soporte. Por favor, revise los detalles a continuación y tome las acciones necesarias.</p><p><strong>Detalles del Caso:</strong></p><ul><li>Asunto: {case_subject}</li><li>Contacto: {contact_name}</li><li>Asignado a: {assigned_user_name}</li><li>Prioridad: {case_priority}</li><li>Estado: {case_status}</li><li>Fecha de Creación: {case_created_date}</li></ul><p><strong>Descripción:</strong></p><p>{case_description}</p><p><strong>Representante Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Gracias por su apoyo.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'حالة دعم جديدة - {case_subject}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء حالة دعم جديدة. يرجى مراجعة التفاصيل أدناه واتخاذ الإجراءات اللازمة.</p><p><strong>تفاصيل الحالة:</strong></p><ul><li>الموضوع: {case_subject}</li><li>جهة الاتصال: {contact_name}</li><li>معين إلى: {assigned_user_name}</li><li>الأولوية: {case_priority}</li><li>الحالة: {case_status}</li><li>تاريخ الإنشاء: {case_created_date}</li></ul><p><strong>الوصف:</strong></p><p>{case_description}</p><p><strong>الممثل المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>شكراً لدعمك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Supportsag - {case_subject}',
                        'content' => '<p>Hej,</p><p>En ny supportsag er blevet oprettet. Gennemgå venligst detaljerne nedenfor og tag de nødvendige handlinger.</p><p><strong>Sagsdetaljer:</strong></p><ul><li>Emne: {case_subject}</li><li>Kontakt: {contact_name}</li><li>Tildelt til: {assigned_user_name}</li><li>Prioritet: {case_priority}</li><li>Status: {case_status}</li><li>Oprettelsesdato: {case_created_date}</li></ul><p><strong>Beskrivelse:</strong></p><p>{case_description}</p><p><strong>Tildelt Repræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Tak for din støtte.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neuer Support-Fall - {case_subject}',
                        'content' => '<p>Hallo,</p><p>Ein neuer Support-Fall wurde erstellt. Bitte überprüfen Sie die folgenden Details und ergreifen Sie die notwendigen Maßnahmen.</p><p><strong>Falldetails:</strong></p><ul><li>Betreff: {case_subject}</li><li>Kontakt: {contact_name}</li><li>Zugewiesen an: {assigned_user_name}</li><li>Priorität: {case_priority}</li><li>Status: {case_status}</li><li>Erstellungsdatum: {case_created_date}</li></ul><p><strong>Beschreibung:</strong></p><p>{case_description}</p><p><strong>Zugewiesener Vertreter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Vielen Dank für Ihre Unterstützung.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Cas de Support - {case_subject}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau cas de support a été créé. Veuillez consulter les détails ci-dessous et prendre les mesures nécessaires.</p><p><strong>Détails du Cas:</strong></p><ul><li>Sujet: {case_subject}</li><li>Contact: {contact_name}</li><li>Assigné à: {assigned_user_name}</li><li>Priorité: {case_priority}</li><li>Statut: {case_status}</li><li>Date de création: {case_created_date}</li></ul><p><strong>Description:</strong></p><p>{case_description}</p><p><strong>Représentant Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Merci pour votre soutien.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'פנייה חדשה - {case_subject}',
                        'content' => '<p>שלום,</p><p>פנייה חדשה נוצרה. אנא עיין בפרטים למטה ונקוט בפעולות הנדרשות.</p><p><strong>פרטי הפנייה:</strong></p><ul><li>נושא: {case_subject}</li><li>איש קשר: {contact_name}</li><li>מוקצה ל: {assigned_user_name}</li><li>עדיפות: {case_priority}</li><li>סטטוס: {case_status}</li><li>תאריך יצירה: {case_created_date}</li></ul><p><strong>תיאור:</strong></p><p>{case_description}</p><p><strong>נציג מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>תודה על התמיכה שלך.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Caso di Supporto - {case_subject}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo caso di supporto. Si prega di rivedere i dettagli seguenti e intraprendere le azioni necessarie.</p><p><strong>Dettagli del Caso:</strong></p><ul><li>Oggetto: {case_subject}</li><li>Contatto: {contact_name}</li><li>Assegnato a: {assigned_user_name}</li><li>Priorità: {case_priority}</li><li>Stato: {case_status}</li><li>Data di creazione: {case_created_date}</li></ul><p><strong>Descrizione:</strong></p><p>{case_description}</p><p><strong>Rappresentante Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Grazie per il tuo supporto.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しいサポートケース - {case_subject}',
                        'content' => '<p>こんにちは、</p><p>新しいサポートケースが作成されました。以下の詳細を確認し、必要な対応をお願いします。</p><p><strong>ケース詳細:</strong></p><ul><li>件名: {case_subject}</li><li>連絡先: {contact_name}</li><li>担当者: {assigned_user_name}</li><li>優先度: {case_priority}</li><li>ステータス: {case_status}</li><li>作成日: {case_created_date}</li></ul><p><strong>説明:</strong></p><p>{case_description}</p><p><strong>担当担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>ご協力ありがとうございます。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Supportcase - {case_subject}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe supportcase is aangemaakt. Controleer de onderstaande details en onderneem de nodige stappen.</p><p><strong>Case Details:</strong></p><ul><li>Onderwerp: {case_subject}</li><li>Contact: {contact_name}</li><li>Toegewezen aan: {assigned_user_name}</li><li>Prioriteit: {case_priority}</li><li>Status: {case_status}</li><li>Aanmaakdatum: {case_created_date}</li></ul><p><strong>Beschrijving:</strong></p><p>{case_description}</p><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bedankt voor je inzet.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zgłoszenie - {case_subject}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zgłoszenie wsparcia. Proszę zapoznaj się ze szczegółami poniżej i podejmij odpowiednie działania.</p><p><strong>Szczegóły Zgłoszenia:</strong></p><ul><li>Temat: {case_subject}</li><li>Kontakt: {contact_name}</li><li>Przypisany do: {assigned_user_name}</li><li>Priorytet: {case_priority}</li><li>Status: {case_status}</li><li>Data utworzenia: {case_created_date}</li></ul><p><strong>Opis:</strong></p><p>{case_description}</p><p><strong>Przypisany Przedstawiciel:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Dziękujemy za Twoje wsparcie.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Novo Caso de Suporte - {case_subject}',
                        'content' => '<p>Olá,</p><p>Foi criado um novo caso de suporte. Por favor, reveja os detalhes abaixo e tome as ações necessárias.</p><p><strong>Detalhes do Caso:</strong></p><ul><li>Assunto: {case_subject}</li><li>Contacto: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Prioridade: {case_priority}</li><li>Status: {case_status}</li><li>Data de criação: {case_created_date}</li></ul><p><strong>Descrição:</strong></p><p>{case_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu apoio.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Novo Caso de Suporte - {case_subject}',
                        'content' => '<p>Olá,</p><p>Foi criado um novo caso de suporte. Por favor, revise os detalhes abaixo e tome as ações necessárias.</p><p><strong>Detalhes do Caso:</strong></p><ul><li>Assunto: {case_subject}</li><li>Contato: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Prioridade: {case_priority}</li><li>Status: {case_status}</li><li>Data de criação: {case_created_date}</li></ul><p><strong>Descrição:</strong></p><p>{case_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu apoio.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый случай поддержки - {case_subject}',
                        'content' => '<p>Привет,</p><p>Создан новый случай поддержки. Пожалуйста, ознакомьтесь с деталями ниже и примите необходимые меры.</p><p><strong>Детали случая:</strong></p><ul><li>Тема: {case_subject}</li><li>Контакт: {contact_name}</li><li>Назначено: {assigned_user_name}</li><li>Приоритет: {case_priority}</li><li>Статус: {case_status}</li><li>Дата создания: {case_created_date}</li></ul><p><strong>Описание:</strong></p><p>{case_description}</p><p><strong>Назначенный представитель:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Спасибо за вашу поддержку.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Destek Vakası - {case_subject}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir destek vakası oluşturuldu. Lütfen aşağıdaki ayrıntıları inceleyin ve gerekli adımları atın.</p><p><strong>Vaka Detayları:</strong></p><ul><li>Konu: {case_subject}</li><li>İletişim: {contact_name}</li><li>Atanan: {assigned_user_name}</li><li>Öncelik: {case_priority}</li><li>Durum: {case_status}</li><li>Oluşturulma tarihi: {case_created_date}</li></ul><p><strong>Açıklama:</strong></p><p>{case_description}</p><p><strong>Atanan Temsilci:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Destekleriniz için teşekkür ederiz.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新的支持案例 - {case_subject}',
                        'content' => '<p>你好，</p><p>已创建一个新的支持案例。请查看以下详情并采取必要的措施。</p><p><strong>案例详情：</strong></p><ul><li>主题：{case_subject}</li><li>联系人：{contact_name}</li><li>分配给：{assigned_user_name}</li><li>优先级：{case_priority}</li><li>状态：{case_status}</li><li>创建日期：{case_created_date}</li></ul><p><strong>描述：</strong></p><p>{case_description}</p><p><strong>指定代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>感谢你的支持。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Opportunity Created
            [
                'name' => 'Opportunity Created',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Opportunity Created - {opportunity_name}',
                        'content' => '<p>Hello,</p><p>A new sales opportunity has been created. Please review the details below and take appropriate action.</p><p><strong>Opportunity Details:</strong></p><ul><li>Opportunity Name: {opportunity_name}</li><li>Account: {account_name}</li><li>Contact: {contact_name}</li><li>Assigned To: {assigned_user_name}</li><li>Stage: {opportunity_stage}</li><li>Amount: {opportunity_amount}</li><li>Close Date: {opportunity_close_date}</li></ul><p><strong>Description:</strong></p><p>{opportunity_description}</p><p><strong>Assigned Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please log into the system to view full opportunity details.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Oportunidad Creada - {opportunity_name}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva oportunidad de ventas. Por favor revise los detalles a continuación y tome las medidas apropiadas.</p><p><strong>Detalles de la Oportunidad:</strong></p><ul><li>Nombre de la Oportunidad: {opportunity_name}</li><li>Cuenta: {account_name}</li><li>Contacto: {contact_name}</li><li>Asignado a: {assigned_user_name}</li><li>Etapa: {opportunity_stage}</li><li>Monto: {opportunity_amount}</li><li>Fecha de Cierre: {opportunity_close_date}</li></ul><p><strong>Descripción:</strong></p><p>{opportunity_description}</p><p><strong>Representante Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor inicie sesión en el sistema para ver los detalles completos de la oportunidad.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم إنشاء فرصة جديدة - {opportunity_name}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء فرصة مبيعات جديدة. يرجى مراجعة التفاصيل أدناه واتخاذ الإجراء المناسب.</p><p><strong>تفاصيل الفرصة:</strong></p><ul><li>اسم الفرصة: {opportunity_name}</li><li>الحساب: {account_name}</li><li>جهة الاتصال: {contact_name}</li><li>معين إلى: {assigned_user_name}</li><li>المرحلة: {opportunity_stage}</li><li>المبلغ: {opportunity_amount}</li><li>تاريخ الإغلاق: {opportunity_close_date}</li></ul><p><strong>الوصف:</strong></p><p>{opportunity_description}</p><p><strong>الممثل المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى تسجيل الدخول إلى النظام لعرض تفاصيل الفرصة الكاملة.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Mulighed Oprettet - {opportunity_name}',
                        'content' => '<p>Hej,</p><p>En ny salgsmulighed er blevet oprettet. Gennemgå venligst detaljerne nedenfor og tag passende handling.</p><p><strong>Muligheds Detaljer:</strong></p><ul><li>Muligheds Navn: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Tildelt til: {assigned_user_name}</li><li>Fase: {opportunity_stage}</li><li>Beløb: {opportunity_amount}</li><li>Lukkedato: {opportunity_close_date}</li></ul><p><strong>Beskrivelse:</strong></p><p>{opportunity_description}</p><p><strong>Tildelt Repræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Log venligst ind i systemet for at se fulde muligheds detaljer.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Verkaufschance Erstellt - {opportunity_name}',
                        'content' => '<p>Hallo,</p><p>Eine neue Verkaufschance wurde erstellt. Bitte überprüfen Sie die Details unten und ergreifen Sie entsprechende Maßnahmen.</p><p><strong>Verkaufschancen Details:</strong></p><ul><li>Verkaufschancen Name: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Zugewiesen an: {assigned_user_name}</li><li>Phase: {opportunity_stage}</li><li>Betrag: {opportunity_amount}</li><li>Abschlussdatum: {opportunity_close_date}</li></ul><p><strong>Beschreibung:</strong></p><p>{opportunity_description}</p><p><strong>Zugewiesener Vertreter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte loggen Sie sich in das System ein, um vollständige Verkaufschancen-Details anzuzeigen.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouvelle Opportunité Créée - {opportunity_name}',
                        'content' => '<p>Bonjour,</p><p>Une nouvelle opportunité de vente a été créée. Veuillez examiner les détails ci-dessous et prendre les mesures appropriées.</p><p><strong>Détails de l\'Opportunité:</strong></p><ul><li>Nom de l\'Opportunité: {opportunity_name}</li><li>Compte: {account_name}</li><li>Contact: {contact_name}</li><li>Assigné à: {assigned_user_name}</li><li>Étape: {opportunity_stage}</li><li>Montant: {opportunity_amount}</li><li>Date de Clôture: {opportunity_close_date}</li></ul><p><strong>Description:</strong></p><p>{opportunity_description}</p><p><strong>Représentant Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez vous connecter au système pour voir les détails complets de l\'opportunité.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזדמנות חדשה נוצרה - {opportunity_name}',
                        'content' => '<p>שלום,</p><p>הזדמנות מכירות חדשה נוצרה. אנא עיין בפרטים למטה ונקוט בפעולה המתאימה.</p><p><strong>פרטי ההזדמנות:</strong></p><ul><li>שם ההזדמנות: {opportunity_name}</li><li>חשבון: {account_name}</li><li>איש קשר: {contact_name}</li><li>מוקצה ל: {assigned_user_name}</li><li>שלב: {opportunity_stage}</li><li>סכום: {opportunity_amount}</li><li>תאריך סגירה: {opportunity_close_date}</li></ul><p><strong>תיאור:</strong></p><p>{opportunity_description}</p><p><strong>נציג מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא התחבר למערכת כדי לראות פרטי הזדמנות מלאים.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuova Opportunità Creata - {opportunity_name}',
                        'content' => '<p>Ciao,</p><p>Una nuova opportunità di vendita è stata creata. Si prega di rivedere i dettagli qui sotto e prendere le azioni appropriate.</p><p><strong>Dettagli dell\'Opportunità:</strong></p><ul><li>Nome dell\'Opportunità: {opportunity_name}</li><li>Account: {account_name}</li><li>Contatto: {contact_name}</li><li>Assegnato a: {assigned_user_name}</li><li>Fase: {opportunity_stage}</li><li>Importo: {opportunity_amount}</li><li>Data di Chiusura: {opportunity_close_date}</li></ul><p><strong>Descrizione:</strong></p><p>{opportunity_description}</p><p><strong>Rappresentante Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di accedere al sistema per visualizzare i dettagli completi dell\'opportunità.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい営業機会が作成されました - {opportunity_name}',
                        'content' => '<p>こんにちは、</p><p>新しい営業機会が作成されました。以下の詳細を確認し、適切な対応を取ってください。</p><p><strong>営業機会の詳細：</strong></p><ul><li>営業機会名：{opportunity_name}</li><li>アカウント：{account_name}</li><li>連絡先：{contact_name}</li><li>担当者：{assigned_user_name}</li><li>ステージ：{opportunity_stage}</li><li>金額：{opportunity_amount}</li><li>クローズ日：{opportunity_close_date}</li></ul><p><strong>説明：</strong></p><p>{opportunity_description}</p><p><strong>担当担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>システムにログインして営業機会の詳細を確認してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Kans Aangemaakt - {opportunity_name}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe verkoopkans is aangemaakt. Bekijk de details hieronder en onderneem de juiste actie.</p><p><strong>Kans Details:</strong></p><ul><li>Kans Naam: {opportunity_name}</li><li>Account: {account_name}</li><li>Contact: {contact_name}</li><li>Toegewezen aan: {assigned_user_name}</li><li>Fase: {opportunity_stage}</li><li>Bedrag: {opportunity_amount}</li><li>Sluitingsdatum: {opportunity_close_date}</li></ul><p><strong>Beschrijving:</strong></p><p>{opportunity_description}</p><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Log in op het systeem om volledige kansdetails te bekijken.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowa Szansa Utworzona - {opportunity_name}',
                        'content' => '<p>Cześć,</p><p>Nowa szansa sprzedażowa została utworzona. Przejrzyj szczegóły poniżej i podejmij odpowiednie działania.</p><p><strong>Szczegóły Szansy:</strong></p><ul><li>Nazwa Szansy: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Przypisany do: {assigned_user_name}</li><li>Etap: {opportunity_stage}</li><li>Kwota: {opportunity_amount}</li><li>Data Zamknięcia: {opportunity_close_date}</li></ul><p><strong>Opis:</strong></p><p>{opportunity_description}</p><p><strong>Przypisany Przedstawiciel:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Zaloguj się do systemu, aby zobaczyć pełne szczegóły szansy.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Oportunidade Criada - {opportunity_name}',
                        'content' => '<p>Olá,</p><p>Uma nova oportunidade de vendas foi criada. Por favor reveja os detalhes abaixo e tome as medidas apropriadas.</p><p><strong>Detalhes da Oportunidade:</strong></p><ul><li>Nome da Oportunidade: {opportunity_name}</li><li>Conta: {account_name}</li><li>Contacto: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Fase: {opportunity_stage}</li><li>Montante: {opportunity_amount}</li><li>Data de Fecho: {opportunity_close_date}</li></ul><p><strong>Descrição:</strong></p><p>{opportunity_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor faça login no sistema para ver os detalhes completos da oportunidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Oportunidade Criada - {opportunity_name}',
                        'content' => '<p>Olá,</p><p>Uma nova oportunidade de vendas foi criada. Por favor revise os detalhes abaixo e tome as medidas apropriadas.</p><p><strong>Detalhes da Oportunidade:</strong></p><ul><li>Nome da Oportunidade: {opportunity_name}</li><li>Conta: {account_name}</li><li>Contato: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Estágio: {opportunity_stage}</li><li>Valor: {opportunity_amount}</li><li>Data de Fechamento: {opportunity_close_date}</li></ul><p><strong>Descrição:</strong></p><p>{opportunity_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor faça login no sistema para ver os detalhes completos da oportunidade.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Создана новая возможность - {opportunity_name}',
                        'content' => '<p>Привет,</p><p>Новая возможность продаж была создана. Пожалуйста, просмотрите детали ниже и предпримите соответствующие действия.</p><p><strong>Детали Возможности:</strong></p><ul><li>Название Возможности: {opportunity_name}</li><li>Аккаунт: {account_name}</li><li>Контакт: {contact_name}</li><li>Назначено: {assigned_user_name}</li><li>Этап: {opportunity_stage}</li><li>Сумма: {opportunity_amount}</li><li>Дата Закрытия: {opportunity_close_date}</li></ul><p><strong>Описание:</strong></p><p>{opportunity_description}</p><p><strong>Назначенный представитель:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, войдите в систему, чтобы просмотреть полные детали возможности.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Fırsat Oluşturuldu - {opportunity_name}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir satış fırsatı oluşturuldu. Lütfen aşağıdaki detayları inceleyin ve uygun eylemi gerçekleştirin.</p><p><strong>Fırsat Detayları:</strong></p><ul><li>Fırsat Adı: {opportunity_name}</li><li>Hesap: {account_name}</li><li>İletişim: {contact_name}</li><li>Atanan: {assigned_user_name}</li><li>Aşama: {opportunity_stage}</li><li>Tutar: {opportunity_amount}</li><li>Kapanış Tarihi: {opportunity_close_date}</li></ul><p><strong>Açıklama:</strong></p><p>{opportunity_description}</p><p><strong>Atanan Temsilci:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen tam fırsat detaylarını görüntülemek için sisteme giriş yapın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新机会已创建 - {opportunity_name}',
                        'content' => '<p>你好，</p><p>一个新的销售机会已创建。请查看以下详细信息并采取适当行动。</p><p><strong>机会详情：</strong></p><ul><li>机会名称：{opportunity_name}</li><li>客户：{account_name}</li><li>联系人：{contact_name}</li><li>分配给：{assigned_user_name}</li><li>阶段：{opportunity_stage}</li><li>金额：{opportunity_amount}</li><li>关闭日期：{opportunity_close_date}</li></ul><p><strong>描述：</strong></p><p>{opportunity_description}</p><p><strong>指定代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请登录系统查看完整的机会详情。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Opportunity Status Changed
            [
                'name' => 'Opportunity Status Changed',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Opportunity Stage Updated - {opportunity_name}',
                        'content' => '<p>Hello,</p><p>The stage of the opportunity has been updated from <strong>{old_opportunity_stage}</strong> to <strong>{new_opportunity_stage}</strong>.</p><p><strong>Opportunity Details:</strong></p><ul><li>Opportunity Name: {opportunity_name}</li><li>Account: {account_name}</li><li>Contact: {contact_name}</li><li>Assigned To: {assigned_user_name}</li><li>Current Stage: {new_opportunity_stage}</li><li>Amount: {opportunity_amount}</li><li>Close Date: {opportunity_close_date}</li></ul><p><strong>Description:</strong></p><p>{opportunity_description}</p><p><strong>Assigned Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please log into the system to view full opportunity details.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Etapa de Oportunidad Actualizada - {opportunity_name}',
                        'content' => '<p>Hola,</p><p>La etapa de la oportunidad ha sido actualizada de <strong>{old_opportunity_stage}</strong> a <strong>{new_opportunity_stage}</strong>.</p><p><strong>Detalles de la Oportunidad:</strong></p><ul><li>Nombre de la Oportunidad: {opportunity_name}</li><li>Cuenta: {account_name}</li><li>Contacto: {contact_name}</li><li>Asignado a: {assigned_user_name}</li><li>Etapa Actual: {new_opportunity_stage}</li><li>Monto: {opportunity_amount}</li><li>Fecha de Cierre: {opportunity_close_date}</li></ul><p><strong>Descripción:</strong></p><p>{opportunity_description}</p><p><strong>Representante Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor inicie sesión en el sistema para ver los detalles completos de la oportunidad.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم تحديث مرحلة الفرصة - {opportunity_name}',
                        'content' => '<p>مرحباً،</p><p>تم تحديث مرحلة الفرصة من <strong>{old_opportunity_stage}</strong> إلى <strong>{new_opportunity_stage}</strong>.</p><p><strong>تفاصيل الفرصة:</strong></p><ul><li>اسم الفرصة: {opportunity_name}</li><li>الحساب: {account_name}</li><li>جهة الاتصال: {contact_name}</li><li>معين إلى: {assigned_user_name}</li><li>المرحلة الحالية: {new_opportunity_stage}</li><li>المبلغ: {opportunity_amount}</li><li>تاريخ الإغلاق: {opportunity_close_date}</li></ul><p><strong>الوصف:</strong></p><p>{opportunity_description}</p><p><strong>الممثل المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى تسجيل الدخول إلى النظام لعرض تفاصيل الفرصة الكاملة.</p><p style="text-align: right;">أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Muligheds Fase Opdateret - {opportunity_name}',
                        'content' => '<p>Hej,</p><p>Fasen af muligheden er blevet opdateret fra <strong>{old_opportunity_stage}</strong> til <strong>{new_opportunity_stage}</strong>.</p><p><strong>Muligheds Detaljer:</strong></p><ul><li>Muligheds Navn: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Tildelt til: {assigned_user_name}</li><li>Nuværende Fase: {new_opportunity_stage}</li><li>Beløb: {opportunity_amount}</li><li>Lukkedato: {opportunity_close_date}</li></ul><p><strong>Beskrivelse:</strong></p><p>{opportunity_description}</p><p><strong>Tildelt Repræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Log venligst ind i systemet for at se fulde muligheds detaljer.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Verkaufschancen Phase Aktualisiert - {opportunity_name}',
                        'content' => '<p>Hallo,</p><p>Die Phase der Verkaufschance wurde von <strong>{old_opportunity_stage}</strong> auf <strong>{new_opportunity_stage}</strong> aktualisiert.</p><p><strong>Verkaufschancen Details:</strong></p><ul><li>Verkaufschancen Name: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Zugewiesen an: {assigned_user_name}</li><li>Aktuelle Phase: {new_opportunity_stage}</li><li>Betrag: {opportunity_amount}</li><li>Abschlussdatum: {opportunity_close_date}</li></ul><p><strong>Beschreibung:</strong></p><p>{opportunity_description}</p><p><strong>Zugewiesener Vertreter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte loggen Sie sich in das System ein, um vollständige Verkaufschancen-Details anzuzeigen.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Étape d\'Opportunité Mise à Jour - {opportunity_name}',
                        'content' => '<p>Bonjour,</p><p>L\'étape de l\'opportunité a été mise à jour de <strong>{old_opportunity_stage}</strong> à <strong>{new_opportunity_stage}</strong>.</p><p><strong>Détails de l\'Opportunité:</strong></p><ul><li>Nom de l\'Opportunité: {opportunity_name}</li><li>Compte: {account_name}</li><li>Contact: {contact_name}</li><li>Assigné à: {assigned_user_name}</li><li>Étape Actuelle: {new_opportunity_stage}</li><li>Montant: {opportunity_amount}</li><li>Date de Clôture: {opportunity_close_date}</li></ul><p><strong>Description:</strong></p><p>{opportunity_description}</p><p><strong>Représentant Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez vous connecter au système pour voir les détails complets de l\'opportunité.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'שלב ההזדמנות עודכן - {opportunity_name}',
                        'content' => '<p>שלום,</p><p>שלב ההזדמנות עודכן מ<strong>{old_opportunity_stage}</strong> ל<strong>{new_opportunity_stage}</strong>.</p><p><strong>פרטי ההזדמנות:</strong></p><ul><li>שם ההזדמנות: {opportunity_name}</li><li>חשבון: {account_name}</li><li>איש קשר: {contact_name}</li><li>מוקצה ל: {assigned_user_name}</li><li>שלב נוכחי: {new_opportunity_stage}</li><li>סכום: {opportunity_amount}</li><li>תאריך סגירה: {opportunity_close_date}</li></ul><p><strong>תיאור:</strong></p><p>{opportunity_description}</p><p><strong>נציג מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא התחבר למערכת כדי לראות פרטי הזדמנות מלאים.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Fase Opportunità Aggiornata - {opportunity_name}',
                        'content' => '<p>Ciao,</p><p>La fase dell\'opportunità è stata aggiornata da <strong>{old_opportunity_stage}</strong> a <strong>{new_opportunity_stage}</strong>.</p><p><strong>Dettagli dell\'Opportunità:</strong></p><ul><li>Nome dell\'Opportunità: {opportunity_name}</li><li>Account: {account_name}</li><li>Contatto: {contact_name}</li><li>Assegnato a: {assigned_user_name}</li><li>Fase Attuale: {new_opportunity_stage}</li><li>Importo: {opportunity_amount}</li><li>Data di Chiusura: {opportunity_close_date}</li></ul><p><strong>Descrizione:</strong></p><p>{opportunity_description}</p><p><strong>Rappresentante Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di accedere al sistema per visualizzare i dettagli completi dell\'opportunità.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '営業機会のステージが更新されました - {opportunity_name}',
                        'content' => '<p>こんにちは、</p><p>営業機会のステージが<strong>{old_opportunity_stage}</strong>から<strong>{new_opportunity_stage}</strong>に更新されました。</p><p><strong>営業機会の詳細：</strong></p><ul><li>営業機会名：{opportunity_name}</li><li>アカウント：{account_name}</li><li>連絡先：{contact_name}</li><li>担当者：{assigned_user_name}</li><li>現在のステージ：{new_opportunity_stage}</li><li>金額：{opportunity_amount}</li><li>クローズ日：{opportunity_close_date}</li></ul><p><strong>説明：</strong></p><p>{opportunity_description}</p><p><strong>担当担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>システムにログインして営業機会の詳細を確認してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Kans Fase Bijgewerkt - {opportunity_name}',
                        'content' => '<p>Hallo,</p><p>De fase van de kans is bijgewerkt van <strong>{old_opportunity_stage}</strong> naar <strong>{new_opportunity_stage}</strong>.</p><p><strong>Kans Details:</strong></p><ul><li>Kans Naam: {opportunity_name}</li><li>Account: {account_name}</li><li>Contact: {contact_name}</li><li>Toegewezen aan: {assigned_user_name}</li><li>Huidige Fase: {new_opportunity_stage}</li><li>Bedrag: {opportunity_amount}</li><li>Sluitingsdatum: {opportunity_close_date}</li></ul><p><strong>Beschrijving:</strong></p><p>{opportunity_description}</p><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Log in op het systeem om volledige kansdetails te bekijken.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Etap Szansy Zaktualizowany - {opportunity_name}',
                        'content' => '<p>Cześć,</p><p>Etap szansy został zaktualizowany z <strong>{old_opportunity_stage}</strong> na <strong>{new_opportunity_stage}</strong>.</p><p><strong>Szczegóły Szansy:</strong></p><ul><li>Nazwa Szansy: {opportunity_name}</li><li>Konto: {account_name}</li><li>Kontakt: {contact_name}</li><li>Przypisany do: {assigned_user_name}</li><li>Obecny Etap: {new_opportunity_stage}</li><li>Kwota: {opportunity_amount}</li><li>Data Zamknięcia: {opportunity_close_date}</li></ul><p><strong>Opis:</strong></p><p>{opportunity_description}</p><p><strong>Przypisany Przedstawiciel:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Zaloguj się do systemu, aby zobaczyć pełne szczegóły szansy.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Fase da Oportunidade Actualizada - {opportunity_name}',
                        'content' => '<p>Olá,</p><p>A fase da oportunidade foi actualizada de <strong>{old_opportunity_stage}</strong> para <strong>{new_opportunity_stage}</strong>.</p><p><strong>Detalhes da Oportunidade:</strong></p><ul><li>Nome da Oportunidade: {opportunity_name}</li><li>Conta: {account_name}</li><li>Contacto: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Fase Actual: {new_opportunity_stage}</li><li>Montante: {opportunity_amount}</li><li>Data de Fecho: {opportunity_close_date}</li></ul><p><strong>Descrição:</strong></p><p>{opportunity_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor faça login no sistema para ver os detalhes completos da oportunidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Estágio da Oportunidade Atualizado - {opportunity_name}',
                        'content' => '<p>Olá,</p><p>O estágio da oportunidade foi atualizado de <strong>{old_opportunity_stage}</strong> para <strong>{new_opportunity_stage}</strong>.</p><p><strong>Detalhes da Oportunidade:</strong></p><ul><li>Nome da Oportunidade: {opportunity_name}</li><li>Conta: {account_name}</li><li>Contato: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Estágio Atual: {new_opportunity_stage}</li><li>Valor: {opportunity_amount}</li><li>Data de Fechamento: {opportunity_close_date}</li></ul><p><strong>Descrição:</strong></p><p>{opportunity_description}</p><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor faça login no sistema para ver os detalhes completos da oportunidade.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Этап возможности обновлен - {opportunity_name}',
                        'content' => '<p>Привет,</p><p>Этап возможности был обновлен с <strong>{old_opportunity_stage}</strong> на <strong>{new_opportunity_stage}</strong>.</p><p><strong>Детали Возможности:</strong></p><ul><li>Название Возможности: {opportunity_name}</li><li>Аккаунт: {account_name}</li><li>Контакт: {contact_name}</li><li>Назначено: {assigned_user_name}</li><li>Текущий Этап: {new_opportunity_stage}</li><li>Сумма: {opportunity_amount}</li><li>Дата Закрытия: {opportunity_close_date}</li></ul><p><strong>Описание:</strong></p><p>{opportunity_description}</p><p><strong>Назначенный представитель:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, войдите в систему, чтобы просмотреть полные детали возможности.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Fırsat Aşaması Güncellendi - {opportunity_name}',
                        'content' => '<p>Merhaba,</p><p>Fırsatın aşaması <strong>{old_opportunity_stage}</strong> seviyesinden <strong>{new_opportunity_stage}</strong> seviyesine güncellendi.</p><p><strong>Fırsat Detayları:</strong></p><ul><li>Fırsat Adı: {opportunity_name}</li><li>Hesap: {account_name}</li><li>İletişim: {contact_name}</li><li>Atanan: {assigned_user_name}</li><li>Mevcut Aşama: {new_opportunity_stage}</li><li>Tutar: {opportunity_amount}</li><li>Kapanış Tarihi: {opportunity_close_date}</li></ul><p><strong>Açıklama:</strong></p><p>{opportunity_description}</p><p><strong>Atanan Temsilci:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen tam fırsat detaylarını görüntülemek için sisteme giriş yapın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '机会阶段已更新 - {opportunity_name}',
                        'content' => '<p>你好，</p><p>机会阶段已从<strong>{old_opportunity_stage}</strong>更新为<strong>{new_opportunity_stage}</strong>。</p><p><strong>机会详情：</strong></p><ul><li>机会名称：{opportunity_name}</li><li>客户：{account_name}</li><li>联系人：{contact_name}</li><li>分配给：{assigned_user_name}</li><li>当前阶段：{new_opportunity_stage}</li><li>金额：{opportunity_amount}</li><li>关闭日期：{opportunity_close_date}</li></ul><p><strong>描述：</strong></p><p>{opportunity_description}</p><p><strong>指定代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请登录系统查看完整的机会详情。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ],
                ]
            ],
            // Invoice Payment Reminder
            [
                'name' => 'Invoice Payment Reminder',
                'from' => 'Accounts Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Payment Reminder - Invoice {invoice_number}',
                        'content' => '<p>Hello {billing_contact_name},</p><p>This is a friendly reminder that payment for the following invoice is due.</p><p><strong>Invoice Details:</strong></p><ul><li>Invoice Number: {invoice_number}</li><li>Invoice Date: {invoice_date}</li><li>Due Date: {invoice_due_date}</li><li>Total Amount: {invoice_total}</li><li>Amount Due: {invoice_amount_due}</li></ul><p>Please process the payment at your earliest convenience to avoid any service interruptions.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Pay Now</a></p><p>If you have already made the payment, please disregard this reminder.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Recordatorio de Pago - Factura {invoice_number}',
                        'content' => '<p>Hola {billing_contact_name},</p><p>Este es un recordatorio amistoso de que el pago de la siguiente factura está pendiente.</p><p><strong>Detalles de la Factura:</strong></p><ul><li>Número de Factura: {invoice_number}</li><li>Fecha de Factura: {invoice_date}</li><li>Fecha de Vencimiento: {invoice_due_date}</li><li>Monto Total: {invoice_total}</li><li>Monto Pendiente: {invoice_amount_due}</li></ul><p>Por favor procese el pago lo antes posible para evitar interrupciones en el servicio.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Pagar Ahora</a></p><p>Si ya ha realizado el pago, por favor ignore este recordatorio.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تذكير بالدفع - فاتورة {invoice_number}',
                        'content' => '<p>مرحباً {billing_contact_name}،</p><p>هذا تذكير ودي بأن الدفع للفاتورة التالية مستحق.</p><p><strong>تفاصيل الفاتورة:</strong></p><ul><li>رقم الفاتورة: {invoice_number}</li><li>تاريخ الفاتورة: {invoice_date}</li><li>تاريخ الاستحقاق: {invoice_due_date}</li><li>المبلغ الإجمالي: {invoice_total}</li><li>المبلغ المستحق: {invoice_amount_due}</li></ul><p>يرجى معالجة الدفع في أقرب وقت ممكن لتجنب أي انقطاع في الخدمة.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">ادفع الآن</a></p><p>إذا كنت قد قمت بالدفع بالفعل، يرجى تجاهل هذا التذكير.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Betalingspåmindelse - Faktura {invoice_number}',
                        'content' => '<p>Hej {billing_contact_name},</p><p>Dette er en venlig påmindelse om, at betaling for følgende faktura er forfalden.</p><p><strong>Faktura Detaljer:</strong></p><ul><li>Fakturanummer: {invoice_number}</li><li>Fakturadato: {invoice_date}</li><li>Forfaldsdato: {invoice_due_date}</li><li>Samlet beløb: {invoice_total}</li><li>Forfaldent beløb: {invoice_amount_due}</li></ul><p>Venligst behandl betalingen hurtigst muligt for at undgå afbrydelser i servicen.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Betal Nu</a></p><p>Hvis du allerede har foretaget betalingen, bedes du se bort fra denne påmindelse.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Zahlungserinnerung - Rechnung {invoice_number}',
                        'content' => '<p>Hallo {billing_contact_name},</p><p>Dies ist eine freundliche Erinnerung, dass die Zahlung für die folgende Rechnung fällig ist.</p><p><strong>Rechnungsdetails:</strong></p><ul><li>Rechnungsnummer: {invoice_number}</li><li>Rechnungsdatum: {invoice_date}</li><li>Fälligkeitsdatum: {invoice_due_date}</li><li>Gesamtbetrag: {invoice_total}</li><li>Fälliger Betrag: {invoice_amount_due}</li></ul><p>Bitte veranlassen Sie die Zahlung so bald wie möglich, um Serviceunterbrechungen zu vermeiden.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Jetzt Bezahlen</a></p><p>Wenn Sie die Zahlung bereits geleistet haben, ignorieren Sie bitte diese Erinnerung.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Rappel de Paiement - Facture {invoice_number}',
                        'content' => '<p>Bonjour {billing_contact_name},</p><p>Ceci est un rappel amical que le paiement de la facture suivante est dû.</p><p><strong>Détails de la Facture:</strong></p><ul><li>Numéro de facture: {invoice_number}</li><li>Date de facture: {invoice_date}</li><li>Date d\'échéance: {invoice_due_date}</li><li>Montant total: {invoice_total}</li><li>Montant dû: {invoice_amount_due}</li></ul><p>Veuillez traiter le paiement dès que possible pour éviter toute interruption de service.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Payer Maintenant</a></p><p>Si vous avez déjà effectué le paiement, veuillez ignorer ce rappel.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'תזכורת תשלום - חשבונית {invoice_number}',
                        'content' => '<p>שלום {billing_contact_name},</p><p>זוהי תזכורת ידידותית שהתשלום עבור החשבונית הבאה מגיע.</p><p><strong>פרטי החשבונית:</strong></p><ul><li>מספר חשבונית: {invoice_number}</li><li>תאריך חשבונית: {invoice_date}</li><li>תאריך פירעון: {invoice_due_date}</li><li>סכום כולל: {invoice_total}</li><li>סכום לתשלום: {invoice_amount_due}</li></ul><p>אנא בצע את התשלום בהקדם האפשרי כדי למנוע הפרעות בשירות.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">שלם עכשיו</a></p><p>אם כבר ביצעת את התשלום, אנא התעלם מתזכורת זו.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Promemoria Pagamento - Fattura {invoice_number}',
                        'content' => '<p>Ciao {billing_contact_name},</p><p>Questo è un promemoria amichevole che il pagamento per la seguente fattura è dovuto.</p><p><strong>Dettagli Fattura:</strong></p><ul><li>Numero fattura: {invoice_number}</li><li>Data fattura: {invoice_date}</li><li>Data di scadenza: {invoice_due_date}</li><li>Importo totale: {invoice_total}</li><li>Importo dovuto: {invoice_amount_due}</li></ul><p>Si prega di elaborare il pagamento il prima possibile per evitare interruzioni del servizio.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Paga Ora</a></p><p>Se hai già effettuato il pagamento, ignora questo promemoria.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => 'お支払いのリマインダー - 請求書 {invoice_number}',
                        'content' => '<p>こんにちは {billing_contact_name}さん、</p><p>以下の請求書のお支払い期限が到来していることをお知らせいたします。</p><p><strong>請求書詳細:</strong></p><ul><li>請求書番号: {invoice_number}</li><li>請求書日付: {invoice_date}</li><li>支払期限: {invoice_due_date}</li><li>合計金額: {invoice_total}</li><li>支払金額: {invoice_amount_due}</li></ul><p>サービスの中断を避けるため、できるだけ早くお支払いをお願いいたします.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">今すぐ支払う</a></p><p>既にお支払いいただいている場合は、このリマインダーを無視してください.</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Betalingsherinnering - Factuur {invoice_number}',
                        'content' => '<p>Hallo {billing_contact_name},</p><p>Dit is een vriendelijke herinnering dat de betaling voor de volgende factuur verschuldigd is.</p><p><strong>Factuur Details:</strong></p><ul><li>Factuurnummer: {invoice_number}</li><li>Factuurdatum: {invoice_date}</li><li>Vervaldatum: {invoice_due_date}</li><li>Totaalbedrag: {invoice_total}</li><li>Verschuldigd bedrag: {invoice_amount_due}</li></ul><p>Verwerk de betaling zo snel mogelijk om serviceonderbrekingen te voorkomen.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Nu Betalen</a></p><p>Als je de betaling al hebt gedaan, negeer dan deze herinnering.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Przypomnienie o Płatności - Faktura {invoice_number}',
                        'content' => '<p>Witaj {billing_contact_name},</p><p>To przyjazne przypomnienie, że płatność za poniższą fakturę jest wymagalna.</p><p><strong>Szczegóły Faktury:</strong></p><ul><li>Numer faktury: {invoice_number}</li><li>Data faktury: {invoice_date}</li><li>Termin płatności: {invoice_due_date}</li><li>Łączna kwota: {invoice_total}</li><li>Kwota do zapłaty: {invoice_amount_due}</li></ul><p>Prosimy o dokonanie płatności jak najszybciej, aby uniknąć przerw w świadczeniu usług.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Zapłać Teraz</a></p><p>Jeśli płatność została już dokonana, prosimy zignorować to przypomnienie.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Lembrete de Pagamento - Fatura {invoice_number}',
                        'content' => '<p>Olá {billing_contact_name},</p><p>Este é um lembrete amigável de que o pagamento da seguinte fatura está pendente.</p><p><strong>Detalhes da Fatura:</strong></p><ul><li>Número da fatura: {invoice_number}</li><li>Data da fatura: {invoice_date}</li><li>Data de vencimento: {invoice_due_date}</li><li>Valor total: {invoice_total}</li><li>Valor devido: {invoice_amount_due}</li></ul><p>Por favor, processe o pagamento o mais rápido possível para evitar interrupções no serviço.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Pagar Agora</a></p><p>Se já efectuou o pagamento, por favor ignore este lembrete.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Lembrete de Pagamento - Fatura {invoice_number}',
                        'content' => '<p>Olá {billing_contact_name},</p><p>Este é um lembrete amigável de que o pagamento da seguinte fatura está pendente.</p><p><strong>Detalhes da Fatura:</strong></p><ul><li>Número da fatura: {invoice_number}</li><li>Data da fatura: {invoice_date}</li><li>Data de vencimento: {invoice_due_date}</li><li>Valor total: {invoice_total}</li><li>Valor devido: {invoice_amount_due}</li></ul><p>Por favor, processe o pagamento o mais rápido possível para evitar interrupções no serviço.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Pagar Agora</a></p><p>Se você já efetuou o pagamento, por favor ignore este lembrete.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Напоминание об оплате - Счет {invoice_number}',
                        'content' => '<p>Привет {billing_contact_name},</p><p>Это дружеское напоминание о том, что оплата следующего счета просрочена.</p><p><strong>Детали счета:</strong></p><ul><li>Номер счета: {invoice_number}</li><li>Дата счета: {invoice_date}</li><li>Срок оплаты: {invoice_due_date}</li><li>Общая сумма: {invoice_total}</li><li>Сумма к оплате: {invoice_amount_due}</li></ul><p>Пожалуйста, произведите оплату как можно скорее, чтобы избежать перерывов в обслуживании.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Оплатить Сейчас</a></p><p>Если вы уже произвели оплату, пожалуйста, проигнорируйте это напоминание.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Ödeme Hatırlatması - Fatura {invoice_number}',
                        'content' => '<p>Merhaba {billing_contact_name},</p><p>Bu, aşağıdaki faturanın ödemesinin vadesi geldiğine dair dostça bir hatırlatmadır.</p><p><strong>Fatura Detayları:</strong></p><ul><li>Fatura numarası: {invoice_number}</li><li>Fatura tarihi: {invoice_date}</li><li>Vade tarihi: {invoice_due_date}</li><li>Toplam tutar: {invoice_total}</li><li>Ödenecek tutar: {invoice_amount_due}</li></ul><p>Hizmet kesintilerini önlemek için lütfen ödemeyi en kısa sürede işleme alın.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Şimdi Öde</a></p><p>Ödemeyi zaten yaptıysanız, lütfen bu hatırlatmayı dikkate almayın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '付款提醒 - 发票 {invoice_number}',
                        'content' => '<p>你好 {billing_contact_name}，</p><p>这是一个友好的提醒，以下发票的付款已到期。</p><p><strong>发票详情：</strong></p><ul><li>发票编号：{invoice_number}</li><li>发票日期：{invoice_date}</li><li>到期日期：{invoice_due_date}</li><li>总金额：{invoice_total}</li><li>应付金额：{invoice_amount_due}</li></ul><p>请尽快处理付款以避免服务中断.</p><p style="text-align: center; margin: 20px 0;"><a href="{invoice_payment_link}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">立即支付</a></p><p>如果您已经付款，请忽略此提醒.</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Invoice Created
            [
                'name' => 'Invoice Created',
                'from' => 'Accounts Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Invoice Created - {invoice_number}',
                        'content' => '<p>Hello,</p><p>A new invoice has been created. Please review the details below.</p><p><strong>Invoice Details:</strong></p><ul><li>Invoice Number: {invoice_number}</li><li>Invoice Name: {invoice_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Total Amount: {invoice_total}</li><li>Invoice Date: {invoice_date}</li><li>Due Date: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Assigned To:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please process this invoice accordingly.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Factura Creada - {invoice_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva factura. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Factura:</strong></p><ul><li>Número de Factura: {invoice_number}</li><li>Nombre de Factura: {invoice_name}</li><li>Contacto: {contact_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {invoice_total}</li><li>Fecha de Factura: {invoice_date}</li><li>Fecha de Vencimiento: {due_date}</li><li>Estado: {invoice_status}</li></ul><p><strong>Asignado a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor procese esta factura en consecuencia.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'تم إنشاء فاتورة جديدة - {invoice_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء فاتورة جديدة. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل الفاتورة:</strong></p><ul><li>رقم الفاتورة: {invoice_number}</li><li>اسم الفاتورة: {invoice_name}</li><li>جهة الاتصال: {contact_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {invoice_total}</li><li>تاريخ الفاتورة: {invoice_date}</li><li>تاريخ الاستحقاق: {due_date}</li><li>الحالة: {invoice_status}</li></ul><p><strong>معين إلى:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى معالجة هذه الفاتورة وفقاً لذلك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Faktura Oprettet - {invoice_number}',
                        'content' => '<p>Hej,</p><p>En ny faktura er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Faktura Detaljer:</strong></p><ul><li>Fakturanummer: {invoice_number}</li><li>Fakturanavn: {invoice_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {invoice_total}</li><li>Fakturadato: {invoice_date}</li><li>Forfaldsdato: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Tildelt til:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Behandl venligst denne faktura i overensstemmelse hermed.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Rechnung Erstellt - {invoice_number}',
                        'content' => '<p>Hallo,</p><p>Eine neue Rechnung wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Rechnungsdetails:</strong></p><ul><li>Rechnungsnummer: {invoice_number}</li><li>Rechnungsname: {invoice_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {invoice_total}</li><li>Rechnungsdatum: {invoice_date}</li><li>Fälligkeitsdatum: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Zugewiesen an:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte bearbeiten Sie diese Rechnung entsprechend.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouvelle Facture Créée - {invoice_number}',
                        'content' => '<p>Bonjour,</p><p>Une nouvelle facture a été créée. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails de la Facture:</strong></p><ul><li>Numéro de facture: {invoice_number}</li><li>Nom de la facture: {invoice_name}</li><li>Contact: {contact_name}</li><li>Compte: {account_name}</li><li>Montant total: {invoice_total}</li><li>Date de facture: {invoice_date}</li><li>Date d\'échéance: {due_date}</li><li>Statut: {invoice_status}</li></ul><p><strong>Assigné à:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez traiter cette facture en conséquence.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'חשבונית חדשה נוצרה - {invoice_number}',
                        'content' => '<p>שלום,</p><p>חשבונית חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי החשבונית:</strong></p><ul><li>מספר חשבונית: {invoice_number}</li><li>שם חשבונית: {invoice_name}</li><li>איש קשר: {contact_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {invoice_total}</li><li>תאריך חשבונית: {invoice_date}</li><li>תאריך פירעון: {due_date}</li><li>סטטוס: {invoice_status}</li></ul><p><strong>מוקצה ל:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא עבד חשבונית זו בהתאם.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuova Fattura Creata - {invoice_number}',
                        'content' => '<p>Ciao,</p><p>È stata creata una nuova fattura. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Fattura:</strong></p><ul><li>Numero fattura: {invoice_number}</li><li>Nome fattura: {invoice_name}</li><li>Contatto: {contact_name}</li><li>Account: {account_name}</li><li>Importo totale: {invoice_total}</li><li>Data fattura: {invoice_date}</li><li>Data di scadenza: {due_date}</li><li>Stato: {invoice_status}</li></ul><p><strong>Assegnato a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di elaborare questa fattura di conseguenza.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい請求書が作成されました - {invoice_number}',
                        'content' => '<p>こんにちは、</p><p>新しい請求書が作成されました。以下の詳細をご確認ください。</p><p><strong>請求書詳細:</strong></p><ul><li>請求書番号: {invoice_number}</li><li>請求書名: {invoice_name}</li><li>連絡先: {contact_name}</li><li>アカウント: {account_name}</li><li>合計金額: {invoice_total}</li><li>請求書日付: {invoice_date}</li><li>支払期限: {due_date}</li><li>ステータス: {invoice_status}</li></ul><p><strong>担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>この請求書を適切に処理してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Factuur Aangemaakt - {invoice_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe factuur is aangemaakt. Bekijk de details hieronder.</p><p><strong>Factuur Details:</strong></p><ul><li>Factuurnummer: {invoice_number}</li><li>Factuurnaam: {invoice_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {invoice_total}</li><li>Factuurdatum: {invoice_date}</li><li>Vervaldatum: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Toegewezen aan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Verwerk deze factuur dienovereenkomstig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowa Faktura Utworzona - {invoice_number}',
                        'content' => '<p>Witaj,</p><p>Nowa faktura została utworzona. Przejrzyj szczegóły poniżej.</p><p><strong>Szczegóły Faktury:</strong></p><ul><li>Numer faktury: {invoice_number}</li><li>Nazwa faktury: {invoice_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {invoice_total}</li><li>Data faktury: {invoice_date}</li><li>Termin płatności: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Przypisany do:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Proszę przetworzyć tę fakturę odpowiednio.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Fatura Criada - {invoice_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova fatura. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Fatura:</strong></p><ul><li>Número da fatura: {invoice_number}</li><li>Nome da fatura: {invoice_name}</li><li>Contacto: {contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {invoice_total}</li><li>Data da fatura: {invoice_date}</li><li>Data de vencimento: {due_date}</li><li>Estado: {invoice_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta fatura em conformidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Fatura Criada - {invoice_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova fatura. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes da Fatura:</strong></p><ul><li>Número da fatura: {invoice_number}</li><li>Nome da fatura: {invoice_name}</li><li>Contato: {contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {invoice_total}</li><li>Data da fatura: {invoice_date}</li><li>Data de vencimento: {due_date}</li><li>Status: {invoice_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta fatura adequadamente.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Создан новый счет - {invoice_number}',
                        'content' => '<p>Привет,</p><p>Создан новый счет. Пожалуйста, просмотрите детали ниже.</p><p><strong>Детали счета:</strong></p><ul><li>Номер счета: {invoice_number}</li><li>Название счета: {invoice_name}</li><li>Контакт: {contact_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {invoice_total}</li><li>Дата счета: {invoice_date}</li><li>Срок оплаты: {due_date}</li><li>Статус: {invoice_status}</li></ul><p><strong>Назначено:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, обработайте этот счет соответствующим образом.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Fatura Oluşturuldu - {invoice_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir fatura oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Fatura Detayları:</strong></p><ul><li>Fatura numarası: {invoice_number}</li><li>Fatura adı: {invoice_name}</li><li>İletişim: {contact_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {invoice_total}</li><li>Fatura tarihi: {invoice_date}</li><li>Vade tarihi: {due_date}</li><li>Durum: {invoice_status}</li></ul><p><strong>Atanan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen bu faturayı buna göre işleme alın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新发票已创建 - {invoice_number}',
                        'content' => '<p>你好，</p><p>已创建新的发票。请查看以下详细信息。</p><p><strong>发票详情：</strong></p><ul><li>发票编号：{invoice_number}</li><li>发票名称：{invoice_name}</li><li>联系人：{contact_name}</li><li>账户：{account_name}</li><li>总金额：{invoice_total}</li><li>发票日期：{invoice_date}</li><li>到期日期：{due_date}</li><li>状态：{invoice_status}</li></ul><p><strong>分配给：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请相应地处理此发票。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Purchase Order Created
            [
                'name' => 'Purchase Order Created',
                'from' => 'Procurement Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Purchase Order - {purchase_order_number}',
                        'content' => '<p>Hello,</p><p>A new purchase order has been created. Please review the details below.</p><p><strong>Purchase Order Details:</strong></p><ul><li>Purchase Order Number: {purchase_order_number}</li><li>Purchase Order Name: {purchase_order_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Total Amount: {purchase_order_total}</li><li>Purchase Order Date: {purchase_order_date}</li><li>Expected Delivery Date: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Assigned To:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please process this purchase order accordingly.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Orden de Compra - {purchase_order_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva orden de compra. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Orden de Compra:</strong></p><ul><li>Número de Orden de Compra: {purchase_order_number}</li><li>Nombre de Orden de Compra: {purchase_order_name}</li><li>Contacto: {contact_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {purchase_order_total}</li><li>Fecha de Orden de Compra: {purchase_order_date}</li><li>Fecha de Entrega Esperada: {expected_delivery_date}</li><li>Estado: {purchase_order_status}</li></ul><p><strong>Asignado a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor procese esta orden de compra en consecuencia.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'أمر شراء جديد - {purchase_order_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء أمر شراء جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل أمر الشراء:</strong></p><ul><li>رقم أمر الشراء: {purchase_order_number}</li><li>اسم أمر الشراء: {purchase_order_name}</li><li>جهة الاتصال: {contact_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {purchase_order_total}</li><li>تاريخ أمر الشراء: {purchase_order_date}</li><li>تاريخ التسليم المتوقع: {expected_delivery_date}</li><li>الحالة: {purchase_order_status}</li></ul><p><strong>معين إلى:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى معالجة أمر الشراء هذا وفقاً لذلك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Indkøbsordre - {purchase_order_number}',
                        'content' => '<p>Hej,</p><p>En ny indkøbsordre er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Indkøbsordre Detaljer:</strong></p><ul><li>Indkøbsordrenummer: {purchase_order_number}</li><li>Indkøbsordrenavn: {purchase_order_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {purchase_order_total}</li><li>Indkøbsordredato: {purchase_order_date}</li><li>Forventet leveringsdato: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Tildelt til:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Behandl venligst denne indkøbsordre i overensstemmelse hermed.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Bestellung - {purchase_order_number}',
                        'content' => '<p>Hallo,</p><p>Eine neue Bestellung wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Bestelldetails:</strong></p><ul><li>Bestellnummer: {purchase_order_number}</li><li>Bestellname: {purchase_order_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {purchase_order_total}</li><li>Bestelldatum: {purchase_order_date}</li><li>Erwartetes Lieferdatum: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Zugewiesen an:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte bearbeiten Sie diese Bestellung entsprechend.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Bon de Commande - {purchase_order_number}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau bon de commande a été créé. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails du Bon de Commande:</strong></p><ul><li>Numéro de bon de commande: {purchase_order_number}</li><li>Nom du bon de commande: {purchase_order_name}</li><li>Contact: {contact_name}</li><li>Compte: {account_name}</li><li>Montant total: {purchase_order_total}</li><li>Date du bon de commande: {purchase_order_date}</li><li>Date de livraison prévue: {expected_delivery_date}</li><li>Statut: {purchase_order_status}</li></ul><p><strong>Assigné à:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez traiter ce bon de commande en conséquence.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנת רכש חדשה - {purchase_order_number}',
                        'content' => '<p>שלום,</p><p>הזמנת רכש חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי הזמנת הרכש:</strong></p><ul><li>מספר הזמנת רכש: {purchase_order_number}</li><li>שם הזמנת רכש: {purchase_order_name}</li><li>איש קשר: {contact_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {purchase_order_total}</li><li>תאריך הזמנת רכש: {purchase_order_date}</li><li>תאריך אספקה צפוי: {expected_delivery_date}</li><li>סטטוס: {purchase_order_status}</li></ul><p><strong>מוקצה ל:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא עבד הזמנת רכש זו בהתאם.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Ordine di Acquisto - {purchase_order_number}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo ordine di acquisto. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Ordine di Acquisto:</strong></p><ul><li>Numero ordine di acquisto: {purchase_order_number}</li><li>Nome ordine di acquisto: {purchase_order_name}</li><li>Contatto: {contact_name}</li><li>Account: {account_name}</li><li>Importo totale: {purchase_order_total}</li><li>Data ordine di acquisto: {purchase_order_date}</li><li>Data di consegna prevista: {expected_delivery_date}</li><li>Stato: {purchase_order_status}</li></ul><p><strong>Assegnato a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di elaborare questo ordine di acquisto di conseguenza.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい発注書 - {purchase_order_number}',
                        'content' => '<p>こんにちは、</p><p>新しい発注書が作成されました。以下の詳細をご確認ください。</p><p><strong>発注書詳細:</strong></p><ul><li>発注書番号: {purchase_order_number}</li><li>発注書名: {purchase_order_name}</li><li>連絡先: {contact_name}</li><li>アカウント: {account_name}</li><li>合計金額: {purchase_order_total}</li><li>発注書日付: {purchase_order_date}</li><li>配送予定日: {expected_delivery_date}</li><li>ステータス: {purchase_order_status}</li></ul><p><strong>担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>この発注書を適切に処理してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Inkooporder - {purchase_order_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe inkooporder is aangemaakt. Controleer de onderstaande details.</p><p><strong>Inkooporder Details:</strong></p><ul><li>Inkoopordernummer: {purchase_order_number}</li><li>Inkoopordernaam: {purchase_order_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {purchase_order_total}</li><li>Inkooporderdatum: {purchase_order_date}</li><li>Verwachte leverdatum: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Toegewezen aan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Verwerk deze inkooporder dienovereenkomstig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zamówienie Zakupu - {purchase_order_number}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zamówienie zakupu. Proszę zapoznaj się ze szczegółami poniżej.</p><p><strong>Szczegóły Zamówienia Zakupu:</strong></p><ul><li>Numer zamówienia zakupu: {purchase_order_number}</li><li>Nazwa zamówienia zakupu: {purchase_order_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {purchase_order_total}</li><li>Data zamówienia zakupu: {purchase_order_date}</li><li>Oczekiwana data dostawy: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Przypisany do:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Proszę przetworzyć to zamówienie zakupu odpowiednio.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Ordem de Compra - {purchase_order_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de compra. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Compra:</strong></p><ul><li>Número da ordem de compra: {purchase_order_number}</li><li>Nome da ordem de compra: {purchase_order_name}</li><li>Contacto: {contact_name}</li><li>Conta: {account_name}</li><li>Montante total: {purchase_order_total}</li><li>Data da ordem de compra: {purchase_order_date}</li><li>Data de entrega prevista: {expected_delivery_date}</li><li>Estado: {purchase_order_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de compra em conformidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Ordem de Compra - {purchase_order_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de compra. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Compra:</strong></p><ul><li>Número da ordem de compra: {purchase_order_number}</li><li>Nome da ordem de compra: {purchase_order_name}</li><li>Contato: {contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {purchase_order_total}</li><li>Data da ordem de compra: {purchase_order_date}</li><li>Data de entrega prevista: {expected_delivery_date}</li><li>Status: {purchase_order_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de compra adequadamente.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый заказ на покупку - {purchase_order_number}',
                        'content' => '<p>Привет,</p><p>Создан новый заказ на покупку. Пожалуйста, ознакомьтесь с деталями ниже.</p><p><strong>Детали заказа на покупку:</strong></p><ul><li>Номер заказа на покупку: {purchase_order_number}</li><li>Название заказа на покупку: {purchase_order_name}</li><li>Контакт: {contact_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {purchase_order_total}</li><li>Дата заказа на покупку: {purchase_order_date}</li><li>Ожидаемая дата доставки: {expected_delivery_date}</li><li>Статус: {purchase_order_status}</li></ul><p><strong>Назначено:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, обработайте этот заказ на покупку соответствующим образом.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Satın Alma Siparişi - {purchase_order_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir satın alma siparişi oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Satın Alma Siparişi Detayları:</strong></p><ul><li>Satın alma siparişi numarası: {purchase_order_number}</li><li>Satın alma siparişi adı: {purchase_order_name}</li><li>İletişim: {contact_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {purchase_order_total}</li><li>Satın alma siparişi tarihi: {purchase_order_date}</li><li>Beklenen teslimat tarihi: {expected_delivery_date}</li><li>Durum: {purchase_order_status}</li></ul><p><strong>Atanan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen bu satın alma siparişini buna göre işleme alın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新采购订单 - {purchase_order_number}',
                        'content' => '<p>你好，</p><p>已创建新的采购订单。请查看以下详细信息。</p><p><strong>采购订单详情：</strong></p><ul><li>采购订单编号：{purchase_order_number}</li><li>采购订单名称：{purchase_order_name}</li><li>联系人：{contact_name}</li><li>账户：{account_name}</li><li>总金额：{purchase_order_total}</li><li>采购订单日期：{purchase_order_date}</li><li>预计交货日期：{expected_delivery_date}</li><li>状态：{purchase_order_status}</li></ul><p><strong>分配给：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请相应地处理此采购订单。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Receipt Order Created
            [
                'name' => 'Receipt Order Created',
                'from' => 'Operations Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Receipt Order - {receipt_number}',
                        'content' => '<p>Hello,</p><p>A new receipt order has been created. Please review the details below.</p><p><strong>Receipt Order Details:</strong></p><ul><li>Receipt Number: {receipt_number}</li><li>Receipt Name: {receipt_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Total Amount: {receipt_total}</li><li>Receipt Date: {receipt_date}</li><li>Expected Receipt Date: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Assigned To:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please process this receipt order accordingly.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Orden de Recibo - {receipt_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva orden de recibo. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Orden de Recibo:</strong></p><ul><li>Número de Recibo: {receipt_number}</li><li>Nombre de Recibo: {receipt_name}</li><li>Contacto: {contact_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {receipt_total}</li><li>Fecha de Recibo: {receipt_date}</li><li>Fecha de Recibo Esperada: {expected_receipt_date}</li><li>Estado: {receipt_status}</li></ul><p><strong>Asignado a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor procese esta orden de recibo en consecuencia.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'أمر استلام جديد - {receipt_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء أمر استلام جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل أمر الاستلام:</strong></p><ul><li>رقم الاستلام: {receipt_number}</li><li>اسم الاستلام: {receipt_name}</li><li>جهة الاتصال: {contact_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {receipt_total}</li><li>تاريخ الاستلام: {receipt_date}</li><li>تاريخ الاستلام المتوقع: {expected_receipt_date}</li><li>الحالة: {receipt_status}</li></ul><p><strong>معين إلى:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى معالجة أمر الاستلام هذا وفقاً لذلك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Kvitteringsordre - {receipt_number}',
                        'content' => '<p>Hej,</p><p>En ny kvitteringsordre er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Kvitteringsordre Detaljer:</strong></p><ul><li>Kvitteringsnummer: {receipt_number}</li><li>Kvitteringsnavn: {receipt_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {receipt_total}</li><li>Kvitteringsdato: {receipt_date}</li><li>Forventet kvitteringsdato: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Tildelt til:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Behandl venligst denne kvitteringsordre i overensstemmelse hermed.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Empfangsbestellung - {receipt_number}',
                        'content' => '<p>Hallo,</p><p>Eine neue Empfangsbestellung wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Empfangsbestellung Details:</strong></p><ul><li>Empfangsnummer: {receipt_number}</li><li>Empfangsname: {receipt_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {receipt_total}</li><li>Empfangsdatum: {receipt_date}</li><li>Erwartetes Empfangsdatum: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Zugewiesen an:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte bearbeiten Sie diese Empfangsbestellung entsprechend.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Bon de Réception - {receipt_number}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau bon de réception a été créé. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails du Bon de Réception:</strong></p><ul><li>Numéro de réception: {receipt_number}</li><li>Nom de réception: {receipt_name}</li><li>Contact: {contact_name}</li><li>Compte: {account_name}</li><li>Montant total: {receipt_total}</li><li>Date de réception: {receipt_date}</li><li>Date de réception prévue: {expected_receipt_date}</li><li>Statut: {receipt_status}</li></ul><p><strong>Assigné à:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez traiter ce bon de réception en conséquence.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנת קבלה חדשה - {receipt_number}',
                        'content' => '<p>שלום,</p><p>הזמנת קבלה חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי הזמנת הקבלה:</strong></p><ul><li>מספר קבלה: {receipt_number}</li><li>שם קבלה: {receipt_name}</li><li>איש קשר: {contact_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {receipt_total}</li><li>תאריך קבלה: {receipt_date}</li><li>תאריך קבלה צפוי: {expected_receipt_date}</li><li>סטטוס: {receipt_status}</li></ul><p><strong>מוקצה ל:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא עבד הזמנת קבלה זו בהתאם.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Ordine di Ricevuta - {receipt_number}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo ordine di ricevuta. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Ordine di Ricevuta:</strong></p><ul><li>Numero ricevuta: {receipt_number}</li><li>Nome ricevuta: {receipt_name}</li><li>Contatto: {contact_name}</li><li>Account: {account_name}</li><li>Importo totale: {receipt_total}</li><li>Data ricevuta: {receipt_date}</li><li>Data di ricevuta prevista: {expected_receipt_date}</li><li>Stato: {receipt_status}</li></ul><p><strong>Assegnato a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di elaborare questo ordine di ricevuta di conseguenza.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい受領注文 - {receipt_number}',
                        'content' => '<p>こんにちは、</p><p>新しい受領注文が作成されました。以下の詳細をご確認ください。</p><p><strong>受領注文詳細:</strong></p><ul><li>受領番号: {receipt_number}</li><li>受領名: {receipt_name}</li><li>連絡先: {contact_name}</li><li>アカウント: {account_name}</li><li>合計金額: {receipt_total}</li><li>受領日: {receipt_date}</li><li>受領予定日: {expected_receipt_date}</li><li>ステータス: {receipt_status}</li></ul><p><strong>担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>この受領注文を適切に処理してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Ontvangstorder - {receipt_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe ontvangstorder is aangemaakt. Controleer de onderstaande details.</p><p><strong>Ontvangstorder Details:</strong></p><ul><li>Ontvangstordernummer: {receipt_number}</li><li>Ontvangstordernaam: {receipt_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {receipt_total}</li><li>Ontvangstdatum: {receipt_date}</li><li>Verwachte ontvangstdatum: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Toegewezen aan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Verwerk deze ontvangstorder dienovereenkomstig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zamówienie Odbioru - {receipt_number}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zamówienie odbioru. Proszę zapoznaj się ze szczegółami poniżej.</p><p><strong>Szczegóły Zamówienia Odbioru:</strong></p><ul><li>Numer odbioru: {receipt_number}</li><li>Nazwa odbioru: {receipt_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {receipt_total}</li><li>Data odbioru: {receipt_date}</li><li>Oczekiwana data odbioru: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Przypisany do:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Proszę przetworzyć to zamówienie odbioru odpowiednio.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Ordem de Recebimento - {receipt_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de recebimento. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Recebimento:</strong></p><ul><li>Número de recebimento: {receipt_number}</li><li>Nome de recebimento: {receipt_name}</li><li>Contacto: {contact_name}</li><li>Conta: {account_name}</li><li>Montante total: {receipt_total}</li><li>Data de recebimento: {receipt_date}</li><li>Data de recebimento prevista: {expected_receipt_date}</li><li>Estado: {receipt_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de recebimento em conformidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Ordem de Recebimento - {receipt_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de recebimento. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Recebimento:</strong></p><ul><li>Número de recebimento: {receipt_number}</li><li>Nome de recebimento: {receipt_name}</li><li>Contato: {contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {receipt_total}</li><li>Data de recebimento: {receipt_date}</li><li>Data de recebimento prevista: {expected_receipt_date}</li><li>Status: {receipt_status}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de recebimento adequadamente.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый заказ на получение - {receipt_number}',
                        'content' => '<p>Привет,</p><p>Создан новый заказ на получение. Пожалуйста, ознакомьтесь с деталями ниже.</p><p><strong>Детали заказа на получение:</strong></p><ul><li>Номер получения: {receipt_number}</li><li>Название получения: {receipt_name}</li><li>Контакт: {contact_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {receipt_total}</li><li>Дата получения: {receipt_date}</li><li>Ожидаемая дата получения: {expected_receipt_date}</li><li>Статус: {receipt_status}</li></ul><p><strong>Назначено:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, обработайте этот заказ на получение соответствующим образом.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Alındı Siparişi - {receipt_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir alındı siparişi oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Alındı Siparişi Detayları:</strong></p><ul><li>Alındı numarası: {receipt_number}</li><li>Alındı adı: {receipt_name}</li><li>İletişim: {contact_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {receipt_total}</li><li>Alındı tarihi: {receipt_date}</li><li>Beklenen alındı tarihi: {expected_receipt_date}</li><li>Durum: {receipt_status}</li></ul><p><strong>Atanan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen bu alındı siparişini buna göre işleme alın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新收货订单 - {receipt_number}',
                        'content' => '<p>你好，</p><p>已创建新的收货订单。请查看以下详细信息。</p><p><strong>收货订单详情：</strong></p><ul><li>收货编号：{receipt_number}</li><li>收货名称：{receipt_name}</li><li>联系人：{contact_name}</li><li>账户：{account_name}</li><li>总金额：{receipt_total}</li><li>收货日期：{receipt_date}</li><li>预计收货日期：{expected_receipt_date}</li><li>状态：{receipt_status}</li></ul><p><strong>分配给：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请相应地处理此收货订单。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Return Order Created
            [
                'name' => 'Return Order Created',
                'from' => 'Operations Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Return Order - {return_number}',
                        'content' => '<p>Hello,</p><p>A new return order has been created. Please review the details below.</p><p><strong>Return Order Details:</strong></p><ul><li>Return Number: {return_number}</li><li>Return Name: {return_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Total Amount: {return_total}</li><li>Return Date: {return_date}</li><li>Status: {return_status}</li><li>Return Reason: {return_reason}</li><li>Tracking Number: {tracking_number}</li></ul><p><strong>Assigned To:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Please process this return order accordingly.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Orden de Devolución - {return_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva orden de devolución. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Orden de Devolución:</strong></p><ul><li>Número de Devolución: {return_number}</li><li>Nombre de Devolución: {return_name}</li><li>Contacto: {contact_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {return_total}</li><li>Fecha de Devolución: {return_date}</li><li>Estado: {return_status}</li><li>Razón de Devolución: {return_reason}</li><li>Número de Seguimiento: {tracking_number}</li></ul><p><strong>Asignado a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor procese esta orden de devolución en consecuencia.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'أمر إرجاع جديد - {return_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء أمر إرجاع جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل أمر الإرجاع:</strong></p><ul><li>رقم الإرجاع: {return_number}</li><li>اسم الإرجاع: {return_name}</li><li>جهة الاتصال: {contact_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {return_total}</li><li>تاريخ الإرجاع: {return_date}</li><li>الحالة: {return_status}</li><li>سبب الإرجاع: {return_reason}</li><li>رقم التتبع: {tracking_number}</li></ul><p><strong>معين إلى:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>يرجى معالجة أمر الإرجاع هذا وفقاً لذلك.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Returordre - {return_number}',
                        'content' => '<p>Hej,</p><p>En ny returordre er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Returordre Detaljer:</strong></p><ul><li>Returnummer: {return_number}</li><li>Returnavn: {return_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {return_total}</li><li>Returdato: {return_date}</li><li>Status: {return_status}</li><li>Returårsag: {return_reason}</li><li>Sporingsnummer: {tracking_number}</li></ul><p><strong>Tildelt til:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Behandl venligst denne returordre i overensstemmelse hermed.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neue Rücksendung - {return_number}',
                        'content' => '<p>Hallo,</p><p>Eine neue Rücksendung wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Rücksendung Details:</strong></p><ul><li>Rücksendungsnummer: {return_number}</li><li>Rücksendungsname: {return_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {return_total}</li><li>Rücksendungsdatum: {return_date}</li><li>Status: {return_status}</li><li>Rücksendungsgrund: {return_reason}</li><li>Sendungsnummer: {tracking_number}</li></ul><p><strong>Zugewiesen an:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bitte bearbeiten Sie diese Rücksendung entsprechend.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Bon de Retour - {return_number}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau bon de retour a été créé. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails du Bon de Retour:</strong></p><ul><li>Numéro de retour: {return_number}</li><li>Nom de retour: {return_name}</li><li>Contact: {contact_name}</li><li>Compte: {account_name}</li><li>Montant total: {return_total}</li><li>Date de retour: {return_date}</li><li>Statut: {return_status}</li><li>Raison du retour: {return_reason}</li><li>Numéro de suivi: {tracking_number}</li></ul><p><strong>Assigné à:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Veuillez traiter ce bon de retour en conséquence.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנת החזרה חדשה - {return_number}',
                        'content' => '<p>שלום,</p><p>הזמנת החזרה חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי הזמנת ההחזרה:</strong></p><ul><li>מספר החזרה: {return_number}</li><li>שם החזרה: {return_name}</li><li>איש קשר: {contact_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {return_total}</li><li>תאריך החזרה: {return_date}</li><li>סטטוס: {return_status}</li><li>סיבת החזרה: {return_reason}</li><li>מספר מעקב: {tracking_number}</li></ul><p><strong>מוקצה ל:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>אנא עבד הזמנת החזרה זו בהתאם.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Ordine di Reso - {return_number}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo ordine di reso. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Ordine di Reso:</strong></p><ul><li>Numero reso: {return_number}</li><li>Nome reso: {return_name}</li><li>Contatto: {contact_name}</li><li>Account: {account_name}</li><li>Importo totale: {return_total}</li><li>Data reso: {return_date}</li><li>Stato: {return_status}</li><li>Motivo del reso: {return_reason}</li><li>Numero di tracciamento: {tracking_number}</li></ul><p><strong>Assegnato a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Si prega di elaborare questo ordine di reso di conseguenza.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい返品注文 - {return_number}',
                        'content' => '<p>こんにちは、</p><p>新しい返品注文が作成されました。以下の詳細をご確認ください。</p><p><strong>返品注文詳細:</strong></p><ul><li>返品番号: {return_number}</li><li>返品名: {return_name}</li><li>連絡先: {contact_name}</li><li>アカウント: {account_name}</li><li>合計金額: {return_total}</li><li>返品日: {return_date}</li><li>ステータス: {return_status}</li><li>返品理由: {return_reason}</li><li>追跡番号: {tracking_number}</li></ul><p><strong>担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>この返品注文を適切に処理してください。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Retourorder - {return_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe retourorder is aangemaakt. Controleer de onderstaande details.</p><p><strong>Retourorder Details:</strong></p><ul><li>Retournummer: {return_number}</li><li>Retournaam: {return_name}</li><li>Contact: {contact_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {return_total}</li><li>Retourdatum: {return_date}</li><li>Status: {return_status}</li><li>Retourreden: {return_reason}</li><li>Trackingnummer: {tracking_number}</li></ul><p><strong>Toegewezen aan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Verwerk deze retourorder dienovereenkomstig.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zamówienie Zwrotu - {return_number}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zamówienie zwrotu. Proszę zapoznaj się ze szczegółami poniżej.</p><p><strong>Szczegóły Zamówienia Zwrotu:</strong></p><ul><li>Numer zwrotu: {return_number}</li><li>Nazwa zwrotu: {return_name}</li><li>Kontakt: {contact_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {return_total}</li><li>Data zwrotu: {return_date}</li><li>Status: {return_status}</li><li>Powód zwrotu: {return_reason}</li><li>Numer śledzenia: {tracking_number}</li></ul><p><strong>Przypisany do:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Proszę przetworzyć to zamówienie zwrotu odpowiednio.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Ordem de Devolução - {return_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de devolução. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Devolução:</strong></p><ul><li>Número de devolução: {return_number}</li><li>Nome de devolução: {return_name}</li><li>Contacto: {contact_name}</li><li>Conta: {account_name}</li><li>Montante total: {return_total}</li><li>Data de devolução: {return_date}</li><li>Estado: {return_status}</li><li>Motivo da devolução: {return_reason}</li><li>Número de rastreamento: {tracking_number}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de devolução em conformidade.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Ordem de Devolução - {return_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de devolução. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Devolução:</strong></p><ul><li>Número de devolução: {return_number}</li><li>Nome de devolução: {return_name}</li><li>Contato: {contact_name}</li><li>Conta: {account_name}</li><li>Valor total: {return_total}</li><li>Data de devolução: {return_date}</li><li>Status: {return_status}</li><li>Motivo da devolução: {return_reason}</li><li>Número de rastreamento: {tracking_number}</li></ul><p><strong>Atribuído a:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Por favor processe esta ordem de devolução adequadamente.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый заказ на возврат - {return_number}',
                        'content' => '<p>Привет,</p><p>Создан новый заказ на возврат. Пожалуйста, ознакомьтесь с деталями ниже.</p><p><strong>Детали заказа на возврат:</strong></p><ul><li>Номер возврата: {return_number}</li><li>Название возврата: {return_name}</li><li>Контакт: {contact_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {return_total}</li><li>Дата возврата: {return_date}</li><li>Статус: {return_status}</li><li>Причина возврата: {return_reason}</li><li>Номер отслеживания: {tracking_number}</li></ul><p><strong>Назначено:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Пожалуйста, обработайте этот заказ на возврат соответствующим образом.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni İade Siparişi - {return_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir iade siparişi oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>İade Siparişi Detayları:</strong></p><ul><li>İade numarası: {return_number}</li><li>İade adı: {return_name}</li><li>İletişim: {contact_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {return_total}</li><li>İade tarihi: {return_date}</li><li>Durum: {return_status}</li><li>İade nedeni: {return_reason}</li><li>Takip numarası: {tracking_number}</li></ul><p><strong>Atanan:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Lütfen bu iade siparişini buna göre işleme alın.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新退货订单 - {return_number}',
                        'content' => '<p>你好，</p><p>已创建新的退货订单。请查看以下详细信息。</p><p><strong>退货订单详情：</strong></p><ul><li>退货编号：{return_number}</li><li>退货名称：{return_name}</li><li>联系人：{contact_name}</li><li>账户：{account_name}</li><li>总金额：{return_total}</li><li>退货日期：{return_date}</li><li>状态：{return_status}</li><li>退货原因：{return_reason}</li><li>追踪号码：{tracking_number}</li></ul><p><strong>分配给：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>请相应地处理此退货订单。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Sales Order Created
            [
                'name' => 'Sales Order Created',
                'from' => 'Sales Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Sales Order - {order_number}',
                        'content' => '<p>Hello,</p><p>A new sales order has been created. Please review the details below.</p><p><strong>Order Details:</strong></p><ul><li>Order Number: {order_number}</li><li>Order Name: {order_name}</li><li>Contact: {billing_contact_name}</li><li>Assigned To: {assigned_user_name}</li><li>Account: {account_name}</li><li>Total Amount: {order_total}</li><li>Order Date: {order_date}</li><li>Delivery Date: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Assigned Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Thank you for your business.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Orden de Venta - {order_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva orden de venta. Por favor revise los detalles a continuación.</p><p><strong>Detalles del Pedido:</strong></p><ul><li>Número de Pedido: {order_number}</li><li>Nombre del Pedido: {order_name}</li><li>Contacto: {billing_contact_name}</li><li>Asignado a: {assigned_user_name}</li><li>Cuenta: {account_name}</li><li>Monto Total: {order_total}</li><li>Fecha del Pedido: {order_date}</li><li>Fecha de Entrega: {delivery_date}</li><li>Estado: {order_status}</li></ul><p><strong>Representante Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Gracias por su negocio.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'طلب مبيعات جديد - {order_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء طلب مبيعات جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل الطلب:</strong></p><ul><li>رقم الطلب: {order_number}</li><li>اسم الطلب: {order_name}</li><li>جهة الاتصال: {billing_contact_name}</li><li>معين إلى: {assigned_user_name}</li><li>الحساب: {account_name}</li><li>المبلغ الإجمالي: {order_total}</li><li>تاريخ الطلب: {order_date}</li><li>تاريخ التسليم: {delivery_date}</li><li>الحالة: {order_status}</li></ul><p><strong>الممثل المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>شكراً لتعاملكم معنا.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Salgsordre - {order_number}',
                        'content' => '<p>Hej,</p><p>En ny salgsordre er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Ordre Detaljer:</strong></p><ul><li>Ordrenummer: {order_number}</li><li>Ordrenavn: {order_name}</li><li>Kontakt: {billing_contact_name}</li><li>Tildelt til: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Samlet beløb: {order_total}</li><li>Ordredato: {order_date}</li><li>Leveringsdato: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Tildelt Repræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Tak for din forretning.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neuer Verkaufsauftrag - {order_number}',
                        'content' => '<p>Hallo,</p><p>Ein neuer Verkaufsauftrag wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Auftragsdetails:</strong></p><ul><li>Auftragsnummer: {order_number}</li><li>Auftragsname: {order_name}</li><li>Kontakt: {billing_contact_name}</li><li>Zugewiesen an: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Gesamtbetrag: {order_total}</li><li>Auftragsdatum: {order_date}</li><li>Lieferdatum: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Zugewiesener Vertreter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Vielen Dank für Ihr Geschäft.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouvelle Commande - {order_number}',
                        'content' => '<p>Bonjour,</p><p>Une nouvelle commande a été créée. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails de la Commande:</strong></p><ul><li>Numéro de commande: {order_number}</li><li>Nom de la commande: {order_name}</li><li>Contact: {billing_contact_name}</li><li>Assigné à: {assigned_user_name}</li><li>Compte: {account_name}</li><li>Montant total: {order_total}</li><li>Date de commande: {order_date}</li><li>Date de livraison: {delivery_date}</li><li>Statut: {order_status}</li></ul><p><strong>Représentant Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Merci pour votre confiance.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנת מכירה חדשה - {order_number}',
                        'content' => '<p>שלום,</p><p>הזמנת מכירה חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי ההזמנה:</strong></p><ul><li>מספר הזמנה: {order_number}</li><li>שם הזמנה: {order_name}</li><li>איש קשר: {billing_contact_name}</li><li>מוקצה ל: {assigned_user_name}</li><li>חשבון: {account_name}</li><li>סכום כולל: {order_total}</li><li>תאריך הזמנה: {order_date}</li><li>תאריך אספקה: {delivery_date}</li><li>סטטוס: {order_status}</li></ul><p><strong>נציג מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>תודה על העסק שלך.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Ordine di Vendita - {order_number}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo ordine di vendita. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Ordine:</strong></p><ul><li>Numero ordine: {order_number}</li><li>Nome ordine: {order_name}</li><li>Contatto: {billing_contact_name}</li><li>Assegnato a: {assigned_user_name}</li><li>Account: {account_name}</li><li>Importo totale: {order_total}</li><li>Data ordine: {order_date}</li><li>Data di consegna: {delivery_date}</li><li>Stato: {order_status}</li></ul><p><strong>Rappresentante Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Grazie per il tuo business.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい販売注文 - {order_number}',
                        'content' => '<p>こんにちは、</p><p>新しい販売注文が作成されました。以下の詳細を確認してください。</p><p><strong>注文詳細：</strong></p><ul><li>注文番号：{order_number}</li><li>注文名：{order_name}</li><li>連絡先：{billing_contact_name}</li><li>担当者：{assigned_user_name}</li><li>アカウント：{account_name}</li><li>合計金額：{order_total}</li><li>注文日：{order_date}</li><li>配送日：{delivery_date}</li><li>ステータス：{order_status}</li></ul><p><strong>担当担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>ご利用ありがとうございます。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Verkooporder - {order_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe verkooporder is aangemaakt. Controleer de onderstaande details.</p><p><strong>Order Details:</strong></p><ul><li>Ordernummer: {order_number}</li><li>Ordernaam: {order_name}</li><li>Contact: {billing_contact_name}</li><li>Toegewezen aan: {assigned_user_name}</li><li>Account: {account_name}</li><li>Totaalbedrag: {order_total}</li><li>Orderdatum: {order_date}</li><li>Leverdatum: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bedankt voor je bestelling.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zamówienie Sprzedaży - {order_number}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zamówienie sprzedaży. Proszę zapoznaj się ze szczegółami poniżej.</p><p><strong>Szczegóły Zamówienia:</strong></p><ul><li>Numer zamówienia: {order_number}</li><li>Nazwa zamówienia: {order_name}</li><li>Kontakt: {billing_contact_name}</li><li>Przypisany do: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Łączna kwota: {order_total}</li><li>Data zamówienia: {order_date}</li><li>Data dostawy: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Przypisany Przedstawiciel:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Dziękujemy za współpracę.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Encomenda de Venda - {order_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova encomenda de venda. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Encomenda:</strong></p><ul><li>Número da encomenda: {order_number}</li><li>Nome da encomenda: {order_name}</li><li>Contacto: {billing_contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Conta: {account_name}</li><li>Montante total: {order_total}</li><li>Data da encomenda: {order_date}</li><li>Data de entrega: {delivery_date}</li><li>Estado: {order_status}</li></ul><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu negócio.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Novo Pedido de Venda - {order_number}',
                        'content' => '<p>Olá,</p><p>Foi criado um novo pedido de venda. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes do Pedido:</strong></p><ul><li>Número do pedido: {order_number}</li><li>Nome do pedido: {order_name}</li><li>Contato: {billing_contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Conta: {account_name}</li><li>Valor total: {order_total}</li><li>Data do pedido: {order_date}</li><li>Data de entrega: {delivery_date}</li><li>Status: {order_status}</li></ul><p><strong>Representante Designado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu negócio.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый заказ на продажу - {order_number}',
                        'content' => '<p>Привет,</p><p>Создан новый заказ на продажу. Пожалуйста, ознакомьтесь с деталями ниже.</p><p><strong>Детали заказа:</strong></p><ul><li>Номер заказа: {order_number}</li><li>Название заказа: {order_name}</li><li>Контакт: {billing_contact_name}</li><li>Назначено: {assigned_user_name}</li><li>Аккаунт: {account_name}</li><li>Общая сумма: {order_total}</li><li>Дата заказа: {order_date}</li><li>Дата доставки: {delivery_date}</li><li>Статус: {order_status}</li></ul><p><strong>Назначенный представитель:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Спасибо за ваш бизнес.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Satış Siparişi - {order_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir satış siparişi oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Sipariş Detayları:</strong></p><ul><li>Sipariş numarası: {order_number}</li><li>Sipariş adı: {order_name}</li><li>İletişim: {billing_contact_name}</li><li>Atanan: {assigned_user_name}</li><li>Hesap: {account_name}</li><li>Toplam tutar: {order_total}</li><li>Sipariş tarihi: {order_date}</li><li>Teslimat tarihi: {delivery_date}</li><li>Durum: {order_status}</li></ul><p><strong>Atanan Temsilci:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>İşiniz için teşekkür ederiz.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新销售订单 - {order_number}',
                        'content' => '<p>你好，</p><p>已创建新的销售订单。请查看以下详情。</p><p><strong>订单详情：</strong></p><ul><li>订单编号：{order_number}</li><li>订单名称：{order_name}</li><li>联系人：{billing_contact_name}</li><li>分配给：{assigned_user_name}</li><li>账户：{account_name}</li><li>总金额：{order_total}</li><li>订单日期：{order_date}</li><li>交货日期：{delivery_date}</li><li>状态：{order_status}</li></ul><p><strong>指定代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>感谢您的业务。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
            // Delivery Order Created
            [
                'name' => 'Delivery Order Created',
                'from' => 'Operations Team',
                'translations' => [
                    'en' => [
                        'subject' => 'New Delivery Order - {delivery_order_number}',
                        'content' => '<p>Hello,</p><p>A new delivery order has been created. Please review the details below.</p><p><strong>Delivery Order Details:</strong></p><ul><li>Delivery Order Number: {delivery_order_number}</li><li>Delivery Order Name: {delivery_order_name}</li><li>Contact: {contact_name}</li><li>Assigned To: {assigned_user_name}</li><li>Account: {account_name}</li><li>Delivery Date: {delivery_date}</li><li>Expected Delivery Date: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Tracking Number: {tracking_number}</li></ul><p><strong>Assigned Representative:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Thank you for your business.</p><p style="text-align: right;">Best regards,<br>{company_name}</p>'
                    ],
                    'es' => [
                        'subject' => 'Nueva Orden de Entrega - {delivery_order_number}',
                        'content' => '<p>Hola,</p><p>Se ha creado una nueva orden de entrega. Por favor revise los detalles a continuación.</p><p><strong>Detalles de la Orden de Entrega:</strong></p><ul><li>Número de Orden de Entrega: {delivery_order_number}</li><li>Nombre de Orden de Entrega: {delivery_order_name}</li><li>Contacto: {contact_name}</li><li>Asignado a: {assigned_user_name}</li><li>Cuenta: {account_name}</li><li>Fecha de Entrega: {delivery_date}</li><li>Fecha de Entrega Esperada: {expected_delivery_date}</li><li>Estado: {delivery_status}</li><li>Número de Seguimiento: {tracking_number}</li></ul><p><strong>Representante Asignado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Gracias por su negocio.</p><p style="text-align: right;">Saludos cordiales,<br>{company_name}</p>'
                    ],
                    'ar' => [
                        'subject' => 'طلب توصيل جديد - {delivery_order_number}',
                        'content' => '<p>مرحباً،</p><p>تم إنشاء طلب توصيل جديد. يرجى مراجعة التفاصيل أدناه.</p><p><strong>تفاصيل طلب التوصيل:</strong></p><ul><li>رقم طلب التوصيل: {delivery_order_number}</li><li>اسم طلب التوصيل: {delivery_order_name}</li><li>جهة الاتصال: {contact_name}</li><li>معين إلى: {assigned_user_name}</li><li>الحساب: {account_name}</li><li>تاريخ التوصيل: {delivery_date}</li><li>تاريخ التوصيل المتوقع: {expected_delivery_date}</li><li>الحالة: {delivery_status}</li><li>رقم التتبع: {tracking_number}</li></ul><p><strong>الممثل المعين:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>شكراً لتعاملكم معنا.</p><p style="text-align: right;">مع أطيب التحيات،<br>{company_name}</p>'
                    ],
                    'da' => [
                        'subject' => 'Ny Leveringsordre - {delivery_order_number}',
                        'content' => '<p>Hej,</p><p>En ny leveringsordre er blevet oprettet. Gennemgå venligst detaljerne nedenfor.</p><p><strong>Leveringsordre Detaljer:</strong></p><ul><li>Leveringsordrenummer: {delivery_order_number}</li><li>Leveringsordrenavn: {delivery_order_name}</li><li>Kontakt: {contact_name}</li><li>Tildelt til: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Leveringsdato: {delivery_date}</li><li>Forventet leveringsdato: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Sporingsnummer: {tracking_number}</li></ul><p><strong>Tildelt Repræsentant:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Tak for din forretning.</p><p style="text-align: right;">Med venlig hilsen,<br>{company_name}</p>'
                    ],
                    'de' => [
                        'subject' => 'Neuer Lieferauftrag - {delivery_order_number}',
                        'content' => '<p>Hallo,</p><p>Ein neuer Lieferauftrag wurde erstellt. Bitte überprüfen Sie die Details unten.</p><p><strong>Lieferauftrag Details:</strong></p><ul><li>Lieferauftragsnummer: {delivery_order_number}</li><li>Lieferauftragsname: {delivery_order_name}</li><li>Kontakt: {contact_name}</li><li>Zugewiesen an: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Lieferdatum: {delivery_date}</li><li>Erwartetes Lieferdatum: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Sendungsnummer: {tracking_number}</li></ul><p><strong>Zugewiesener Vertreter:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Vielen Dank für Ihr Geschäft.</p><p style="text-align: right;">Mit freundlichen Grüßen,<br>{company_name}</p>'
                    ],
                    'fr' => [
                        'subject' => 'Nouveau Bon de Livraison - {delivery_order_number}',
                        'content' => '<p>Bonjour,</p><p>Un nouveau bon de livraison a été créé. Veuillez consulter les détails ci-dessous.</p><p><strong>Détails du Bon de Livraison:</strong></p><ul><li>Numéro de bon de livraison: {delivery_order_number}</li><li>Nom du bon de livraison: {delivery_order_name}</li><li>Contact: {contact_name}</li><li>Assigné à: {assigned_user_name}</li><li>Compte: {account_name}</li><li>Date de livraison: {delivery_date}</li><li>Date de livraison prévue: {expected_delivery_date}</li><li>Statut: {delivery_status}</li><li>Numéro de suivi: {tracking_number}</li></ul><p><strong>Représentant Assigné:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Merci pour votre confiance.</p><p style="text-align: right;">Cordialement,<br>{company_name}</p>'
                    ],
                    'he' => [
                        'subject' => 'הזמנת משלוח חדשה - {delivery_order_number}',
                        'content' => '<p>שלום,</p><p>הזמנת משלוח חדשה נוצרה. אנא עיין בפרטים למטה.</p><p><strong>פרטי הזמנת המשלוח:</strong></p><ul><li>מספר הזמנת משלוח: {delivery_order_number}</li><li>שם הזמנת משלוח: {delivery_order_name}</li><li>איש קשר: {contact_name}</li><li>מוקצה ל: {assigned_user_name}</li><li>חשבון: {account_name}</li><li>תאריך משלוח: {delivery_date}</li><li>תאריך משלוח צפוי: {expected_delivery_date}</li><li>סטטוס: {delivery_status}</li><li>מספר מעקב: {tracking_number}</li></ul><p><strong>נציג מוקצה:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>תודה על העסק שלך.</p><p style="text-align: right;">בברכה,<br>{company_name}</p>'
                    ],
                    'it' => [
                        'subject' => 'Nuovo Ordine di Consegna - {delivery_order_number}',
                        'content' => '<p>Ciao,</p><p>È stato creato un nuovo ordine di consegna. Si prega di rivedere i dettagli qui sotto.</p><p><strong>Dettagli Ordine di Consegna:</strong></p><ul><li>Numero ordine di consegna: {delivery_order_number}</li><li>Nome ordine di consegna: {delivery_order_name}</li><li>Contatto: {contact_name}</li><li>Assegnato a: {assigned_user_name}</li><li>Account: {account_name}</li><li>Data di consegna: {delivery_date}</li><li>Data di consegna prevista: {expected_delivery_date}</li><li>Stato: {delivery_status}</li><li>Numero di tracciamento: {tracking_number}</li></ul><p><strong>Rappresentante Assegnato:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Grazie per il tuo business.</p><p style="text-align: right;">Cordiali saluti,<br>{company_name}</p>'
                    ],
                    'ja' => [
                        'subject' => '新しい配送注文 - {delivery_order_number}',
                        'content' => '<p>こんにちは、</p><p>新しい配送注文が作成されました。以下の詳細を確認してください。</p><p><strong>配送注文詳細：</strong></p><ul><li>配送注文番号：{delivery_order_number}</li><li>配送注文名：{delivery_order_name}</li><li>連絡先：{contact_name}</li><li>担当者：{assigned_user_name}</li><li>アカウント：{account_name}</li><li>配送日：{delivery_date}</li><li>配送予定日：{expected_delivery_date}</li><li>ステータス：{delivery_status}</li><li>追跡番号：{tracking_number}</li></ul><p><strong>担当担当者:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>ご利用ありがとうございます。</p><p style="text-align: right;">よろしくお願いします、<br>{company_name}</p>'
                    ],
                    'nl' => [
                        'subject' => 'Nieuwe Leveringsorder - {delivery_order_number}',
                        'content' => '<p>Hallo,</p><p>Een nieuwe leveringsorder is aangemaakt. Controleer de onderstaande details.</p><p><strong>Leveringsorder Details:</strong></p><ul><li>Leveringsordernummer: {delivery_order_number}</li><li>Leveringsordernaam: {delivery_order_name}</li><li>Contact: {contact_name}</li><li>Toegewezen aan: {assigned_user_name}</li><li>Account: {account_name}</li><li>Leverdatum: {delivery_date}</li><li>Verwachte leverdatum: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Trackingnummer: {tracking_number}</li></ul><p><strong>Toegewezen Vertegenwoordiger:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Bedankt voor je bestelling.</p><p style="text-align: right;">Met vriendelijke groet,<br>{company_name}</p>'
                    ],
                    'pl' => [
                        'subject' => 'Nowe Zamówienie Dostawy - {delivery_order_number}',
                        'content' => '<p>Witaj,</p><p>Utworzono nowe zamówienie dostawy. Proszę zapoznaj się ze szczegółami poniżej.</p><p><strong>Szczegóły Zamówienia Dostawy:</strong></p><ul><li>Numer zamówienia dostawy: {delivery_order_number}</li><li>Nazwa zamówienia dostawy: {delivery_order_name}</li><li>Kontakt: {contact_name}</li><li>Przypisany do: {assigned_user_name}</li><li>Konto: {account_name}</li><li>Data dostawy: {delivery_date}</li><li>Oczekiwana data dostawy: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Numer śledzenia: {tracking_number}</li></ul><p><strong>Przypisany Przedstawiciel:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Dziękujemy za współpracę.</p><p style="text-align: right;">Z poważaniem,<br>{company_name}</p>'
                    ],
                    'pt' => [
                        'subject' => 'Nova Ordem de Entrega - {delivery_order_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de entrega. Por favor reveja os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Entrega:</strong></p><ul><li>Número da ordem de entrega: {delivery_order_number}</li><li>Nome da ordem de entrega: {delivery_order_name}</li><li>Contacto: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Conta: {account_name}</li><li>Data de entrega: {delivery_date}</li><li>Data de entrega prevista: {expected_delivery_date}</li><li>Estado: {delivery_status}</li><li>Número de rastreamento: {tracking_number}</li></ul><p><strong>Representante Atribuído:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu negócio.</p><p style="text-align: right;">Cumprimentos,<br>{company_name}</p>'
                    ],
                    'pt-BR' => [
                        'subject' => 'Nova Ordem de Entrega - {delivery_order_number}',
                        'content' => '<p>Olá,</p><p>Foi criada uma nova ordem de entrega. Por favor revise os detalhes abaixo.</p><p><strong>Detalhes da Ordem de Entrega:</strong></p><ul><li>Número da ordem de entrega: {delivery_order_number}</li><li>Nome da ordem de entrega: {delivery_order_name}</li><li>Contato: {contact_name}</li><li>Atribuído a: {assigned_user_name}</li><li>Conta: {account_name}</li><li>Data de entrega: {delivery_date}</li><li>Data de entrega prevista: {expected_delivery_date}</li><li>Status: {delivery_status}</li><li>Número de rastreamento: {tracking_number}</li></ul><p><strong>Representante Designado:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Obrigado pelo seu negócio.</p><p style="text-align: right;">Atenciosamente,<br>{company_name}</p>'
                    ],
                    'ru' => [
                        'subject' => 'Новый заказ на доставку - {delivery_order_number}',
                        'content' => '<p>Привет,</p><p>Создан новый заказ на доставку. Пожалуйста, ознакомьтесь с деталями ниже.</p><p><strong>Детали заказа на доставку:</strong></p><ul><li>Номер заказа на доставку: {delivery_order_number}</li><li>Название заказа на доставку: {delivery_order_name}</li><li>Контакт: {contact_name}</li><li>Назначено: {assigned_user_name}</li><li>Аккаунт: {account_name}</li><li>Дата доставки: {delivery_date}</li><li>Ожидаемая дата доставки: {expected_delivery_date}</li><li>Статус: {delivery_status}</li><li>Номер отслеживания: {tracking_number}</li></ul><p><strong>Назначенный представитель:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>Спасибо за ваш бизнес.</p><p style="text-align: right;">С уважением,<br>{company_name}</p>'
                    ],
                    'tr' => [
                        'subject' => 'Yeni Teslimat Siparişi - {delivery_order_number}',
                        'content' => '<p>Merhaba,</p><p>Yeni bir teslimat siparişi oluşturuldu. Lütfen aşağıdaki detayları inceleyin.</p><p><strong>Teslimat Siparişi Detayları:</strong></p><ul><li>Teslimat siparişi numarası: {delivery_order_number}</li><li>Teslimat siparişi adı: {delivery_order_name}</li><li>İletişim: {contact_name}</li><li>Atanan: {assigned_user_name}</li><li>Hesap: {account_name}</li><li>Teslimat tarihi: {delivery_date}</li><li>Beklenen teslimat tarihi: {expected_delivery_date}</li><li>Durum: {delivery_status}</li><li>Takip numarası: {tracking_number}</li></ul><p><strong>Atanan Temsilci:</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>İşiniz için teşekkür ederiz.</p><p style="text-align: right;">Saygılarımızla,<br>{company_name}</p>'
                    ],
                    'zh' => [
                        'subject' => '新配送订单 - {delivery_order_number}',
                        'content' => '<p>你好，</p><p>已创建新的配送订单。请查看以下详情。</p><p><strong>配送订单详情：</strong></p><ul><li>配送订单编号：{delivery_order_number}</li><li>配送订单名称：{delivery_order_name}</li><li>联系人：{contact_name}</li><li>分配给：{assigned_user_name}</li><li>账户：{account_name}</li><li>配送日期：{delivery_date}</li><li>预计配送日期：{expected_delivery_date}</li><li>状态：{delivery_status}</li><li>追踪号码：{tracking_number}</li></ul><p><strong>指定代表：</strong></p><p>{assigned_user_name} - {assigned_user_email}</p><p>感谢您的业务。</p><p style="text-align: right;">此致敬礼，<br>{company_name}</p>'
                    ]
                ]
            ],
        ];

        foreach ($templates as $templateData) {
            $existingTemplate = EmailTemplate::where('name', $templateData['name'])->first();

            if ($existingTemplate) {
                continue;
            }

            $template = EmailTemplate::create([
                'name' => $templateData['name'],
                'from' => $templateData['from'],
                'user_id' => 1
            ]);

            foreach ($langCodes as $langCode) {
                $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                EmailTemplateLang::create([
                    'parent_id' => $template->id,
                    'lang' => $langCode,
                    'subject' => $translation['subject'],
                    'content' => $translation['content']
                ]);
            }

            UserEmailTemplate::create([
                'template_id' => $template->id,
                'user_id' => 1,
                'is_active' => true
            ]);
        }
    }
}
