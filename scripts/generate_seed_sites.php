#!/usr/bin/env php
<?php
/**
 * Генератор тестовых сайтов для каталога (шаг 22)
 *
 * Использование:
 *   php scripts/generate_seed_sites.php [--mode=production|test] [--per-section=25]
 *
 *   --mode=production  генерирует migrations/004_seed_sites.sql (15-35 сайтов на раздел)
 *   --mode=test        генерирует готовый tests/fixtures/db-seed.sql (минимум для тестов)
 *   --per-section=N    число сайтов на конечный раздел (по умолчанию 25)
 */

declare(strict_types=1);

// ─── Конфигурация ────────────────────────────────────────────────

$options = getopt('', ['mode:', 'per-section:']);
$mode = $options['mode'] ?? 'production';
$perSection = (int) ($options['per-section'] ?? 25);
$perSection = max(15, min(35, $perSection));

// ─── Разделы (из 002_seed_data.sql) ──────────────────────────────

$sections = [
    ['id' => 1,  'parent_id' => null, 'name' => 'Дом и интерьер',       'slug' => 'dom-i-interer',      'theme' => 'home'],
    ['id' => 6,  'parent_id' => 1,    'name' => 'Мебель',                'slug' => 'mebel',              'theme' => 'home'],
    ['id' => 7,  'parent_id' => 1,    'name' => 'Декор',                 'slug' => 'dekor',              'theme' => 'home'],
    ['id' => 8,  'parent_id' => 1,    'name' => 'Текстиль',              'slug' => 'tekstil',            'theme' => 'home'],
    ['id' => 9,  'parent_id' => 1,    'name' => 'Освещение',             'slug' => 'osveschenie',        'theme' => 'home'],
    ['id' => 10, 'parent_id' => 1,    'name' => 'Ремонт и отделка',      'slug' => 'remont-i-otdelka',   'theme' => 'home'],
    ['id' => 11, 'parent_id' => 1,    'name' => 'Другое',                'slug' => 'dom-drugoe',         'theme' => 'home'],

    ['id' => 2,  'parent_id' => null, 'name' => 'Сад и огород',          'slug' => 'sad-i-ogorod',       'theme' => 'garden'],
    ['id' => 12, 'parent_id' => 2,    'name' => 'Растения',              'slug' => 'rasteniya',          'theme' => 'garden'],
    ['id' => 13, 'parent_id' => 2,    'name' => 'Инструменты',           'slug' => 'instrumenty',        'theme' => 'garden'],
    ['id' => 14, 'parent_id' => 2,    'name' => 'Ландшафтный дизайн',    'slug' => 'landshaftnyi-dizain','theme' => 'garden'],
    ['id' => 15, 'parent_id' => 2,    'name' => 'Теплицы и парники',     'slug' => 'teplicy-i-parniki',  'theme' => 'garden'],
    ['id' => 16, 'parent_id' => 2,    'name' => 'Другое',                'slug' => 'sad-drugoe',         'theme' => 'garden'],

    ['id' => 3,  'parent_id' => null, 'name' => 'Кулинария',             'slug' => 'kulinariya',         'theme' => 'cooking'],
    ['id' => 17, 'parent_id' => 3,    'name' => 'Рецепты',               'slug' => 'recepty',            'theme' => 'cooking'],
    ['id' => 18, 'parent_id' => 3,    'name' => 'Посуда и техника',      'slug' => 'posuda-i-tehnika',   'theme' => 'cooking'],
    ['id' => 19, 'parent_id' => 3,    'name' => 'Напитки',               'slug' => 'napitki',            'theme' => 'cooking'],
    ['id' => 20, 'parent_id' => 3,    'name' => 'Этикет и сервировка',   'slug' => 'etiket-i-servirovka','theme' => 'cooking'],
    ['id' => 21, 'parent_id' => 3,    'name' => 'Другое',                'slug' => 'kulinariya-drugoe',  'theme' => 'cooking'],

    ['id' => 4,  'parent_id' => null, 'name' => 'Дети и развитие',       'slug' => 'deti-i-razvitie',    'theme' => 'kids'],
    ['id' => 22, 'parent_id' => 4,    'name' => 'Игрушки',               'slug' => 'igrushki',           'theme' => 'kids'],
    ['id' => 23, 'parent_id' => 4,    'name' => 'Детская комната',       'slug' => 'detskaya-komnata',   'theme' => 'kids'],
    ['id' => 24, 'parent_id' => 4,    'name' => 'Образование',           'slug' => 'obrazovanie',        'theme' => 'kids'],
    ['id' => 25, 'parent_id' => 4,    'name' => 'Одежда и обувь',        'slug' => 'odezhda-i-obuv',     'theme' => 'kids'],
    ['id' => 26, 'parent_id' => 4,    'name' => 'Другое',                'slug' => 'deti-drugoe',        'theme' => 'kids'],

    ['id' => 5,  'parent_id' => null, 'name' => 'Здоровье и спорт',      'slug' => 'zdorovie-i-sport',   'theme' => 'health'],
    ['id' => 27, 'parent_id' => 5,    'name' => 'Фитнес и тренировки',   'slug' => 'fitnes-i-trenirovki','theme' => 'health'],
    ['id' => 28, 'parent_id' => 5,    'name' => 'Правильное питание',    'slug' => 'pravilnoe-pitanie',  'theme' => 'health'],
    ['id' => 29, 'parent_id' => 5,    'name' => 'Медицина',              'slug' => 'medicina',           'theme' => 'health'],
    ['id' => 30, 'parent_id' => 5,    'name' => 'Инвентарь',             'slug' => 'inventar',           'theme' => 'health'],
    ['id' => 31, 'parent_id' => 5,    'name' => 'Другое',                'slug' => 'zdorovie-drugoe',    'theme' => 'health'],
];

