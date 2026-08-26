<?php
/**
 * Run docs/spam-messages.md corpus against Layer 1 heuristics (CLI).
 *
 * Usage: php tools/corpus-spam-check.php
 *
 * @package LanceDesk_Form_Guard
 */

declare( strict_types=1 );

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string      $text   Text.
	 * @param string|null $domain Domain.
	 * @return string
	 */
	function __( string $text, $domain = null ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return $text;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/includes/class-ld-fg-heuristics.php';

$ld_fg_settings = array(
	'heuristics' => array(
		'bbcode_url'          => 1,
		'url_count'           => 1,
		'honeypot'            => 1,
		'too_fast'            => 1,
		'suspicious_links'    => 1,
		'commercial_outreach' => 1,
		'gibberish'           => 1,
	),
);

$ld_fg_labels = array(
	1  => 'Soft SEO strategy (Robin)',
	2  => 'Darknet BLSP + BBCode',
	3  => 'Russian tehosmotr short',
	4  => 'Russian casino BC Game + BBCode',
	5  => 'Soft SEO strategy (Henry)',
	6  => 'Russian tehosmotr EAISTO',
	7  => 'Russian tehosmotr SPB',
	8  => 'Russian tehosmotr SPB card',
	9  => 'Xrumer/GSA SEO databases short',
	10 => 'Xrumer/GSA SEO databases long',
	11 => 'Soft backlink collaboration (Clark)',
	12 => 'Crypto token credit scam',
	13 => 'Russian tehosmotr diagnostic SPB',
	14 => 'Russian GelTaxi spam',
	15 => 'SMARTAL facade glazing',
	16 => 'German solar Dieburg',
	17 => 'Russian tehosmotr price SPB',
	18 => 'Russian Kraken darknet',
	19 => 'Vendor contact-card (recodecommerce)',
);

$ld_fg_messages = array();

$ld_fg_messages[1] = "Hi,\n\nI was looking over your website and noticed a few specific areas where optimization could boost your Google rankings and attract higher-quality leads.\n\nI have put together a brief SEO strategy and pricing proposal designed to help scale your organic growth.\n\nWould you be open to reviewing it?\n\nBest regards,\n\nRobin";

$ld_fg_messages[2] = 'Part One: The Rise of BLSP

Following the wave of "slon" and "kraken" domains in 2026, a new structure emerged on the network stage, unified under the blsp prefix. Its base address became blsp.at — a short, memorable domain in the Austrian .at zone. However, as with previous generations of mirrors, the developers anticipated mobile users by creating a dedicated subdomain: m.blsp.at — a separate address optimized for smartphones and smaller screens. Technical analysis reveals that m.blsp.at has its own architecture, fine-tuned for mobile protocols, making it an independent link in the overall chain.
[url=https://bs2clear-dark.net]skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd onion[/url]
Interestingly, users quickly adapted to various spelling variations. Alongside the classic blsp.at, search queries increasingly feature spaced versions — blsp at, m.blsp at, and even m blsp at. This creates a multiplier effect, giving the illusion of multiple resources, although technically all these addresses lead to a single infrastructure.

Part Two: The Rival Duo — BS2BEST and BS2WEB
[url=https://bls2best.com]m.blsp at[/url]
In parallel with BLSP, two new players have entered the network: bs2best.at and bs2web.at. Their names clearly hint at their positioning — "best" and "web version." Both domains are based in the same .at zone, indicating a centralization of the Austrian segment as the primary platform for deploying next-generation mirrors.

Analysts note that bs2best.at is positioned as the main, "elite" gateway, while bs2web.at serves as the universal entry point for the mass user. As with BLSP, there are spelling variations — bs2best at and bs2web at with spaces — which are used in verbal recommendations and informal discussions. This adds an extra layer of protection: even if one variant is blocked, the alternative remains operational.
[url=https://blsp2tor.com]bs2best at[/url]
Part Three: The Long Shadow Giant — SKYIWREDSHJNHJ

The most mysterious element of the new network is the unusually long .onion address — skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd.onion. This 58-character behemoth has become a real challenge for system administrators and security experts. Unlike the short .at domains, this address exists exclusively on the Tor network and has no "light" version for regular browsers.
[url=https://blackspruty4w3j4bzyhlk24jr32wbpnfo3oyywn4ckwylo4hkcyy4yd.ltd]blsp.at[/url]
Interestingly, in informal communication, users often drop the .onion and simply write skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd onion with a space. This suggests that the address has already become recognizable, despite its complexity. Technical experts speculate that this .onion serves as a backup channel in case all .at domains are simultaneously blocked.

Part Four: The Return of TRIP76

The new architecture also found room for an old familiar face — trip76. This domain, already established as a testing ground in previous waves, continues to operate in two variants: trip76.co and trip76.at. As before, users actively use spaced versions — trip76 co and trip76 at.

However, trip76 now has a new role. According to network monitoring data, this domain has become the bridge between the BLSP structure and the BS2 family. For example, technical logs show that updates tested on trip76.co are then transferred to blsp.at, and from there to bs2best.at and bs2web.at. This has made trip76 a kind of "incubator" for the entire ecosystem.
[url=https://a-blsp.net]m.blsp.at[/url]
Part Five: The Complete Picture

When all the elements are assembled, a complex but logical system emerges:

First echelon — test polygons: trip76.co, trip76.at, trip76 co, trip76 at. This is where new configurations are born.

Second echelon — primary mobile and web gateways: m.blsp.at, blsp.at, blsp at, m.blsp at, m blsp at. These are the daily working addresses for the majority of users.

Third echelon — elite and backup gateways: bs2best.at, bs2web.at, bs2best at, bs2web at. These are used in case of overload or blocking of the second echelon.

Fourth echelon — the protected rear: skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd.onion and its spaced version skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd onion. This is the last line of defense, operating even when all regular domains are completely offline.

Conclusion

The year 2026 demonstrates the evolution of mirror networks from simple numbered series (as with the "slon" domains) to complex multi-layered architectures with clearly defined functional divisions. blsp.at, bs2best.at, bs2web.at, and the enigmatic skyiwredshjnhj...onion are no longer just links — they are a fully-fledged ecosystem where each element performs a strictly defined role. However, experts warn: the more complex the system, the more vulnerable its users. Fraudsters can easily copy this architecture, creating fake blsp.at clones, phishing bs2best.at look-alikes, and malicious replicas of the long .onion address. The only reliable defense in 2026 remains the same as always: vigilance, caution, and the simple refusal to trust any address from these growing lists.

https://blacksprutbsgl.com

skyiwredshjnhjgeleladu7m7mgpuxgsnfxzhncwtvmhr7l5bniutayd onio';

$ld_fg_messages[3]  = "Техосмотр автомобиля в 2026 стал точечным, но для регистрации карта часто нужна. Удобнее брать ПТО рядом с маршрутом — Московское шоссе, КАД, район. Читать:\n\nпроходить техосмотр";
$ld_fg_messages[4]  = "В современном мире онлайн-гейминга популярность платформ для азартных игр постоянно растет. Одной из таких платформ является hash.game, которая привлекает игроков разнообразием игр и удобством использования. Пользователи могут легко получить доступ к играм через официальный сайт bc.co или посетить bcgame.im, чтобы ознакомиться с текущими предложениями и бонусами.\n[url=https://bch-games.casino]bc casino [/url]\nДля тех,кто ищет надежные и безопасные развлечения, рекомендовано использовать официальные ресурсы, такие как bc.casino. Там доступны разные игровые автоматы и настольные игры, а также возможность скачать bc game apk для мобильных устройств. Это обеспечивает комфортный и непрерывный игровой процесс без необходимости постоянного подключения к интернету.\n[url=https://bch-casino.games]bcgame.im [/url]\nМногие игроки задаются вопросом о специальных промоакциях и бонусах. Чтобы получить bc game bonus, необходимо зарегистрироваться и ввести bc game code при пополнении счета. Такие акции позволяют увеличить баланс и получить дополнительные шансы на выигрыш. Также стоит следить за обновлениями, чтобы не пропустить новые акции и bc game bonus.\n[url=https://bchgames.top]bc game apk [/url]\nДля постоянных участников существуют программы лояльности и бонусные предложения, связанные с bc games. Важно использовать все преимущества платформы, чтобы максимально повысить шансы на успех. Регулярные игры и участие в турнирах делают игровой опыт более захватывающим и прибыльным.\n[url=https://bch-games.casino]bc game apk [/url]\nВ целом, платформа bc casino предлагает широкий выбор развлечений и выгодных условий для игроков. Регистрация и использование bc game code позволяют получить дополнительные привилегии, а постоянное обновление информации помогает оставаться в курсе всех акций. Играйте ответственно и наслаждайтесь увлекательным миром онлайн-гемблинга!\n\n\nhttps://bchgames.top\n\nbc game code";
$ld_fg_messages[5]  = "Hi,\n\nI was looking over your website and noticed a few specific areas where optimization could boost your Google rankings and attract higher-quality leads.\n\nI have put together a brief SEO strategy and pricing proposal designed to help scale your organic growth.\n\nWould you be open to reviewing it?\n\nBest regards,\n\nHenry";
$ld_fg_messages[6]  = "Проверить техосмотр по номеру стоит до визита в МРЭО — так не будет сюрпризов с ЕАИСТО. Как пройти и где записаться:\n\nпроходим техосмотр";
$ld_fg_messages[7]  = "Где пройти техосмотр в СПб: аккредитованный пункт, документы, проверка в ЕАИСТО. Для постановки на учёт это обычно обязательный шаг. Материал:\n\nтехосмотр спб для постановки на учет";
$ld_fg_messages[8]  = "Если ищете «техосмотр спб для постановки на учет» — ориентируйтесь на официальный пункт и действующую диагностическую карту. Подробности:\n\nтехосмотр автомобиля";
$ld_fg_messages[9]  = "Если вам нужны регулярно обновляемые базы для SEO-инструментов, обратите внимание на этот сервис. Подписка для Xrumer и GSA Search Engine Ranker действует 12 месяцев и не требует ежемесячной оплаты.\nhttps://dseo24.monster";
$ld_fg_messages[10] = "Хочу рассказать о сервисе, которым пользуются специалисты SEO-сообщества. Здесь можно приобрести подписку на базы для Xrumer и GSA Search Engine Ranker с обновлениями на 12 месяцев. Условия прозрачные, а цена остается доступной.\nhttps://dseo24.monster";
$ld_fg_messages[11] = "Hi,\n\nI’m putting together a small backlink collaboration and thought you might be a good fit.\n\nI can place your site - magicfrank.co.uk on 5 quality local business websites (DR30+, ~2k–5k traffic), no payment needed.\n\nIn return, I’d ask for links to 5 of my client sites from your end — keeping it balanced and natural for SEO.\n\nLet me know if you’d like to explore it.\n\nBest,\nClark";
$ld_fg_messages[12] = "TOKENS CREDITED AUTOMATICALLY TO YOUR BALANCE NOW 86206.18 USD RECEIVE http://www.graph.org/bc248984-08-06\n\nPXQRmagicfrank.co.ukPXQR";
$ld_fg_messages[13] = "Официальный пункт техосмотра, диагностическая карта и запись без очереди — три вещи, которые реально экономят время перед ГИБДД. В 2026 правила для легковых стали точечнее. Читать:\n\n\nгде получить диагностическую карту в спб";
$ld_fg_messages[14] = 'Данный сервис представляет удобное а также надежное средство для дальнобойных путешествий клиентов. Обратившись к geltaxi вы имеете возможность заказать трансфер в минимальные время кроме того поддержать удобство а также безопасность. Этот сервис имеет легкий интерфейс, что пользователям удобно мгновенно подтверждать транспортировки любой расположения на регионе что быстрое обеспечение а также готовность организовать перевозку независимо от часа вечера. За geltaxi, вы можете подобрать разные категории машин, подобрать наиболее подходящий оптимальный по цене вариант кроме того получить удобство а также надежность поездки. Данный сервис к тому же гарантирует широкий способы оплаты, что позволяет работу более простым. В целом, этот сервис предоставляет современный при этом условный дальнейший перевозку с минимальными затратами а также максимальным привлекательностью.';
$ld_fg_messages[15] = "Good day!\n\nModern translucent structures made of aluminum and other modern materials are very popular among Russian consumers. The SMARTAL Group of Companies provides a wide range of services in the design, production, and installation of translucent structures. We manufacture and install all types of facade glazing.\n\nWhether you want to successfully order facade glazing or order curtain wall ventilated facades, or we design translucent structures for any project, you can do so by contacting SMARTAL!\n\nOur organization receives mostly excellent reviews\n\n. The company's full list of services includes:\n\nThe SMARTAL Group of Companies is participating in a tender.\n\n\nNon-flammability of curtain wall materials according to Smartal standards.\n\n\nWarehouse buildings of the Multifunctional Logistics Park.\n\n\nServices";
$ld_fg_messages[16] = "Solar Dieburg\n\n12 KW Hybrid-Wechselrichter 3 Phasen 48V";
$ld_fg_messages[17] = "Нужен техосмотр для постановки на учёт? Сначала уточните тип операции в МРЭО, потом пройдите осмотр и проверьте карту в базе. В СПб много точек у основных магистралей. Полезная ссылка:\n\n\nдиагностическая карта спб цена";
$ld_fg_messages[18] = "Пользователи Кракен применяют криптовалюту как расчётное средство, исключающее банковский контроль.\n\nдаркнет маркетплейс, который действительно держит слово: если заявлено, что товар будет у тебя через пять минут, он будет у тебя через пять минут, без единого исключения.\n\nВХОД НА ЧЕРНЫЙ РЫНОК KRAKEN\n\nТот самый момент, когда ты нажимаешь кнопку оплаты, и цифровой товар мгновенно падает в твой доступ — это чистый, неразбавленный дофаминовый удар. Мозг просто не успевает обработать, как быстро сбылось его желание, и это вызывает настоящую эйфорию.\n\nНа площадке Kraken можно обнаружить предложения о сбыте фальшивых документов — от паспортов до водительских удостоверений и дипломов. Также фигурируют услуги, связанные с неправомерным получением конфиденциальных сведений о гражданах.";

$ld_fg_messages[19] = "Name: recodecomsymn recodecomsymnHS\nEmail: info@recodecommerce.com\nSubject: eCommerce Development\n\nFlagged Content\nhttps://recodecommerce.com/contact\nPrefer email? Reach us at info@recodecommerce.com";

$ld_fg_blocked = 0;
$ld_fg_allowed = 0;
$ld_fg_allow_ids = array();

echo "LanceDesk Form Guard — corpus spam check (Layer 1)\n";
echo str_repeat( '-', 72 ) . "\n";

foreach ( $ld_fg_messages as $ld_fg_id => $ld_fg_msg ) {
	$ld_fg_result  = LD_FG_Heuristics::evaluate(
		array(
			'fields'      => array( 'message' => $ld_fg_msg ),
			'honeypot'    => '',
			'render_time' => 0,
			'submit_time' => time(),
		),
		$ld_fg_settings
	);
	$ld_fg_is_spam = ( 'spam' === $ld_fg_result['verdict'] );

	if ( $ld_fg_is_spam ) {
		++$ld_fg_blocked;
		$ld_fg_status = 'BLOCKED';
	} else {
		++$ld_fg_allowed;
		$ld_fg_status      = 'ALLOWED';
		$ld_fg_allow_ids[] = $ld_fg_id;
	}

	printf(
		"#%02d [%s] %s\n    matched: %s | conf: %s | %s\n\n",
		$ld_fg_id,
		$ld_fg_status,
		$ld_fg_labels[ $ld_fg_id ],
		$ld_fg_result['matched'] ? (string) $ld_fg_result['matched'] : '—',
		(string) $ld_fg_result['confidence'],
		$ld_fg_result['reason'] ? (string) $ld_fg_result['reason'] : '(none)'
	);
}

echo str_repeat( '-', 72 ) . "\n";
printf( "Layer 1: %d blocked, %d allowed (of %d)\n", $ld_fg_blocked, $ld_fg_allowed, count( $ld_fg_messages ) );
echo 'Allowed IDs: ' . ( $ld_fg_allow_ids ? implode( ', ', $ld_fg_allow_ids ) : '(none)' ) . "\n";

// False-positive sanity checks.
$ld_fg_legit = array(
	'Wedding magician booking' => "Hi Magic Frank,\n\nWe love the look of your magic!\n\nWe are thinking we would like a roaming magician to entertain guests after our wedding breakfast and before the first dance when evening guests arrive and everyone will be mingling and chilling out before the party. This would probably be for an hour and a half.\n\nPlease let us know if you’re free.\n\nThanks!\nLucy and Dan",
	'Church inquiry'           => "Hi, I'd like to know your service times and whether you have a newcomers class. Thank you!",
	'Prayer request'           => 'Please pray for my family during a difficult time. God bless.',
	'Volunteer note'           => "I'm so excited to volunteer with your youth program! I've been looking for a church home and would love to help however I can.",
	'Looked at website (legit)' => "Hi, I was looking over your website and wanted to ask about availability for a birthday party next month. Thanks!",
);

echo "\nFalse-positive checks (should ALLOW):\n";
foreach ( $ld_fg_legit as $ld_fg_label => $ld_fg_text ) {
	$ld_fg_r = LD_FG_Heuristics::evaluate(
		array(
			'fields'      => array( 'message' => $ld_fg_text ),
			'honeypot'    => '',
			'render_time' => 0,
			'submit_time' => time(),
		),
		$ld_fg_settings
	);
	$ld_fg_ok = ( 'spam' !== $ld_fg_r['verdict'] );
	printf(
		"  [%s] %s%s\n",
		$ld_fg_ok ? 'ALLOW' : 'FALSE POSITIVE',
		$ld_fg_label,
		$ld_fg_ok ? '' : ( ' — ' . $ld_fg_r['reason'] )
	);
}
