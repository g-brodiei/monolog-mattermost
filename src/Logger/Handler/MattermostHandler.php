<?php

namespace Drupal\monolog_mattermost\Logger\Handler;

use Drupal\monolog_mattermost\Logger\Formatter\MattermostFormatter;
use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\Curl\Util;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\MissingExtensionException;
use Monolog\Logger;

/**
 *
 */
class MattermostHandler extends AbstractProcessingHandler {

  /**
   * Mattermost Webhook token.
   *
   * @var string
   */
  private $webhookUrl;

  /**
   *
   */
  public function __construct(string $webhookUrl, $level = Logger::CRITICAL, bool $bubble = TRUE) {
    if (!extension_loaded('curl')) {
      throw new MissingExtensionException('The curl extension is needed to use the MattermostWebhookHandler');
    }

    parent::__construct($level, $bubble);
    $this->webhookUrl = $this->filterUri($webhookUrl);
  }

  /**
   * Filter Uri.
   *
   * @param mixed $uri
   *
   * @throws Exception if the URI is invalid
   *
   * @return string
   */
  protected function filterUri($uri) {
    if (is_string($uri) || (is_object($uri) && method_exists($uri, '__toString'))) {
      return (string) $uri;
    }

    throw new Exception(sprintf('% expects the uri to be an string or a stringable object', __METHOD__));
  }

  /**
   *
   */
  protected function write(array $record): void {
    $postString = $record['formatted'];

    $ch = curl_init();
    $options = [
      CURLOPT_URL => $this->webhookUrl,
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => ['Content-type: application/json'],
      CURLOPT_POSTFIELDS => $postString,
    ];
    if (defined('CURLOPT_SAFE_UPLOAD')) {
      $options[CURLOPT_SAFE_UPLOAD] = TRUE;
    }

    curl_setopt_array($ch, $options);

    Util::execute($ch);
  }

  /**
   * {@inheritDoc}.
   */
  public function setFormatter(FormatterInterface $formatter): HandlerInterface {
    if ($formatter instanceof MattermostFormatter) {
      return parent::setFormatter($formatter);
    }

    throw new \InvalidArgumentException('MattermostHandler is only compatible with MattermostFormatter');
  }

  /**
   *
   */
  protected function getDefaultFormatter(): FormatterInterface {
    return new MattermostFormatter();
  }

}