// ─── Данные для генерации по тематикам ──────────────────────────

$namePool = [
    'home' => [
        'МебельПро', 'ДомКомфорт', 'СтильМебель', 'МягкийДом', 'ШкафыОнлайн',
        'ДиванЛаб', 'КроватьГрупп', 'СтолСтул', 'МебельГрад', 'WoodCraft',
        'ИнтерьерМаркет', 'ДекорХаус', 'СтильныйДом', 'АртДекор', 'ДизайнСтудия',
        'КраскиДома', 'ОбоиПро', 'СантехникаОнлайн', 'ПлиткаМир', 'РемонтЭксперт',
        'ЛаминатПро', 'КоврыРу', 'ШторыДом', 'ТекстильМаркет', 'ПодушкаПро',
        'СветДома', 'ЛюстрыОнлайн', 'БраСтиль', 'ТоршерМир', 'ЛедЛента',
        'ГарнитурПро', 'КухниСтиль', 'ФасадМаркет', 'ЗеркалаАрт', 'КартиныДекор',
    ],
    'garden' => [
        'СадМаркет', 'ОгородПро', 'РастенияОнлайн', 'СеменаПочтой', 'ГазонГрин',
        'ЦветыСад', 'СаженцыДом', 'РозыМир', 'ОвощиГрад', 'ТраваГазон',
        'ИнструментБокс', 'ЛопатаПро', 'ТриммерМаркет', 'СекаторОнлайн', 'ГазонокосилкаПро',
        'ЛандшафтДизайн', 'СадовыйРай', 'ПрудДекор', 'ДорожкиСад', 'ДренажСистемы',
        'ТеплицаПро', 'ПарникМаркет', 'ПоликарбонатОнлайн', 'ПоливАвто', 'СтеллажСад',
        'ГрунтМаркет', 'УдобренияПро', 'Фитосвет', 'ГоршкиСад', 'БиоГрунт',
    ],
    'cooking' => [
        'КулинарнаяКнига', 'РецептДня', 'ВкусныйБлог', 'ШефПовар', 'ГотовимДома',
        'ВыпечкаПро', 'СупыОнлайн', 'СалатМаркет', 'ДесертГрад', 'ЗаготовкиДом',
        'ПосудаМаркет', 'ТехникаКухни', 'СковородаПро', 'КастрюляМир', 'БлендерОнлайн',
        'МультиваркаПро', 'ХлебопечкаДом', 'НожиШеф', 'СервизМаркет', 'КухонныйМир',
        'КофеГрад', 'ЧайныйКлуб', 'КоктейльБар', 'ЛимонадДом', 'КомпотМаркет',
        'ЭтикетСтиль', 'СервировкаПро', 'СалфеткаМаркет', 'БлюдоДекор', 'ПодачаБлюд',
    ],
    'kids' => [
        'ДетскийМир', 'ИгрушкиДом', 'КонструкторПро', 'КуклыМаркет', 'МягкиеИгрушкиОнлайн',
        'НастольныеИгры', 'МашинкиДетям', 'Развивашка', 'УмныйРебенок', 'ИгрыДети',
        'ДетскаяМебель', 'КроваткаМаркет', 'СтолДетский', 'ШкафДети', 'КоврикИгровой',
        'ОбразованиеОнлайн', 'КружкиДети', 'ШколаОнлайн', 'УчебникМаркет', 'РаннееРазвитие',
        'ОдеждаДетям', 'ОбувьДети', 'БебиМода', 'ПодростокСтиль', 'МалышМаркет',
        'КонструкторМир', 'ПазлыДети', 'ТворчествоДом', 'Лего Маркет', 'РоботИгрушки',
    ],
    'health' => [
        'ФитнесДома', 'ЙогаМир', 'КардиоПро', 'ПохудениеОнлайн', 'ТренингГрупп',
        'СиловойТренинг', 'ПилатесСтудия', 'РастяжкаПро', 'КроссфитМаркет', 'ЗумбаДэнс',
        'ПравильноеПитание', 'РецептыПП', 'КалорияМир', 'БжуМаркет', 'ДоставкаЕды',
        'МедицинаОнлайн', 'КлиникаПро', 'ВрачДом', 'ДиагностикаМир', 'СтоматологияГрад',
        'АптекаОнлайн', 'ЗдоровьеМаркет', 'ВитаминыПро', 'БадыМир', 'ЛекарстваДом',
        'ИнвентарьСпорт', 'ГантелиПро', 'КоврикЙога', 'ВелотренажерМаркет', 'МассажерДом',
        'ФитнесБраслет', 'ТрекерАктивности', 'СпортПит', 'ПротеинМаркет', 'ДобавкиСпорт',
    ],
];

