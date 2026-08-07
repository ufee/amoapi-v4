<?php
/**
 * amoCRM API client Widgets service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\Models;

class Widgets extends Service
{
	/**
	 * Salesbot
	 */
	public const BOT_SALESBOT = 'salesbot';

	/**
	 * Marketingbot
	 */
	public const BOT_MARKETINGBOT = 'marketingbot';

	/**
	 * Хендлер show — показать текст/кнопки
	 */
	public const HANDLER_SHOW = 'show';

	/**
	 * Хендлер goto — переход к шагу бота
	 */
	public const HANDLER_GOTO = 'goto';

	protected $api_path = '/api/v4/widgets';
	protected $entity_key = 'widgets';

	protected $entity_model = '\Ufee\AmoV4\Models\Widget';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Widgets';

	/**
	 * Допустимые типы бота для continue
	 * @return string[]
	 */
	public static function botTypeValues(): array
	{
		return [
			self::BOT_SALESBOT,
			self::BOT_MARKETINGBOT,
		];
	}

	/**
	 * Допустимые типы execute_handlers
	 * @return string[]
	 */
	public static function handlerValues(): array
	{
		return [
			self::HANDLER_SHOW,
			self::HANDLER_GOTO,
		];
	}

    /**
     * Install widget
	 * @param string $widget_code
	 * @param array $settings
	 * @return Models\Widget
     */
	public function install(string $widget_code, array $settings)
	{
		$query = $this->instance->query('POST', $this->api_path.'/'.$widget_code);
		$query->setJsonData($settings);
		$query->execute();

		$result = $query->response->validated();
		return new Models\Widget((array)$result, $this);
	}

    /**
     * Uninstall widget
	 * @param string $widget_code
	 * @return bool
     */
    public function uninstall(string $widget_code)
    {
		$query = $this->instance->query('DELETE', $this->api_path.'/'.$widget_code);
		$query->execute();
		return $query->response->getCode() === 204;
	}

	/**
	 * Parse return_url from widget_request webhook
	 * Example: https://example.amocrm.ru/api/v4/marketingbot/321/continue/123
	 *
	 * Порядок как у continueBot: [bot_type, bot_id, continue_id]
	 *
	 * @param string $return_url
	 * @return array{0: string, 1: int, 2: int}
	 */
	public static function parseContinueUrl(string $return_url): array
	{
		$path = parse_url($return_url, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			// relative path without scheme/host
			$path = $return_url;
		}
		if (!preg_match('#/api/v4/(?<bot_type>salesbot|marketingbot)/(?<bot_id>\d+)/continue/(?<continue_id>\d+)$#', rtrim($path, '/'), $m)) {
			throw new \InvalidArgumentException('Invalid continue return_url format');
		}
		return [$m['bot_type'], (int)$m['bot_id'], (int)$m['continue_id']];
	}

	/**
	 * Confirm widget block via return_url from webhook
	 * @param string $return_url
	 * @param array $data
	 * @param array $execute_handlers
	 * @return bool
	 */
	public function continueFromUrl(string $return_url, array $data = [], array $execute_handlers = []): bool
	{
		[$bot_type, $bot_id, $continue_id] = static::parseContinueUrl($return_url);
		return $this->continueBot($bot_type, $bot_id, $continue_id, $data, $execute_handlers);
	}

	/**
	 * Confirm widget block execution and continue Salesbot / Marketingbot
	 * POST /api/v4/{salesbot|marketingbot}/{bot_id}/continue/{continue_id}
	 *
	 * @param string $bot_type salesbot|marketingbot
	 * @param int $bot_id
	 * @param int $continue_id
	 * @param array $data данные для виджета (необязательно)
	 * @param array $execute_handlers хендлеры show|goto, максимум 10 (необязательно)
	 * @return bool
	 */
	public function continueBot(string $bot_type, int $bot_id, int $continue_id, array $data = [], array $execute_handlers = []): bool
	{
		if (!in_array($bot_type, static::botTypeValues(), true)) {
			throw new \InvalidArgumentException('Bot type must be one of: ' . implode(', ', static::botTypeValues()));
		}
		if (!is_int($bot_id) || $bot_id <= 0) {
			throw new \InvalidArgumentException('Bot ID must be positive integer, got ' . $bot_id);
		}
		if (!is_int($continue_id) || $continue_id <= 0) {
			throw new \InvalidArgumentException('Continue ID must be positive integer, got ' . $continue_id);
		}
		if (count($execute_handlers) > 10) {
			throw new \InvalidArgumentException('execute_handlers supports maximum 10 handlers');
		}

		foreach ($execute_handlers as $handler) {
			$this->validateExecuteHandler($handler);
		}

		$payload = [];
		if ($data !== []) {
			$payload['data'] = $data;
		}
		if ($execute_handlers !== []) {
			$payload['execute_handlers'] = $execute_handlers;
		}

		$query = $this->instance->query('POST', "/api/v4/{$bot_type}/{$bot_id}/continue/{$continue_id}");
		$query->setJsonData($payload === [] ? new \stdClass() : $payload);
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * Validate one execute_handler item
	 * @param mixed $handler
	 */
	protected function validateExecuteHandler($handler): void
	{
		if (!is_array($handler)) {
			throw new \InvalidArgumentException('Each execute_handler must be an array');
		}
		$name = $handler['handler'] ?? null;
		if (!is_string($name) || !in_array($name, static::handlerValues(), true)) {
			throw new \InvalidArgumentException('execute_handler handler must be one of: show, goto');
		}
		$params = $handler['params'] ?? null;
		if (!is_array($params)) {
			throw new \InvalidArgumentException('execute_handler params must be an array');
		}
		if ($name === self::HANDLER_SHOW) {
			$type = $params['type'] ?? null;
			if ($type === 'text' && isset($params['value']) && is_string($params['value']) && mb_strlen($params['value']) > 80) {
				throw new \InvalidArgumentException('show handler text value must not exceed 80 characters');
			}
			if (isset($params['buttons']) && is_array($params['buttons']) && count($params['buttons']) > 25) {
				throw new \InvalidArgumentException('show handler supports maximum 25 buttons');
			}
		}
	}
}
