<?php
/**
 * Pure matcher for the post-article service offer.
 *
 * No WordPress calls: categories, tags and an optional explicit choice
 * decide whether one commercial landing is relevant. Unmapped posts stay quiet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commercial offers. Category hit is 5. A tag value is its own weight.
 * The block appears only when the best score is at least 5, so a weak tag
 * does not attach a service to an unrelated article.
 *
 * @return array<string, array<string, mixed>>
 */
function krv_service_offer_catalog(): array {
	return array(
		'vps'       => array(
			'title'      => 'Нужна помощь с настройкой сервера?',
			'text'       => 'Настрою VPS, Nginx, PHP и безопасность под ваш проект. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://vps.krivoshein.site/',
			'categories' => array( 'vps', 'hosting', 'docker', 'devops' ),
			'tags'       => array(
				'nginx'    => 5,
				'vps'      => 5,
				'ubuntu'   => 5,
				'docker'   => 5,
				'devops'   => 5,
				'server'   => 5,
				'hosting'  => 5,
				'lemp'     => 5,
				'dns'      => 2,
				'domain'   => 2,
				'ssl'      => 2,
				'ssh'      => 2,
				'beget'    => 2,
				'firstvds' => 2,
				'selectel' => 2,
			),
		),
		'wordpress' => array(
			'title'      => 'Нужна помощь с WordPress?',
			'text'       => 'Доработаю сайт, блоки, формы и обновления. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://wordpress.krivoshein.site/',
			'categories' => array( 'wordpress', 'plaginy-wordpress' ),
			'tags'       => array(
				'wordpress'   => 5,
				'plugin'      => 5,
				'gutenberg'   => 5,
				'wp-cli'      => 5,
				'woocommerce' => 5,
				'acf'         => 2,
				'elementor'   => 2,
			),
		),
		'flask'     => array(
			'title'      => 'Нужен сайт или сервис на Python?',
			'text'       => 'Соберу страницы, формы и серверную логику на Flask. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://flask.krivoshein.site/',
			'categories' => array(),
			'tags'       => array(
				'python'   => 5,
				'flask'    => 5,
				'django'   => 5,
				'gunicorn' => 5,
				'fastapi'  => 5,
			),
		),
		'direct'    => array(
			'title'      => 'Нужна реклама, которая приводит заявки?',
			'text'       => 'Разберу Яндекс Директ, минус-слова и посадочные. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://direct.krivoshein.site/',
			'categories' => array( 'kontekstnaya-reklama', 'rsya' ),
			'tags'       => array(
				'yandex-direct' => 5,
				'direkt'        => 5,
				'wordstat'      => 5,
				'reklama'       => 5,
				'rsya'          => 5,
			),
		),
		'bots'      => array(
			'title'      => 'Нужен бот для заявок?',
			'text'       => 'Соберу сценарий для Telegram или MAX и подключу уведомления. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://bots.krivoshein.site/',
			'categories' => array(),
			'tags'       => array(
				'telegram' => 5,
				'max'      => 5,
			),
		),
		'ai'        => array(
			'title'      => 'Нужно, чтобы сайт понимали нейропоиск и ассистенты?',
			'text'       => 'Приведу структуру, FAQ и разметку в порядок. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://ai-ready.krivoshein.site/',
			'categories' => array( 'ai' ),
			'tags'       => array(
				'llm'      => 5,
				'ii'       => 5,
				'chatgpt'  => 5,
				'gigachat' => 5,
				'openai'   => 5,
				'claude'   => 5,
				'gemini'   => 5,
				'grok'     => 5,
			),
		),
		'landing'   => array(
			'title'      => 'Нужен сайт под задачу?',
			'text'       => 'Соберу лендинг с понятным оффером и кнопкой связи. Работаю официально, по договору.',
			'cta'        => 'Обсудить задачу',
			'url'        => 'https://landing.krivoshein.site/',
			'categories' => array(),
			'tags'       => array(
				'landing' => 5,
				'taplink' => 5,
			),
		),
	);
}

/**
 * Pick one service id, or an empty string when the article should stay clean.
 *
 * Explicit "off" always wins. An explicit known id wins over taxonomy.
 * Equal scores use the primary category, then a fixed specificity order.
 *
 * @param array<int, string> $categories Category slugs.
 * @param array<int, string> $tags       Tag slugs.
 */
function krv_service_offer_pick( array $categories, array $tags, string $primary = '', string $explicit = '' ): string {
	$explicit = strtolower( trim( $explicit ) );
	$catalog  = krv_service_offer_catalog();

	if ( 'off' === $explicit ) {
		return '';
	}

	if ( '' !== $explicit && isset( $catalog[ $explicit ] ) ) {
		return $explicit;
	}

	$categories = array_values( array_unique( array_map( 'strval', $categories ) ) );
	$tags       = array_values( array_unique( array_map( 'strval', $tags ) ) );
	$scores     = array();

	foreach ( $catalog as $service_id => $service ) {
		$score = 0;

		foreach ( $categories as $slug ) {
			if ( in_array( $slug, $service['categories'], true ) ) {
				$score += 5;
			}
		}

		foreach ( $tags as $slug ) {
			if ( isset( $service['tags'][ $slug ] ) ) {
				$score += (int) $service['tags'][ $slug ];
			}
		}

		$scores[ $service_id ] = $score;
	}

	$best = 0;
	foreach ( $scores as $score ) {
		if ( $score > $best ) {
			$best = $score;
		}
	}

	if ( $best < 5 ) {
		return '';
	}

	$winners = array();
	foreach ( $scores as $service_id => $score ) {
		if ( $score === $best ) {
			$winners[] = $service_id;
		}
	}

	if ( 1 === count( $winners ) ) {
		return $winners[0];
	}

	if ( '' !== $primary ) {
		foreach ( $winners as $service_id ) {
			if ( in_array( $primary, $catalog[ $service_id ]['categories'], true ) ) {
				return $service_id;
			}
		}
	}

	$priority = array( 'flask', 'bots', 'direct', 'wordpress', 'ai', 'vps', 'landing' );
	foreach ( $priority as $service_id ) {
		if ( in_array( $service_id, $winners, true ) ) {
			return $service_id;
		}
	}

	return $winners[0];
}