$domainPool = [
    'home' => [
        'mebelpro.ru', 'domcomfort.ru', 'stylemebel.ru', 'divanlab.ru', 'intermarket.ru',
        'dekorhaus.ru', 'stildom.ru', 'artdecor.pro', 'designstd.ru', 'kraskidoma.ru',
        'oboipro.ru', 'santehnika.online', 'plitkamir.ru', 'remontexpert.pro', 'laminatpro.ru',
        'kovry.ru', 'shtorydom.ru', 'tekstilmarket.ru', 'podushka.shop', 'svetdoma.ru',
        'lyustry.online', 'brastyle.ru', 'torshermir.ru', 'ledlenta.shop', 'garniturpro.ru',
        'kuhnistyle.ru', 'fasadmarket.ru', 'zerkala.art', 'kartinydekor.ru', 'woodeco.pro',
    ],
    'garden' => [
        'sadmarket.ru', 'ogorod.pro', 'plants-online.ru', 'semena.shop', 'gazon.green',
        'tsvetysad.ru', 'sazhentsy.dom', 'rozymir.ru', 'ovoshi.grad', 'trava.gazon',
        'instrumentbox.ru', 'lopata.pro', 'trimmer.market', 'sekator.online', 'gazonopro.ru',
        'landschaft.pro', 'sadovyrai.ru', 'pruddekor.ru', 'dorozhkisad.ru', 'drenazh.systems',
        'teplitsa.pro', 'parnik.market', 'polikarbonat.online', 'poliv.auto', 'stellazh.sad',
        'gruntmarket.ru', 'udobrenia.pro', 'fitosvet.shop', 'gorshki.sad', 'biogrunt.ru',
    ],
    'cooking' => [
        'cookbook.ru', 'retseptdnya.ru', 'vkusnyiblog.ru', 'shefpovar.pro', 'gotovimdoma.ru',
        'vipechka.pro', 'supy.online', 'salatmarket.ru', 'desertgrad.ru', 'zagotovki.dom',
        'posudamarket.ru', 'tehnikakuhni.ru', 'skovoroda.pro', 'kastrulyamir.ru', 'blender.online',
        'multivarka.pro', 'hlebopechka.dom', 'nozhishef.ru', 'servizmarket.ru', 'kuhonnyimir.ru',
        'kofegrad.ru', 'chainyiklub.ru', 'kokteil.bar', 'limonad.dom', 'kompotmarket.ru',
        'etiketsyle.ru', 'servirovka.pro', 'salfetka.market', 'bludodekor.ru', 'podacha.blud',
    ],
    'kids' => [
        'detskiymir.ru', 'igrushkidom.ru', 'konstruktor.pro', 'kukly.market', 'myagkieigrushki.online',
        'nastolnyeigry.ru', 'mashinkidetyam.ru', 'razvivashka.pro', 'umnyirebenok.ru', 'igrydeti.ru',
        'detskayamebel.shop', 'krovatka.market', 'stoldetskiy.ru', 'shkafdeti.ru', 'kovrikigrovoy.ru',
        'obrazovanie.online', 'kruzhkideti.ru', 'shkola.online', 'uchebnik.market', 'ranneerazvitie.pro',
        'odezhdadetyam.ru', 'obuvdeti.ru', 'bebi.mod', 'podrostok.style', 'malyshmarket.ru',
        'konstruktormir.ru', 'pazlydeti.ru', 'tvorchestvo.dom', 'legomarket.shop', 'robotigrushki.ru',
    ],
    'health' => [
        'fitnesdoma.ru', 'yogamir.ru', 'kardio.pro', 'pohudenie.online', 'treninggroup.ru',
        'silovoitrening.ru', 'pilatesstudia.ru', 'rastyazhka.pro', 'krossfitmarket.ru', 'zumbadance.ru',
        'pravilnoepitanie.ru', 'retseptypp.ru', 'kaloriyamir.ru', 'bzhumarket.ru', 'dostavkaedy.pro',
        'medicina.online', 'klinika.pro', 'vrachdom.ru', 'diagnostikamir.ru', 'stomatologigrad.ru',
        'apteka.online', 'zdoroviemarket.ru', 'vitaminy.pro', 'badymir.ru', 'lekarstva.dom',
        'inventarsport.ru', 'ganteli.pro', 'kovrik.yoga', 'velotrenazher.market', 'massazherdom.ru',
        'fitnesbraslet.shop', 'trekeraktivnosti.ru', 'sportpit.pro', 'proteinmarket.ru', 'dobavkisport.ru',
    ],
];

$descriptionPool = [
    'home' => [
        'Интернет-магазин {$name}. Широкий ассортимент, доставка по всей России. Более 5000 товаров в наличии.',
        'Профессиональный подход к {$name}. Консультации дизайнеров, выезд на замер, изготовление на заказ.',
        '{$name} — ваш надёжный партнёр в мире интерьера. Качественные материалы, современный дизайн, доступные цены.',
        'Всё для вашего дома: {$name}. Прямые поставки от производителей, гарантия качества, быстрая доставка.',
        'Интернет-портал {$name}. Обзоры, сравнения, отзывы покупателей. Поможем выбрать лучшее для вашего дома.',
        'Создайте уют с {$name}. Индивидуальный подход к каждому клиенту. Работаем с 2015 года.',
        '{$name} — лидер продаж в категории. Собственное производство, складская программа, опт и розница.',
        'Уникальные решения для интерьера: {$name}. Эксклюзивные коллекции, профессиональный монтаж, сервис.',
    ],
    'garden' => [
        'Интернет-магазин {$name}. Семена, саженцы, удобрения. Доставка почтой по всей России.',
        '{$name} — всё для сада и огорода. Профессиональные консультации агрономов, сезонные скидки.',
        'Ваш сад будет лучшим с {$name}. Широкий выбор растений, инструментов и аксессуаров для садоводов.',
        'Профессиональный подход к садоводству: {$name}. Качественные семена, проверенные сорта, быстрые всходы.',
        '{$name} — интернет-портал для дачников. Статьи, советы, календарь работ. Всё для богатого урожая.',
        'Создайте сад мечты с {$name}. Ландшафтный дизайн, системы полива, декоративные элементы.',
        '{$name} — надёжный поставщик садового инвентаря. Работаем с 2010 года, тысячи довольных клиентов.',
        'Ваш огород — наша забота. {$name} предлагает полный спектр товаров для садоводов и огородников.',
    ],
    'cooking' => [
        '{$name} — кулинарный сайт с проверенными рецептами. Пошаговые инструкции, фото, видеоуроки.',
        'Тысячи рецептов на {$name}. Салаты, супы, горячее, десерты. Готовьте с удовольствием каждый день.',
        '{$name} — интернет-магазин посуды и кухонной техники. Официальный дилер ведущих брендов.',
        'Откройте мир вкуса с {$name}. Авторские рецепты, мастер-классы, обзоры продуктов и техники.',
        '{$name} — ваш проводник в мире кулинарии. Простые и вкусные рецепты для всей семьи.',
        'Профессиональная посуда и техника: {$name}. Доставка, установка, обучение. Гарантия до 5 лет.',
        '{$name} — сообщество поваров и гурманов. Делитесь рецептами, оценивайте блюда, вдохновляйтесь.',
        'Вкусные истории от {$name}. Традиционные и современные рецепты, секреты шеф-поваров, обзоры.',
    ],
    'kids' => [
        '{$name} — интернет-магазин детских товаров. Игрушки, одежда, мебель. Быстрая доставка, низкие цены.',
        'Развивайте ребёнка с {$name}. Развивающие игры, конструкторы, книги. Для детей от 0 до 16 лет.',
        '{$name} — всё для счастливого детства. Безопасные материалы, сертифицированные товары, скидки.',
        'Онлайн-школа {$name}. Курсы для детей и подростков. Программирование, языки, творчество, наука.',
        '{$name} — модная и удобная одежда для детей. Натуральные ткани, стильный дизайн, размеры на любой возраст.',
        'Помогите ребёнку учиться с {$name}. Учебные материалы, пособия, онлайн-тренажёры. 1-11 классы.',
        '{$name} — крупнейший каталог игрушек. Более 10000 товаров, регулярные акции, бонусная программа.',
        'Дети будут в восторге от {$name}. Уникальные игрушки, наборы для творчества, спортивные товары.',
    ],
    'health' => [
        '{$name} — онлайн-тренировки для дома. Йога, пилатес, кардио. Программы для любого уровня подготовки.',
        'Будьте в форме с {$name}. Персональные тренировки, планы питания, отслеживание прогресса.',
        '{$name} — портал о здоровом образе жизни. Статьи врачей, советы тренеров, рецепты правильного питания.',
        'Медицинский портал {$name}. Запись к врачу онлайн, база клиник и специалистов, отзывы пациентов.',
        '{$name} — правильное питание с доставкой. Сбалансированное меню на неделю, подсчёт КБЖУ.',
        'Спортивный инвентарь от {$name}. Гантели, коврики, тренажёры. Всё для фитнеса дома и в зале.',
        '{$name} — аптека онлайн. Широкий ассортимент лекарств, витаминов и БАДов. Доставка в день заказа.',
        'Ваше здоровье — наш приоритет. {$name} предоставляет проверенную информацию и качественные товары.',
    ],
];

$emailPool = [
    'info@', 'shop@', 'hello@', 'support@', 'sales@', 'admin@', 'contact@', 'mail@', 'team@', '',
];

// ─── Вспомогательные функции ─────────────────────────────────────

function generateSlug(string $name): string
{
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z',
        'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
        'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $slug = mb_strtolower($name);
    $slug = str_replace(array_keys($map), array_values($map), $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function escapeSql(string $s): string
{
    return str_replace("'", "''", $s);
}

/**
 * Генерирует массив уникальных случайных элементов из пула.
 */
function pickUnique(array $pool, int $count): array
{
    $keys = array_rand($pool, min($count, count($pool)));
    if (!is_array($keys)) $keys = [$keys];
    $result = [];
    foreach ($keys as $k) $result[] = $pool[$k];
    return $result;
}

/**
 * Генерирует описание, заменяя {$name} на название сайта.
 */
function fillDescription(string $template, string $name): string
{
    return str_replace('{$name}', $name, $template);
}

// ─── Генерация SQL ───────────────────────────────────────────────

$sites = [];
$totalSites = 0;
$usedSlugs = [];

foreach ($sections as $section) {
    // Корневые разделы: 5-8 сайтов (они же «Другое» категории)
    // Подразделы: основная масса сайтов
    if ($section['parent_id'] === null) {
        $count = random_int(5, 8);
    } else {
        $count = $perSection + random_int(-3, 3);
        $count = max(10, min(38, $count));
    }

    $theme = $section['theme'];
    $names = pickUnique($namePool[$theme], $count);
    $domains = pickUnique($domainPool[$theme], $count);
    $descriptions = [];
    for ($i = 0; $i < $count; $i++) {
        $descriptions[] = $descriptionPool[$theme][array_rand($descriptionPool[$theme])];
    }

    for ($i = 0; $i < $count; $i++) {
        $name = $names[$i] . ($count > count($namePool[$theme]) ? ' ' . ($i + 1) : '');
        $domain = $domains[$i];
        $slug = generateSlug($name);
        $baseSlug = $slug;
        $suffix = 2;
        while (isset($usedSlugs[$slug])) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
        $usedSlugs[$slug] = true;

        $desc = fillDescription($descriptions[$i], $name);
        $email = $emailPool[array_rand($emailPool)];
        $emailStr = ($email === '') ? 'NULL' : "'" . escapeSql($email . $domain) . "'";

        // Случайная дата за последние 60 дней
        $daysAgo = random_int(0, 60);
        $createdAt = "NOW() - INTERVAL '{$daysAgo} days'";

        $sites[] = [
            'section_id' => $section['id'],
            'name' => $name,
            'slug' => $slug,
            'url' => 'https://' . $domain,
            'description' => $desc,
            'email' => $emailStr,
            'created_at' => $createdAt,
        ];
        $totalSites++;
    }
}

// ─── Формирование SQL-файла ──────────────────────────────────────

$header = <<<SQL
-- ============================================
-- Сгенерированные сайты для каталога (шаг 22)
-- Всего сайтов: {$totalSites}
-- Сгенерировано: {$perSection} ± 3 сайтов на подраздел
-- ID генерируются автоматически (sequence)
-- ============================================

BEGIN;

SQL;

$sql = "INSERT INTO sites (section_id, name, slug, url, description, email, status, created_at, moderated_at) VALUES\n";
$rows = [];
foreach ($sites as $s) {
    $nameEsc = escapeSql($s['name']);
    $slugEsc = escapeSql($s['slug']);
    $urlEsc = escapeSql($s['url']);
    $descEsc = escapeSql($s['description']);
    $rows[] = "  ({$s['section_id']}, '{$nameEsc}', '{$slugEsc}', '{$urlEsc}', '{$descEsc}', {$s['email']}, 1, {$s['created_at']}, NOW())";
}
$sql .= implode(",\n", $rows) . "\nON CONFLICT (slug) DO NOTHING;\n";

$footer = <<<SQL

COMMIT;
SQL;

// ─── Вывод ───────────────────────────────────────────────────────

if ($mode === 'test') {
    $output = $header . "\n-- (тестовый режим)\n\n";
    $output .= "-- Тестовые данные для Playwright (без явных ID)\n";
    $output .= "DELETE FROM sites;\n";
    $output .= "ALTER SEQUENCE sites_id_seq RESTART WITH 1;\n\n";
    $output .= $sql;
    $output .= $footer;
    $targetFile = __DIR__ . '/../tests/fixtures/db-seed-gen.sql';
} else {
    $output = $header . $sql . $footer;
    $targetFile = __DIR__ . '/../migrations/004_seed_sites.sql';
}

file_put_contents($targetFile, $output);

echo "✅ Сгенерировано {$totalSites} сайтов в " . count($sections) . " разделах\n";
echo "   Файл: {$targetFile}\n";
echo "   Размер: " . strlen($output) . " байт\n";

// Статистика по разделам
$bySection = [];
foreach ($sites as $s) {
    $bySection[$s['section_id']] = ($bySection[$s['section_id']] ?? 0) + 1;
}
echo "\n   Сайтов по разделам:\n";
foreach ($sections as $sec) {
    $cnt = $bySection[$sec['id']] ?? 0;
    $indent = $sec['parent_id'] ? '     └─ ' : '';
    echo "   {$indent}{$sec['name']}: {$cnt}\n";
}
